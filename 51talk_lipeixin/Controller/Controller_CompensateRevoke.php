<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/6/27
 * Time: 10:15
 */
class Controller_CompensateRevoke extends MasterController
{

    public function __construct()
    {
        parent::__construct();
    }

    //返回值处理
    private function apiRes($res,$code=10000){
        $result = array();
        $result['code'] = $code;
        if(is_array($res)){
            $result['res'] = $res;
        }else{
            $result['message'] = $res;
        }
        $data_json = json_encode($result);
        $callback = Http::request('callback');
        if($callback) {
            echo $callback . '(' . $data_json . ')';
        }else{
            echo $data_json;
        }
        exit;
    }

    //获取补偿原因
    public function getCompensateReason(){
        $changeReason = array(	'体验课未成功补偿',
            '老师缺席赔偿',
            '老师迟到补偿',
            '老师早退补偿',
            '老师态度不好补偿',
            '老师背景嘈杂影响上课补偿',
            '缺席严重投诉',
            '网络连接影响上课补偿',
            '课程不满意赔偿',
            '服务不满意补偿',
            '参加优惠活动',
            '邀请好友',
            '销售促单赠送',
            '当天体验当天付费',
            '包月转成次卡',
            '51Service专用',
            '其他',
            '特殊操作');
        if($_SESSION['admin_group_id'] == 'root'){
            $changeReason[] = '次卡转成包月';
        }
        $this->apiRes($changeReason);
    }

    //补偿撤销按钮初始化
    public function compensateRevokeInit(){
        $app_ids = Http::request('app_ids');
        $app_ids = explode(',',$app_ids);
        //$PointChangeLog = (new \Logic\Comm\PointChangeLog());
        $PointChangeLog = Load::loadData('PointChangeLog');
        $Appoint = new Logic\Comm\Appoint();
        $compensate_revoke = array();
        $cr_right = Load::loadModel('AuthExt')->checkUpdata('compensate_revoke');
        if(!$cr_right) {
            $this->apiRes(array('没有权限'), '11000');
        }
        foreach ($app_ids as $app_id){
            $app_info = $Appoint->getAppointById($app_id);
            if(!in_array($app_info['point_type'],array('point','month','na_pri')) || $app_info['use_point']=='free'){
                continue;
            }
            //$point_change_log = $PointChangeLog->getPointChangeLogByAppId($app_id);
            /*$point_change_log = $PointChangeLog->getPointChangeLogCount('app_id='.$app_id);
            if($point_change_log > 0){
                if($point_change_log == 1){
                    $compensate_revoke[$app_id] = 'revoke';
                }else{
                    continue;
                }
            }else{
                $compensate_revoke[$app_id] = 'compensate';
            }*/
            $point_change_log = $PointChangeLog->getPointChangeLogList('app_id='.$app_id,'do_type','id desc');
            if($point_change_log[0]['do_type'] == 'admin_add_point'){
                $compensate_revoke[$app_id] = 'revoke';
            }else{
                $compensate_revoke[$app_id] = 'compensate';
            }
        }
        $this->apiRes($compensate_revoke);
    }

    //补偿
    public function compensate(){
        $app_id = Http::request('app_id');
        $reason = Http::request('reason');
        $point_type_new = Http::request('point_type_new');
        $do_type = 'admin_add_point';

        $PointChangeLog = Load::loadData('PointChangeLog');
        $point_change_log = $PointChangeLog->getPointChangeLogList('app_id='.$app_id,'do_type','id desc');
        if($point_change_log[0]['do_type'] == 'admin_add_point'){
            $this->apiRes('该约课已经补偿过了！',10001);
        }
        $app_info = (new Logic\Comm\Appoint())->getAppointById($app_id);
        if($point_type_new != 'om_one_class' && !in_array($app_info['point_type'],array('point','month','na_pri'))){
            $this->apiRes('不是次卡（包含欧美一对一），包月或课时包（美小）！',10002);
        }
        //执行点数修改
        $result = $this->stuPointModify($app_id,$reason,$point_type_new,$do_type,$app_info);
        if($result) {
            $this->apiRes('补偿成功！');
        }else{
            $this->apiRes('补偿失败！',10004);
        }
    }

    //撤销
    public function revoke(){
        $app_id = Http::request('app_id');
        $reason = '撤销';
        $point_type_new = Http::request('point_type_new');
        $do_type = 'admin_reduce_point';

        $PointChangeLog = Load::loadData('PointChangeLog');
        $point_change_log = $PointChangeLog->getPointChangeLogList('app_id='.$app_id,'do_type','id desc');
        if($point_change_log[0]['do_type'] == 'admin_reduce_point'){
            $this->apiRes('该约课已经撤销过了！',10003);
        }
        $app_info = (new Logic\Comm\Appoint())->getAppointById($app_id);
        if($point_type_new != 'om_one_class' && !in_array($app_info['point_type'],array('point','month','na_pri'))){
            $this->apiRes('不是次卡（包含欧美一对一），包月或课时包（美小）！',10002);
        }

        //执行点数修改
        $result = $this->stuPointModify($app_id,$reason,$point_type_new,$do_type,$app_info);
        if($result) {
            $this->apiRes('撤销成功！');
        }else{
            $this->apiRes('撤销失败！',10005);
        }
    }

