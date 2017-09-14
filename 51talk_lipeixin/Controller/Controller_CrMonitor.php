<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/4/5
 * Time: 17:46
 */
class Controller_CrMonitor extends MasterController
{
    private $server;//天润服务器
    private $queue;//天润CR组队列号
    private $alias;//Auth系统CR组别名
    private $auto_calling;//天润接口封装类

    public function __construct()
    {
        parent::__construct();
        $this->server = 1;
        $this->queue = '0091';
        //$auth_alias = Load::loadConfig("auth_alias");
        //$this->alias = $auth_alias['cr_group'][0];
        $this->alias = 1368;
        $this->auto_calling = new AutoCalling($this->server);
    }

    public function index(){
        /*$redis = new RedisCrm();
        $redis_cr_monitor = $redis->get('cr_monitor');
        $session_cr_monitor = $_SESSION['cr_monitor'];
        if(isset($redis_cr_monitor)){
            if(isset($session_cr_monitor)){
                if($session_cr_monitor != $redis_cr_monitor){
                    echo '本页面已被其他人占用！';
                    exit;
                }
            }else{
                echo '本页面已被其他人占用！';
                exit;
            }
        }else{
            if(!isset($session_cr_monitor)){
                $_SESSION['cr_monitor'] = mt_rand(1000,9999);
            }
            $redis->set('cr_monitor',$_SESSION['cr_monitor'],120);
        }*/

        $auth_ext = Load::loadModel('AuthExt');
        $obj_admin_user = new \Logic\Comm\AdminUser();
        $status_zh = array('calling'=>'通话中','free'=>'空闲','pause'=>'置忙');

        //获取呼入量
        $callListNum = $this->getCallListNum(date('Y-m-d 00:00:00'),date('Y-m-d H:i:s'));//呼入量
        $now_minute = (int)date('i');
        $end_minute = '00';
        if($now_minute > 30){
            $end_minute = '30';
        }
        $end_time = date('Y-m-d H:'.$end_minute.':00');
        $start_time = date('Y-m-d H:i:s',strtotime($end_time)-30*60);
        $callListNum30 = $this->getCallListNum($start_time,$end_time);//30分钟呼入量

        //调用队列坐席状态接口
        $queueInfo = $this->auto_calling->queueMonitoring($this->queue);

        $calls = $queueInfo['queueStatus']['0']['queueParams']['calls'];//排队量
        $abandoned = $queueInfo['queueStatus']['0']['queueParams']['abandoned'];//挂断量
        $freeCount = 0;//空闲数
        $pauseCount = 0;//置忙数
        $serviceLevelPerf = $queueInfo['queueStatus']['0']['queueParams']['serviceLevelPerf'].'%';//10秒接听率

        //获得CR组队列中坐席对应的状态
        $clientHash = array();
        $memberStatus = $queueInfo['queueStatus']['0']['memberStatus'];
        $key_params = $this->auto_calling->getkeyParamsArray();
        foreach($memberStatus as $value){
            if($value['loginStatus'] != 'offline') {
                $clientInfo = array();
                $queueNo = substr($value['cid'], strlen($key_params[$this->server]['enterpriseId']));
                if($value['deviceStatus'] == 'busy'){
                    $clientInfo['status'] = 'calling';//通话中
                }elseif($value['loginStatus'] == 'online' && $value['deviceStatus'] == 'idle'){
                    $clientInfo['status'] = 'free';//空闲
                }elseif($value['loginStatus'] == 'pause' && $value['deviceStatus'] == 'idle'){
                    $clientInfo['status'] = 'pause';//置忙
                }else{
                    $clientInfo['status'] = 'pause';
                }
                $clientInfo['duration'] = gmstrftime('%H:%M:%S', $value['duration']);//状态时长
                $clientInfo['cname'] = $value['cname'];//坐席名字
                //$admin_user = $obj_admin_user->getAdminUserInfoByWhere('join_number='.$queueNo);
                //$clientInfo['admin_user'] = $admin_user;
                $clientHash[$queueNo] = $clientInfo;
            }
        }

        //空闲和置忙明细
        $free_busy_detail = array();
        $org = $auth_ext->getSonOrAliasStrByAlias($this->alias);
        $org = array_flip($org);
        foreach ($org as $group_name=>$alias){
            $admin_list = array();
            $children = $auth_ext->getChildrenUser($alias);
            foreach ($children as $old_id=>$user_name){
                $admin_info = array();
                //$admin_auth = $auth_ext->getUser($old_id);
                $admin_user = $obj_admin_user->getAdminUserById($old_id);
                if(!isset($admin_user['join_number']) || empty($admin_user['join_number'])){
                    continue;
                }
                $join_number = intval($admin_user['join_number']);
                if(!isset($clientHash[$join_number])){
                    continue;
                }
                $client = $clientHash[$join_number];
                $admin_info['user_name'] = $user_name;
                $admin_info['name_zh'] = $admin_user['name_zh'];
                $admin_info['cname'] = $client['cname'];
                $admin_info['status'] = $client['status'];
                $admin_info['status_zh'] = $status_zh[$client['status']];
                $admin_info['time'] = $client['duration'];
                $admin_list[] = $admin_info;
                if($client['status'] == 'free'){
                    $freeCount++;
                }
                if($client['status'] == 'pause'){
                    $pauseCount++;
                }
            }
            $free_busy_detail[$group_name] = $admin_list;
        }

        $this->assign('callListNum',$callListNum);
        $this->assign('callListNum30',$callListNum30);
        $this->assign('calls',$calls);
        $this->assign('abandoned',$abandoned);
        $this->assign('freeCount',$freeCount);
        $this->assign('pauseCount',$pauseCount);
        $this->assign('serviceLevelPerf',$serviceLevelPerf);

        $this->assign('free_busy_detail',$free_busy_detail);
        $this->display('CrMonitor/cr_monitor.html');
    }

