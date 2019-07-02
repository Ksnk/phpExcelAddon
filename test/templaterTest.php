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
    public function test3()
    {
        $data=['hello'=>'just to say hello', 'item'=>[1,2,3,4,5]];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals('пять рублей 00 копеек',$templater->parce('for item ; item ; endfor'));
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
    }

    public function test0()
    {
        $data=[];
        $templater=new templater($data,'\Ksnk\phpExcelAddon\filterClass');
        $this->assertEquals(2,$templater->parce('1+1'));
    }
}
