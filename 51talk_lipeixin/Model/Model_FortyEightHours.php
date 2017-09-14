<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/8/29
 * Time: 18:18
 */
class Model_FortyEightHours extends DataModel
{
    public function __construct(){
        $this->data = Load::loadData('FortyEightHours');
    }

    public function getRowsByWhere($where,$field='*',$order=''){
        return $this->data->getRowsByWhere($where,$field,$order);
    }

    public function getRowByWhere($where,$field='*',$order=''){
        return $this->data->getRowByWhere($where,$field,$order);
    }

    public function addRowByData($data){
        return $this->data->addRowByData($data);
    }

    public function updateRowByWhere($where,$data){
        return $this->data->updateRowByWhere($where,$data);
    }

    public function deleteRowByWhere($where){
        return $this->data->deleteRowByWhere($where);
    }

    public function modifyRowByWhere($where,$data){
        $row = $this->getRowByWhere($where,'id');
        if($row){
            return $this->updateRowByWhere('id='.$row['id'],$data);
        }else{
            return $this->addRowByData($data);
        }
    }
}