    //获取呼入量
    private function getCallListNum($start_time,$end_time){
        $param['startTime'] = $start_time;
        $param['endTime'] = $end_time;
        //$param['start'] = 0;
        //$param['limit'] = 1000;
        $param['title'] = 'qno';
        $param['value'] = $this->queue;
        $result = $this->auto_calling->getWebCallList($param);//调用通话记录接口
        $callListNum = $result['msg']['paging']['totalSize'];//呼入量
        return $callListNum;
    }

    public function AdminUserPhone(){
        $this->display('CrMonitor/admin_user_phone.html');
    }

    public function AdminUserPhoneActive(){
        $file_admin_user_phone = $_FILES['file_admin_user_phone'];
        $end_file = BASE_PATH.'/Html/upload'.$file_admin_user_phone['name'];
        $ext_name = pathinfo($end_file)['extension'];
        if(!in_array($ext_name,array('xls','xlsx'))){
            die('文件格式错误！');
        }
        move_uploaded_file($file_admin_user_phone['tmp_name'],$end_file);
        $data = $this->getArrFromExcel($end_file, 'Excel2007');
        $model_AdminUser = Load::loadModel('AdminUser');
        $service_AdminUser = (new \Logic\Comm\AdminUser());
        $result = array();
        foreach ($data as $value){
            $user_name = trim($value[0]);
            if(empty($user_name)){
                continue;
            }
            $phone = trim($value[1]);
            if(empty($phone)){
                $result[$user_name] = '没有电话';
                continue;
            }
            $admin_user_id = $service_AdminUser->getAdminUserInfoRowsByWhere('user_name="'.$user_name.'"','id')[0]['id'];
            if(empty($admin_user_id)){
                $result[$user_name] = '没有这个帐号';
                continue;
            }
            $res = $model_AdminUser->updateAdminUser($admin_user_id,array('phone'=>$phone));
            if($res > 0){
                $result[$user_name] = '成功';
            }else{
                $result[$user_name] = '成功';
            }
        }
        echo json_encode($result);
    }

    function getArrFromExcel($inputFileName, $inputFileType) {
        include_once (BASE_PATH . '/vendor/phpoffice/phpexcel/Classes/PHPExcel.php');
        $objReader = PHPExcel_IOFactory::createReader ( $inputFileType );
        // 设置只读，可取消类似"3.08E-05"之类自动转换的数据格式，避免写库失败
        $objReader->setReadDataOnly ( true );
        $objPHPExcel = $objReader->load ( $inputFileName );
        // 获取excel总页数
        $sheet_count = $objPHPExcel->getSheetCount ();
        $data = array ();
        for($s = 0; $s < $sheet_count; $s ++) {
            // 当前页
            $currentSheet = $objPHPExcel->getSheet ( $s );
            // 当前页行数
            $row_num = $currentSheet->getHighestRow ();
            // 当前页最大列号
            $col_max = $currentSheet->getHighestColumn ();

            // 循环从第二行开始，第一行是表头
            for($i = 1; $i <= $row_num; $i ++) {
                $cell_values = array ();
                for($j = 'A'; $j <= $col_max; $j ++) {
                    // 单元格坐标
                    $address = $j . $i;
                    $value = $currentSheet->getCell ( $address )->getFormattedValue ();
                    $value = trim ( $value );
                    $value = addslashes ( $value );
                    $cell_values [] = $value;
                }
                $data [] = $cell_values;
            }
        }
        return $data;
    }
}