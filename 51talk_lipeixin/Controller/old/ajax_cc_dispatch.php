<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/10/27
 * Time: 14:36
 */
include "../../init.php";

$sid_str = Http::post('sid');
$custom_id = Http::post('comstorid');
$admin_id = Http::session("admin_user_id");

$CcUserCycle = Load::loadData("CcUserCycle");
$SmallTreasuriesLog = Load::loadData("SmallTreasuriesLog");
$CcHoldTimeRule     = Load::loadModel("CcHoldTimeRule");
$obj_dispatchcc = new DispatchCc();

$buy_dispatch_cc = Load::loadModel('AuthExt')->checkUpdata('buy_dispatch_cc');

$sid_arr = explode(',',$sid_str);
$sid_arr = array_unique($sid_arr);
$stu_list = (new \Logic\Comm\User())->getUserListByUids($sid_arr, "id,user_name,mobile,invite_costomer,custom_id,is_buy");

$result = array();
$result['un_diff'] = array();
$result['is_buy'] = array();
$result['small'] = array();
$result['success'] = array();
$result['failed '] = array();
foreach($stu_list as $stu){
    //判断是否未修改
    if($stu['custom_id'] == $custom_id){
        $result['un_diff'][] = $stu['id'];
        continue;
    }
    //判断是否已付费
    if($stu['is_buy'] == 'buy' && false == $buy_dispatch_cc){
        $result['is_buy'][] = $stu['id'];
        continue;
    }
    //小金库 如果在小金库中 那么 先判断 该cc 是不是在
    $is_inSt = (new \Logic\Comm\CcUserCycle())->getCcUserCycleCountByWhere("user_id = ".$stu['id'] . " and status = 3");//判断该学员是否在小金库中  and status = 3
    if($is_inSt){
        //假如说在小金库中则需要更新 status状态 如果在小金库中那么 判断 该被分配的cc当前小金库的数量
        $nums = (new \Logic\Comm\CcUserCycle())->getCcUserCycleCountByWhere("admin_id = '$custom_id' and status = 3 ");
        $ruleData = $CcHoldTimeRule->getCcHoldTimeRule("st_hold_num");
        $nums = $nums ? $nums :0;
        $ups  = $ruleData['st_hold_num'] ? $ruleData['st_hold_num'] : 0;
        if($nums >= $ups){  //超过上限
            $result['small'][] = $stu['id'];
            continue;
        }else{
            $smdata_log = array();
            $smdata_log['user_id']    = $stu['id'];
            $smdata_log['admin_id']   = $custom_id;
            $smdata_log['add_time']   = time();
            $smdata_log['type']       = 2;
            $smdata_log['oprator_id'] =$admin_id;
            $SmallTreasuriesLog->addRowsToLog($smdata_log);
        }
    }
    //判断小金库结束

    //改派CC
    $dispatch_res = $obj_dispatchcc->changeCc($stu['id'], $custom_id, $admin_id, 'cc');
    if($dispatch_res){
        $result['success'][] = $stu['id'];
        (new \Logic\Comm\User())->clearUserCacheByUid($stu['id']);


        $dataUrl = "http://recommend.51talk.com/leads_cutoff/diviner?t=djy.000003&leads={$sid}&ccid={$comstorid}";
        $curl = new Curl();
        $curl->setUseCache(false);
        $dataResult = json_decode($curl->cacheRequest($dataUrl),true);
        if(isset($dataResult["url"])){
            $curl->cacheRequest($dataResult["url"]);
        }

    }else{
        $result['failed '][] = $stu['id'];
    }
}
if(empty($result['success'])){
    echo '修改失败！';
    if(!empty($result['is_buy'])){
        echo implode(',',$result['is_buy']).'是付费学员！';
    }
}else{
    //通知黑鸟
    $param = array();
    $param['admin_id'] = $custom_id;
    $user_info = array();
    foreach($stu_list as $stu){
        $user_info[] = array('user_id'=>$stu['id'],'user_name'=>$stu['user_name'],'user_mobile'=>$stu['mobile']);
    }
    $param['user_info'] = $user_info;
    $param = json_encode($param);
    oauthCurl('http://blackbird.51talk.com/Api/sendRemindWhenNewLeads',$param);
    echo '修改成功！';
}