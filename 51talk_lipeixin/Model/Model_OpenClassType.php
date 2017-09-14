<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/11/8
 * Time: 10:16
 * OC课程类型管理
 */
class Model_OpenClassType extends DataModel
{

    public function getTypeList(){
        $type_list = array();
        $status_define = array(1=>'启用',2=>'停用');
        $model_adminuser = (new \Logic\Comm\AdminUser());
        $open_class_type = Load::loadData('OpenClassType')->getListByWhere('1=1');
        foreach ($open_class_type as $type){
            $type['admin_user'] = $model_adminuser->getAdminUserById($type['admin_id'],'user_name')['user_name'];
            $type['last_admin_user'] = $model_adminuser->getAdminUserById($type['last_admin_id'],'user_name')['user_name'];
            $type['status'] = $status_define[$type['status']];
            if(strtotime($type['last_update_time']) < 0){
                $type['last_update_time'] = '';
            }
            $type_list[] = $type;
        }
        return $type_list;
    }

    public function getTypeById($id){
        return Load::loadData('OpenClassType')->getRowByWhere('id='.$id);
    }

    public function TypeModify($id,$data){
        if($id > 0){
            $data['last_admin_id'] = Http::session('admin_user_id');
            $data['last_update_time'] = date('Y-m-d H:i:s');
            return Load::loadData('OpenClassType')->openClassTypeUpdate('id='.$id,$data);
        }else{
            $data['admin_id'] = Http::session('admin_user_id');
            $data['create_time'] = date('Y-m-d H:i:s');
            return Load::loadData('OpenClassType')->openClassTypeAdd($data);
        }
    }

    public function TypeDel($id){
        return Load::loadData('OpenClassType')->openClassTypeDel('id='.$id);
    }

    /**
     * 根据登陆的人获取组织架构对应的课程分类  如果没有则显示全部
     * @author yanghao<yanghao@51talk.com>
     */
    public function getTypeListByLoger()
    {
        $allUserType = array('TM','CC','IS','PT','CT','CST','CR');// 需要过滤分类的组织架构
        $authInfo = Load::loadModel('AuthExt')->getUserInfo(Http::session('admin_user_id'));
        $openClassTypeData = Load::loadData('OpenClassType');
        if(in_array($authInfo['name'], $allUserType)){
            //如果在规定的组织架构中 则返回组织架构对应的课程分类
            $typeInfo = $openClassTypeData->getOpenClassTypeListByDepartment($authInfo['name']);
        }else{
            //$typeInfo = $openClassTypeData->getOpenClassTypeListAll();//获取全部的课程分类下拉
            $typeInfo = $openClassTypeData->getOpenClassTypeListByDepartment('其它');
        }
        return $typeInfo;
    }

    /**
     * 获取登陆人对应的分类下拉
     * @return array
     * @author yanghao<yanghao@51talk.com>
     */
    public function getTypeListSelect()
    {
        $typeInfo = $this->getTypeListByLoger();
        $typeSelect = array();
        foreach ($typeInfo as $k => $v){
            $typeSelect[$v['id']] = $v['type_name'];
        }
        return $typeSelect;
    }
}