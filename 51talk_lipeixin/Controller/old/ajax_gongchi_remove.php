<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/2/23
 * Time: 14:38
 */
//CC自动外呼放入藏金阁操作
include "../../init.php";
$user_ids = Http::post('user_id');
$admin_id = Http::post("admin_id");
//$admin_id = Http::session("admin_user_id");
$date = date('Y-m-d H:i:s');

$obj_user = Load::loadModel("User");
$obj_log = Load::loadModel("Log");
$obj_autocallingccuser = Load::loadModel('AutoCallingCcUser');
$obj_ccdispath = Load::loadModel('CcDispatch');
$obj_usergongchi = Load::loadData('UserGongchi');

$CcUserCycle = Load::loadData("CcUserCycle");
$SmallTreasuriesLog = Load::loadData("SmallTreasuriesLog");

//代理商
$parentIdsCc = (new \Logic\Comm\Agent())->getAgentTagByType('cc');
$parentIdsTmk = (new \Logic\Comm\Agent())->getAgentTagByType('tmk');
$parentIds = array_merge($parentIdsCc,$parentIdsTmk);

//特殊渠道用户分配处理
$unsetUserId = array(2822312,2822313);

$fail_user_ids=array();
$success_user_ids=array();
$user_id_arr = explode(',',$user_ids);
foreach($user_id_arr as $user_id){
    $gongchi_arr = $obj_usergongchi->getSingleUserGongchi("stu_id=".$user_id);
    //判断公池里是否有该学员
    if (!$gongchi_arr) {
        $fail_user_ids[]=array('user_id'=>$user_id,'reason'=>3);
        continue;
    }

    $user_arr = (new \Logic\Comm\User())->getUserByUid($user_id, "mobile,parent_id,custom_id,register_from,is_buy");
    //判断是否为付费学员
    if($user_arr['is_buy'] == 'buy'){
        $fail_user_ids[]=array('user_id'=>$user_id,'reason'=>4);
        continue;
    }

    //判断代理商
    if(in_array($user_arr['parent_id'], $parentIds)){
        $fail_user_ids[]=array('user_id'=>$user_id,'reason'=>0);
        if($gongchi_arr['customer_id']!=$admin_id) {
            continue;
        }
    }

    //特殊渠道用户分配处理
    if(in_array($user_arr['register_from'],$unsetUserId)) {
        $fail_user_ids[]=array('user_id'=>$user_id,'reason'=>2);
        continue;
    }

    //小金库
    $is_inSt         = (new \Logic\Comm\CcUserCycle())->getCcUserCycleCountByWhere("user_id = '$user_id' and status = 3 ");
    if($is_inSt){  //假如说在小金库中则需要更新 status状态
        $smdata_log['user_id']    = $user_id;
        $smdata_log['admin_id']   = $user_arr['custom_id'];
        $smdata_log['add_time']   = time();
        $smdata_log['type']       = 4;
        $smdata_log['oprator_id'] =$admin_id;
        $SmallTreasuriesLog->addRowsToLog($smdata_log);
    }
    //小金库

    //修改公池
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
    $gresult = $obj_user->updateUserGongchi($user_id, $data);

    (new \Logic\Comm\AllTableChangeLog())->addAllTableChangeLog("user_gongchi",$user_id,'is_select',$data['is_select'],'cc_del',1,'',$user_id,$admin_id);
    (new \Logic\Comm\AutoCallingCcUser())->updateAutoCallingCcUserByUserId($user_id,array('admin_call_status'=>2));
    (new \Logic\Comm\FollowLog())->updateFollowLogDataToDb(array('follow_date'=>'null'), $user_id);
    $obj_ccdispath->insertDispatchLog($user_id,$admin_id,'-3');

    $success_user_ids[]=$user_id;
}
$result = (new \Logic\Comm\User())->updateUserListByUids($success_user_ids, array('custom_id'=>$admin_id));

if(empty($fail_user_ids)) {
    echo 0;
}else{
    $fail_log = array();
    $fail_log['admin_id'] = $admin_id;
    $fail_log['time'] = time();
    $fail_log['user_id'] = array();
    $fail_reason_arr=array();
    foreach ($fail_user_ids as $fail) {
        $fail_log['user_id'][] = $fail['user_id'] . '-' . $fail['reason'];
        $fail_reason_arr[$fail['reason']][]=$fail['user_id'];
    }
    $obj_autocallingccuser->cronRedisLog('gold_fail', 600, $fail_log);
    echo json_encode($fail_reason_arr);
}
