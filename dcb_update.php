<?php
/**
 * Created by PhpStorm.
 * User: lipeixin
 * Date: 2017/9/15
 * Time: 10:43
 */
function getDcbHistory($current_code = 2017106){
    $file_url = 'http://kaijiang.zhcw.com/zhcw/html/ssq/list_';
    $last_code = 2003001;
    $page = 1;
    $stop = 0;

    $dcb_list = array();
    do {
        $dcb_history = file($file_url . $page . '.html');
        for ($i = 42; $i < count($dcb_history) - 20; $i += 30) {
            $code = intval(strip_tags($dcb_history[$i + 1]));
            if ($code == $current_code) {
                $stop = 1;
                break;
            }

            $dcb = array();
            $dcb['time'] = trim(strip_tags($dcb_history[$i]));//开奖日期
            $dcb['code'] = $code; //期号
            $ball_arr = array();
            for ($j = 4; $j <= 16; $j += 2) {
                $ball_arr[] = intval(strip_tags($dcb_history[$i + $j]));
            }
            $dcb['dcb'] = implode(',', $ball_arr);//中奖号码
            $dcb['sales'] = intval(str_replace(',', '', trim(strip_tags($dcb_history[$i + 17]))));//销售额
            $dcb['first'] = intval(strip_tags($dcb_history[$i + 18]));//一等奖
            $dcb['second'] = intval(strip_tags($dcb_history[$i + 21]));//二等奖
            $dcb_list[] = $dcb;

            if ($code == $last_code) {
                $stop = 1;
                break;
            }
        }
        $page++;
    } while ($stop == 0);
    return $dcb_list;
}
