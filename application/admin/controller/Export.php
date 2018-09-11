<?php
/**
 * Created by PhpStorm.
 * User: zhan
 * Date: 2017/12/15  10:51
 */
namespace app\admin\Controller;
use think\controller;
class Export extends controller
{
    /**
     * 导出excel格式
     * @createtime 2015-08-31
     * @param string $fileName 导出文件名字
     * @param array $headArr excel表格标题
     * @param array $data excel数据
     * @return file
     */
    function ImportExcel($fileName, $headArr = array(), $data = array())
    {
        vendor("Excel.PHPExcel");

        $fileName .= ".xlsx";
        //创建新的PHPExcel对象
        $objPHPExcel = new \PHPExcel();
        $objProps = $objPHPExcel->getProperties();

        //设置表头
        $key = ord("A");
        foreach ($headArr as $v) {
            $colum = chr($key);
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($colum . '1', $v);
            $key += 1;
        }

        $column = 2;
        $objActSheet = $objPHPExcel->getActiveSheet();
        foreach ($data as $key => $rows) { //行写入
            $span = ord("A");
            foreach ($rows as $keyName => $value) {// 列写入
                $j = chr($span);
                $objActSheet->setCellValue($j . $column, $value);
                $span++;
            }
            $column++;
        }

        $fileName = iconv("utf-8", "gb2312", $fileName);
        //重命名表
        $objPHPExcel->getActiveSheet()->setTitle('Simple');
        //设置活动单指数到第一个表,所以Excel打开这是第一个表
        $objPHPExcel->setActiveSheetIndex(0);
        //将输出重定向到一个客户端web浏览器(Excel2007)
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-type: application/vnd.ms-excel');
        header("Content-Disposition: attachment; filename=\"$fileName\"");
        header('Cache-Control: max-age=0');
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        /*$filePath = '/tmp/' . rand(0, getrandmax()) . rand(0, getrandmax()) . ".tmp";
        $objWriter->save($filePath);
        readfile($filePath);
        unlink($filePath);*/
        $objWriter->save('php://output'); //文件通过浏览器下载
        die;
    }
}