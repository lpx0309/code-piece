<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/8/10
 * Time: 14:47
 */
class Controller_FortyEightHours extends MasterController {

    public function __construct()
    {
        parent::__construct();
        $this->redis = new RedisCrm();
        $this->cron_log_key = 'forty_eight_hours_cron_log';
        $this->cron_log_len = 600;
        $this->default_start_time = date('Y-m-d',strtotime('today -3 day'));
        $this->default_end_time = date('Y-m-d',strtotime('yesterday'));
    }

    public function index(){
        $start_time = Http::get('start_time');
        if(!$start_time){
            $start_time = $this->default_start_time;
        }
        $end_time = Http::get('end_time');
        if(!$end_time){
            $end_time = $this->default_end_time;
        }
        $age_type = Http::get('age_type');
        $modify = Http::get('modify');

        //缓存表最早的和最晚的数据时间
        $model_fortyeighthours = Load::loadModel('FortyEightHours');
        $earliest_time = $model_fortyeighthours->getRowByWhere('1=1','date','date asc')['date'];
        $latest_time = $model_fortyeighthours->getRowByWhere('1=1','date','date desc')['date'];

        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('age_type',$age_type);
        $this->assign('modify',$modify);
        $this->assign('earliest_time',$earliest_time);
        $this->assign('latest_time',$latest_time);
        $this->assign('controller','FortyEightHours');
        $this->display('FortyEightHours/fortyEightHours.html');
    }

    public function detail(){
        $admin_id = Http::session('admin_user_id');
        $start_time = Http::get('start_time');
        $end_time = Http::get('end_time');
        $age_type = Http::get('age_type');
        switch($age_type){
            case 'adult':
                $occup = ' and age_type=0';//成人
                break;
            case 'young':
                $occup = ' and age_type=1';//青少
                break;
            default:
                $occup = '';
                break;
        }
        $redis_key = 'forty_eight_hours_'.$admin_id.'_'.$start_time.'_'.$end_time.'_'.$age_type;
        $admin_groups = $this->redis->get($redis_key);
        if($admin_groups){
            //使用缓存
            $admin_groups = json_decode($admin_groups,true);
        }else {
            //查数据库
            $admin_groups = array();
            $model_adminuser = Load::loadModel('AdminUser');
            $model_fortyeighthours = Load::loadModel('FortyEightHours');
            $model_auth = load::loadModel('Auth');
            $cc_group = load::loadConfig('cc_group', 'cc_group');

            //获取TL名下组织
            $children = $model_auth->getUserByUid($admin_id);
            if (!is_array($children)) {
                echo '当前登录的用户不是TL';
                exit;
            }
            $old_group = array();
            $uneed_group = array();
            foreach ($children as $c) {
                $alias = $c['organization_alias'];
                if (in_array($alias, $uneed_group)) {
                    continue;
                }
                $old_name = (new \Logic\Comm\AdminUser())->getAdminUserById($c['old_id'], 'group_id')['group_id'];
                if (in_array("'" . $old_name . "'", $cc_group)) {
                    $old_group[] = $old_name;
                } else {
                    $uneed_group[] = $alias;
                    continue;
                }
            }
            $old_group = array_unique($old_group);

            $sort_arr = array();
            foreach ($old_group as $old_name) {
                $forty_eight_hours = $model_fortyeighthours->getRowByWhere('cc_group="' . $old_name . '" and (date between "' . $start_time . '" and "' . $end_time . '")' . $occup, 'sum(free_trial_end) as fte,sum(forty_eight_pay) as fep,sum(forty_eight_call) as fec');
                $free_trial_end = $forty_eight_hours['fte'] ? $forty_eight_hours['fte'] : 0;
                $forty_eight_pay = $forty_eight_hours['fep'] ? $forty_eight_hours['fep'] : 0;
                $forty_eight_call = $forty_eight_hours['fec'] ? $forty_eight_hours['fec'] : 0;

                $pay_percent = round($forty_eight_pay / $free_trial_end * 100, 2);
                $sort_arr[] = $pay_percent;//使用48小时内付费率为排序
                $new_name = (new \Logic\Comm\UserGroup())->getGroupInfoByTagName($old_name, 'show_name')['show_name'];//获取组显示名称
                $admin_groups[$new_name] = array();
                $admin_groups[$new_name]['free_num'] = $free_trial_end;
                $admin_groups[$new_name]['pay_num'] = $forty_eight_pay;
                $admin_groups[$new_name]['pay_percent'] = $pay_percent . '%';
                $admin_groups[$new_name]['call_percent'] = round($forty_eight_call / $free_trial_end * 100, 2) . '%';
            }
            array_multisort($sort_arr, SORT_DESC, SORT_NUMERIC, $admin_groups);
            $this->redis->set($redis_key,json_encode($admin_groups),300);
        }
        $this->assign('admin_groups',$admin_groups);
        $this->display('FortyEightHours/fortyEightHoursDetail.html');
    }

    //读取运行结果日志
    public function cronLog(){
        $cron_log = $this->redis->lrange($this->cron_log_key, 0, $this->cron_log_len);
        if($cron_log) {
            $cron_log = array_reverse($cron_log);
            foreach ($cron_log as $key => $log) {
                echo ($key + 1) . '.' . $log . '<br>';
            }
        }else{
            echo '暂无运行日志';
        }
    }

    //清空运行结果日志
    public function cronLogDel(){
        echo $this->redis->del($this->cron_log_key);
    }

    //修改缓存表数据用
    public function modify(){
        $model_fortyeighthours = Load::loadModel('FortyEightHours');
        //echo $model_fortyeighthours->updateRowByWhere('1=1',array('forty_eight_pay'=>0,'forty_eight_call'=>0));
        echo $model_fortyeighthours->deleteRowByWhere('1=1');//清空数据，慎用！
    }

}