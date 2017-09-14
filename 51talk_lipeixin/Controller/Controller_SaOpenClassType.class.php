<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/11/8
 * Time: 16:07
 * OC课程类型管理
 */
class Controller_SaOpenClassType extends MasterController
{
    public function TypeList(){
        $type_list = Load::loadModel('OpenClassType')->getTypeList();
        $this->assign('text_max',100);
        $this->assign('type_list',$type_list);
        $this->display('SaOpenClass/type_list.html');
    }

    public function TypeModify(){
        $id = Http::get('id');
        $type = array();
        if($id > 0) {
            $type = Load::loadModel('OpenClassType')->getTypeById($id);
        }
        $department_list = array('TM','CC','IS','CR','PT','CT','CST','其它');
        $this->assign('type',$type);
        $this->assign('department_list',$department_list);
        $this->assign('text_max',100);
        $this->display('SaOpenClass/type_modify.html');
    }

    public function TypeSave(){
        $id = Http::post('id');
        $type = Http::post('type');
        $status = Http::post('status');
        $department = Http::post('department');
        $declare = Http::post('declare');
        
        if(strlen($type) > 30){//一个汉字3个字符
            echo "分类名称不能超过10个字！";exit;
        }
        $data = array();
        $data['type_name'] = $type;
        $data['status'] = $status;
        $data['department'] = $department;
        $data['declare'] = $declare;
        echo Load::loadModel('OpenClassType')->TypeModify($id,$data);
    }

    public function TypeDel(){
        $id = Http::post('id');
        echo Load::loadModel('OpenClassType')->TypeDel($id);
    }
}