<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/8/1
 * Time: 13:42
 */
class Model_StudyRewardGift extends DataModel
{
    private $data;

    public function __construct()
    {
        $this->data = Load::loadData('StudyRewardGift');
    }

    /**
     * 获取一条信息
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 15:37:02
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGiftByWhere($where, $field='*', $order='')
    {
        return $this->data->getGiftsByWhere($where, $field, $order);
    }

    /**
     * 获取一条信息
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 15:37:02
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function getGiftsByWhere($where, $field='*', $order='')
    {
        return $this->data->getGiftsByWhere($where, $field, $order);
    }

    /**
     * 添加信息
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 15:36:50
     * @param $data
     * @return mixed
     */
    public function addGift($data)
    {
        return $this->data->addGift($data);
    }

    /**
     * 更新
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 18:21:36
     * @param $where
     * @param $data
     * @return mixed
     */
    public function updateGift($where,$data)
    {
        return $this->data->updateGift($where,$data);
    }

    public function getGiftList($param){
        $where = '1111=1111';
        if($param['event_name']){
            $ids = Load::loadModel('StudyRewardEvent')->getEventIdsLikeName($param['event_name']);
            $where.=' and event_id in ('.$ids.')';
        }
        if($param['user_id']){
            $where.=' and user_id='.$param['user_id'];
        }
        if($param['status']!='all'){
            $where.=' and status='.$param['status'];
        }
        $data = array();
        $data['total'] = $this->data->getGiftCountByWhere($where);
        $data['res'] = $this->data->getGiftsByWhere($where,'*','id desc',$param['limit']);
        return $data;
    }

}