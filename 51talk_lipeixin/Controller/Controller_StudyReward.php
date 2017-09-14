<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/8/1
 * Time: 13:42
 */
class Controller_StudyReward extends MasterController
{
    public function __construct()
    {
        parent::__construct();
    }

    //活动列表页
    public function EventList(){
        $PageId = Http::get('PageID', 1);
        $PageSize = 20;

        $param = array();
        $param['event_name'] = Http::get('event_name');
        $param['start_date'] = Http::get('start_date');
        if(!$param['start_date']){
            $param['start_date'] = date('Y-m-1');
        }
        $param['end_date'] = Http::get('end_date');
        if(!$param['end_date']){
            $param['end_date'] = date('Y-m-d');
        }
        $param['start_time'] = $param['start_date'].' 00:00:00';
        $param['end_time'] = $param['end_date'].' 23:59:59';
        $param['user_type'] = Http::get('user_type');
        $param['limit'] = (($PageId-1)*$PageSize).','.$PageSize;

        $user_type = array(0=>'所有',1=>'成人',2=>'青少');
        $appoint_type = array(0=>'所有',1=>'菲教一对一',2=>'欧美一对一');
        $gift_type = array(1=>'课耗',2=>'财富');
        $pay_type = array(0=>'所有',1=>'首单',2=>'续费');

        $AdminUser = (new Logic\Comm\AdminUser());
        $event = Load::loadModel('StudyRewardEvent')->getEventList($param);
        $event_list = array();
        foreach ($event['res'] as $e){
            $e['event_name'] = str_replace($param['event_name'],'<font color="red">'.$param['event_name'].'</font>',$e['event_name']);
            $e['user_type'] = $user_type[$e['user_type']];
            $e['appoint_type'] = $appoint_type[$e['appoint_type']];
            $e['gift_type'] = $gift_type[$e['gift_type']];
            $e['pay_type'] = $pay_type[$e['pay_type']];
            if($e['status']==0){
                $e['status_word'] = '停止';
            }else{
                if(time() < strtotime($e['start_time'])){
                    $e['status_word'] = '未开始';
                }elseif(time() > strtotime($e['end_time'])){
                    $e['status_word'] = '已结束';
                }else{
                    $e['status_word'] = '进行中';
                }
            }
            $e['start_time'] = date('Y-m-d',strtotime($e['start_time']));
            $e['end_time'] = date('Y-m-d',strtotime($e['end_time']));
            $e['creater'] = $AdminUser->getAdminUserById($e['creater_id'])['user_name'];
            $e['updater'] = $AdminUser->getAdminUserById($e['updater_id'])['user_name'];
            $event_list[] = $e;
        }

        //分页
        $MyPage = new Page();
        $MyPage->initNew($event['total'], $PageId, $PageSize);
        $Page = $MyPage->Show();

        //var_dump($event_list);
        $this->assign('param',$param);
        $this->assign('user_type',$user_type);
        $this->assign('appoint_type',$appoint_type);
        $this->assign('gift_type',$gift_type);
        $this->assign('pay_type',$pay_type);
        $this->assign('event_list',$event_list);
        $this->assign('Page',$Page);
        $this->display('StudyReward/event_list.html');
    }

    public function getEvent(){
        $event_id = Http::get('event_id');
        $event = Load::loadModel('StudyRewardEvent')->getEventById($event_id);
        $event['start_date'] = date('Y-m-d',strtotime($event['start_time']));
        $event['end_date'] = date('Y-m-d',strtotime($event['end_time']));
        $event['rule'] = Load::loadModel('StudyRewardRule')->getRuleByEventId($event_id);
        echo json_encode($event);
    }

    //添加修改活动
    public function EventModify(){
        $event_id = Http::post('event_id');

        $data = array();
        $data['event_name'] = Http::post('event_name');
        $data['start_time'] = Http::post('start_date').' 00:00:00';
        $data['end_time'] = Http::post('end_date').' 23:59:59';
        if(strtotime($data['start_time']) >= strtotime($data['end_time'])){
            echo '活动开始时间必须小于结束时间！';
            exit;
        }
        if($event_id){
            if($this->isActive($event_id)){
                echo '活动启动中，请先停止活动！';
                exit;
            }
        }else{
            if(strtotime($data['start_time'])< time()){
                echo '活动开始时间必须大于当前时间！';
                exit;
            }
        }

        $data['cycle'] = Http::post('cycle');
        $data['user_type'] = Http::post('user_type');
        $data['appoint_type'] = Http::post('appoint_type');
        //$data['pay_type'] = Http::post('pay_type');
        $data['pay_type'] = 1;//目前只有首单
        $data['pay_min'] = Http::post('pay_min');
        $data['pay_max'] = Http::post('pay_max');
        $data['gift_type'] = Http::post('gift_type');

        $rule_id = Http::post('rule_id');
        $range_start = Http::post('range_start');
        $range_end = Http::post('range_end');
        $gift_point = Http::post('gift_point');

        if($event_id){
            $event = Load::loadModel('StudyRewardEvent')->getEventById($event_id);
            if($event['event_name'] == $data['event_name']){
                $is_judge = false;
            }else{
                $is_judge = true;
            }
            $data['updater_id'] = Http::session('admin_user_id');
            $data['update_time'] = date('Y-m-d H:i:s');
        }else{
            $is_judge = true;
            $data['creater_id'] = Http::session('admin_user_id');
            $data['create_time'] = date('Y-m-d H:i:s');
        }
        if($is_judge) {
            $has_name = Load::loadModel('StudyRewardEvent')->getEventByName($data['event_name']);
            if ($has_name) {
                echo '该活动名称已存在！';
                exit;
            }
        }
        if($event_id){
            //修改活动
            $res = Load::loadModel('StudyRewardEvent')->updateEventById($event_id,$data);
            //修改或添加活动规则
            if(!empty($gift_point)) {
                foreach ($gift_point as $k => $r) {
                    if(!$range_end[$k] || !$r){
                        if ($rule_id[$k]) {
                            Load::loadModel('StudyRewardRule')->deleteRuleById($rule_id[$k]);
                        }
                        continue;
                    }
                    $data = array();
                    $data['range_start'] = $range_start[$k];
                    $data['range_end'] = $range_end[$k];
                    $data['gift_point'] = $r;
                    if ($rule_id[$k]) {
                        Load::loadModel('StudyRewardRule')->updateRuleById($rule_id[$k], $data);//如果有规则ID则是修改
                    } else {
                        $data['event_id'] = $event_id;
                        Load::loadModel('StudyRewardRule')->addRule($data);//如果没有规则ID则是添加
                    }
                }
            }
        }else{
            //添加活动
            $res = Load::loadModel('StudyRewardEvent')->addEvent($data);
            //添加活动规则
            $event_id = $res;
            if(!empty($gift_point)) {
                foreach ($gift_point as $k => $r) {
                    if(!$range_end[$k] || !$r){
                        continue;
                    }
                    $data = array();
                    $data['event_id'] = $event_id;
                    $data['range_start'] = $range_start[$k];
                    $data['range_end'] = $range_end[$k];
                    $data['gift_point'] = $r;
                    Load::loadModel('StudyRewardRule')->addRule($data);
                }
            }
        }
        echo $res;
    }

