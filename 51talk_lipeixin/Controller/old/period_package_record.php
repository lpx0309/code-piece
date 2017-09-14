<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/27
 * Time: 12:11
 */
require_once '../../init.php';

$stu_id = Http::get('stu_id');

$content = array('na_pri'=>'北美外教一对一课程','na_open'=>'精品绘本阅读课');

$period_package_record = array();
$point_change_log = Load::loadData('PointChangeLog')->getPointChangeLogList('user_id='.$stu_id.' and account_type in ("na_pri","na_open")','*','add_time desc');
if(!empty($point_change_log)){
    foreach($point_change_log as $log){
        $data = array();
        $data['do_type'] = $log['do_type'];
        if($log['do_type'] == 'extend') {
            $data['content'] = '套餐延期';
        }else{
            $data['content'] = $log['content'].'课时'.$content[$log['account_type']];
        }
        $data['reason'] = $log['remark'];
        $data['before_modify'] = $log['oricontent'];
        $data['after_modify'] = $log['lastcontent'];
        $data['admin_name'] = $log['operator'];
        $data['add_time'] = $log['add_time'];
        $period_package_record[] = $data;
    }
}
$log_add = ['add','admin_add_point','appoint_add_point','month_to_point','admin_b2b_add','admin_classtime_add','admin_classtime_add','na_pri_add','na_open_add'];
$log_reduce = ['reduce','admin_reduce_point','appoint_reduce_point','tuikuan_reduce_point','admin_classtime_reduce','tuikuan_reduce_month','admin_classtime_reduce','na_pri_reduce','na_open_reduce'];
$log_extend = ['extend','admin_extend_point','admin_classtime_extend','extend_user_buy','admin_classtime_extend'];
$log_reset = ['reset','admin_month_reset'];
$add_phone = ['add_phone','admin_add_phone'];
$red_phone = ['red_phone','admin_red_phone'];
$add_month = ['admin_month_add'];
$TPL->assign("log_add",$log_add);
$TPL->assign("log_reduce",$log_reduce);
$TPL->assign("log_extend",$log_extend);
$TPL->assign("log_reset",$log_reset);
$TPL->assign("add_phone",$add_phone);
$TPL->assign("red_phone",$red_phone);
$TPL->assign("add_month",$add_month);
$TPL->assign('period_package_record', $period_package_record);
$TPL->display("StyleDefault/admin/user/period_package_record.html");