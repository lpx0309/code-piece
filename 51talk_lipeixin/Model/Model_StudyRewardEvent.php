<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/8/1
 * Time: 13:42
 */
class Model_StudyRewardEvent extends DataModel
{
    private $data;

    public function __construct()
    {
        $this->data = Load::loadData('StudyRewardEvent');
    }

    public function getEventList($param){
        $where = '1111=1111';
        if($param['event_name']){
            $where.=' and event_name like "%'.$param['event_name'].'%"';
        }
        if($param['user_type']){
            $where.=' and user_type='.$param['user_type'];
        }
        if($param['start_time']){
            $where.=' and create_time>="'.$param['start_time'].'"';
        }
        if($param['end_time']){
            $where.=' and create_time<="'.$param['end_time'].'"';
        }
        $data = array();
        $data['total'] = $this->data->getEventCountByWhere($where);
        $data['res'] = $this->data->getEventsByWhere($where,'*','create_time desc',$param['limit']);
        return $data;
    }

    public function getEventById($id){
        return $this->data->getEventByWhere('id='.$id);
    }

    public function getEventByName($event_name){
        return $this->data->getEventByWhere('event_name="'.$event_name.'"');
    }

    public function getEventIdsLikeName($event_name){
        $ids = $this->data->getEventsByWhere('event_name like "%'.$event_name.'%"','id');
        $ids = array_column($ids,'id');
        $ids = implode(',',$ids);
        return $ids;
    }

    public function updateEventById($id,$data){
        return $this->data->updateEvent('id='.$id,$data);
    }

    public function addEvent($data){
        return $this->data->addEvent($data);
    }

    /**
     * 获取指定时间段内，在进行中的活动列表
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 14:51:22
     * @param $day_time
     * @param string $field
     * @param string $order
     * @param $limit
     * @return mixed
     */
    public function getDayTimeOnEvent($day_time, $field='*', $order='', $limit)
    {
        $where = "status = 1 AND end_time >= '{$day_time}' AND start_time <= '{$day_time}'";
        return $this->data->getEventsByWhere($where, $field, $order, $limit);
    }

    /**
     * 获取活动列表
     * @author libo <libo01@51talk.com>
     * @date   2017-08-03 17:23:59
     * @param $where
     * @param string $field
     * @param string $order
     * @param $limit
     * @return mixed
     */
    public function getStudyRewardEventList($where = "", $field='*', $order='', $limit)
    {
        return $this->data->getEventsByWhere($where, $field, $order, $limit);
    }

    public function delEvent($id){
        return $this->data->delEvent('id='.$id);
    }
}