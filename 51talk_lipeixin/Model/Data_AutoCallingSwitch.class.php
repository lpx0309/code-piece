<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/2/15
 * Time: 9:50
 */
class Data_AutoCallingSwitch extends Data
{

    public function getTableName()
    {
        return 'crmnew.auto_calling_switch';
    }

    //获取开关
    public function getSwitch($admin_id=false){
        if($admin_id == false) {
            $admin_id = Http::session('admin_user_id');
        }
        $switch=$this->getRow('admin_id='.$admin_id);
        if($switch){
            return $switch;
        }else{
            $data=array();
            $data['admin_id']=$admin_id;
            $data['auto_calling_cc_user']=1;
            $data['cc_user_cycle']=0;
            return $data;
        }
    }

    //执行开关
    public function updateSwitch($data){
        $admin_id=Http::session('admin_user_id');
        $switch=$this->getRow('admin_id='.$admin_id);
        if($switch){
            $data['update_time']=time();
            return $this->update('admin_id='.$admin_id,$data);
        }else{
            $data['admin_id']=$admin_id;
            $data['add_time']=time();
            return $this->addRow($data);
        }
    }

}