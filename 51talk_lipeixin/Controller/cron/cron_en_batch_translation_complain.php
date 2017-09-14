<?php
/**
 * 学员投诉批量翻译成英文
 * User: zhanghaisheng<zhanghaisheng@51talk.com>
 * Date: 2015/11/24
 * Time: 16:56
 * modify to cron by lipeixin @2016-4-18
 */

require_once(dirname(dirname(__FILE__))."/init.php");

$api_url = "http://fanyi.youdao.com/openapi.do?keyfrom=51talk&key=1566149960&type=data&doctype=json&version=1.1";

$data =  array();

//查找投诉信息
$where = " (complaint_edesc = '' or complaint_edesc is null) and (complaint_desc !='' or complaint_desc is not null) and last_time between '".date('Y-m-d 00:00:00')."' and '".date('Y-m-d 23:59:59')."'";
$complain_infos = (new \Logic\Comm\Complaint())->getComplaintInfo($where,"id,complaint_desc,complaint_type,approve");
$complain_infos = $complain_infos ? CommTool::format_array_by_key($complain_infos,"id") : array();

foreach($complain_infos as $id=>$info){
    $param = array();
    $param['q'] = $info['complaint_desc'];
    $translation_info = oauthCurl($api_url,$param,'json');
    if($translation_info['translation']){
        $data[$id] = $translation_info['translation'][0];
    }
}
if($data){
    foreach($data as $id=>$val){
        if(!$complain_infos[$id]['approve']){
            $tea_id = $complain_infos[$id]['tea_id'];
            $appoint_id = $complain_infos[$id]['appoint_id'];
            if($complain_infos[$id] && $complain_infos[$id]['complaint_type']=='关于老师-About Teacher')
            {
               $test =  Load::loadData('TeacherLevel')->reduceLeveByStar($tea_id, $appoint_id);
            }
        }
        Load::loadData('Complaint')->updateComplaint($id,array('complaint_edesc'=>$val,'approve'=>'y'));
    }
}
var_dump($data);