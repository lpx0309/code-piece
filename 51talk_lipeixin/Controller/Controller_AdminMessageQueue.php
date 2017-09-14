<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/3/27
 * Time: 18:15
 */
class Controller_AdminMessageQueue extends MasterController
{
    private $admin_id;
    private $model;
    private $dataType;            //返回值是否jsonp

    public function __construct()
    {
        //admin_id为必须参数
        $this->admin_id = Http::request('admin_id');
        if(!$this->admin_id) {
            $this->admin_id = Http::session('admin_user_id');
            if(!$this->admin_id) {
                $this->apiRes('未登录或缺少参数admin_id', 90100);
            }
        }
        $this->model = Load::loadModel('AdminMessageQueue');
        $this->dataType = 'jsonp';//返回值是jsonp
        parent::__construct();
    }

    //接口生成返回值
    private function apiRes($res,$code = 0){
        $message_key = 'msg';
        $res_key = 'info';
        $result = array();
        $result['code'] = $code;
        $result[$message_key] = 'success';
        if(is_string($res)){
            $result[$message_key] = $res;
        }
        if(is_array($res)){
            if(empty($res)){
                $result['code'] = 11000;
                $result[$message_key] = 'empty';
            }else{
                $result[$res_key] = $res;
            }
        }
        $data_json = json_encode($result);
        if($this->dataType == 'jsonp') {
            $callback = Http::request('callback');
            if($callback) {
                echo $callback . '(' . $data_json . ')';
            }else{
                echo $data_json;
            }
        }else{
            echo $data_json;
        }
        exit;
    }

    //获得消息类型的数据量
    public function getMessageTypeCount(){
        $typeCount = $this->model->getMessageTypeCount($this->admin_id);
        foreach ($typeCount as $key=>$value){
            $typeCount[$value['type']] = $value;
            unset($typeCount[$value['type']]['type']);
            unset($typeCount[$key]);
        }
        $this->apiRes($typeCount);
    }

    //获得消息列表
    public function getMessageList(){
        //按类型过滤
        $type = Http::get('type');
        //排序
        $sort = Http::get('sort');
        $by = Http::get('by');
        $order = '';
        if($sort && $by){
            $order = $sort.' '.$by;
        }
        //分页
        $page_no = Http::get('page_no');
        $page_size = Http::get('page_size');
        $limit = '';
        if($page_no && $page_size){
            $limit = ($page_no - 1)*$page_size.','.$page_size;
        }
        $data = $this->model->getMessageList($this->admin_id,$type,$order,$limit);
        $sort_len = 30;
        $AdminUser = (new \Logic\Comm\AdminUser());
        foreach ($data['list'] as $key=>$value){
            $data['list'][$key]['sort_message'] = $value['message'];
            if(mb_strlen($value['message']) > $sort_len) {
                $data['list'][$key]['sort_message'] = mb_substr($value['message'], 0,$sort_len,'utf-8');
            }
            $sender_info = $AdminUser->getAdminUserById($value['sender_id']);
            $data['list'][$key]['sender_name'] = $sender_info['user_name'];
        }
        $this->apiRes($data);
    }

    //获得单条消息
    public function getMessage(){
        $id = Http::get('id');
        if(!$id){
            $this->apiRes('缺少参数id！',10001);
        }
        $data = $this->model->getMessage($id);
        $this->apiRes($data);
    }

    //标记单条/多条消息为已读
    public function markReaded(){
        $ids = Http::request('ids');
        if(!$ids){
            $this->apiRes('缺少参数ids！',10001);
        }
        $res = $this->model->markReaded('multi',$ids);
        if($res > 0) {
            $this->apiRes('标记成功！');
        }else{
            $this->apiRes('标记失败！',10002);
        }
    }

    //标记单条/多条消息为未读
    public function markUnReaded(){
        $ids = Http::request('ids');
        if(!$ids){
            $this->apiRes('缺少参数ids！',10001);
        }
        $res = $this->model->markUnReaded($ids);
        if($res > 0) {
            $this->apiRes('标记成功！');
        }else{
            $this->apiRes('标记失败！',10002);
        }
    }

    //全部标记为已读
    public function markReadedAll(){
        $res = $this->model->markReaded('all',$this->admin_id);
        if($res > 0) {
            $this->apiRes('标记成功！');
        }else{
            $this->apiRes('标记失败！',10002);
        }
    }

    //删除单条/多条消息
    public function delMessage(){
        $ids = Http::request('ids');
        if(!$ids){
            $this->apiRes('缺少参数ids！',10001);
        }
        $res = $this->model->delMessage('multi',$ids);
        if($res > 0) {
            $this->apiRes('删除成功！');
        }else{
            $this->apiRes('删除失败！',10002);
        }
    }

    //删除所有消息
    public function delMessageAll(){
        $res = $this->model->delMessage('all',$this->admin_id);
        if($res > 0) {
            $this->apiRes('删除成功！');
        }else{
            $this->apiRes('删除失败！',10002);
        }
    }

