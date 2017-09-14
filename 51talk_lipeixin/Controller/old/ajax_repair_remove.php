<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/2/23
 * Time: 14:38
 */
//CC自动外呼修复数据并放入藏金阁操作
include "../../init.php";
$user_ids = Http::post('user_id');
$admin_id = Http::post("admin_id");
//$admin_id = Http::session("admin_user_id");
$date = date('Y-m-d H:i:s');

$obj_user = Load::loadModel("User");
//$obj_log = Load::loadModel("Log");
$obj_autocallingccuser = Load::loadModel('AutoCallingCcUser');
$FollowLog = new \Logic\Comm\FollowLog();
$obj_ccdispath = Load::loadModel('CcDispatch');
$obj_usergongchi = Load::loadData('UserGongchi');

$obj_appoint = Load::loadData("Appoint");

$CcUserCycle = Load::loadData("CcUserCycle");
$SmallTreasuriesLog = Load::loadData("SmallTreasuriesLog");

$fail_user_ids=array();
$success_user_ids=array();
$user_id_arr = explode(',',$user_ids);
foreach($user_id_arr as $user_id){
    $userInfo = (new \Logic\Comm\User())->getUserByUid($user_id);

    //小金库
    $is_inSt         = (new \Logic\Comm\CcUserCycle())->getCcUserCycleCountByWhere("user_id = '$user_id' and status = 3 ");
    if($is_inSt){  //假如说在小金库中则需要更新 status状态
        $smdata_log['user_id']    = $user_id;
        $smdata_log['admin_id']   = $userInfo['custom_id'];
        $smdata_log['add_time']   = time();
        $smdata_log['type']       = 4;
        $smdata_log['oprator_id'] =$admin_id;
        $SmallTreasuriesLog->addRowsToLog($smdata_log);
    }
    //小金库

    $data = array();
    $data['select_time'] = '';
    //判断该用户是否满足进冷冻期逻辑：3天未跟进并且超过3个CC跟进过
    $follow_three_cc = (new \Logic\Comm\RemarkLog())->getLogListByParams(array('user_id'=>$user_id,'log_types'=>'2,4'), 'distinct(operator)');
    if(count($follow_three_cc)>=3){
        $data['is_select'] = 2;
        $data['frozen_time'] = $date;
        $data['last_time'] = $date;
    }else{
        $data['is_select'] = 0;
        $data['into_time'] = $date;
        $data['last_time'] = $date;
    }

    $data['stu_id']        = $userInfo['id'];
    $data['email']         = $userInfo['user_name'];
    $data['stu_name']      = $userInfo['nick_name'];
    $data['tel']           = $userInfo['tel'];
    $data['mobile']        = $userInfo['mobile'];
    $data['skype_id']      = $userInfo['skype_id'];
    $data['occup']         = $userInfo['occup'];
    $data['customer_id']   = $userInfo['custom_id'];
    $data['register_from'] = $userInfo['register_from'];
    $data['from_url']      = $userInfo['from_url_id'];
    $data['city']          = $userInfo['city'];
    $data['reg_time']      = $userInfo['add_time'];
    $data['qq']            = $userInfo['qq'];
    $data['is_staff']      = $userInfo['is_staff'];
    //获取该学员的体验课信息
    //非中教约课
    $class_info = $obj_appoint->getStuLastScripturesTime("s_id = $user_id ",'id,start_time,status,course_id,add_time','id desc');
    if(empty($class_info)){
        //中教的问题
        $class_info = (new \Logic\Comm\CtAppointDetail())->getInfoByWhereNew("user_id = $user_id ", 'id,start_time,status,course_id,add_time', 'id desc');
        if(empty($class_info)){
            //$fail_user_ids[]=array('user_id'=>$user_id,'reason'=>'该学员中教非中教都没约课');
            //continue;
            $data['cn_app_id'] = 0;
            $data['free_time']  = 0;
            $data['last_follow'] = 0;
        }else{
            $data['cn_app_id'] =$class_info['id'];
            $data['free_time']     = date('Y-m-d H:i:s',$class_info['start_time']);
            $data['last_follow']   = date('Y-m-d H:i:s',$class_info['start_time']);
        }
        $data['app_id']= 0;
    }else{
        $data['app_id'] = $class_info['id'];
        $data['free_time']     = $class_info['start_time'];
        $data['last_follow']   = $class_info['start_time'];
    }
    $data['free_status']   = $class_info['status'];
    $data['course']        = $class_info['course_id'];

    $follow_info           = (new \Logic\Comm\FollowLog())->getFollowLogByUserId($user_id);
    $data['buy_level']     = $follow_info['user_level'];
    $data['follow_date']   = $follow_info['follow_date'];
    $data['add_time']      = $class_info['add_time'];
    $data['last_time']     = $class_info['add_time'];
    //print_r($data);
    $insert_gongchi_result = $obj_usergongchi->addUserGongchi($data);
    //echo $insert_gongchi_result;

    (new \Logic\Comm\AllTableChangeLog())->addAllTableChangeLog("user_gongchi",$user_id,'is_select',$data['is_select'],'cc_del',1,'',$user_id,$admin_id);
    $FollowLog->updateFollowLogDataToDb(array('follow_date' => 'null'), $user_id);
    (new \Logic\Comm\AutoCallingCcUser())->updateAutoCallingCcUserByUserId($user_id,array('admin_call_status'=>2));
    $obj_ccdispath->insertDispatchLog($user_id,$admin_id,'-3');

    $success_user_ids[]=$user_id;
}
$result = (new \Logic\Comm\User())->updateUserListByUids($success_user_ids, array('custom_id'=>$admin_id));

if(!empty($fail_user_ids)) {
    echo json_encode($fail_user_ids);
}

