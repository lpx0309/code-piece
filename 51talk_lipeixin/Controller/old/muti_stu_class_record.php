<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/18
 * Time: 14:46
 */
require_once '../../init.php';

$stu_id = Http::get('stu_id');

//$muti_stu_class_record = load::loadData('MutiStuClassModifyLog')->getLog('stu_id='.$stu_id);

$muti_stu_class_record = array();
$point_change_log = Load::loadData('PointChangeLog')->getPointChangeLogList('user_id='.$stu_id.' and account_type in ("class")','*','add_time desc');
if(!empty($point_change_log)){
    foreach($point_change_log as $log){
        $data = array();
        $remark = explode('=>',$log['remark']);
        $data['class_name'] = $remark[0];
        $data['type'] = 2;
        $data['reason'] = $remark[1];
        $data['before_lession_num'] = $log['oricontent'];
        $data['after_lession_num'] = $log['lastcontent'];
        $data['admin_name'] = $log['operator'];
        $data['add_time'] = $log['add_time'];
        $muti_stu_class_record[] = $data;
    }
}
//var_dump($muti_stu_class_record);

$TPL->assign('muti_stu_class_record', $muti_stu_class_record);
$TPL->display("StyleDefault/admin/user/muti_stu_class_record.html");