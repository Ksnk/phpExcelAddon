<?php

namespace Ksnk\phpExcelAddon;

use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Штука такая, для вывода Excel c шаблоной разметкой
 * Class ExcelAddon
 */
class ExcelAddon
{

    var $result=[], $styles=[];

    /**
     * Копировать строки
     * @param $sheet
     * @param $srcRow
     * @param $dstRow
     * @param $height
     * @param $width
     */
    static function copyRows($sheet, $srcRow, $dstRow, $height, $width)
    {
        //$objPHPExcel->getActiveSheet()->insertNewRowBefore(2,10);
        if($height<=1) return ;
        $sheet->insertNewRowBefore($srcRow + 1, $height-1);
        // copied cell info
        /*
        $info=[];
        for ($col = 0; $col < $width; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $srcRow );
            $info[$col]=[
                'value'=>$cell->getValue(),
                'style'=>$sheet->getStyleByColumnAndRow($col, $srcRow )
            ];
        }
        $h = $sheet->getRowDimension($srcRow )->getRowHeight();
        */
        /*
                $data=$sheet->rangeToArray('A'.$srcRow.':'.PHPExcel_Cell::stringFromColumnIndex($width).$srcRow);
                $sheet->fromArray($data,null,'A'.$dstRow);
                $sheet->duplicateStyle($sheet->getStyle('A'.$srcRow),'A'.$dstRow.':A'.($dstRow+1));
        */
        for ($row = 0; $row < $height-1; $row++) {
            for ($col = 0; $col < $width; $col++) {
                $cell = $sheet->getCellByColumnAndRow($col, $srcRow + $row);
                $style = $sheet->getStyleByColumnAndRow($col, $srcRow + $row);
                $dstCell = PHPExcel_Cell::stringFromColumnIndex($col) . (string)($dstRow + $row);
                $sheet->setCellValue($dstCell, $cell->getValue());
                $sheet->duplicateStyle($sheet->getStyleByColumnAndRow($col, $srcRow ), $dstCell);
            }
            $h = $sheet->getRowDimension($srcRow )->getRowHeight();
            $sheet->getRowDimension($dstRow + $row)->setRowHeight($h);
        }

        foreach ($sheet->getMergeCells() as $mergeCell) {
            $mc = explode(":", $mergeCell);
            $col_s = preg_replace("/[0-9]*/", "", $mc[0]);
            $col_e = preg_replace("/[0-9]*/", "", $mc[1]);
            $row_s = ((int)preg_replace("/[A-Z]*/", "", $mc[0])) - $srcRow;
            $row_e = ((int)preg_replace("/[A-Z]*/", "", $mc[1])) - $srcRow;

            if (0 <= $row_s && $row_s < $height) {
                for ($row = 0; $row < $height-1; $row++) {
                    $merge = $col_s . (string)($dstRow + $row_s+$row) . ":" . $col_e . (string)($dstRow + $row_e+$row);
                    $sheet->mergeCells($merge);
                }
            }
        }

    }

    function setResult($i,$value, $o){
        if(!isset($this->result[$i[0]]))
            $this->result[$i[0]]=[];
        $this->result[$i[0]][$i[1]]=[$value,$o];
    }

    public static function convert($file, $data = array(), $outfile = '')
    {
        $self=new self();
        $templater = new templater($data, '\\'.__NAMESPACE__.'\\filterClass');
        ob_start();
        try {
            $reader = IOFactory::createReaderForFile($file);
            // $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file);
            $dataArray = $spreadsheet->getActiveSheet()->toArray();
            for($ri=0, $rri=0; $ri<count($dataArray); $ri++, $rri++)
            for($ci=0, $rci=0; $ci<count($dataArray[$ri]); $ci++, $rci++){
                if(!isset($dataArray[$ri][$ci])) continue;
                $val=$dataArray[$ri][$ci];
                if ((substr($val, 0, 1) === '=') && (strlen($val) > 1)) {
                    $val = $spreadsheet
                        ->getActiveSheet()
                        ->getCellByColumnAndRow($ci, $ri)
                        ->getCalculatedValue();
                }
                if ($val == '') continue;
                try {
                    $modified=false;
                    $val = preg_replace_callback('/{{(.*?)}}/', function ($a) use (&$modified,$templater, $ri,$ci) {
                        $modified=true;
                        $pos=[0,$ri,$ci];
                        return $templater->parce($a[1], $pos);
                    }, $val);
                    if(!$modified) continue;
                } catch (\Exception $e) {
                    $val = $e->getMessage() . ' -- ' . $val;
                }
                $self->setResult([$rri, $rci], $val,[$ri,$ci]);
            }
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}

