<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/18
 * Time: 14:42
 */
require_once '../../init.php';

$stu_id =  Http::post('stu_id');
$class_ids = Http::post('muti_stu_class');
$muti_stu_class_name = Http::post('muti_stu_class_name');
$remark = Http::post('muti_stu_class_modify_remark');
$remain = Http::post('muti_stu_class_remain');
$operator = Http::session('admin_user_name');
$java_api = load::loadConfig('javaApi');
//退费判断 [添加挽单 @szt/2017-08-09]
$is_refund = Load::loadModel('RefundApply')->getOnRefundByStuId($stu_id,'0,1,2,3,4,6,8');
if ($is_refund=='y') {
    echo '该学员目前有财务处理中的退费订单！此功能暂不可用！';
    exit;
}
//接口参数
$param = array();
$param['stu_id'] = $stu_id;
$param['class_ids'] = $class_ids;

//调用接口
$del_url = $java_api['api_url'].'talkplatform_appoint_consumer/course_class/refund';
$del_result = oauthCurl($del_url,$param,'json');
if($del_result['code'] != $java_api['success_code']){
    echo '调用接口：'.$del_url.'失败！方法：POST，参数：';
    print_r($param);
    echo '返回结果：';
    print_r($del_result);
    exit;
}

//前台要求（方学正）
$class_order = (new \Logic\Comm\Order\Order())->getUserOrderByStuId($stu_id, '', 'success',array('class','multi','pgmulti','na_dls','multi_class','apollo_class'));
$class_order = $class_order[0];
if(!$class_order) {
    RedisModel::delRedisCache('user_class', array($stu_id));
}

//调用接口通知AC取消约课
$lession_url = $java_api['api_url'].'talkplatform_appoint_consumer/course_class/query_student_timetable_end?is_all=1&course_type=20&stu_id='.$stu_id;
$lession_result = oauthCurl($lession_url, '', 'json');
if(isset($lession_result['res']) && !empty($lession_result['res'])) {
    foreach ($lession_result['res'] as $lession) {
        if($lession['class_id']!=$class_ids){
            continue;
        }
        $start_time = strtotime($lession['start_time']);
        AcNotice::new_ac_notice(9, 0, $stu_id, 0, 0, 9, $lession['lesson_id'], '', (int) $start_time , 0);
        $teacher_type =  ( $lession['tea_type'] == 1)?4:1;
        AcNotice::new_ac_notice(9, 0, 0, $teacher_type, $lession['tea_id'], 9, $lession['lesson_id'], '', (int) $start_time, 0);
    }
}

//班课流水记录（操作日志）
$data = array();
$data['user_id'] = $stu_id;
$data['account_type'] = 'class';
$data['do_type'] = 'reset';
$data['remark'] = $muti_stu_class_name.'=>'.$remark;
$data['oricontent'] = $remain;
$data['lastcontent'] = 0;
$data['operator'] = $operator;
$data['add_time'] = date('Y-m-d H:i:s', time());
echo (new \Logic\Comm\StuPoint\StuPoint())->addPointChangeLog($data);
