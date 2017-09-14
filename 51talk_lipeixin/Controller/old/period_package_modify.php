<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/26
 * Time: 18:34
 */
require_once '../../init.php';
$stu_id = Http::get('stu_id');
$blank = Http::get('blank');//新窗口打开

$admin_user_id = Http::session('admin_user_id');

$add_reason = array('老师缺席赔偿','老师迟到补偿','老师态度不好补偿','老师背景嘈杂影响上课补偿','换老师赔偿','网络连接影响上课补偿','课程不满意赔偿','服务不满意补偿','其他');

//判断是否有权限
$modify_right = 1;
$oauth = Load::loadModel('Auth')->checkUpdata('na_period_modify');
if(!$oauth){
    $modify_right = 0;
}

//获取北美当前课时数
$stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointListByUidValidStartTime($stu_id, false ,false, 'na_pri,na_open', false, "id,content,type,valid_end,order_id", 'id asc');

$lack_type ='';//购买时缺少的记录类型

if($stu_point) {
    //获取北美课时数定义
    $model_userorder = Load::loadModel('UserOrder');
    $model_apiproduct = Load::loadModel('ApiProduct');
    $item_arr = array('na_pri' => 'item_list', 'na_open' => 'gift_list');
    foreach ($stu_point as $key => $point) {
        $user_order = $model_userorder->getUserOrderById($point['order_id'], 'extend_id');
        $product = $model_apiproduct->getProductDetail($user_order['extend_id']);
        $stu_point[$key]['class_time'] = $product[$item_arr[$point['type']]][$point['type']]['num'];
    }

    //获得最大有效期
    $valid_end = array_column($stu_point,'valid_end');
    $valid_end = array_map('strtotime',$valid_end);
    $valid_end = max($valid_end);
    $valid_end = date('Y-m-d',$valid_end);

    //购买时缺少的记录类型
    if(count($stu_point)==1){
        if($stu_point[0]['type']=='na_pri'){
            $lack_type = 'na_open';
        }else{
            $lack_type = 'na_pri';
        }
    }
}else{
    $valid_end = date('Y-m-d',strtotime('tomorrow'));
}

$TPL->assign('stu_id', $stu_id);
$TPL->assign('blank', $blank);
$TPL->assign('add_reason', $add_reason);
$TPL->assign('modify_right', $modify_right);
$TPL->assign('stu_point', $stu_point);
$TPL->assign('valid_end', $valid_end);
$TPL->assign('lack_type', $lack_type);
$TPL->display("StyleDefault/admin/user/period_package_modify.html");