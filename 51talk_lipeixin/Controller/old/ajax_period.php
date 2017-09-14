<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/6/13
 * Time: 15:34
 */
//课时修改
require_once '../../init.php';

$op = Http::post('op');
$stu_id = Http::post('stu_id');
$remark = Http::post('remark');
$stu_point_id = Http::post('stu_point_id');
$content = Http::post('content');
$reason = Http::post('reason');
$before = Http::post('before');
$after = Http::post('after');

$admin_user_name = Http::session('admin_user_name');
$admin_user_id = Http::session('admin_user_id', 0);
$model_adminstupoint = load::loadModel('AdminStuPoint');

//退费判断
$is_refund = Load::loadModel('RefundApply')->getOnRefundByStuId($stu_id);
if ($is_refund=='y') {
    echo '该学员目前有财务处理中的退费订单！此功能暂不可用！';
    exit;
}

$do_type = '';
$data = array();
$stuPointLog = array();
$point_info = Load::loadModel('StuPoint')->getPointInfoByStuId($stu_id, '*', 'classtime');
switch($op) {
    case 1:
        //添加课量
        $do_type = 'admin_classtime_add_point';
        $data['content'] = $content;
        $stuPointLog = 	[
            'stu_id' => $stu_id,
            'log_type' => $do_type,
            'prev_point' => $point_info['content'],
            'current_point' => $content,
            'valid_end' => $point_info['valid_end'],
            'operat_time' => date('Y-m-d H:i:s', time()),
            'admin_user' => Http::session("admin_user_id"),
        ];
        $stuPointLog['operat_num'] = $content-$point_info['content'];
        break;
    case 2:
        //减少课量
        $do_type = 'admin_classtime_reduce_point';
        $data['content'] = $content;
        $stuPointLog = 	[
            'stu_id' => $stu_id,
            'log_type' => $do_type,
            'prev_point' => $point_info['content'],
            'current_point' => $content,
            'valid_end' => $point_info['valid_end'],
            'operat_time' => date('Y-m-d H:i:s', time()),
            'admin_user' => Http::session("admin_user_id"),
        ];
        $stuPointLog['operat_num'] = $content-$point_info['content'];
        break;
    case 3:
        //延长期限
        $do_type = 'admin_classtime_extend_point';
        $data['valid_end'] = $content;
        break;
    default:
        break;
}

//修改日志
$point_change_log = array();
$point_change_log['user_id'] = $stu_id;
$point_change_log['account_type'] = 'classtime';
$point_change_log['do_type'] = $do_type;
$point_change_log['remark'] = $reason.'=>'.$remark;
$point_change_log['oricontent'] = $before;
$point_change_log['lastcontent'] = $after;
$point_change_log['operator'] = $admin_user_name;
$point_change_log['add_time'] = date('Y-m-d H:i:s', time());

if ($op == 3) {
    //走非包月延期
    $days                      = round((strtotime($content) - strtotime($point_info["valid_end"])) / 86400);
    $stupoint_arr['id']        = $point_info['id'];
    $stupoint_arr['valid_end'] = $content;
    $result = (new \Logic\Service\HolidayService)->delayAsset($stu_id, $point_info['type'], $days, $_SESSION['admin_user_id'], $_SESSION['admin_user_name'], $stupoint_arr, $stuPointLog, $point_change_log);
} elseif ($op == 2) {
    $count = $content - $point_info['content'];
    $operator_id = Http::session('admin_user_id', 0);
    $stupoint_arr['content'] = $content;
    $res = (new \Logic\Service\AssetService())->reduceAssetsCount($stu_id, $point_info['type'], $count, $operator_id, $stupoint_arr, $stuPointLog, $point_change_log);
    $result = $res['code'] == 10000 ? true : false;
} elseif (isset($data['content']) && ($point_info['type'] != 'month') && ($data['content'] > $point_info['content'])) {
    $inc_content      = $data['content'] - $point_info['content'];
    $stu_point_result = (new \Logic\Service\AssetService())->addGiveAsset([
        'stu_id'        => $stu_id,
        'sku_type_name' => $point_info['type'],
        'count'         => $inc_content,
        'days'          => 0,
        'remark'        => $reason . '=>' . $remark,
        'operator_id'   => $admin_user_id
    ], $data, $stuPointLog, $point_change_log);
    $result           = $stu_point_result['success'];
} else {
    $result = $model_adminstupoint->updateStuPointById($stu_point_id, $data, $do_type, '', $stuPointLog, $point_change_log);
}

if (!$result) {
    ExceptionMonitor::critical(140103, '管理员操作课时失败');
}
$result?$result=1:'';
echo $result;
