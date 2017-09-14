<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/1/28
 * Time: 13:33
 */

class Controller_AutoCallingCcUser extends MasterController {

    public function __construct() {
        parent::__construct();

        $this->redis = Load::loadRedis();

        define('RUN_LOG_MAX',600);//进程日志最大记录数
        define('DO_WHILE_LOG_MAX',RUN_LOG_MAX*5);//动态过程日志最大记录数
        define('TIME_ROUND',4);//时间保留位数
        define('PERCENT_ROUND',1);//百分比保留位数

        $this->uncall_reason=array();
        $this->uncall_reason[0]='坐席已锁定';
        $this->uncall_reason[1]='测试关闭外呼';
        $this->uncall_reason[2]='调用天润接口外呼失败';
        $this->uncall_reason[3]='没有取到任何学员';
        $this->uncall_reason[4]='follow总拨打次数上限';
        $this->uncall_reason[5]='外呼设置为全不呼';

        $this->uncall_reason[10]='坐席admin_id错误';
        $this->uncall_reason[11]='坐席藏经阁开关未开';
        $this->uncall_reason[12]='坐席已达藏金阁分配上限';
        $this->uncall_reason[13]='坐席未重新登录天润';
        $this->uncall_reason[14]='坐席是topsales';
        $this->uncall_reason[15]='坐席名下学员已达上限';
        $this->uncall_reason[16]='系统分配学员user_id错误';
        $this->uncall_reason[17]='系统分配学员已锁定';
        $this->uncall_reason[18]='系统分配学员电话为空或是付费用户';
        $this->uncall_reason[19]='系统分配学员未上体验课';
        $this->uncall_reason[20]='插入或修改自动外呼池失败';
    }

    //获取redis日志
    public function getRedisLog($key=false,$len=false){
        if(!$key){
            $key=Http::request('key');
        }
        if(!$len){
            $len=Http::request('len');
            if(!$len){
                $len = $this->redis -> llen($key);
            }
        }
        $len--;
        $log = $this->redis -> lrange($key,0,$len);
        $log = array_reverse($log);
        var_dump($log);
    }

    //清除redis日志
    public function clearRedisKey($key=false){
        if(!$key){
            $key=Http::request('key');
        }
        $this->redis ->del($key);
    }


    //CC学员详情页呼叫提示
    public function CallUserTip(){
        $admin_user_id = Http::session('admin_user_id');
        $stuid = Http::request('stuid');
        $callback = Http::get('callback');

        //查看是否有缓存的提示语
        
        $tip_cache = $this->redis->get('ccTip_'.$admin_user_id.'_'.$stuid);
        if($tip_cache) {
            echo $callback.$tip_cache;
            exit;
        }

        $data_user_order = Load::loadData("UserOrder");

        //提示信息配置
        $cc_tip = Load::loadConfig('call_user_tip','cc');

        //新权限系统外呼提示设置（errcode为0是有权限）
        $authExtModel = Load::loadModel('AuthExt');
        $cc_followbefore = $authExtModel->checkUpdata('cc_followbefore');
        $cc_followafter  = $authExtModel->checkUpdata('cc_followafter');
        $cc_otherfollow  = $authExtModel->checkUpdata('cc_otherfollow');

        //判断学员付费情况
        $user_order_num = $data_user_order->getSuccessOrderNumByUids($stuid);
        
        if ($user_order_num == 0) {
            $Appoint = Load::loadData("Appoint");
            $appoint_arr=$Appoint->getAppointInfo('s_id='.$stuid, 'start_time,end_time,status', 'id desc');//最近一次约课记录

            if(!empty($appoint_arr)) {
                $now_time = time();
                $start_time = strtotime($appoint_arr['start_time']);
                $end_time = strtotime($appoint_arr['end_time']);

                if ($now_time < $start_time) {
                    //学员最近一次外教1V1上课开始时间之前触发
                    $has_ended=$Appoint->getAppointInfo('s_id='.$stuid.' and status="end"', 'id');//之前有没有正常结束的课程信息
                    if(!$has_ended) {
                        //如果课程状态没有为'正常结束'
                        if ($cc_followbefore === true) {
                            $tip = $cc_tip['A'];
                        } else {
                            $tip = 11;
                        }
                    }else{
                        if ($cc_followafter === true) {
                            $tip = $cc_tip['B'];
                        }else{
                            $tip = 12;
                        }
                    }
                } elseif ($now_time > $end_time) {
                    //学员最近一次外教1V1上课结束时间至首次付费之间触发
                    if ($appoint_arr['status'] == 'end') {
                        //课程状态为'正常结束'
                        if ($cc_followafter === true) {
                            $tip = $cc_tip['B'];
                        } else {
                            $tip = 13;
                        }
                    } else {
                        if ($cc_followbefore === true) {
                            $tip = $cc_tip['A'];
                        }else{
                            $tip = 14;
                        }
                    }
                } else {
                    //课中
                    if ($cc_followbefore === true) {
                        $tip = $cc_tip['A'];
                    } else {
                        $tip = 15;
                    }
                }
            }else{
                //没有约课
                if ($cc_followbefore === true) {
                    $tip = $cc_tip['A'];
                }else{
                    $tip = 16;
                }
            }
        } else {
            //已付费
            if ($cc_otherfollow === true) {
                $tip = $cc_tip['OTHER'];
            }else{
                $tip = 17;
            }
        }
        
        if(is_numeric($tip)) {
            $call_user_tip_log = json_encode(array('time'=>time(),'admin_id'=>$admin_user_id,'stuid'=>$stuid,'tip'=>$tip));
            $this->setRedisLog('call_user_tip_log', 1000, $call_user_tip_log);
        }else{
            $tip_word = '('.json_encode($tip).')';
            $this->redis->set('ccTip_'.$admin_user_id.'_'.$stuid,$tip_word,300);//缓存提示语
            echo $callback.$tip_word;
        }
    }