    //封装点数修改
    private function stuPointModify($app_id,$reason,$point_type_new,$do_type,$app_info){
        $admin_user_id = Http::session("admin_user_id");
        $admin_user_name = Http::session("admin_user_name");
        $now = date('Y-m-d H:i:s', time());
        $orderService = new \Logic\Service\AssetService();
        if($point_type_new == 'om_one_class'){
            //欧美一对一
            $account_type = 'point';
            $add_point = 4;
            $add_day = 1;
        }else{
            $account_type = $app_info['point_type'];
            switch($app_info['point_type']){
                case 'point';
                    //次卡
                    $add_point = 1;
                    $add_day = 1;
                    break;
                case 'month';
                    //包月
                    $add_point = 0;
                    $add_day = 1;
                    break;
                case 'na_pri';
                    //课时包（美小）
                    $add_point = 0;
                    $add_day = 1;
                    break;
                default:
                    $add_point = 0;
                    $add_day = 0;
                    break;
            }
        }
        //如果是撤销则都是负的
        if($do_type == 'admin_reduce_point'){
            $add_point = -$add_point;
            $add_day = -$add_day;
        }
        //获得学员所有该类型点数记录
        $stu_point = (new \Logic\Comm\StuPoint\StuPoint())->getStuPointListByUidValidStartTime($app_info['s_id'], false ,false, $account_type, false, "id,stu_id,content,type,valid_end", 'id asc');
        if(!$stu_point){
            return false;
        }
        foreach ($stu_point as $sp){
            if($do_type == 'admin_reduce_point') {
                if($account_type != 'month') {
                    if ((int)$sp['content'] == 0) {
                        $this->apiRes('该学员暂无财富，无法撤销补偿！', 10006);
                    }
                }else {
                    if (strtotime($sp['valid_end']) < time()) {
                        $this->apiRes('该学员已经过期，无法撤销补偿！', 10007);
                    }
                }
            }
            $param = array();
            $param['content'] = strval($sp['content'] + $add_point);
            $param['valid_end'] = date('Y-m-d',strtotime($sp['valid_end'].' +'.$add_day.'day'));
            //生成日志
            if($account_type != 'month') {
                $stuPointLog = ['stu_id' => $sp['stu_id'],
                    'log_type' => $do_type,
                    'operat_num' => $add_point,
                    'prev_point' => $sp['content'],
                    'valid_end' => $sp['valid_end'],
                    'operat_time' => $now,
                    'admin_user' => $admin_user_id,
                    'current_point' => $param['content']
                ];
            }else{
                $stuPointLog = [];
            }
            $point_change_log = array();
            $point_change_log['user_id'] = $sp['stu_id'];
            $point_change_log['account_type'] = $account_type;
            $point_change_log['do_type'] = $do_type;
            $point_change_log['remark'] = $reason;
            $point_change_log['content'] = $add_point;
            if($account_type != 'month') {
                $point_change_log['oricontent'] = $sp['content'];
                $point_change_log['lastcontent'] = $param['content'];
            }else{
                $point_change_log['oricontent'] = $sp['valid_end'];
                $point_change_log['lastcontent'] = $param['valid_end'];
            }
            $point_change_log['operator'] = $admin_user_name;
            $point_change_log['add_time'] = $now;
            $point_change_log['course_start_time'] = $app_info['start_time'];
            $point_change_log['course_end_time'] = $app_info['end_time'];
            $point_change_log['app_id'] = $app_id;
            //执行修改
            if ($do_type == 'admin_reduce_point' && $account_type != 'month') {
                //执行减少财富操作
                $count = $add_point;
                $operator_id = Http::session('admin_user_id', 0);
                $stupoint_arr['content'] = $param['content'];
                $stupoint_arr['valid_end'] = $param['valid_end'];
                $res = (new \Logic\Service\AssetService())->reduceAssetsCount($sp['stu_id'], $sp['type'], $count, $operator_id, $stupoint_arr, $stuPointLog, $point_change_log);
            } else {
                $give_params = [
                    'stu_id'        => $sp['stu_id'],
                    'sku_type_name' => $sp['type'],
                    'count'         => $add_point,
                    'days'          => $add_day,
                    'remark'        => $reason,
                    'operator_id'   => $admin_user_id,
                ];
                $res = $orderService->addGiveAsset($give_params, $param, $stuPointLog, $point_change_log);
            }
            if(!$res['success']){
                return false;
            }
        }
        return true;
    }

}
