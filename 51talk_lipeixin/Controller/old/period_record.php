<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/27
 * Time: 12:11
 */
require_once '../../init.php';

$stu_id = Http::get('stu_id');

$do_type = array('add'=>'添加课时','reduce'=>'减少课时','extend'=>'延长期限');

$point_change_log = Load::loadData('PointChangeLog')->getPointChangeLogList('user_id='.$stu_id.' and account_type in ("classtime")','*','add_time desc');
if(!empty($point_change_log)){
    foreach($point_change_log as $key=>$log){
        $point_change_log[$key]['do_type'] = $do_type[$log['do_type']];
    }
}

$TPL->assign('point_change_log', $point_change_log);
$TPL->display("StyleDefault/admin/user/period_record.html");