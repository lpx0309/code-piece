<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/11/7
 * Time: 16:07
 * 销售和AC老师帐号关联
 */
class Controller_SaOpenClassAcAdmin extends MasterController
{
    //销售和AC老师帐号关联列表
    public function AcAdminList(){
        $ac_admin_list = Load::loadModel('AcAdminRelation')->getRelationList();
        $this->assign('ac_admin_list',$ac_admin_list);
        $this->display('SaOpenClass/ac_admin_list.html');
    }

    //销售和AC老师帐号关联修改确定
    public function AcAdminSave(){
        $id = Http::post('id');
        $teacher = Http::post('teacher');
        $admin = Http::post('admin');

        $had = Load::loadModel('AcAdminRelation')->getRelationByWhere('id=' . $id);
        if($had){
            if($had['ac_teacher_id'] == $teacher){
                $teacher_validate = 0;
            }else{
                $teacher_validate = 1;
            }
            if($had['admin_id'] == $admin){
                $admin_validate = 0;
            }else{
                $admin_validate = 1;
            }
        }else{
            $teacher_validate = 1;
            $admin_validate = 1;
        }
        if($teacher_validate == 1) {
            $has = Load::loadModel('AcAdminRelation')->getRelationByWhere('ac_teacher_id=' . $teacher);
            if ($has) {
                echo '该老师帐号已被关联！';
                exit;
            }
        }
        if($admin_validate == 1) {
            $has = Load::loadModel('AcAdminRelation')->getRelationByWhere('admin_id=' . $admin);
            if ($has) {
                echo '该管理员帐号已被关联！';
                exit;
            }
        }
        $data = array();
        $data['ac_teacher_id'] = $teacher;
        $data['admin_id'] = $admin;
        $data['admin_user'] = Load::loadModel('AdminUser')->getAdminUserById($admin,'user_name')['user_name'];
        echo Load::loadModel('AcAdminRelation')->relationModify($id,$data);
    }

    //销售和AC老师帐号关联删除
    public function AcAdminDel(){
        $id = Http::post('id');
        echo Load::loadModel('AcAdminRelation')->relationDel($id);
    }

}