    //进程监控主页
    public function ccAutoCallingCronLog(){
        $this->display("AutoCallingCcUser/cc_auto_calling_cron_log.html");
    }

    //进程运行详情
    public function ccAutoCallingCronLogDetail(){
        $run_log = $this->redis->lrange('auto_calling_cc_outcall_run_log', 0, RUN_LOG_MAX);
        $run_log = array_reverse($run_log);
        $run_log_list = array();
        foreach($run_log as $run){
            $run=json_decode($run,true);
            $run_arr=array();
            $run_arr['id']=$run['id'];
            $run_arr['start_time']=$run['start_time'];
            $run_arr['config_time']=$this->makeZero($run['config_time']);
            $run_arr['free_clients_count']=$run['free_clients_count'];
            $run_arr['free_clients_time']=$this->makeZero($run['free_clients_time']);
            $run_arr['auto_calling_cc_user_count']=$run['auto_calling_cc_user_count'];
            $run_arr['auto_calling_cc_user_time']=$this->makeZero($run['auto_calling_cc_user_time']);
            if(isset($run['auto_calling_admin']) && !empty($run['auto_calling_admin'])) {
                $auto_calling_admin_arr = explode(',', $run['auto_calling_admin']);
                $auto_calling_admin_count = count($auto_calling_admin_arr);
                $auto_calling_admin_percent = round($auto_calling_admin_count / $run['free_clients_count'] * 100, PERCENT_ROUND);
                $run_arr['auto_calling_admin']=$auto_calling_admin_count . '(' . $auto_calling_admin_percent . '%)';
            }else{
                $run_arr['auto_calling_admin'] = '0(0%)';
            }
            if(isset($run['dispatch_admin']) && !empty($run['dispatch_admin'])) {
                $dispatch_admin_arr=explode(',',$run['dispatch_admin']);
                $dispatch_admin_count = count($dispatch_admin_arr);
                $dispatch_admin_percent = round($dispatch_admin_count / $run['free_clients_count'] * 100,PERCENT_ROUND);
                $run_arr['dispatch_admin'] = $dispatch_admin_count . '(' . $dispatch_admin_percent . '%)';

            }else{
                $run_arr['dispatch_admin'] = '0(0%)';
            }
            $run_arr['mobile_count'] = $run['mobile_count']. '(' . round($run['mobile_count'] / $run['free_clients_count'] * 100,PERCENT_ROUND) . '%)';
            $run_arr['mobile_time'] = $this->makeZero($run['mobile_time']);
            if(isset($run['uncall_admin']) && !empty($run['uncall_admin'])) {
                $uncall_admin_arr=explode(',',$run['uncall_admin']);
                $uncall_admin_arr_unique=array();
                foreach($uncall_admin_arr as $uncall_admin){
                    $uncall_admin_arr_unique[]=explode('-',$uncall_admin)[0];
                }
                $uncall_admin_arr_unique = array_unique($uncall_admin_arr_unique);
                $uncall_admin_count = count($uncall_admin_arr_unique);
                $uncall_admin_percent = round($uncall_admin_count / $run['free_clients_count'] * 100,PERCENT_ROUND);
                $run_arr['uncall_admin'] = $uncall_admin_count . '(' . $uncall_admin_percent . '%)';
            }else{
                $run_arr['uncall_admin'] = '0(0%)';
            }
            $run_arr['uncall_list'] = $run['uncall_admin'];
            $run_arr['called_count'] = $run['called_count']. '(' . round($run['called_count'] / $run['free_clients_count'] * 100,PERCENT_ROUND) . '%)';
            $run_arr['calling_time'] = $this->makeZero($run['calling_time']);
            $run_arr['end_time'] = $run['end_time'];
            if($run['run_time']>60 && $run['is_dowhile']==0){
                $run_arr['run_time'] = '<font color="red">'.$this->makeZero($run['run_time']).'</font>';
            }else{
                $run_arr['run_time'] = $this->makeZero($run['run_time']);
            }
            $run_log_list[]=$run_arr;
        }
        $run_log=$run_log_list;
        unset($run_log_list);
        //var_dump($run_log);
        //exit;
        $this->assign('run_log',$run_log);
        $this->display("AutoCallingCcUser/cc_auto_calling_cron_log_detail.html");
    }

