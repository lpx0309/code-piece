<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/11/15
 * Time: 13:51
 */
class Controller_StuList extends MasterController
{
    public function index(){
        $is_buy			 = trim(Http::get('is_buy'));
        $user_id		 = trim(Http::get('user_id'));
        $student_id		 = trim(Http::get('student_id'));
        $skype_id		 = trim(Http::get('skype_id'));
        $email			 = trim(Http::get('email'));
        $qq				 = trim(Http::get('qq'));
        $mobile			 = trim(Http::get('mobile'));
        $agent			 = trim(Http::get('agent'));
        $userList        = array();

        $admin_user_id	 = trim(Http::session('admin_user_id'));
        $admin_group_id	 = trim(Http::session('admin_group_id'));

        //有查询条件
        if(!empty($user_id) || !empty($student_id) || !empty($skype_id) || !empty($email) || !empty($qq) || !empty($mobile) || !empty($agent)){
            $model_auth             = load::loadModel('Auth');
            $model_user             = Load::loadModel("User");
            $FollowLog              = new \Logic\Comm\FollowLog();
            $User                   = new \Logic\Comm\User();
            $Agent                  = new \Logic\Comm\Agent();

            //根据查询条件获取所有符合条件的学员
            $userList = $User->getStuListByMultiCondition();
            if(!empty($userList)) {
                //身份设置权限
                $action_identity = $model_auth->checkUpdata('identity');
                //$action_identity = true;
                $this->assign("check_identity",$action_identity);

                //冻结权限
                $action_freeze = $model_auth->checkUpdata("stu_list_freeze");
                $this->assign("check_freeze",$action_freeze);

                //重置密码权限
                $action_reset_password = $model_auth->checkUpdata("reset_password");
                $this->assign("check_reset_password",$action_reset_password);


                foreach ($userList as $key => $user_info) {
                    //查看所有学员的权限
                    $isLook = Load::loadModel('UserCrm')->LogerIsLookStuDetailsInfo($user_info['id']);
                    if($isLook['code'] == 0){
                        unset($userList[$key]);
                        continue;
                    }

                    //用户身份
                    $userList[$key]['identity'] = (new \Logic\Comm\User())->getUserByUid($user_info['id'])['identity'];

                    //用户等级，点数
                    $level_type = $User->isJuniorUser($user_info["id"]) ? 2 : 1;
                    //默认取用户身份对应的定级
                    $now_level = $User->getUserNowLevel($user_info["id"], $level_type);
                    $userList[$key]["now_level_cn"] = getMsgByStuLevel($now_level);
                    $userList[$key]["user_point"] = $model_user->getUserPoint($user_info["id"]);

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
                        $parentall = $Agent->getAgentDataByCompany($agent);
                        $userList[$key]["agent"] = $parentall['company'];
                    } else {
                        if (!empty($user_info["parent_id"])) {
                            $parent_id = $user_info["parent_id"];
                            $parentall = $Agent->getAgentDataByTag($parent_id, 'company', 'desc');
                            $userList[$key]["agent"] = $parentall['company'];
                        } else {
                            $userList[$key]["agent"] = "";
                        }
                    }
                    //判断是否是推荐学员  如果是 背景标蓝
                    $isRecommend = Load::loadModel('UserCrm')->isRecommendUser($user_info['id']);
                    if(true == $isRecommend){
                        $userList[$key]['isRecommend'] = 1;
                    }else{
                        $userList[$key]['isRecommend'] = 0;
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
        }
        $this->assign("is_buy",		$is_buy);
        $this->assign("user_id",	$user_id);
        $this->assign("student_id",	$student_id);
        $this->assign("skype_id",	$skype_id);
        $this->assign("email",		$email);
        $this->assign("qq",			$qq);
        $this->assign("mobile",		$mobile);
        $this->assign("agent",		$agent);
        $this->assign("userList",	$userList);
        $this->display('StuList/stu_list.html');
    }
}