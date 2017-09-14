<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/14
 * Time: 18:45
 */
class Data_AdminUserExt extends Data{
    function __construct(){
        parent::__construct();
        $this->admin_id = Http::session('admin_user_id');
        $this->redis = Load::loadRedis();
    }

    public function getTableName(){
        return 'crmnew.admin_user_ext';
    }

    //获得单条记录
    public function getRecord($where,$field='*',$order=''){
        return $this->getRow($where,$field,$order);
    }

    //判断是否弹出收集信息弹出层
    public function checkAdminUserExt($modify=false){
        $admin_user = (new \Logic\Comm\AdminUser())->getAdminUserById($this->admin_id,'name_zh,work_number,email');
        $oauth = Load::loadModel('AuthExt')->checkUpdata('CollectEmployee');
        //获取当前admin收集信息配置数组

        //判断当前admin所在的组是否有设置
        //if(!empty($admin_user_ext_config) && in_array(1,$admin_user_ext_config)) {
        if($oauth == true){
            $admin_user_ext = $this->getRow('admin_id=' . $this->admin_id);
            if ($admin_user_ext) {
                $complite = array_search('', $admin_user_ext);
                if ($complite && $complite!='work_number') {
                    return ($admin_user_ext);//填的不完整
                } else {
                    if($modify){
                        return ($admin_user_ext);//修改
                    }else{
                        return 1;//填的完整
                    }
                }
            } else {
                //没填过
                $admin_user_ext = $admin_user;
                $admin_user_ext['admin_id'] = $this->admin_id;
                $admin_user_ext['name_en'] = '';
                $admin_user_ext['mobile'] = '';
                $admin_user_ext['enterprise_qq'] = '';
                $admin_user_ext['weixin'] = '';
                $admin_user_ext['motto'] = '';
                return $admin_user_ext;
            }
        }else{
            return 1;//设置不弹出
        }
    }

    //添加或修改收集信息
    public function AdminUserExt($data){
        //print_r($data);
        $admin_user_ext = $this->getRow('admin_id='.$data['admin_id']);
        if($admin_user_ext){
            $data['update_time'] = time();
            return $this->update('admin_id='.$data['admin_id'],$data);
        }else{
            $data['add_time'] = time();
            return $this->addRow($data);
        }
    }

    public function AdminUserExtDel($id=false){
        if($id) {
            return $this->delete('id=' . $id);
        }
        return false;
    }
    /**
    *根据手机号获取员工信息
    *@author guojianye
    *@param  int $mobile
    *@return array
    */
    public function getAdminInfoByMobile ($mobile,$filed='id') {
        if(!$mobile) {
            return false;
        }
        $where = "mobile=$mobile";
        return $this->getRow($where,$filed);
    }

}