    //根据进程查找外呼失败的坐席
    public function UnCallAdmin(){
        $obj_admin_user = Load::loadData('AdminUser');
        $uncall_admin = Http::post('uncall_admin');
        $uncall_admin = explode(',',$uncall_admin);
        $uncall_admin_list = array();
        foreach ($uncall_admin as $admin) {
            $admin_cell = explode('-', $admin);
            $reason = $this->uncall_reason[$admin_cell[1]].'('.$admin_cell[1].')';
            if (isset($uncall_admin_list[$admin_cell[0]])) {
                $uncall_admin_list[$admin_cell[0]]['reason'].= '，'.$reason;
            } else {
                $uncall_admin_list[$admin_cell[0]] = (new \Logic\Comm\AdminUser())->getAdminUserById($admin_cell[0], "id as admin_id,user_name,group_id,name_zh,join_number");
                $uncall_admin_list[$admin_cell[0]]['reason'] = $reason;
            }
        }
        $this->assign('type','uncall_admin');
        $this->assign('fail_list',$uncall_admin_list);
        $this->display("AutoCallingCcUser/cc_auto_calling_fail_list.html");
    }

    //根据用户名或中文名取数据成功的坐席
    public function DataAdminList(){
        $user_name=Http::post('user_name');
        $user_name=trim($user_name);

        $admin_user=(new \Logic\Comm\AdminUser())->getAdminUserByUnameOrNameZh($user_name,'id,user_name,name_zh,group_id,join_number');


        $run_log = $this->redis->lrange('auto_calling_cc_outcall_run_log', 0, RUN_LOG_MAX);
        $run_log = array_reverse($run_log);

        $data_admin_list=array();
        if(!empty($admin_user)) {
            foreach ($run_log as $run) {
                $run = json_decode($run, true);
                $auto_calling_admin = explode(',', $run['auto_calling_admin']);
                $dispatch_admin = explode(',', $run['dispatch_admin']);
                $search_result=array();
                $search_result['time']=$run['start_time'];
                $search_result['type']='';
                if(in_array($admin_user['id'],$auto_calling_admin)){
                    $search_result['type']='自动外呼池';
                }elseif(in_array($admin_user['id'],$dispatch_admin)){
                    $search_result['type']='藏金阁';
                }
                if(!empty($search_result['type'])){
                    $data_admin_list[]=$search_result;
                }
            }
        }
        $this->assign('admin_user',$admin_user);
        $this->assign('data_list',$data_admin_list);
        $this->display("AutoCallingCcUser/cc_auto_calling_cron_data.html");
    }

