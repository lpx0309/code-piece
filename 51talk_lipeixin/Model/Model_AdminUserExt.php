<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/14
 * Time: 18:44
 */
class Model_AdminUserExt extends DataModel{

    function __construct(){
        //parent::__construct();
        $this->data = Load::loadData('AdminUserExt');
    }

    //获得一条消息
    public function getAdminUserExtByAdminId($admin_id){
        $result = $this->data->getRecord('admin_id='.$admin_id);
        return $result?$result:array();
    }

    public function checkAdminUserExt($modify){
        return $this->data->checkAdminUserExt($modify);
    }

    public function AdminUserExt($data){
        return $this->data->AdminUserExt($data);
    }

    public function AdminUserExtDel($id=false){
        if($id) {
            return $this->data->AdminUserExtDel($id);
        }
        return false;
    }

}