    //删除活动
    public function EventDelete(){
        $event_id = Http::post('event_id');
        if($this->isActive($event_id)){
            echo '活动启动中，请先停止活动！';
            exit;
        }
        Load::loadModel('StudyRewardEvent')->delEvent($event_id);
        Load::loadModel('StudyRewardRule')->deleteRuleByEventId($event_id);
    }

    private function isActive($event_id){
        $event = Load::loadModel('StudyRewardEvent')->getEventById($event_id);
        if($event['status']!=0){
            return true;
        }
        return false;
    }

    //修改活动状态
    public function EventStatus(){
        $event_id = Http::post('event_id');
        $event = Load::loadModel('StudyRewardEvent')->getEventById($event_id);
        $data = array();
        if($event['status'] == 0){
            $rule = Load::loadModel('StudyRewardRule')->getRuleByEventId($event_id);
            if(!$rule){
                echo '该活动还没有赠课规则，请修改添加！';
                exit;
            }
            $data['status'] = 1;
        }else{
            $data['status'] = 0;
        }
        $data['updater_id'] = Http::session('admin_user_id');
        $data['update_time'] = date('Y-m-d H:i:s');
        $res = Load::loadModel('StudyRewardEvent')->updateEventById($event_id,$data);
        echo $res;
    }


    //赠课列表页
    public function GiftList(){
        $excel = Http::get('excel');
        $PageId = Http::get('PageID', 1);
        $PageSize = 20;

        $param = array();
        $param['event_name'] = Http::get('event_name');
        $param['user_id'] = Http::get('user_id');
        $param['status'] = Http::get('status','all');
        if(!$excel) {
            $param['limit'] = (($PageId - 1) * $PageSize) . ',' . $PageSize;
        }

        $status = array('all'=>'全部',0=>'进行中',1=>'成功',2=>'失败',3=>'不满足赠送规则');
        $gift_type = array(1=>'课耗',2=>'财富');

        $Model_StudyRewardEvent = Load::loadModel('StudyRewardEvent');
        $gift_list = array();
        $gift = Load::loadModel('StudyRewardGift')->getGiftList($param);
        foreach ($gift['res'] as $g){
            $g['event_name'] = '';
            $event =  $Model_StudyRewardEvent->getEventById($g['event_id'],'event_name,start_time,end_time,cycle,gift_type,status');
            if($event) {
                $g['event_name'] = str_replace($param['event_name'], '<font color="red">' . $param['event_name'] . '</font>', $event['event_name']);
                $g['cycle'] = $event['cycle'];
                $g['gift_type'] = $gift_type[$event['gift_type']];
                if($event['status']==0){
                    $g['event_status'] = '停止';
                }else{
                    if(time() < strtotime($event['start_time'])){
                        $g['event_status'] = '未开始';
                    }elseif(time() > strtotime($event['end_time'])){
                        $g['event_status'] = '已结束';
                    }else{
                        $g['event_status'] = '进行中';
                    }
                }
            }
            $g['status_word'] = $status[$g['status']];
            $gift_list[] = $g;
        }
        //分页
        $MyPage = new Page();
        $MyPage->initNew($gift['total'], $PageId, $PageSize);
        $Page = $MyPage->Show();

        if($excel) {
            $field = array();
            $field['user_id'] = '学员ID';
            $field['event_name'] = '活动名称';
            $field['cycle'] = '活动周期';
            $field['event_status'] = '活动状态';
            $field['gift_type'] = '赠课类型';
            $field['finish'] = '已消耗（课耗/财富）';
            $field['gift'] = '已赠课数';
            $field['status_word'] = '赠课结果';
            $field['send_point_time'] = '赠课时间';
            down_xls($gift_list, $field, '赠课列表'.date('Y-m-d-H-i-s'));
        }

        $this->assign('param',$param);
        $this->assign('status',$status);
        $this->assign('gift_list',$gift_list);
        $this->assign('Page',$Page);
        $this->display('StudyReward/gift_list.html');
    }

}