    //根据用户名或中文名查找外呼失败的坐席
    public function UnCallAdminList(){
        $user_name=Http::post('user_name');
        $user_name=trim($user_name);

        $admin_user=(new \Logic\Comm\AdminUser())->getAdminUserByUnameOrNameZh($user_name,'id,name_zh,user_name,group_id,join_number');

        //var_dump($admin_user);

        $run_log = $this->redis->lrange('auto_calling_cc_outcall_run_log', 0, RUN_LOG_MAX);
        $run_log = array_reverse($run_log);

        $uncall_admin_list = array();
        if(!empty($admin_user)) {
            foreach ($run_log as $run) {
                $run = json_decode($run, true);
                $uncall_admin = explode(',', $run['uncall_admin']);
                $search_result = array();
                foreach ($uncall_admin as $admin) {
                    $admin_arr = explode('-', $admin);
                    if ($admin_arr[0] == $admin_user['id']) {
                        $search_reason = '';
                        if (isset($this->uncall_reason[$admin_arr[1]])) {
                            $search_reason .= $this->uncall_reason[$admin_arr[1]];
                        }
                        $search_reason .= '(' . $admin_arr[1] . ')';
                        $search_result[] = $search_reason;
                    }
                }
                if(!empty($search_result)) {
                    $li = array();
                    $li['time'] = $run['start_time'];
                    $li['type'] = implode('，', $search_result);
                    $uncall_admin_list[] = $li;
                }
            }
        }
        //var_dump($data_admin_list);
        $this->assign('admin_user',$admin_user);
        $this->assign('data_list',$uncall_admin_list);
        $this->display("AutoCallingCcUser/cc_auto_calling_cron_data.html");
    }

    //备注触发外呼失败记录
    public function RemarkCall(){
        $obj_admin_user = Load::loadData('AdminUser');

        $remark_call_admin=Http::post('remark_call_admin');
        $remark_call_admin=trim($remark_call_admin);

        $remark_call = $this->redis->lrange('remark_call', 0, RUN_LOG_MAX);
        $remark_call = array_reverse($remark_call);

        $remark_call_list=array();
        foreach($remark_call as $remark){
            $remark=json_decode($remark,true);
            $admin_arr = (new \Logic\Comm\AdminUser())->getAdminUserById($remark['admin_id'], "user_name,group_id,name_zh,name_en,join_number");
            $remark['time'] = date('Y-m-d H:i:s',$remark['time']);
            $remark['user_name'] = $admin_arr['user_name'];
            $remark['name_zh'] = $admin_arr['name_zh'];
            $remark['group_id'] = $admin_arr['group_id'];
            $remark['join_number'] = $admin_arr['join_number'];

            if(is_numeric($remark['msg'])){
                $remark['reason']=$this->uncall_reason[$remark['msg']].'('.$remark['msg'].')';
            }else{
                $remark['reason']=$remark['msg'];
            }
            unset($remark['msg']);

            if($remark_call_admin) {
                //搜索
                if($remark_call_admin==$remark['user_name'] || $remark_call_admin==$remark['name_zh']) {
                    $remark_call_list[] = $remark;
                }
            }else{
                $remark_call_list[] = $remark;
            }
        }
        $this->assign('type','remark_call');
        $this->assign('fail_admin',$remark_call_admin);
        $this->assign('fail_list',$remark_call_list);
        $this->display("AutoCallingCcUser/cc_auto_calling_fail_list.html");
    }

