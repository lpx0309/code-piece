<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/7/26
 * Time: 14:32
 */
class Controller_WechatMessage extends MasterController
{

    public function __construct()
    {
        parent::__construct();
    }

    //统计
    public function Report(){
        $report_list = array();
        $start_date = Http::get('start_date');
        if(!$start_date){
            $start_date = date('Y-m-1');
        }
        $end_date = Http::get('end_date');
        if(!$end_date){
            $end_date = date('Y-m-d');
        }
        $start_time = $start_date.' 00:00:00';
        $end_time = $end_date.' 23:59:59';
        $wechat_message = (new \Logic\Comm\WechatMessage())->getWechatMessageByDate($start_time,$end_time,'type,add_time','add_time desc');
        if(is_array($wechat_message)) {
            foreach ($wechat_message as $wm) {
                $add_time = explode(' ', $wm['add_time']);
                $report_list[$add_time[0]][$wm['type']]++;
            }
        }
        $this->assign('start_date',$start_date);
        $this->assign('end_date',$end_date);
        $this->assign('report_list',$report_list);
        $this->display('WechatMessage/report.html');
    }

    //文案配置
    public function WordConfig(){
        $type = array(Model_WechatMessageWord::STUDYPLAN_TYPE=>'学习计划',Model_WechatMessageWord::OC_TYPE=>'OC课',Model_WechatMessageWord::USERHOLIDY_TYPE=>'请假');
        $word_list = Load::loadModel('WechatMessageWord')->getWordList();
        $this->assign('type',$type);
        $this->assign('word_list',$word_list);
        $this->display('WechatMessage/word_config.html');
    }

    public function getWordById(){
        $id = Http::get('id');
        $word = Load::loadModel('WechatMessageWord')->getWordById($id);
        echo json_encode($word);
    }

    public function WordModify(){
        $id = Http::post('id');
        $data = array();
        $data['alias'] = Http::post('alias');
        $data['type'] = Http::post('type');
        $data['u_word'] = Http::post('u_word');
        $data['a_word'] = Http::post('a_word');
        $data['status'] = Http::post('status');
        if($id){
            $word = Load::loadModel('WechatMessageWord')->getWordById($id);
            if($word['alias'] == $data['alias']){
                $is_judge = false;
            }else{
                $is_judge = true;
            }
        }else{
            $is_judge = true;
        }
        if($is_judge) {
            $has_alias = Load::loadModel('WechatMessageWord')->getWordByAlias($data['alias']);
            if ($has_alias) {
                echo '该别名已存在！';
                exit;
            }
        }
        if($id){
            $res = Load::loadModel('WechatMessageWord')->updateWordById($id,$data);
        }else{
            $res = Load::loadModel('WechatMessageWord')->addWord($data);
        }
        if($res>0){
            echo '修改成功！';
        }else{
            echo '修改失败或没有修改！';
        }
    }

    public function delWord(){
        $id = Http::post('id');
        $res = Load::loadModel('WechatMessageWord')->delWordById($id);
        if($res>0){
            echo '删除成功！';
        }else{
            echo '删除失败或没有修改！';
        }
    }

}