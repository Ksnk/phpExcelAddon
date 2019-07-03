<?php
/**
 * Created by PhpStorm.
 * User: s.koryakin
 * Date: 01.07.2019
 * Time: 18:14
 */

use Ksnk\phpExcelAddon\templater;

class templaterTest extends PHPUnit_Framework_TestCase
{
/*
    public function test8()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $excel=[['for item','if item!=3 ;~item ;endif','endfor']];
        $result=[];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass',
            function($reason, $param  //$val,$r,$c
                    ) use (&$result){
                switch($reason){
                    case 'setvalue':
                        break;
                    case 'loop':
                        break;
                }
                //if(!isset($result[$r]))$result[$r]=[];
                //$result[$r][$c]=$val;
            });
        foreach($excel as $ri=>$row)foreach($row as $ci=>$cell){
            $templater->parce($cell,[$ri,$ci]);
        }
        $this->assertEquals('0:1 1:2 2:3 3:4 4:5 ',$templater->parce('for item as k,i; ~k;~":";~i;~" "; endfor'));
    }

    public function test7()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('0:1 1:2 2:3 3:4 4:5 ',$templater->parce(
            'if 1<0 ; ~111; else ; ~222 ; endif'
        ));
    }
*/
    public function test6()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('0:1 1:2 2:3 3:4 4:5 ',$templater->parce('for item as k,i; ~k;~":";~i;~" "; endfor'));
    }


    public function test6_2()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('36',$templater->parce(
            '1+(1;2;~3;4;5)'
        ));
    }

    public function test6_1()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('4',$templater->parce(
            '5+(1-2)'
        ));
    }


    public function test5()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');

        $this->assertEquals(' 1 2345',
            $templater->parce('for item').' '.
            $templater->parce('~item').' '.
            $templater->parce('endfor')
        );
    }

    public function test4()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('12345',$templater->parce('for item ; ~item ; endfor'));
    }

    public function test3()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('1211',$templater->parce('~12; 11'));
    }

    public function test2_1()
    {
        $data=['item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        //$this->assertEquals('пять рублей 00 копеек',$templater->parce('item|count|num2str'));
        $this->assertEquals('1',$templater->parce('------------1'));// 12 минусов
        $this->assertEquals('-1',$templater->parce('-------------1'));// 13 минусов
        $this->assertEquals('1',$templater->parce('--1'));// 2 минуса
        $this->assertEquals('1',$templater->parce('2-1'));// 1 минус
        $this->assertEquals('1',$templater->parce('2+-1'));// 1 минус
    }

    public function test2()
    {
        $data=['item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('пять рублей 00 копеек',$templater->parce('item|count|num2str'));
        $this->assertEquals('пять',$templater->parce('item|count|num2str("simple")'));
    }

    public function test1()
    {
        $data=[];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals(4,$templater->parce('1+2/2+4/2'));
        $this->assertEquals(-1,$templater->parce('1+2**2/4-3'));
    }

    public function test0()
    {
        $data=[];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals(2,$templater->parce('1+1'));
    }
}
