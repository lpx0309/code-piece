<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/2/15
 * Time: 9:42
 */
require_once("../../init.php");

$obj_autocallingswitch=Load::loadData('AutoCallingSwitch');

$data=array();
$data['auto_calling_cc_user']=Http::post('call_auto_calling_cc_user');
$data['cc_user_cycle']=Http::post('call_cc_user_cycle');

$switch=$obj_autocallingswitch->updateSwitch($data);

print_r($switch);
