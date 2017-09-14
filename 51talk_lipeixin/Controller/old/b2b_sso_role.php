<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/3/17
 * Time: 14:03
 */
include "../../init.php";

use \Logic\Comm\SSO;
$obj_sso = new SSO();

$b2b_student = Load::loadData('B2BStudent')->getInfos('2=2','uid','id asc');

foreach($b2b_student as $stu){
    echo $stu['uid'];
    $result = $obj_sso->setRole($stu['uid'], \Sdk\SSO::GROUP_USER, array(\Sdk\SSO::ROLE_B2B));
    var_dump($result);
    echo '<br>';
}

