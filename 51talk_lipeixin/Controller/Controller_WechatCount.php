<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/5/5
 * Time: 16:30
 */
class Controller_WechatCount extends MasterController
{
    public function __construct()
    {
        parent::__construct();
        $this->redis = Load::loadRedis('admin');
    }

    public function index(){
        $admin_type = Http::get('admin_type');
        $admin_type = strtoupper($admin_type);
        if(!$admin_type){
            $admin_type = 'TMK';
        }else{
            if(!in_array($admin_type,array('TMK','CC'))){
                echo '参数错误';
                exit;
            }
        }
        if(isset($_COOKIE['wechat_count_start_time'])){
            $start_time = $_COOKIE['wechat_count_start_time'];
        }else{
            $start_time = date('Y-m-d',time());
        }
        if(isset($_COOKIE['wechat_count_end_time'])){
            $end_time = $_COOKIE['wechat_count_end_time'];
        }else{
            $end_time = date('Y-m-d',time());
        }
        $this->assign('start_time',$start_time);
        $this->assign('end_time',$end_time);
        $this->assign('admin_type',$admin_type);
        $this->display('WechatCount/wechatCount.html');
    }

    public function detail(){
        $admin_type = Http::get('admin_type');
        $start_time_get = Http::get('start_time');
        setcookie('wechat_count_start_time',$start_time_get);
        $start_time = $start_time_get.' 00:00:00';
        $end_time_get = Http::get('end_time');
        setcookie('wechat_count_end_time',$end_time_get);
        $end_time = $end_time_get.' 23:59:59';
        $cc_group = load::loadConfig('cc_group','cc_group');
        $tm_group = load::loadConfig('cc_group','tm_group');
        $data_adminuser = load::loadData('AdminUser');
        //$data_cuschangelog = load::loadData('CusChangeLog');
        $data_wechataddedcount = load::loadData('WechatAddedCount');
        $model_auth = load::loadModel('Auth');

        //获取新组织架构下的下属组
        $admin_id = Http::session('admin_user_id');
        $children = $model_auth->getUserByUid($admin_id);
        if(!is_array($children)){
            echo '当前登录的用户不是TL';
            exit;
        }
        //获得修改微信记录
        $wechat_modify_log = load::loadData('WechatModifyLog')->getLog('wechat != "" and add_time between "' . $start_time . '" and "' . $end_time . '" and update_time="0000-00-00 00:00:00"', '*', 'add_time asc');
        $wechat_count = array();
        foreach ($wechat_modify_log as $log) {
            //if (in_array($log['admin_group'], $admin_groups)) {
                if (in_array("'" . $log['admin_group'] . "'", $tm_group)) {
                    if ($admin_type != 'TMK') {
                        continue;
                    }
                } elseif (in_array("'" . $log['admin_group'] . "'", $cc_group)) {
                    if ($admin_type != 'CC') {
                        continue;
                    }
                } else {
                    continue;
                }
            //} else {
                //continue;
            //}
            if (isset($wechat_count[$log['admin_group']])) {
                $wechat_count[$log['admin_group']]['count']++;
            } else {
                $count = array();
                //$count['admin_group'] = $log['admin_group'];
                //$count['dispath'] = 0;
                $count['count'] = 1;
                //$added_count = $data_wechataddedcount->getCount('date between "' . $start_time_get . '" and "' . $end_time_get . '" and admin_group="' . $log['admin_group'] . '"', 'sum(count) as count')['count'];
                //$count['added'] = $added_count ? $added_count : 0;
                $wechat_count[$log['admin_group']] = $count;
            }
        }

        $admin_groups = array();
        $uneed_groups = array();
        foreach($children as $c){
            $new_name = $c['organization_name'];
            if(in_array($new_name,$uneed_groups)){
                continue;
            }
            if(!isset($admin_groups[$new_name])) {
                $old_name = (new \Logic\Comm\AdminUser())->getAdminUserById($c['old_id'],'group_id')['group_id'];
                if (in_array("'" . $old_name . "'", $tm_group)) {
                    if ($admin_type != 'TMK') {
                        $uneed_groups[] = $new_name;
                        continue;
                    }
                } elseif (in_array("'" . $old_name . "'", $cc_group)) {
                    if ($admin_type != 'CC') {
                        $uneed_groups[] = $new_name;
                        continue;
                    }
                } else {
                    $uneed_groups[] = $new_name;
                    continue;
                }
                $admin_groups[$new_name] = array();
                //$admin_groups[$new_name]['city_id'] = $c['city_id'];
                $admin_groups[$new_name]['old_name'] = $old_name;
                $count = $wechat_count[$old_name]['count'];
                $admin_groups[$new_name]['count'] = $count ? $count : 0;
                $added_count = $data_wechataddedcount->getCount('date between "' . $start_time_get . '" and "' . $end_time_get . '" and admin_group="' . $old_name . '"', 'sum(count) as count')['count'];
                $admin_groups[$new_name]['added'] = $added_count ? $added_count : 0;
            }
            //$admin_groups[$new_name]['admin'][$c['old_id']] = 0;
        }
        ksort($admin_groups);
        //var_dump($admin_groups);

        $this->assign('start_time',$start_time_get);
        $this->assign('end_time',$end_time_get);
        $this->assign('admin_type',$admin_type);
        //$this->assign('wechat_count',$wechat_count);
        $this->assign('admin_groups',$admin_groups);
        $this->display('WechatCount/wechatCountDetail.html');
    }

    //修改微信已添加学员数
    public function addedCount(){
        $date = Http::post('date');
        $admin_group = Http::post('admin_group');
        $count = Http::post('count');
        $data_wechataddedcount = load::loadData('WechatAddedCount');
        $added_count = $data_wechataddedcount->getCount('date="'.$date.'" and admin_group="'.$admin_group.'"','count')['count'];
        $data = array();
        $data['count'] = $count;
        $data['operator'] = Http::session('admin_user_id');
        if(isset($added_count)){
            $data['update_time'] = date('Y-m-d H:i:s',time());
            echo $data_wechataddedcount->updateCount('date="'.$date.'" and admin_group="'.$admin_group.'"',$data);
        }else{
            $data['date'] = $date;
            $data['admin_group'] = $admin_group;
            $data['add_time'] = date('Y-m-d H:i:s',time());
            echo $data_wechataddedcount->addCount($data);
        }
    }

    public function delEmptyWechat(){
        echo load::loadData('WechatModifyLog')->delLog('wechat=""');
    }
}