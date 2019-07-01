<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 22.02.2019
 * Time: 11:38
 */
//define('INDEX_DIR', realpath('..'));

include_once '../vendor/autoload.php';

$data=json_decode(file_get_contents('data.json'), true);
$title='x.xlsx';
if(isset($_GET['tpl'])){
    $tname=$_GET['tpl'].'.xlsx';
}
if(!isset($tname) || !is_readable($tname)) {
    $tname='Schet001.tpl.xlsx';
}
$tname=realpath($tname);

header('Content-Disposition:attachment;filename='.$title);
header('Content-Type:application/vnd.ms-excel;charset=windows-1251');

\Ksnk\phpExcelAddon\ExcelAddon::convert( $tname, $data, 'php://output');