    //放入藏金阁失败记录
    public function GoldFail(){
        $obj_admin_user = Load::loadData('AdminUser');

        $gold_fail_admin=Http::post('gold_fail_admin');
        $gold_fail_admin=trim($gold_fail_admin);

        $gold_fail_reason=array();
        $gold_fail_reason[0]='代理商';
        $gold_fail_reason[1]='custom_id > 50000';
        $gold_fail_reason[2]='register_from 2822312,2822313';
        $gold_fail_reason[3]='user_gongchi无数据';
        $gold_fail_reason[4]='已付费';
        $gold_fail_reason[9]='测试';

        $gold_fail = $this->redis->lrange('gold_fail', 0, RUN_LOG_MAX);
        $gold_fail = array_reverse($gold_fail);

        $gold_fail_list=array();
        foreach($gold_fail as $gold){
            $gold=json_decode($gold,true);
            $admin_arr = (new \Logic\Comm\AdminUser())->getAdminUserById($gold['admin_id'], "user_name,group_id,name_zh,name_en,join_number");
            $gold['time'] = date('Y-m-d H:i:s',$gold['time']);
            $gold['user_name'] = $admin_arr['user_name'];
            $gold['name_zh'] = $admin_arr['name_zh'];
            $gold['group_id'] = $admin_arr['group_id'];
            $gold['join_number'] = $admin_arr['join_number'];

            $gold['reason']=array();
            foreach($gold['user_id'] as $reason){
                $reason_arr=explode('-',$reason);
                $gold['reason'][]='学员ID：'.$reason_arr[0].'&nbsp;原因：'.$gold_fail_reason[$reason_arr[1]].'('.$reason_arr[1].')';
            }
            $gold['reason']=implode('<br>',$gold['reason']);

            if($gold_fail_admin) {
                //搜索
                if($gold_fail_admin==$gold['user_name'] || $gold_fail_admin==$gold['name_zh']) {
                    $gold_fail_list[] = $gold;
                }
            }else{
                $gold_fail_list[] = $gold;
            }
        }
        $this->assign('type','gold_fail');
        $this->assign('fail_admin',$gold_fail_admin);
        $this->assign('fail_list',$gold_fail_list);
        $this->display("AutoCallingCcUser/cc_auto_calling_fail_list.html");
    }

    //学员搜索
    public function StuSearch(){
        $stu_id=Http::post('stu_id');

        $obj_usergongchi = Load::loadData('UserGongchi');
        $stu_arr=array();
        $stu_arr['user_gongchi']=$obj_usergongchi->getSingleUserGongchi('stu_id='.$stu_id);
        $stu_arr['auto_calling_cc_user']=(new \Logic\Comm\AutoCallingCcUser())->getAutoCallingCcUserByUserId($stu_id);
        $stu_arr['cc_user_cycle']=(new \Logic\Comm\CcUserCycle())->getCcUserCycleByUserId($stu_id);
        $stu_arr['user']=(new \Logic\Comm\User())->getUserByUid($stu_id);
        $stu_arr['recommend_user']=(new \Logic\Comm\RecommendUser())->getDataByMobile($stu_arr['user']['mobile']);

        //$highlight=array('is_select');

        $this->assign('stu_id',$stu_id);
        $this->assign('stu_arr',$stu_arr);
        $this->display("AutoCallingCcUser/cc_auto_calling_stu_search.html");
    }

    //强制放入藏金阁
    public function IntoUserGongchi(){
        $stu_id=Http::post('stu_id');
        $stu_admin=Http::post('stu_admin');
        $is_select=Http::post('is_select');
        $update_arr=array("is_select"=>$is_select,"into_time"=>date("Y-m-d H:i:s",time()));
        if($stu_admin){
            $update_arr['customer_id']=$stu_admin;
            (new \Logic\Comm\User())->updateUserByUid($stu_id, ["custom_id"=>$stu_admin]);
        }
        Load::loadData('UserGongchi')->updateUserGongchi($stu_id,$update_arr);
    }

    //强制放入自动外呼池
    public function IntoAutoCallingCcUser(){
        $stu_id=Http::post('stu_id');
        $stu_admin=Http::post('stu_admin');
        $obj_ccusercycle = Load::loadData('CcUserCycle');

        $is_cycle=array();
        $is_cycle=(new \Logic\Comm\CcUserCycle())->getCcUserCycleByUserId($stu_id);
        if(!empty($is_cycle)){
            $fieldData=array();
            $fieldData['leads_type'] = 2;
            $fieldData['admin_id']   = $stu_admin;
            $markingResult =(new \Logic\Comm\CcUserCycle())->updateCcUserCycleByUserId($stu_id, $fieldData);
        }
        Load::loadModel('AutoCalling')->addAutoCalling($stu_id, $stu_admin);
    }

    //清空日志
    public function runLogClear(){
        $this->redis->del('auto_calling_cc_outcall_run_log');
        $this->redis->del('auto_calling_cc_outcall_do_while_log');
    }

    //取小数位数
    public function makeZero($data,$length=TIME_ROUND){
        if($data<0){
            return 0;
        }
        return round($data,$length);
    }

}