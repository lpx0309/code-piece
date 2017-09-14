<?php
/**
 * CC课前自动外呼 每1分钟执行一次
 * @author lipeixin<lipeixin@51talk.com>@2016-1
 */
$is_call=1;//如果只测数据不想外呼将其置为0，正式置为1
define('DO_WHILE_LOCK_TIME',120);//动态外呼进程锁定时间
//define('AUTO_CALLING_CC_USER_LOCK_TIME',90);//自动外呼池学员锁时间
define('ADMIN_LOCK_TIME',20);//坐席锁时间
$call_group=3;//每几组号码呼一次后停1秒;
define('RUN_LOG_MAX',600);//进程日志最大记录数
define('DO_WHILE_LOG_MAX',RUN_LOG_MAX*5);//动态过程日志最大记录数

$start_time=microtime(true);//程序开始时间

//程序运行日志
$run_log=array();
$run_log['id']=$start_time;
$run_log['start_time']=date('Y-m-d H:i:s',$start_time);
$run_log['config_time']=0;

$run_log['free_clients_count']=0;
$run_log['auto_calling_cc_user_count']=0;
$run_log['mobile_count']=0;
$run_log['called_count']=0;

$run_log['free_clients_time']=0;
$run_log['auto_calling_cc_user_time']=0;
$run_log['mobile_time']=0;
$run_log['calling_time']=0;

$run_log['do_while_count']=0;
$run_log['do_while_run_time']=0;

$auto_calling_admin=array();//分到自动外呼池的坐席
$dispatch_admin=array();//分到藏金阁的坐席
$uncall_admin=array();//外呼失败的坐席

$run_log['config_time']=microtime(true);//加载设置开始时间

//加载设置
require_once(dirname(dirname(__FILE__))."/init.php");
set_time_limit(0);

$obj_autocallingccconfig = Load::loadModel('AutoCallingCcConfig');
$obj_autocallingqueue = Load::loadModel('AutoCallingQueue');
$obj_autocallingccuser = Load::loadModel('AutoCallingCcUser');
$obj_autocallingswitch = Load::loadData('AutoCallingSwitch');
$obj_cccalldetail = Load::loadModel('CcCallDetail');
$obj_cccalldetaillog = Load::loadData('CcCallDetailLog');
$obj_remarklog = Load::loadModel('RemarkLog');
$obj_admin_user = Load::loadData('AdminUser');
$obj_user = Load::loadData('User');
$lock = New Lock();

//读取配置表
$autocallingccconfig = $obj_autocallingccconfig->getAutoCallingCcConfig('id,is_start,day_start_time,day_end_time,queue_ids,follow_called_num,set_follow_call,cron_call_interval,is_dowhile,dowhile_over_time,dowhile_interval_time,dowhile_scan_lock_time');
unset($obj_autocallingccconfig);
if(empty($autocallingccconfig)){
	echo '请先设定配置信息，再创建外呼';
	exit;
}
//判断外呼是否开启
if($autocallingccconfig['is_start'] == 1){//已关闭
	echo '自动外呼已关闭，无法创建外';
	exit;
}
//判断外呼时间段
if (time() < strtotime(date('Y-m-d') . ' ' . $autocallingccconfig['day_start_time']) || time() >= strtotime(date('Y-m-d') . ' ' . $autocallingccconfig['day_end_time'])) {
    echo '该时间段不允许自动外呼';
    exit;
}
//备注后自动外呼可修改外呼时间间隔
if ($autocallingccconfig['set_follow_call'] == 2 && $autocallingccconfig['cron_call_interval'] > 0){
	$minute = date('i');
	if ($minute%$autocallingccconfig['cron_call_interval'] != 0){
		echo $autocallingccconfig['cron_call_interval']."分钟执行一次";
		exit;
	}
}
//将大区拆分
$queue_ids_list = explode(';',$autocallingccconfig['queue_ids']);
if(!is_array($queue_ids_list)){
    $queue_ids_list = array();
}

