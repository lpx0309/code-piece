<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/8/1
 * Time: 13:42
 */
class Model_StudyRewardRule extends DataModel
{
    private $data;

    public function __construct()
    {
        $this->data = Load::loadData('StudyRewardRule');
    }

    /**
     * 获取规则列表
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 17:23:04
     * @param string $where
     * @param string $field
     * @param string $order
     * @param string $limit
     * @return mixed
     */
    public function getStudyRewardRuleList($where="", $field='*', $order='',$limit="")
    {
        return $this->data->getRulesByWhere($where, $field, $order, $limit);
    }
    public function getRuleByEventId($event_id){
        return $this->data->getRulesByWhere('event_id='.$event_id);
    }

    public function addRule($data){
        return $this->data->addRule($data);
    }

    public function updateRuleById($id,$data){
        return $this->data->updateRule('id='.$id,$data);
    }

    public function deleteRuleByEventId($event_id){
        return $this->data->delRule('event_id='.$event_id);
    }
    public function deleteRuleById($id){
        return $this->data->delRule('id='.$id);
    }

}