<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/6/15
 * Time: 10:55
 */
//公开课取消
include '../../init.php';
$stu_id = Http::get('stu_id');
$id = Http::get('id');
$java_api = Load::loadConfig('javaApi');
$cancel_url = $java_api['api_url'].'talkplatform_appoint_consumer/appoint_multi/cancel_appoint';
//echo $cancel_url;
$param = array();
$param['stu_id'] = $stu_id;
$param['id'] = $id;
$cancel_result = oauthCurl($cancel_url,$param,'json');
//print_r($cancel_result);
if($cancel_result['code'] != $java_api['success_code']){
    echo $cancel_result['message'];
}