//取自动外呼组设置
//$auto_admin_array = Load::loadConfig("cc_group", "auto_admin_array");
//$auto_cc_group = implode(',',$auto_admin_array);
$auto_cc_group = implode(',',$cc_group);
//unset($auto_admin_array);

//动态外呼设置
$is_dowhile = $autocallingccconfig['is_dowhile'];
$overTime = $start_time+$autocallingccconfig['dowhile_over_time'];
$sleepTime = $autocallingccconfig['dowhile_interval_time'];
$waitTime = $autocallingccconfig['dowhile_scan_lock_time'];

$run_log['config_time']=microtime(true)-$run_log['config_time'];//加载设置时间

//开始运行外呼过程
do{
    //动态外呼（如果小于一分钟执行完毕，可多次执行；如果大于一分钟执行完毕，可防止重复执行）
    if($is_dowhile==1) {
        $is_rest = $lock->lock(array('active' => 'auto_calling_cc_outcall'), DO_WHILE_LOCK_TIME);
        if ($is_rest === false) {
            echo '进程已锁';
            sleep($waitTime);
            continue;
        }
    }

    //过程日志
    $do_while_log=array();
    $do_while_log['id']=microtime(true);
    $do_while_log['pid']=$start_time;

    $do_while_log['free_clients_count']=0;
    $do_while_log['auto_calling_cc_user_count']=0;
    $do_while_log['mobile_count']=0;
    $do_while_log['called_count']=0;

    $do_while_log['free_clients_time']=0;
    $do_while_log['auto_calling_cc_user_time']=0;
    $do_while_log['mobile_time']=0;
    $do_while_log['calling_time']=0;

    //遍历大区
    foreach($queue_ids_list as $key=>$ids_str){
        $area=$key+1;//大区编号（目前1北京，2上海，3武汉）

        $autocalling = new AutoCalling($area);//实例化一个大区的外呼接口

        $auto_calling_cc_outcall_time=time();//外呼完成后修改自动外呼池的时间数据

        $free_clients_time=microtime(true);//取空闲坐席时间开始

        //遍历队列获取空闲坐席号
        $join_number=array();
        $queue_ids = explode(',',$ids_str);
        if(empty($queue_ids)){
            echo '请配置大区'.$area.'的队列信息<br>';
            continue;
        }
        foreach($queue_ids as $queue_id){
            $queueinfo = $obj_autocallingqueue->getQueueInfoByQueueId($queue_id,'queue_no');
            if(strlen($queueinfo['queue_no']) <= 0){
                echo '队列'.$queue_id.'配置错误<br>';
                continue;
            }
            //取空闲坐席
            $freeClients = $autocalling->getFreeClientsDetailByQueueId($queueinfo['queue_no'],1);
            if(empty($freeClients)){
                echo '队列'.$queue_id.'无空闲坐席，无法创建新外呼<br><br>';
                continue;
            }
            $join_number=array_merge($join_number,$freeClients);
        }

        $free_clients_time=microtime(true)-$free_clients_time;//取空闲坐席时间结束
        $free_clients_count=count($join_number);
        $do_while_log['free_clients_time']+=$free_clients_time;
        $do_while_log['free_clients_count']+=$free_clients_count;
        $run_log['free_clients_time']+=$free_clients_time;
        $run_log['free_clients_count']+=$free_clients_count;

        $auto_calling_cc_user_time=microtime(true);//取自动外呼池时间开始

        //根据空闲坐席号取cc详细信息
        $join_number = implode(',',$join_number);
        $admin_list = array();
        if(empty($join_number)) {
            echo '大区'.$area.'没有空闲坐席<br>';
            continue;
        }else{
            $admin_list = (new \Logic\Comm\AdminUser())->getAdminUserInfoRowsByWhere("status = 'on' and join_number in ($join_number) and group_id in ($auto_cc_group)", "id as admin_id,join_number,join_password");
        }
        unset($join_number);
        echo '*空闲坐席：*';
        var_dump($admin_list);

        //通过admin_id从自动外呼池中取所有空闲坐席的学员
        $admin_id_arr = array_column($admin_list,'admin_id');
        $auto_calling_cc_user_list = array();
        if(!empty($admin_id_arr)) {
            $auto_calling_cc_user_list = $obj_autocallingccuser->getCcAutoCallList($admin_id_arr);
        }
        //echo '所有空闲坐席的所有自动外呼池数据：';
        //var_dump($auto_calling_cc_user_list);

        //每个坐席筛选一名自动外呼池学员并生成一个以admin_id为键值的MAP数组
        $auto_calling_cc_user_map=array();
        foreach ($auto_calling_cc_user_list as $auto_calling_cc_user) {
            if(isset($auto_calling_cc_user_map[$auto_calling_cc_user['admin_id']]) || (false === FollowUp::checkUserCanBeFollowUp($user_id,FollowUp::CC_FOLLOW_UP_TYPE))){
                continue;
            }else{
                //查看该学员是否加锁
                /*if($obj_autocallingccuser->checkRedisUserIds('auto_calling_user_ids',$auto_calling_cc_user['user_id']) !== false){
                    continue;
                }*/
                $auto_calling_cc_user_map[$auto_calling_cc_user['admin_id']]=$auto_calling_cc_user;
            }
        }
        echo '每个坐席及其所对应的一名自动外呼池学员MAP数组：';
        var_dump($auto_calling_cc_user_map);

        $auto_calling_cc_user_time=microtime(true)-$auto_calling_cc_user_time;//取自动外呼池时间结束
        $auto_calling_cc_user_count=count($auto_calling_cc_user_list);
        $do_while_log['auto_calling_cc_user_time']+=$auto_calling_cc_user_time;
        $do_while_log['auto_calling_cc_user_count']+=$auto_calling_cc_user_count;
        $run_log['auto_calling_cc_user_time']+=$auto_calling_cc_user_time;
        $run_log['auto_calling_cc_user_count']+=$auto_calling_cc_user_count;

        //echo '所有空闲坐席的所有自动外呼池数据个数'.$auto_calling_cc_user_count.'<br><br>';
        unset($auto_calling_cc_user_list);
        echo '<br>*取空闲坐席和学员结束*<br><br>';

        //遍历空闲坐席
        foreach($admin_list as $admin_key=>$admin){
            $mobile_time=microtime(true);//取外呼数据时间开始

            //外呼开关
            $switch = $obj_autocallingswitch->getSwitch($admin['admin_id']);

            //初始学员信息
            $user_arr=array();
            $user_arr['user_id']='';
            $user_arr['auto_calling_cc_user_id']='';
            $user_arr['called_num']='';
            $user_arr['appoint_time']='';
            $user_arr['mobile']='';
            //$user_arr['is_buy']='';

            //从MAP数组中筛选出该坐席的自动外呼池学员
            if($switch['auto_calling_cc_user'] == 1) {
                $user_map = $auto_calling_cc_user_map[$admin['admin_id']];
                $user_arr['user_id'] = $user_map['user_id'];
                $user_arr['auto_calling_cc_user_id'] = $user_map['id'];
                $user_arr['called_num'] = $user_map['called_num'];
                $user_arr['appoint_time'] = $user_map['appoint_time'];
            }else{
                $uncall_admin[] = $admin['admin_id'] . '-5';//外呼开关设为全不呼
            }

            //从用户表取该学员的其他信息
            if(!empty($user_arr['user_id'])) {
                $user_detail = (new \Logic\Comm\User())->getUserInfoByUid($user_arr['user_id'],'mobile');
                //$user_detail = (new \Logic\Comm\User())->getUserInfoByUid($user_arr['user_id'],'mobile,is_buy');
                $user_arr['mobile'] = $user_detail['mobile'];
                //$user_arr['is_buy'] = $user_detail['is_buy'];
                unset($user_detail);
                if(!empty($user_arr['mobile'])) {
                }else{
                    //如果没有学员手机号就扔掉该学员
                    (new \Logic\Comm\AutoCallingCcUser())->updateAutoCallingCcUserByUserId($user_arr['user_id'],array('follow_call_status'=>1,'is_del'=>2,'update_time'=>$auto_calling_cc_outcall_time));
                }
            }

            //如果自动外呼池里没有合适的学员则去藏金阁取一个学员
            if(empty($user_arr['mobile'])){
                if($autocallingccconfig['follow_called_num'] > 0) {
                    $dispatch_user = $obj_autocallingccuser->addUserIntoAutoCalling($admin['admin_id']);
                    echo $admin['admin_id'] . '分的藏金阁数据：';
                    var_dump($dispatch_user);
                    if (is_array($dispatch_user)) {
                        $user_arr['user_id'] = $dispatch_user['user_id'];
                        $user_arr['auto_calling_cc_user_id'] = $dispatch_user['id'];
                        $user_arr['called_num'] = $dispatch_user['called_num'];
                        $user_arr['appoint_time'] = $dispatch_user['appoint_time'];
                        $user_arr['mobile'] = $dispatch_user['mobile'];
                        //$user_arr['is_buy'] = 'free';
                        $dispatch_admin[] = $admin['admin_id'];//得到藏金阁的坐席
                    } else {
                        $uncall_admin[] = $admin['admin_id'] . '-' . strval($dispatch_user);//未得到藏金阁的坐席和错误代码
                    }
                }else{
                    $uncall_admin[] = $admin['admin_id'] . '-4';//follow总拨打次数上限为0
                }
            }else{
                $auto_calling_admin[] = $admin['admin_id'];//得到自动外呼池学员的坐席
            }

            $mobile_time=microtime(true)-$mobile_time;//取外呼数据结束
            $do_while_log['mobile_time']+=$mobile_time;
            $run_log['mobile_time']+=$mobile_time;

            //没有取到学员
            if(empty($user_arr['mobile'])){
                //$uncall_admin[] = $admin['admin_id'] . '-3';
                continue;
            }

            //取到学员
            $do_while_log['mobile_count']++;
            $run_log['mobile_count']++;

            //锁坐席
            $mem_key = $obj_autocallingccuser->getAutoCallingLockKey($admin['admin_id']);
            $is_lock = $lock->tinyLock($mem_key,ADMIN_LOCK_TIME);
            if($is_lock === false){
                $uncall_admin[]=$admin['admin_id'].'-0';
                echo $admin['admin_id'].'已经锁定或者加锁失败<br><br>';
                continue;
            }
            echo $admin['admin_id'].'加锁成功<br><br>';
            $obj_remarklog->callRedisKey($user_arr['user_id'] . '_' . $admin['admin_id'] . '_is_call');

            echo $admin['admin_id'].'的最终用户数据：';
            var_dump($user_arr);

            $calling_time=microtime(true);//取外呼时间开始

            //cc_call_detail表存入日志
            $ti_time = strtotime($autocalling->getCurrentDate());//天润当前时间
            $cc_call_detail = array();
            $cc_call_detail['user_id'] = $user_arr['user_id'];
            $cc_call_detail['mobile'] = $user_arr['mobile'];
            $cc_call_detail['admin_id'] = $admin['admin_id'];
            $cc_call_detail['call_start_time'] = $ti_time;
            $cc_call_detail['create_time'] = $ti_time;
            $cc_call_detail['cno'] = $admin['join_number'];
            $cc_call_detail['call_status'] = 4;//其他
            $cc_call_detail['type'] = 2;
            $cc_call_detail['region'] = $area;
            $cc_call_detail['appoint_time'] = $user_arr['appoint_time'];
            $cc_call_detail_id = $obj_cccalldetail->addCcCallDetail($cc_call_detail);
            unset($cc_call_detail);

            //外呼数据
            $call_arr=array();
            $call_arr['user_id'] = $user_arr['user_id'];
            $call_arr['mobile'] = $user_arr['mobile'];
            $call_arr['cno'] = $admin['join_number'];
            $call_arr['pwd'] = $admin['join_password'];
            $call_arr['id'] = $cc_call_detail_id;
            $call_arr['called_num'] = $user_arr['called_num'];
            $call_arr['auto_calling_cc_user_id'] = $user_arr['auto_calling_cc_user_id'];
            echo $admin['admin_id'].'的外呼数据：';
            var_dump($call_arr);

            //开始外呼
            $called_result=array();
            if($is_call){
                $called_result = $autocalling->previewOutCall(array(0=>$call_arr));
            }else{
                $uncall_admin[]=$admin['admin_id'].'-1';
            }
            unset($call_arr);
            echo $admin['admin_id'].'的外呼结果：';
            var_dump($called_result);
            $called_result= intval(json_decode($called_result,true)[0]['res']);
            //外呼失败
            if($called_result!=0){
                $uncall_admin[]=$admin['admin_id'].'-2';
                //continue;
            }

            //外呼结果处理
            //cc_call_detail_log表存入日志
            $cc_call_detail_log = array();
            $cc_call_detail_log['cc_call_detail_id'] = $cc_call_detail_id;
            $cc_call_detail_log['add_time'] = time();
            $cc_call_detail_log['res'] = $called_result;
            $obj_cccalldetaillog->addCcCallDetailLog($cc_call_detail_log);

            //修改该学员自动外呼池数据
            $auto_calling_cc_user_result=array();
            $auto_calling_cc_user_result['follow_call_status']=1;
            $auto_calling_cc_user_result['last_call_time']=$auto_calling_cc_outcall_time;
            $auto_calling_cc_user_result['called_num']=$user_arr['called_num']+1;
            $auto_calling_cc_user_result['update_time']=$auto_calling_cc_outcall_time;
            $obj_autocallingccuser->updateAutoCallingCcUserById($user_arr['auto_calling_cc_user_id'],$auto_calling_cc_user_result);

            //每几组号码呼一次后停1秒
            if(($admin_key+1)%$call_group==0){
                sleep(1);
            }

            $calling_time=microtime(true)-$calling_time;//取外呼时间结束
            $do_while_log['calling_time']+=$calling_time;
            $do_while_log['called_count']++;
            $run_log['calling_time']+=$calling_time;
            $run_log['called_count']++;

            //遍历空闲坐席结束
        }
        //遍历大区结束
    }
    $do_while_log['run_time']=microtime(true)-$do_while_log['id'];

    if($is_dowhile==1) {
        $obj_autocallingccuser->cronRedisLog('auto_calling_cc_outcall_do_while_log',DO_WHILE_LOG_MAX,$do_while_log);

        $run_log['do_while_count']++;
        $run_log['do_while_run_time']+=$do_while_log['run_time'];
        //redis 解锁
        $lock->unlock(array('active' => 'auto_calling_cc_outcall'), $is_rest);
        sleep($sleepTime);
    }
}while(time()<$overTime && $is_dowhile==1);

//echo '<br>';
//echo '<br>';
//echo 'CC自动外呼执行结束';

$end_time=microtime(true);
$run_log['end_time']=date('Y-m-d H:i:s',$end_time);
$run_log['run_time']=$end_time-$start_time;
$run_log['is_dowhile']=$is_dowhile;
$run_log['auto_calling_admin']=implode(',',$auto_calling_admin);
$run_log['dispatch_admin']=implode(',',$dispatch_admin);
$run_log['uncall_admin']=implode(',',$uncall_admin);
//var_dump($run_log['uncall_admin']);

//echo '<br>';
//echo '<br>';
//var_dump($run_log);

//存入运行时间日志
$obj_autocallingccuser->cronRedisLog('auto_calling_cc_outcall_run_log',RUN_LOG_MAX,$run_log);

exit;
