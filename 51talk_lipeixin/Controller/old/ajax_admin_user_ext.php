<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/22
 * Time: 14:12
 */
require_once("../../init.php");
checkAdminLoginSub();

$admin_id = Http::session('admin_user_id');
$callback = Http::get('callback');

$model_adminuserext = Load::loadModel('AdminUserExt');
$redis = new RedisCrm();

$data = array();
$data['admin_id'] = $admin_id;
$data['work_number'] = trim(Http::post('work_number'));
$data['sex'] = Http::post('sex');
$data['name_zh'] = trim(Http::post('name_zh'));
$data['name_en'] = trim(Http::post('name_en'));
$data['mobile'] = trim(Http::post('mobile'));
$data['enterprise_qq'] = trim(Http::post('enterprise_qq'));
$data['weixin'] = trim(Http::post('weixin'));
$data['email'] = trim(Http::post('email'));
$data['motto'] = Http::post('motto');
//验证某些字段值是否重复
/*$admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$admin_id.' and mobile='.$data['mobile']);
if($admin_user_ext){
    $admin_info = (new \Logic\Comm\AdminUser())->getAdminUserById($admin_user_ext['admin_id'],'status');
    if($admin_info['status'] == 'on') {
        echo $callback.'('.json_encode('该手机号已存在！').')';
        exit;
    }else{
        $model_adminuserext->AdminUserExtDel($admin_user_ext['id']);
    }
}
$admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$admin_id.' and enterprise_qq='.$data['enterprise_qq']);
if($admin_user_ext){
    $admin_info = (new \Logic\Comm\AdminUser())->getAdminUserById($admin_user_ext['admin_id'],'status');
    if($admin_info['status'] == 'on') {
        echo $callback.'('.json_encode('该企业QQ已存在！').')';
        exit;
    }else{
        $model_adminuserext->AdminUserExtDel($admin_user_ext['id']);
    }
}
$admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$admin_id.' and weixin="'.$data['weixin'].'"');
if($admin_user_ext){
    $admin_info = (new \Logic\Comm\AdminUser())->getAdminUserById($admin_user_ext['admin_id'],'status');
    if($admin_info['status'] == 'on') {
        echo $callback.'('.json_encode('该微信已存在！').')';
        exit;
    }else{
        $model_adminuserext->AdminUserExtDel($admin_user_ext['id']);
    }

}
$admin_user_ext = (new \Logic\Comm\AdminUserExt())->getAdminUserExtByWhere('admin_id!='.$admin_id.' and email="'.$data['email'].'"');
if($admin_user_ext){
    $admin_info = (new \Logic\Comm\AdminUser())->getAdminUserById($admin_user_ext['admin_id'],'status');
    if($admin_info['status'] == 'on') {
        echo $callback.'('.json_encode('该邮箱已存在！').')';
        exit;
    }else{
        $model_adminuserext->AdminUserExtDel($admin_user_ext['id']);
    }
}*/
$redis->del('check_admin_user_ext_'.$admin_id);
$result = $model_adminuserext->AdminUserExt($data);
echo $callback.'('.json_encode($result).')';