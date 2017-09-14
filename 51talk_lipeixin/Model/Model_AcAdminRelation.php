<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/11/8
 * Time: 10:16
 * 销售和AC老师帐号关联
 */
class Model_AcAdminRelation extends DataModel
{
    public function getRelationList(){
        $ac_admin_relation = Load::loadData('AcAdminRelation')->getListByWhere('1=1');
        $ac_admin_list = array();
        foreach ($ac_admin_relation as $ac_admin){
            $ac_admin['ac_teacher_name'] = (new \Logic\Comm\AddAcTeacher())->getAcTeacherInfo('user_name', null, $ac_admin['ac_teacher_id'])['user_name'];
            $ac_admin_list[] = $ac_admin;
        }
        return $ac_admin_list;
    }

    public function getRelationByWhere($where,$field='*',$order=''){
        return Load::loadData('AcAdminRelation')->getRowByWhere($where,$field,$order);
    }

    public function relationModify($id,$data){
        if($id > 0){
            return Load::loadData('AcAdminRelation')->relationUpdate('id='.$id,$data);
        }else{
            return Load::loadData('AcAdminRelation')->relationAdd($data);
        }
    }

    public function relationDel($id){
        return Load::loadData('AcAdminRelation')->relationDel('id='.$id);
    }
}