<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/6/13
 * Time: 10:41
 */
require_once '../../init.php';
$stu_id = Http::get('stu_id');
$blank = Http::get('blank');//新窗口打开

//判断是否有权限
$admin_user_id = Http::session('admin_user_id');
$oauth = Load::loadModel('AuthExt')->checkUpdata('class_time_modify');
if($oauth == false){
    $modify_right = 0;
}else{
    $modify_right = 1;
}

$stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointListByUidValidStartTime($stu_id, false ,false, 'classtime', false, "id,content,type,valid_end", 'id asc');
//var_dump($stu_point);
if(!$stu_point){
    exit;
}

//可预约总量
$period_remain = array_column($stu_point,'content');
$period_remain = array_sum($period_remain);
//var_dump($na_total);

//获得最大有效期
$valid_end = array_column($stu_point,'valid_end');
$valid_end = array_map('strtotime',$valid_end);
$valid_end = max($valid_end);
$valid_end = date('Y-m-d',$valid_end);

$add_reason = array('老师缺席赔偿','老师迟到补偿','老师态度不好补偿','老师背景嘈杂影响上课补偿','换老师赔偿','网络连接影响上课补偿','课程不满意赔偿','服务不满意补偿','其他');

$TPL->assign('add_reason', $add_reason);
$TPL->assign('stu_id', $stu_id);
$TPL->assign('blank', $blank);
$TPL->assign('stu_point_id', $stu_point[0]['id']);
$TPL->assign('period_remain', $period_remain);
$TPL->assign('valid_end', $valid_end);
$TPL->assign('modify_right', $modify_right);
$TPL->assign('is_refund',Http::get('is_refund'));
$TPL->display("StyleDefault/admin/user/period_modify.html");