<?php

namespace Ksnk\phpExcelAddon;

/**
 * Еще один шаблонизатор без логики, но с циклами.
 * Для Excel, наверное, тоже подойдет
 * User: Ksnk
 * Date: 15.12.15
 * Time: 1:54
 */

class filterClass
{
    /** @var templater */
    var $ahchorobj=null;

    function __construct($anchor=null)
    {
        $this->ahchorobj=$anchor;
    }

    /**
     * @param $val - значение
     * @return string
     */
    function index($val) //, $obj = '', $name = '')
    {
        if (!empty($this->ahchorobj) && isset($this->ahchorobj->scoups[$val])) {
            return $this->ahchorobj->scoups[$val]['_index'] + 1;
        }
        return '';
    }

    /**
     * Вывести с большой буквы. Для utf-8 - это превращается, превращается
     * @param $str
     * @return string
     */
    function capitalize($str)
    {
        return mb_strtoupper(mb_substr($str, 0, 1, 'utf-8'), 'utf-8')
            . mb_substr($str, 1, mb_strlen($str, 'utf-8'), 'utf-8');
    }

    /**
     * Вывести цену кошерненько с пробелами и рублями, без преносов...
     * @param $val
     * @param string $suf
     * @return mixed
     */
    function price($val, $suf = 'руб.')
    {
        return number_format($val, 2, '.', ' ') . ' ' . $suf;
    }

    /**
     * Вывести цену кошерненько с пробелами и рублями, без преносов...
     * @param $val
     * @return mixed
     */
    function nbsp($val)
    {
        return str_replace(' ', '&nbsp;', $val);
    }

    /**
     * Злые локализаторы завсегда юзера обидеть норовят, склонений не знают
     * @param $date
     * @return string
     */
    function rusdate($date)
    {
        if (is_string($date) && !ctype_digit($date)) {
            $date = strtotime($date);
        }
        return str_replace( //XXX: нужно проверить английские имена месяцев
                ['january', 'february', 'march', 'april', 'may', 'june', 'july',
                    'august', 'september', 'october', 'november', 'december'],
                ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля',
                    'августа', 'сентября', 'октября', 'ноября', 'декабря'],
                strtolower(date("j F Y", $date))) . ' г.';
    }

    /**
     * num2str - стырено с хабра. Долгой жизни товарищу runcore
     * @param $num
     * @return string
     */
    function num2str($num, $mode='rub')
    {
        $nul = 'ноль';
        $ten = array(
            ['', 'один', 'два', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
            ['', 'одна', 'две', 'три', 'четыре', 'пять', 'шесть', 'семь', 'восемь', 'девять'],
        );
        $a20 = array('десять', 'одиннадцать', 'двенадцать', 'тринадцать', 'четырнадцать', 'пятнадцать', 'шестнадцать', 'семнадцать', 'восемнадцать', 'девятнадцать');
        $tens = array(2 => 'двадцать', 'тридцать', 'сорок', 'пятьдесят', 'шестьдесят', 'семьдесят', 'восемьдесят', 'девяносто');
        $hundred = array('', 'сто', 'двести', 'триста', 'четыреста', 'пятьсот', 'шестьсот', 'семьсот', 'восемьсот', 'девятьсот');
        $unit = array( // Units
            array('копейка', 'копейки', 'копеек', 1),
            array('рубль', 'рубля', 'рублей', 0),
            array('тысяча', 'тысячи', 'тысяч', 1),
            array('миллион', 'миллиона', 'миллионов', 0),
            array('миллиард', 'милиарда', 'миллиардов', 0),
        );
        //
        list($rub, $kop) = explode('.', sprintf("%015.2f", floatval($num)));
        $out = array();
        if (intval($rub) > 0) {
            foreach (str_split($rub, 3) as $uk => $v) { // by 3 symbols
                if (!intval($v)) continue;
                $uk = sizeof($unit) - $uk - 1; // unit key
                $gender = $unit[$uk][3];
                list($i1, $i2, $i3) = array_map('intval', str_split($v, 1));
                // mega-logic
                $out[] = $hundred[$i1]; # 1xx-9xx
                if ($i2 > 1) $out[] = $tens[$i2] . ' ' . $ten[$gender][$i3]; # 20-99
                else $out[] = $i2 > 0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
                // units without rub & kop
                if ($uk > 1) $out[] = $this->morph($v, $unit[$uk][0], $unit[$uk][1], $unit[$uk][2]);
            } //foreach
        } else $out[] = $nul;
        if ($mode == 'rub') {
            $out[] = $this->morph(intval($rub), $unit[1][0], $unit[1][1], $unit[1][2]); // rub
            $out[] = $kop . ' ' . $this->morph($kop, $unit[0][0], $unit[0][1], $unit[0][2]); // kop
        }
        return trim(preg_replace('/ {2,}/', ' ', join(' ', $out)));
    }

    /**
     * Склоняем словоформу
     * @ author runcore
     * @param $n
     * @param $f1
     * @param $f2
     * @param string $f5
     * @return string
     */
    function morph($n, $f1, $f2, $f5 = '')
    {
        $n = abs(intval($n)) % 100;
        if ($n > 10 && $n < 20) return $f5;
        $n = $n % 10;
        if ($n > 1 && $n < 5) return $f2;
        if ($n == 1) return $f1;
        return $f5;
    }

    /**
     * значения по умолчанию
     * @param $n
     * @param $def
     * @return mixed
     */
    function def($n,$def){
        return empty($n)?$def:$n;
    }

    // $string - строка, которую нужно укоротить
    function trimtodot($string)
    {
        $pos = strrpos($string, '//'); // поиск позиции точки с конца строки todo: Че?
        if (!$pos) {
            return $string; // если точка не найдена - возвращаем строку
        }
        return substr($string, 0, $pos + 0); // обрезаем строку используя количество
        // символов до точки + 1 (сама точка,
        // если она не нужна "+1" нужно убрать)
    }// конец функции

    /**
     * Удалить разметку, по возможности оставив промежутки между параграфами и br
     * @param $string
     * @return string
     */
    function strip($string){

        return trim(html_entity_decode(preg_replace(
            '/[\t ]*\r?\n[\t ]*\r?\n([\t ]*\r?\n)+/m',PHP_EOL.PHP_EOL,
            strip_tags(preg_replace(
                ['~<br\s*/?>~' ]
                ,[PHP_EOL]
                ,$string)))));
    }

}