<?php
class Excel
{

    //导出excel方法Auth:li.peixin
    public static function dumpExcel($data, $title, $name='dataxls',$old = false){
        if($old){
            //不用PHPExcel
            $xls[] = "<html><meta http-equiv=content-type content=\"text/html; charset=UTF-8\"><body><table border='1'>";
            $xls[] = "<tr><td>" . implode("</td><td>", array_keys($title)) . '</td></tr>';//字段
            $xls[] = "<tr><td>" . implode("</td><td>", array_values($title)) . '</td></tr>';//文字表头
            foreach ($data As $o) {
                $line = array();
                foreach ($title AS $k => $v) {
                    $line[] = isset($o[$k])?$o[$k]:'';
                }
                $xls[] = '<tr><td>' . implode("</td><td>", $line) . '</td></tr>';
            }
            $xls[] = '</table></body></html>';
            $xls = join("\r\n", $xls);
            header('Content-Disposition: attachment; filename="' . $name . '.xls"');
            die(mb_convert_encoding($xls, 'UTF-8', 'UTF-8'));
        }else{
            //用PHPExcel
            $word = self::produceWord(count($title));
            $objPHPExcel = new \PHPExcel();
            //处理表头
            $i=0;
            foreach ($title as $k=>$t){
                $width = 20;
                if(is_array($t)){
                    $width = $t['width'];
                    $t = $t['title'];
                }
                $objPHPExcel->getActiveSheet()->setCellValue($word[$i].'1',$k);//字段
                $objPHPExcel->getActiveSheet()->setCellValue($word[$i].'2',$t);//文字表头
                $objPHPExcel->getActiveSheet()->getColumnDimension($word[$i])->setWidth($width);//设置列宽
                //$objPHPExcel->getActiveSheet()->getStyle($word[$i].'2')->getAlignment()->setWrapText(true);//是否换行
                $i++;
            }
            //处理数据
            foreach ($data as $k=>$v){
                $i=0;
                foreach ($v as $cell){
                    //$objPHPExcel->getActiveSheet()->setCellValue($word[$i].($k+3),$cell);//数据从第三行算起
                    $objPHPExcel->getActiveSheet()->setCellValueExplicit($word[$i].($k+3), strval($cell),\PHPExcel_Cell_DataType::TYPE_STRING);//数据从第三行算起（转换为字符串型）
                    $i++;
                }
            }
            $save = false;
            if($save) {
                //建立临时文件夹
                $dir = 'runtime/phpexcel/export';
                if(!file_exists(ROOT_PATH.'/'.$dir)){
                    mkdir(ROOT_PATH.'/'.$dir);
                }
                $path = $dir.'/'.$name.'.xlsx';
                //保存并输出路径
                $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
                $objWriter->save(ROOT_PATH.'/'.$path);
                $url = 'http://'.$_SERVER['HTTP_HOST'].'/'.$path;
                header('location:'.$url);
            }else {
                //直接输出
                header("Pragma: public");
                header("Expires: 0");
                header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
                header("Content-Type:application/force-download");
                header("Content-Type:application/vnd.ms-execl");
                header("Content-Type:application/octet-stream");
                header("Content-Type:application/download");
                header('Content-Disposition:attachment;filename="' . $name . '.xlsx"');
                header("Content-Transfer-Encoding:binary");
                $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
                $objWriter->save('php://output');
            }
            exit;
        }
    }

    //生产表头字母（最多26+26*26=702个，A到ZZ）
    public static function produceWord($max = 78){
        $proto = range('A','Z');
        $word = $proto;
        $i = 26;
        foreach ($proto as $p){
            foreach ($proto as $t){
                $i++;
                $word[] = $p.$t;
                if($max == $i){
                    break 2;
                }
            }
        }
        return $word;
    }

    //获取Excel数据方法
    public static function getDataFromExcel($file,$fieldHash = []){
        if(!file_exists($file)){
            die('文件未找到！');
        }
        $objPHPExcel = \PHPExcel_IOFactory::load($file);
        $sheetData = $objPHPExcel->getActiveSheet()->toArray(null,true,true,true);
        if(!empty($fieldHash)) {
            foreach ($sheetData as $key => &$cell) {
                $new_cell = array();
                foreach ($cell as $k => $c) {
                    if (isset($fieldHash[$k])) {
                        $new_cell[$fieldHash[$k]] = isset($c)?$c:'';
                    }
                }
                $cell = $new_cell;
            }
        }
        return $sheetData;
    }

}
