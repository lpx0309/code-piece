<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/3/17
 * Time: 12:37
 */
require_once("../../init.php");

$user_id = Http::post('user_id');
$vip = Http::post('vip');

$data = array('identity'=>$vip);

echo (new \Logic\Comm\User())->saveUserExtByUid($user_id, $data);