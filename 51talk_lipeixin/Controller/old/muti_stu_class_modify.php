<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/8
 * Time: 15:53
 */
require_once '../../init.php';

//判断当前用户是否为CR
/*$admin_group_id = Http::session('admin_group_id');
$day_group = Load::loadConfig('cc_group', 'day_group');
if (!in_array("'$admin_group_id'", $day_group)) {
    exit;
}*/

//判断是否有退费权限
$admin_user_id = Http::session('admin_user_id');
$authExtModel = Load::loadModel('AuthExt');
$oauth = $authExtModel->checkUpdata('class_refund');
if($oauth == false){
    $modify_right = 0;
}else{
    $modify_right = 1;
}

$stu_id = Http::get('stu_id');
$blank = Http::get('blank');//新窗口打开
$course_type = Http::get('course_type');
if(!$course_type){
    $course_type = 20;
}

$java_api = Load::loadConfig('javaApi');

$exchange_url = $java_api['api_url'].'talkplatform_product_consumer/goods/query_exchange_class_list?stu_id='.$stu_id.'&course_type='.$course_type.'&sortby=add_time&order=desc';
$class_url = $java_api['api_url'].'talkplatform_appoint_consumer/course_class/query_student_class_list_end?stu_id='.$stu_id.'&course_type='.$course_type;
$lession_url = $java_api['api_url'].'talkplatform_appoint_consumer/course_class/query_student_class_lesson_list_end?is_all=1&course_type='.$course_type.'&stu_id='.$stu_id.'&class_id=';

$class_ids = array();
//取学员换班信息，如果有换班则取最新的班级ID
$exchange_result = oauthCurl($exchange_url,'','json');
if(isset($exchange_result['res']) && !empty($exchange_result['res'])) {
    /*foreach ($exchange_result['res'] as $res) {
        $class_ids[] = $res['current_entity_id'];
    }*/
    $class_ids[] = $exchange_result['res'][0]['current_entity_id'];
}
//如果没换班则按原来的逻辑取班级ID
if(empty($class_ids)){
    $class_result = oauthCurl($class_url,'','json');
    if(isset($class_result['res']) && !empty($class_result['res'])) {
        foreach ($class_result['res']['list'] as $class) {
            $class_ids[] = $class['id'];
        }
    }
}
if(empty($class_ids)){
    exit;
}

$class_list = array();
foreach ($class_ids as $class_id) {
    $lession_url_add = $lession_url.$class_id;
    //var_dump($lession_url_add);
    $lession_result = oauthCurl($lession_url_add, '', 'json');
    if(isset($lession_result['res']) && !empty($lession_result['res'])) {
        $class = array();
        $class['id'] = $class_id;
        $class['total'] = $lession_result['total'];
        $class['remain'] = 0;
        foreach ($lession_result['res'] as $lession) {
            if(!in_array($lession['status'],array(7)) && time() < strtotime($lession['end_time'])) {
                $class['remain']++;
            }
            $class['name'] = $lession['class_name'];
        }
        $class_list[] = $class;
    }
}

//$add_reason = array('老师缺席赔偿','老师迟到补偿','老师态度不好补偿','老师背景嘈杂影响上课补偿','换老师赔偿','网络连接影响上课补偿','课程不满意赔偿','服务不满意补偿','其他');

$TPL->assign('stu_id', $stu_id);
$TPL->assign('blank', $blank);
$TPL->assign('course_type', $course_type);
//$TPL->assign('add_reason', $add_reason);
$TPL->assign('is_refund',Http::get('is_refund'));
$TPL->assign('class_list', $class_list);
$TPL->assign('modify_right', $modify_right);
$TPL->display("StyleDefault/admin/user/muti_stu_class_modify.html");