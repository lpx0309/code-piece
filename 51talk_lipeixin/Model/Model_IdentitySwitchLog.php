<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/5/15
 * Time: 16:57
 */
class Model_IdentitySwitchLog extends DataModel
{
    public function __construct()
    {
        $this->data = Load::loadData('IdentitySwitchLog');
    }

    public function getLogList($switch_type){
        $admin_user = new\Logic\Comm\AdminUser;
        $log = $this->data->getLog('1=1','*','id desc',200);
        foreach ($log as $k=>$l){
            $log[$k]['switch_type'] = $switch_type[$l['switch_type']];
            $log[$k]['user_name'] = $admin_user->getAdminUserById($l['admin_id'])['user_name'];
        }
        return $log;
    }

    public function RecordLog($log){
        return $this->data->record($log);
    }

}