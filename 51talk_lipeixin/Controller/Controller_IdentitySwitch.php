<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/5/15
 * Time: 12:10
 */
class Controller_IdentitySwitch extends MasterController{

    private $log;
    private $switch_type;
    private $identity;

    public function __construct()
    {
        parent::__construct();
        $this->log = Load::loadModel('IdentitySwitchLog');
        $this->switch_type = array(1=>'1v1转1v多',2=>'1v多转1v1');
        $this->identity = array(11=>'青少',12=>'美小');
    }

    public function index(){
        //修改日志
        $switch_log = $this->log->getLogList($this->switch_type);

        //转换权限
        $auth = Load::loadModel('AuthExt');
        $OneToOneTurnOneToN = $auth->checkUpdata('OneToOneTurnOneToN');
        $OneToNTurnOneToOne = $auth->checkUpdata('OneToNTurnOneToOne');
        if(!$OneToOneTurnOneToN){
            unset($this->switch_type[1]);
        }
        if(!$OneToNTurnOneToOne){
            unset($this->switch_type[2]);
        }

        $this->assign('identity',$this->identity);
        $this->assign('switch_type',$this->switch_type);
        $this->assign('switch_log',$switch_log);
        $this->display('IdentitySwitch/identity_switch.html');
    }

    public function SwitchAction(){
        $stu_id = Http::post('stu_id');
        $identity = Http::post('identity');
        $switch_type = Http::post('switch_type');

        $user = (new \Logic\Comm\User());
        $user_info = $user->getUserByUid($stu_id);
        if(empty($user_info)){
            die('当前用户不存在！');
        }
        if($user_info['is_buy'] != 'free'){
            die('当前用户不是体验用户！');
        }
        $service_sso = (new \Logic\Comm\SSO());
        $role = $service_sso->getRole($stu_id,\Sdk\SSO::GROUP_USER);
        if(!isset($role['res'])){
            die('SSO接口错误！');
        }
        if(!in_array($identity,$role['res'])){
            die('当前用户不是'.$this->identity[$identity].'用户！');
        }
        //调用SSO接口转换
        if($switch_type == 1){
            //1v1转1v多
            $service_sso->addRole($stu_id, \Sdk\SSO::GROUP_USER, array(\Sdk\SSO::ROLE_MULTICLASS));//添加班课标识
            Load::loadData('UserExt')->batchUpdate(array($stu_id),array('follow_status'=>3));
        }else{
            //1v多转1v1
            $service_sso->deleteRole($stu_id, \Sdk\SSO::GROUP_USER, array(\Sdk\SSO::ROLE_MULTICLASS));//删除班课标识
            Load::loadData('UserExt')->batchUpdate(array($stu_id),array('follow_status'=>7));
        }
        //记录日志
        $log = array();
        $log['stu_id'] = $stu_id;
        $log['switch_type'] = $switch_type;
        $log['admin_id'] = Http::session('admin_user_id');
        $log['add_time'] = date('Y-m-d H:i:s');
        $this->log->RecordLog($log);
    }

}