<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2016/4/6
 * Time: 14:58
 */
require_once '../../init.php';

$stu_id = Http::get('stu_id');
$more = Http::get('more');
$blank = Http::get('blank');//新窗口打开
$get_course_id = Http::get('course_id');
$PageId = Http::get('PageID', 1);
$PageSize = 20;

$java_api = Load::loadConfig('javaApi');
//$data_teacher = Load::loadData('Teacher');
$obj_userOrder = Load::loadData('UserOrder');
//$model_course = Load::loadData('Course');
$model_mcteacher = Load::loadModel('McTeacher');
$model_apicourse = Load::loadModel('ApiCourse');
//$redis = Load::loadRedis();
$service_teacher = (new \Logic\Comm\Teacher\Teacher());

//没班课订单不显示
//$class_order = $obj_userOrder->getSingleUserOrder('stu_id='.$stu_id.' and order_type="class"');
//对接平台 order fangxuezheng
$class_order = (new \Logic\Comm\Order\Order())->getUserOrderByStuId($stu_id, '', '',array('class','multi','pgmulti','na_dls','multi_class','apollo_class'));
if(!empty($class_order)) {
    /*$order_types = array_column($class_order,'order_type');
    if(in_array('multi_class',$order_types) || in_array('apollo_class',$order_types)){
        $course_type = 20;
    }else{
        $course_type = 15;
    }*/
    $course_type = Http::get('course_type');
    if(!$course_type){
        $course_type = 20;
    }
}else{
    exit;
}

//通过接口获取班课原始数据
$appoint_url = $java_api['api_url'].'talkplatform_appoint_consumer/course_class/query_student_timetable_end?stu_id='.$stu_id.'&course_type='.$course_type;
if($more){
    $appoint_url.='&page_size='.$PageSize.'&page_no='.$PageId;
}else{
    $appoint_url.='&is_all=1';
}
//var_dump($appoint_url);
$appoint_result = oauthCurl($appoint_url,'','json');
//缓存
/*$appoint_result = $redis->get('muti_stu_class_'.$stu_id);
if(!$appoint_result){
    $appoint_result = oauthCurl($appoint_url);
    $redis->set('muti_stu_class_'.$stu_id,$appoint_result,60);
}
$appoint_result = json_decode($appoint_result,true);*/
//var_dump($appoint_result);

