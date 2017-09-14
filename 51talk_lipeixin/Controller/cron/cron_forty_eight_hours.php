<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/9/8
 * Time: 11:55
 * 48小时关单统计
 */
require_once(dirname(dirname(__FILE__)) . "/init.php");

$redis = new RedisCrm();
$cron_log_key = 'forty_eight_hours_cron_log';
$cron_log_len = 600;
$default_start_time = date('Y-m-d',strtotime('today -3 day'));
$default_end_time = date('Y-m-d',strtotime('yesterday'));

$start = microtime(true);
$start_time = Http::get('start_time');
if(!$start_time){
    $start_time = $default_start_time;
}
$end_time = Http::get('end_time');
if(!$end_time){
    $end_time = $default_end_time;
}
//获取时间段内所有日期
$days = array($start_time);
$day = strtotime($start_time);
while($day < strtotime($end_time)){
    $day += 24*3600;
    $days[] = date('Y-m-d',$day);
}

$model_adminuser = Load::loadModel('AdminUser');
$model_user = Load::loadModel('User');
$model_appoint = Load::loadModel('Appoint');
$model_userorder = Load::loadModel('UserOrder');
$model_usercalldetail = Load::loadModel('UserCallDetail');
$model_fortyeighthours = Load::loadModel('FortyEightHours');
$result = array();

//获取该时间段内所有正常结束的体验课
$free_appoint_list = $model_appoint->getUserCourse('use_point="free" and status="end" and (date between "' . $start_time . '" and "' . $end_time . '")', 's_id,end_time');
$free_appoint_map = array();
foreach ($free_appoint_list as $free_appoint) {
    if (!isset($free_appoint_map[$free_appoint['s_id']])) {
        $free_appoint_map[$free_appoint['s_id']] = $free_appoint['end_time'];
    }
}

//获取该时间段和之后48小时所有付费的订单（取出所有，循环内通过键值筛选，提升速度）
$max_end_time = date('Y-m-d', strtotime($end_time . '+2 day'));//所选时间段后48小时（2天）
$order_list = $model_userorder->getUserOrderList('(status="success" or status="refund") and (deal_time between "' . $start_time . '" and "' . $max_end_time . '")', 'stu_id,deal_time,custom_id');
$order_map = array();
foreach ($order_list as $order) {
    if (!isset($order_map[$order['stu_id']])) {
        $order_map[$order['stu_id']] = array();
    }
    $order_map[$order['stu_id']][] = $order;
}

//获取所有CC组
$cc_group = load::loadConfig('cc_group', 'cc_group');
foreach($cc_group as $old_name){
    $old_name = explode("'",$old_name)[1];
    $admin_user = $model_adminuser->getDataByGroupId($old_name, 'on', 'id');//获取组内所有CC的ID
    if (!$admin_user) {
        continue;
    }
    $admin_user = array_column($admin_user, 'id');
    $admin_user = implode(',', $admin_user);
    $where = array('custom_id_in' => $admin_user);
    $stu_list = $model_user->getUserByWhere($where, 'id,occup,custom_id');//获取组下所有学员
    if(!$stu_list){
        continue;
    }
    foreach($days as $day){
        $data = array();
        foreach(array(0,1) as $age_type){
            $data[$age_type]['free_trial_end'] = 0;
            $data[$age_type]['forty_eight_pay'] = 0;
            $data[$age_type]['forty_eight_call'] = 0;
        }
        foreach ($stu_list as $stu) {
            $s_id = $stu['id'];
            if(in_array($stu['occup'],array(3,4,6,7))){
                $age_type = 1;//青少
            }else{
                $age_type = 0;//成人
            }
            //体验课完成人数（通过键值筛选）
            if (isset($free_appoint_map[$s_id]) && date('Y-m-d',strtotime($free_appoint_map[$s_id])) == $day) {
                $first = $model_appoint->getDataByWhere('s_id=' . $s_id . ' and use_point="free" and status="end"', 'end_time')['end_time'];//是否首次
                if ($first == $free_appoint_map[$s_id]) {
                    $data[$age_type]['free_trial_end']++;
                }else{
                    continue;
                }
            }else{
                continue;
            }
            //48小时内付费人数（通过键值筛选）
            $free_end_time = strtotime($free_appoint_map[$s_id]);
            $twoday_ago = strtotime($free_appoint_map[$s_id] . '+2 day');//体验课结束后48小时（2天）
            if (isset($order_map[$s_id])) {
                foreach ($order_map[$s_id] as $order) {
                    $deal_time = strtotime($order['deal_time']);
                    if ($deal_time >= $free_end_time && $deal_time <= $twoday_ago  && $order['custom_id'] == $stu['custom_id']) {
                        $data[$age_type]['forty_eight_pay']++;
                        break;
                    }
                }
            }
            //48小时内外呼数（单个学员查数据库）
            $twoday_ago = date('Y-m-d H:i:s', $twoday_ago);
            $call = $model_usercalldetail->getAllCllDetail('u_id= '.$stu['custom_id'].' and user_id=' . $s_id . ' and (start_time between "' . $free_appoint_map[$s_id] . '" and "' . $twoday_ago . '")', 'id');
            if ($call) {
                $data[$age_type]['forty_eight_call']++;
            }
        }
        foreach($data as $age_type=>$feh){
            $forty_eight_hours = array('cc_group'=>$old_name,'date'=>$day,'age_type'=>$age_type,'free_trial_end'=>$feh['free_trial_end'],'forty_eight_pay'=>$feh['forty_eight_pay'],'forty_eight_call'=>$feh['forty_eight_call']);
            $result[] = $model_fortyeighthours->modifyRowByWhere('cc_group="'.$old_name.'" and date="'.$day.'" and age_type='.$age_type,$forty_eight_hours);
        }
    }
}
$end = microtime(true);
//var_dump($result);
$msg = '数据起始时间：'.$start_time.'，结束时间：'.$end_time.'；更新数据'.count($result).'条；程序运行时长：'.round($end-$start,2).'秒；程序运行时间'.date('Y-m-d H:i:s',$start);//运行结果
//写入运行结果日志
$llen = $redis->llen($cron_log_key);
if($llen > $cron_log_len){
    $redis->lpop($cron_log_key);
}
$redis->rpush($cron_log_key,$msg);
echo $msg;