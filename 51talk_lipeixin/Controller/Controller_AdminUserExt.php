<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/14
 * Time: 18:43
 */
class Controller_AdminUserExt extends MasterController{
    function __construct(){
        parent::__construct();
        $this->model = Load::loadModel('AdminUserExt');
        $this->data = Load::loadData('AdminUserExt');
        $this->admin_id = Http::session('admin_user_id');
        $this->redis = Load::loadRedis('admin');
    }

    /*public function checkAdminUserExt(){
        //echo $this->admin_id;
        echo  $this->model->checkAdminUserExt();
    }*/

    //修改CRM用户信息收集
    public function AdminUserExt(){
        //$this->display('AdminUserExt/admin_user_ext.html');
        //print_r($_POST);
        $callback = Http::get('callback');
        $data = array();
        $data['admin_id'] = $this->admin_id;
        $data['work_number'] = trim(Http::post('work_number'));
        $data['sex'] = Http::post('sex');
        $data['name_zh'] = trim(Http::post('name_zh'));
        $data['name_en'] = trim(Http::post('name_en'));
        $data['mobile'] = trim(Http::post('mobile'));
        $data['enterprise_qq'] = trim(Http::post('enterprise_qq'));
        $data['weixin'] = trim(Http::post('weixin'));
        $data['email'] = trim(Http::post('email'));
        $data['motto'] = Http::post('motto');
       
        $admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$this->admin_id.' and mobile='.$data['mobile']);
        if($admin_user_ext){
            echo $callback.'('.json_encode('该手机号已存在！').')';
            exit;
        }
        $admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$this->admin_id.' and enterprise_qq='.$data['enterprise_qq']);
        if($admin_user_ext){
            echo $callback.'('.json_encode('该企业QQ已存在！').')';
            exit;
        }
        $admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$this->admin_id.' and weixin="'.$data['weixin'].'"');
        if($admin_user_ext){
            echo $callback.'('.json_encode('该微信已存在！').')';
            exit;
        }
        $admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$this->admin_id.' and email="'.$data['email'].'"');
        if($admin_user_ext){
            echo $callback.'('.json_encode('该邮箱已存在！').')';
            exit;
        }
        $this->redis->del('check_admin_user_ext_'.$this->admin_id);
        $result = $this->model->AdminUserExt($data);
        echo $callback.'('.json_encode($result).')';
    }

    public function AdminUserExtDel(){
        $id=Http::get('id');
        echo $this->data->AdminUserExtDel($id);
    }


}