    //接口测试
    public function apiTest(){
        $param = array();
        $param['admin_id'] = 6;
        //$param['api'] = 1;
        //$param['id'] = 3;
        //$param['callback'] = 'sadfs';
        $result = oauthCurl('http://crmapi.51talk.com/AdminMessageQueue/getMessageList',$param);
        echo '<pre>';
        print_r($result);
        echo '</pre>';
    }

    //获取用户信息
    public function getAdminUser(){
        $admin_user = (new \Logic\Comm\AdminUser())->getAdminUserById($this->admin_id);
        $admin_auth = Load::loadModel('AuthExt')->getUser($this->admin_id);
        $admin_user_ext = Load::loadModel('AdminUserExt')->getAdminUserExtByAdminId($this->admin_id);
        $admin_info = array();
        $admin_info['admin_id'] = $this->admin_id;
        $admin_info['user_name'] = $admin_user['user_name'];
        $admin_info['work_number'] = $admin_user['work_number']?$admin_user['work_number']:$admin_user_ext['work_number'];
        $admin_info['sex'] = $admin_user_ext['sex']?$admin_user_ext['sex']:'男';
        $admin_info['name_zh'] = $admin_user['name_zh'];
        $admin_info['name_en'] = $admin_user['name_en'];
        $admin_info['mobile'] = $admin_user['mobile'];
        $admin_info['enterprise_qq'] = $admin_user_ext['enterprise_qq'];
        $admin_info['weixin'] = $admin_user_ext['weixin'];
        $admin_info['phone'] = $admin_user['phone'];
        $admin_info['email'] = $admin_user['email'];
        $admin_info['group'] = $admin_auth['organization_name'];
        $admin_info['motto'] = $admin_user_ext['motto'];
        $avatar = '';
        if($admin_info['work_number']) {
            $avatar = oauthCurl('http://passport.oa.51talk.com/default/photo?staffNo=' . $admin_info['work_number']);
        }
        if(!$avatar){
            $avatar = 'http://web.oa.51talk.com/bbs/uc_server/images/noavatar_middle.gif';
        }
        $admin_info['avatar'] = $avatar;
        $this->apiRes($admin_info);
    }

    //修改用户密码
    public function adminUserPasswordModify(){
        $newPwd = Http::request('password');
        if(!$newPwd){
            $this->apiRes('缺少参数password！',10001);
        }
        $oldPwd = Http::request('old_password');
        if(!$oldPwd){
            $this->apiRes('缺少参数old_password！',10002);
        }
        $admin_user = (new \Logic\Comm\AdminUser())->getAdminUserById($this->admin_id);
        if($admin_user['password']!=md5(trim($oldPwd))){
            $this->apiRes('旧密码错误！',10003);
        }
        $res = Load::loadModel('AdminUser')->updateAdminUser($this->admin_id,array('password'=>md5($newPwd)));
        //$res = (new \Logic\Comm\SSO())->updateUserPasswordForBackend($this->admin_id, \Sdk\SSO::GROUP_ADMIN, $oldPwd, $newPwd);
        SSO::resetPassword($this->admin_id,SSO::GROUP_ADMIN,md5($newPwd));
        $this->apiRes('修改成功！');
    }

    //修改用户信息
    public function adminUserInfoModify(){
        $data = array();
        $data['work_number'] = Http::request('work_number');
        $data['sex'] = Http::request('sex');
        $data['name_zh'] = Http::request('name_zh');
        $data['name_en'] = Http::request('name_en');
        $data['mobile'] = Http::request('mobile');
        $data['enterprise_qq'] = Http::request('enterprise_qq');
        $data['weixin'] = Http::request('weixin');
        $data['motto'] = Http::request('motto');
        if($data['mobile'] && (!is_numeric($data['mobile']) || strlen(strval($data['mobile']))<11)){
            $this->apiRes('手机格式不正确！',10003);
        }
        $data['phone'] = Http::request('phone');
        if($data['phone'] && !is_numeric($data['phone'])){
            $this->apiRes('分机号格式不正确！',10004);
        }
        $data['email'] = Http::request('email');
        if($data['email'] && !preg_match('/^\w+((-\w+)|(\.\w+))*\@[A-Za-z0-9]+((\.|-)[A-Za-z0-9]+)*\.[A-Za-z0-9]+$/',$data['email'])){
            $this->apiRes('邮箱格式不正确！',10005);
        }
        $data['admin_id'] = $this->admin_id;
        foreach($data as $key=>$value){
            if(!isset($value)){
                unset($data[$key]);
            }
        }
        $res = Load::loadModel('AdminUserExt')->AdminUserExt($data);
        unset($data['sex'],$data['enterprise_qq'],$data['weixin'],$data['motto'],$data['admin_id']);
        $res2 = Load::loadModel('AdminUser')->updateAdminUser($this->admin_id,$data);
        if($res > 0 || $res2 > 0) {
            $this->apiRes('修改成功！');
        }else{
            $this->apiRes('修改失败！',10002);
        }
    }

    //清除缓存
    public function cacheClear(){
        $this->apiRes('清除成功！');
    }

}