<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/9/13
 * Time: 14:40
 */
class Model_ComplaintRemark extends DataModel
{
    private $data;

    public function __construct()
    {
        $this->data = Load::loadData('ComplaintRemark');
    }

    public function getRemark($pid){
        return $this->data->getRemarkByWhere('pid='.$pid);
    }

    public function getRemarkList($pid){
        return $this->data->getRemarksByWhere('pid='.$pid);
    }

    public function addRemark($data){
        return $this->data->addRemark($data);
    }


}