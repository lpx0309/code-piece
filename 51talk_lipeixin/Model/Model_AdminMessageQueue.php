<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/3/27
 * Time: 16:47
 */
class Model_AdminMessageQueue extends DataModel
{
    private $data;
    private $redis;

    public function __construct()
    {
        $this->data = Load::loadData('AdminMessageQueue');
        $this->redis = new RedisCrm();
    }

    //获得消息类型的数据量
    public function getMessageTypeCount($admin_id){
        return $this->data->sqlQuery('SELECT `type`,count(*) AS `count` FROM admin_message_queue WHERE admin_id='.$admin_id.' AND status=0 GROUP BY `type` ORDER BY id ASC');
    }

    //记录一条消息队列的消息
    public function MessageRecord($admin_id,$sender_id,$type,$message){
        $data = array();
        $data['admin_id'] = $admin_id;
        $data['sender_id'] = $sender_id;
        $data['type'] = $type;
        $data['message'] = $message;
        $data['status'] = 0;
        $data['add_time'] = date('Y-m-d H:i:s',time());
        return $this->data->addRecord($data);
    }

    //获取消息列表
    public function getMessageList($admin_id,$type,$order,$limit){
        $where = 'admin_id='.$admin_id;
        if($type){
            $where.= ' and type="'.$type.'"';
        }
        $field = '*';
        $result = array();
        $result['total'] = $this->data->getRecordCount($where);
        $result['list'] = $this->data->getRecords($where,$field,$order,$limit);
        return $result?$result:array();
    }

    //获得一条消息
    public function getMessage($id){
        $result = $this->data->getRecord('id='.$id);
        return $result?$result:array();
    }

    //标记消息为已读
    public function markReaded($type,$id){
        $data = array();
        $data['status'] = 1;
        $data['update_time'] = date('Y-m-d H:i:s',time());
        if($type == 'all'){
            $where = 'admin_id='.$id;
        }else{
            $where = 'id in ('.$id.')';
        }
        return $this->data->updateRecord($where,$data);
    }

    //标记消息为未读
    public function markUnReaded($ids){
        $data = array();
        $data['status'] = 0;
        $data['update_time'] = date('Y-m-d H:i:s',time());
        $where = 'id in ('.$ids.')';
        return $this->data->updateRecord($where,$data);
    }

    //删除消息
    public function delMessage($type,$id){
        if($type == 'all'){
            $where = 'admin_id='.$id;
        }else{
            $where = 'id in ('.$id.')';
        }
        return $this->data->delRecord($where);
    }

}