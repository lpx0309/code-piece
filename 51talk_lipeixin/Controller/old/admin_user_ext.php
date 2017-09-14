<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/16
 * Time: 10:32
 */
require_once("../../init.php");
checkAdminLoginSub();

$check_admin_user_ext = Load::loadModel('AdminUserExt')->checkAdminUserExt('modify');
if(is_array($check_admin_user_ext)) {
    $TPL->assign("check_admin_user_ext", $check_admin_user_ext);
    $motto = Load::loadConfig('motto');
    $TPL->assign("motto", $motto);
    $TPL->display("StyleDefault/admin/user/admin_user_ext.html");
}else{
    echo '您所在的组没有开启CRM用户信息收集，请联系管理员！';
}