//生成班课最终数据
$appoint_list = array();
$course_list = array();
if(isset($appoint_result['res'])) {
    foreach ($appoint_result['res'] as $appoint) {
        //退费不显示
        //if ($appoint['status'] == 7) {
            //continue;
        //}
        //课程名称下拉选择
        if($appoint['course_id']) {
            $course_list[$appoint['course_id']] = $appoint['course_name'];
        }
        if ($get_course_id && $get_course_id != $appoint['course_id']) {
            continue;
        }
        $arr = array();
        //班课
        $arr['course_type'] = $appoint['course_cate_label'];
        $arr['course_name'] = $appoint['course_name'];
        //课程
        $arr['lesson_time'] = $appoint['start_time'];
        $arr['lesson_name'] = $appoint['name'];
        $book = $model_apicourse->getCourseInfo($appoint['book_id'])['course_info'];
        if ($book) {
            $arr['book'] = $book;
        } else {
            $arr['book'] = "javascript:alert('教材路径未填写！')";
        }
        //老师
        if($appoint['tea_id'] == 0){
            $arr['tea_name'] = '老师未分配';
        }else {
            if($appoint['tea_type'] == 1) {
                $tea_info = $model_mcteacher->getCacheTeacherBySid($appoint['tea_id']);
            }else{
                $tea_info = $service_teacher->getTeacherInfoById($appoint['tea_id'], false);
            }
            if ($tea_info['real_name']) {
                $arr['tea_name'] = $tea_info['real_name'];
            } else {
                if ($tea_info['nick_name']) {
                    $arr['tea_name'] = $tea_info['nick_name'];
                } else {
                    $arr['tea_name'] = 'ID为' . $appoint['tea_id'] . '的老师不存在';
                }
            }
        }
        //班级
        $arr['class_name'] = $appoint['class_name'];
        //上课方式
        $arr['client'] = '51talkAC';//班课只有AC
        //班课状态
        $arr['status'] = $appoint['status'];
        //学员状态
        switch ($appoint['status']) {
            case 4:
                $stu_status = '缺席';
                break;
            case 7:
                $stu_status = '退费';
                break;
            default:
                $stu_status = '正常';
                break;
        }
        $arr['stu_status'] = $stu_status;
        //课程状态
        if($appoint['status'] == 7){
            $class_status = '退费';
            $arr['is_over'] = 1;
        }elseif($appoint['status'] == 10){
            $class_status = '老师缺席';
            $arr['is_over'] = 1;
        }else {
            if (time() >= strtotime($appoint['end_time'])) {
                $class_status = '已结束';
                $arr['is_over'] = 1;
            } else {
                $class_status = '未开始或未结束';
                $arr['is_over'] = 0;
            }
        }
        $arr['class_status'] = $class_status;
        //补课
        $arr['tutor'] = array();
        if($appoint['tutor_lesson_id']){
            $tutor_url = $java_api['api_url'] . 'talkplatform_course_consumer/course_class/query_lesson?lesson_id=' .$appoint['tutor_lesson_id'].'&course_type='.$course_type;
            $tutor_result = oauthCurl($tutor_url, '', 'json');
            if (isset($tutor_result['res'])) {
                $tutor = $tutor_result['res'][0];
                $arr['tutor']['lesson_time'] = $tutor['start_time'];
                if($tutor['tea_id'] == 0){
                    $arr['tutor']['tea_name'] = '';
                }else {
                    if($tutor['tea_type'] == 1){
                        $tea_info = $model_mcteacher->getCacheTeacherBySid($tutor['tea_id']);
                    }else{
                        $tea_info = $service_teacher->getTeacherInfoById($tutor['tea_id'], false);
                    }
                    if ($tea_info['real_name']) {
                        $arr['tutor']['tea_name'] = $tea_info['real_name'];
                    } else {
                        if ($tea_info['nick_name']) {
                            $arr['tutor']['tea_name'] = $tea_info['nick_name'];
                        } else {
                            $arr['tutor']['tea_name'] = 'ID为' . $tutor['tea_id'] . '的老师不存在';
                        }
                    }
                }
                $arr['tutor']['course_type'] = $appoint['course_cate_label'];
                $arr['tutor']['course_name'] = $tutor['course_name'];
                $arr['tutor']['class_name'] = $tutor['class_name'];
                $arr['tutor']['client'] = '51talkAC';//班课只有AC
                $book = $model_apicourse->getCourseInfo($tutor['book_id'])['course_info'];
                if ($book) {
                    $arr['tutor']['book'] = $book;
                } else {
                    $arr['tutor']['book'] = "javascript:alert('教材路径未填写！')";
                }
                $arr['tutor']['lesson_name'] = $tutor['title'];
                switch ($tutor['status']) {
                    case 4:
                        $stu_status = '缺席';
                        break;
                    case 7:
                        $stu_status = '退费';
                        break;
                    default:
                        $stu_status = '正常';
                        break;
                }
                $arr['tutor']['stu_status'] = $stu_status;
                if($tutor['status'] == 7) {
                    $class_status = '退费';
                    $arr['tutor']['is_over'] = 1;
                }elseif($tutor['status'] == 10){
                    $class_status = '老师缺席';
                    $arr['tutor']['is_over'] = 1;
                }else {
                    if (time() >= strtotime($tutor['end_time'])) {
                        $class_status = '已结束';//已结束
                        $arr['tutor']['is_over'] = 1;
                    } else {
                        $class_status = '未开始或未结束';
                        $arr['tutor']['is_over'] = 0;
                    }
                }
                $arr['tutor']['class_status'] = $class_status;
            }
        }
        //班课列表
        $appoint_list[$appoint['course_id']][] = $arr;
    }
}
if($more) {
    $MyPage->initNew($appoint_result['total'], $PageId, $PageSize);
}
$TPL->assign('stu_id', $stu_id);
$TPL->assign('more', $more);
$TPL->assign('blank', $blank);
$TPL->assign('course_type', $course_type);
$TPL->assign('course_id', $get_course_id);
$TPL->assign('course_list', $course_list);
$TPL->assign('appoint_list', $appoint_list);
if($more) {
    $TPL->assign('PageId', $PageId);
    $TPL->assign("Page", $MyPage->Show());
}
$TPL->display("StyleDefault/admin/user/muti_stu_class.html");