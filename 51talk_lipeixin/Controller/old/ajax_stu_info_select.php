<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/8
 * Time: 15:53
 */
require_once '../../init.php';
$function = Http::request('function');

$occup_id = Http::request('occup_id');
$grade = Http::request('grade');
$purpose = Http::request('purpose');

$redis = Load::loadRedis('admin');
$param = array();
$redis_key = 'crm_purpose_enlevel_'.$function;
if($occup_id) {
    $param['occup_id'] = $occup_id;
    $redis_key.='_occup'.$occup_id;
}
if($grade){
    $param['grade'] = $grade;
    $redis_key.='_grade'.$grade;
}
if($purpose){
    $param['purpose'] = $purpose;
    $redis_key.='_purpose'.$purpose;
}
$crm_purpose_enlevel = $redis->get($redis_key);
if($crm_purpose_enlevel){
    $result = $crm_purpose_enlevel;
}else {
    $result = oauthCurl('http://www.51talk.com/ajax/'.$function,$param);
    $redis->set($redis_key,$result,3600);
}
echo $result;