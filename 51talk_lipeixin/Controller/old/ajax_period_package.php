<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/27
 * Time: 13:34
 */
require_once '../../init.php';
//print_r($_POST);

$op = Http::post('op');
$stu_id = Http::post('stu_id');
$na_type = Http::post('na_type');
$reason = Http::post('reason');
$remark = Http::post('remark');

$model_adminstupoint = load::loadModel('AdminStuPoint');
$result = 0;
$before = '';
$after = '';
$do_type = '';

//记录操作日志
$point_change_log = array();
$point_change_log['user_id'] = $stu_id;
$point_change_log['account_type'] = $na_type;
$point_change_log['remark'] = $reason.'=>'.$remark;
$point_change_log['operator'] = Http::session('admin_user_name');
$point_change_log['add_time'] = date('Y-m-d H:i:s', time());
switch($op){
    case 1:
        //添加课量
        $do_type = $na_type.'_add';
        $point_change_log['do_type'] = $do_type;
        $stu_point_id = Http::post('stu_point_id');
        $period_class_time = Http::post('period_class_time');
        if($stu_point_id) {
            //$stu_point = $data_stupoint->getStuPointRow('id=' . $stu_point_id, 'content,order_id,valid_end');
            $stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointById($stu_point_id,'content,order_id,valid_end');
            $data = array();
            $data['content'] = intval($stu_point['content']) + intval($period_class_time);
            $stuPointLog = 	['stu_id' => $stu_id,
                'log_type' => $do_type,
                'operat_num' => $period_class_time,
                'prev_point' => $stu_point['content'],
                'valid_end' => $stu_point['valid_end'],
                'operat_time' => date('Y-m-d H:i:s', time()),
                'admin_user' =>Http::session("admin_user_id"),
                'current_point'=>$data['content']
            ];
            //获取北美课时数定义
            if(!empty($stu_point['order_id'])) {
                $model_userorder = Load::loadModel('UserOrder');
                $model_apiproduct = Load::loadModel('ApiProduct');
                $item_arr = array('na_pri'=>'item_list','na_open'=>'gift_list');
                $user_order = $model_userorder->getUserOrderById($stu_point['order_id'], 'extend_id');
                $product = $model_apiproduct->getProductDetail($user_order['extend_id']);
                $point_change_log['content'] = $product[$item_arr[$data['account_type']]][$data['account_type']]['num'];
            }
            $point_change_log['oricontent'] = $stu_point['content'];
            $point_change_log['lastcontent'] = $data['content'];
            $result = $model_adminstupoint->updateStuPointById($stu_point_id, $data, $do_type, '', $stuPointLog, $point_change_log);
        }else{
            $result = Load::loadModel('WebStuPoint')->stuPointNAInsert($stu_id, 1, $period_class_time, $na_type);
            if($na_type == 'na_pri'){
                $na_another_type = 'na_open';
            }else{
                $na_another_type = 'na_pri';
            }
            $result = Load::loadModel('WebStuPoint')->stuPointNAInsert($stu_id, 1, 0, $na_another_type);
            $point_change_log['oricontent'] = 0;
            $point_change_log['lastcontent'] = $period_class_time;
            (new \Logic\Comm\PointChangeLog())->addPointChangeLog($point_change_log);
        }
        break;

    case 2:
        //延长期限
        $do_type = $na_type.'_extend';
        $stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointListByUidValidStartTime($stu_id, false ,false, 'na_pri,na_open', false, "id,valid_end,order_id", 'id asc');
        $stu_point_ids = array_column($stu_point,'id');
        $point_change_log['do_type'] = $do_type;
        $point_change_log['oricontent'] = $stu_point[0]['valid_end'];
        $point_change_log['lastcontent'] = Http::post('time_limit');
        foreach($stu_point_ids as $id){
            $data = array();
            $data['valid_end'] = Http::post('time_limit');
            $model_adminstupoint->updateStuPointById($id,$data, $do_type, '', [], $point_change_log);
            $result++;
        }

        break;
    case 4:
        //减少课量
        $do_type = $na_type.'_reduce';
        $point_change_log['do_type'] = $do_type;
        $stu_point_id = Http::post('stu_point_id');
        $period_class_time = Http::post('period_class_time');
        //$stu_point = $data_stupoint->getStuPointRow('id='.$stu_point_id,'content,order_id,valid_end');
        $stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointById($stu_point_id,'content,order_id,valid_end');
        $data = array();
        $data['content'] = intval($stu_point['content']) - $period_class_time;
        $stuPointLog = 	['stu_id' => $stu_id,
            'log_type' => $do_type,
            'operat_num' => -$period_class_time,
            'prev_point' => $stu_point['content'],
            'valid_end' => $stu_point['valid_end'],
            'operat_time' => date('Y-m-d H:i:s', time()),
            'admin_user' =>Http::session("admin_user_id"),
            'current_point'=>$data['content']
        ];
        //获取北美课时数定义
        if(!empty($stu_point['order_id'])) {
            $model_userorder = Load::loadModel('UserOrder');
            $model_apiproduct = Load::loadModel('ApiProduct');
            $item_arr = array('na_pri'=>'item_list','na_open'=>'gift_list');
            $user_order = $model_userorder->getUserOrderById($stu_point['order_id'], 'extend_id');
            $product = $model_apiproduct->getProductDetail($user_order['extend_id']);
            $point_change_log['content'] = $product[$item_arr[$data['account_type']]][$data['account_type']]['num'];
        }
        $point_change_log['oricontent'] = $stu_point['content'];
        $point_change_log['lastcontent'] = $data['content'];
        $result = $model_adminstupoint->updateStuPointById($stu_point_id,$data, $do_type, '', $stuPointLog, $point_change_log);
        break;

    case 5:
        //补齐购买时缺少的记录
        $result = Load::loadModel('WebStuPoint')->stuPointNAInsert($stu_id, 1, 0, $na_type);
        break;

    default:
        break;
}
if (!$result) {
    ExceptionMonitor::critical(140103, '管理员操作课时失败');
}
echo $result?1:'fail';