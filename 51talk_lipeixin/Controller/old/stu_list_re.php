<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/5/25
 * Time: 17:07
 * 学员搜索
 */
define('FORCE_SLAVE', true);
require_once '../../init.php';

//主从切换
defined('AUTODB') or define('AUTODB', true);

//权限验证
checkAdminLoginSub();
//checkquanxian(__FILE__);

$is_buy			 = trim(Http::get('is_buy'));
$user_id		 = trim(Http::get('user_id'));
$student_id		 = trim(Http::get('student_id'));
$skype_id		 = trim(Http::get('skype_id'));
$nick_name		 = trim(Http::get('nick_name'));
$email			 = trim(Http::get('email'));
$qq				 = trim(Http::get('qq'));
$mobile			 = trim(Http::get('mobile'));
$agent			 = trim(Http::get('agent'));

$admin_user_name = trim(Http::session('admin_user_name'));
$admin_user_id	 = trim(Http::session('admin_user_id'));
$admin_group_id	 = trim(Http::session('admin_group_id'));
$admin_users = array($admin_user_id);

$obj_user = Load::loadModel("User");
$obj_follow_log = Load::loadModel('Log');
$obj_agent = Load::loadModel('Agent');
$data_autocallinguser = Load::loadData('AutoCallingUser');
$model_isusercycle = Load::loadModel('IsUserCycle');
$FollowLog = new \Logic\Comm\FollowLog();
$TPL->caching = 0;

$userList = array();
//如果有查询条件,则跟根据查询条件取结查
if(count($_GET)>2){
    //当前用户是体验店加上代理上限制
    $tiyandian = array('gongzhufen'=>'公主坟直营体验店','guomao'=>'国贸直营体验店');
    if($tiyandian[$admin_user_name]){
        Http::setGet('agent',$tiyandian[$admin_user_name]);
    }

    $userList = (new \Logic\Comm\User())->getStuListByMultiCondition();
    //新组织架构
    $model_auth = load::loadModel('Auth');
    $children = $model_auth->getUserByUid($admin_user_id);
    if(is_array($children)){
        $children = array_column($children,'old_id');
        $admin_users = array_merge($admin_users,$children);
    }

    foreach ($userList as $key => $user_info) {
        //如果用户的admin_id不在新组织架构中则搜不到该用户
        $tmk_admin = $data_autocallinguser->getAutoCallingUserInfoByUserId($user_info['id'],'admin_id')['admin_id'];//用户TMK的admin_id
        $is_admin = (new \Logic\Comm\IsUserCycle())->getIsUserCycleByUid($user_info['id'],'admin_id')['admin_id'];//用户IS的admin_id
        if ($user_info['custom_id']) {//CC根据custom_id判断
            if (!in_array($user_info['custom_id'], $admin_users) && !in_array($tmk_admin, $admin_users) && !in_array($is_admin, $admin_users)) {
                unset($userList[$key]);
                continue;
            }
        } else {
            if (!in_array($tmk_admin, $admin_users) && !in_array($is_admin, $admin_users)) {
                unset($userList[$key]);
                continue;
            }
        }

        //藏金阁中数据过滤
        if (in_array("'{$admin_group_id}'", $cc_group) && false === $obj_user->inCangjinge($user_info['id'])) {
            unset($userList[$key]);
            continue;
        }

        //用户身份
        $userList[$key]['identity'] = (new \Logic\Comm\User())->getUserByUid($user_info['id'])['identity'];

        //用户等级，点数
        $level_type = (new \Logic\Comm\User())->isJuniorUser($user_info["id"]) ? 2 : 1;
        //默认取用户身份对应的定级
        $now_level = (new \Logic\Comm\User())->getUserNowLevel($user_info["id"],$level_type);
        $userList[$key]["now_level_cn"] = getMsgByStuLevel($now_level);
        $userList[$key]["user_point"] = $obj_user->getUserPoint($user_info["id"]);
        if (in_array($user_info["id"], $log_list)) {
            $userList[$key]["user_view"] = "y";
        }

        //取会员follow信息
        $userList[$key]["remark"] = "";
        $userList[$key]["follow"] = "";
        $follow_info = $FollowLog->getFollowLogByUserId($user_info['id']);
        if ($follow_info) {
            $userList[$key]["remark"] = $follow_info["id"];
            $userList[$key]["follow"] = $follow_info["follow_date"];
        }

        //代理商
        if ($agent) {
            $parentall = (new \Logic\Comm\Agent())->getAgentDataByCompany($agent);
            $userList[$key]["agent"] = $parentall['company'];
        } else {
            if (!empty($user_info["parent_id"])) {
                $parent_id = $user_info["parent_id"];
                $parentall = (new \Logic\Comm\Agent())->getAgentDataByTag($parent_id, 'company', 'desc');
                $userList[$key]["agent"] = $parentall['company'];
            } else {
                $userList[$key]["agent"] = "";
            }
        }

        //对学员联系信息加星处理
        $userList[$key]['proto_qq'] = $userList[$key]['qq'];
        $userList[$key]['qq'] = format_qq($userList[$key]['qq']);
        $userList[$key]['user_name'] = format_email($userList[$key]['user_name']);
        $userList[$key]['mobile'] = format_mobile($userList[$key]['mobile']);
        $userList[$key]['proto_skype_id'] = $userList[$key]['skype_id'];
        $userList[$key]['skype_id'] = format_skype($userList[$key]['skype_id']);

    }
}
$authExtModel = Load::loadModel('AuthExt');
//身份设置权限
$oauth = $authExtModel->checkUpdata('identity');
if($oauth == true){
    $vip_modify = 1;
}else{
    $vip_modify = 0;
}
$vip_modify = 1;

//冻结权限
$action_freeze = $authExtModel->checkUpdata('stu_list_freeze');
//重置密码权限
$action_reset_password = $authExtModel->checkUpdata('reset_password');

$TPL->assign("is_buy",			$is_buy);
$TPL->assign("user_id",			$user_id);
$TPL->assign("student_id",		$student_id);
$TPL->assign("skype_id",		$skype_id);
$TPL->assign("nick_name",		$nick_name);
$TPL->assign("email",			$email);
$TPL->assign("qq",				$qq);
$TPL->assign("mobile",			$mobile);
$TPL->assign("agent",			$agent);
$TPL->assign("userList",		$userList);
$TPL->assign("vip_modify",	    $vip_modify);
$TPL->assign("check_reset_password",$action_reset_password);
$TPL->assign("check_freeze",$action_freeze);
$TPL->display("StyleDefault/admin/user/stu_list_re.html");