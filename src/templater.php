<?php
/**
 * простой шаблонизатор без особой логики
 */

namespace Ksnk\phpExcelAddon;

class templater
{

    var $scoups = [];
    var $scoupNames = [];
    var $class = 's_func';

    var $ops_prio = [
        '(' => 0,
        ')' => -1000,
        '+' => 6,
        '-' => 6,
        '>' => 5,
        '<' => 5,
        '>=' => 5,
        '<=' => 5,
        '==' => 5,
        '=' => 1,
        ';' => 0,
        '*' => 7,
        '**' => 8,
        '/' => 7,
        '.' => 9,
        ',' => 3,
        '|' => 4,
        '[' => 2,
        ']' => 2,
        '~' => 1,
        'for' => 0,
        'endfor' => 0,
        'as' => 2,
        'if' => 0,
        'else' => 0,
        'elseif' => 0,
        'endif' => 0,
    ];

    var $unop=[
        '-'=>10,
        '~'=>1,
    ];

    var $mode='',
        $skip=[];

    private
        /** набор лексем после парсинга */
        $conditions=[],
        $cid=0;

    /**
     * templater constructor.
     * @param null $data
     * @param string $class
     * @param callable $handler
     */
    function __construct(&$data = null, $class='', $handler = null)
    {
        $this->addScope('', ['_data' => &$data]);
        if(!empty($class) && class_exists($class)) {
            $this->class=new $class($this);
        }
    }

    private function addScope($name, $params = [])
    {
        array_unshift($this->scoupNames, $name);
        if (isset($this->scoups[$name])) {
            $this->scoups[$name] = array_merge($this->scoups[$name], $params);
        } else {
            $this->scoups[$name] = $params;
        }
    }

    /**
     * парсинг выражения
     * @param $line
     * @param null $posX
     * @return bool|mixed
     * @throws \Exception
     */
    public function parce($line, $posX = null)
    {
        // лексический разбор
        $pos = 0;
        $this->_posx=$posX;

        $this->pushop('(');
        // однобуквенные знаки препинания
        static $reg;
        if(!isset($reg)) {
            $opr = '';
            $mop = [];
            $uop = '';
            foreach ($this->ops_prio as $k => $v) {
                if (strlen($k) == 1)
                    $opr .= $k;
                else
                    $mop[] = preg_quote($k, '/');
            }
            foreach ($this->unop as $k => $v) {
                if (strlen($k) == 1)
                    $uop .= $k;
            }
            $reg = '/\s*(?:'
                . '([' . preg_quote($uop, '/') . '])' // 1
                . '|(' . implode('|', $mop) . ')' // 2
                . '|([' . preg_quote($opr, '/') . '])' // 3
                . '|(["\'])((?:[^\\4\\\\]|\\\\.)*?)\\4'  //4,5
                . '|(\d\w*)' //6
                . '|(\w+)\s*(\(|)' //7,8
                . ')()/';
        }
        $commamode = '';
        $place = true; // 1 - операнд  - 0 оператор
        $isfilter = false;//Часть фильтра?
        while (preg_match($reg,$line, $m, PREG_OFFSET_CAPTURE, $pos)) {
            $pos = $m[9][1];
            if (!empty($m[1][0])) {
                if($place) { // унарная операция. стоит на месте операнда
                    $this->pushop($m[1][0].'_u',$this->unop[$m[1][0]]);
                   //$this->pushoperand($m[1][0], 'call');
                    $place = false;
                } elseif(isset($this->ops_prio[$m[1][0]])) { // бинарная операция. стоит на месте операнда
                    $this->pushop($m[1][0]);
                }
            } elseif (!empty($m[2][0])) {
                if($m[2][0]=='for') {
                    $this->pushop('for');
                    $commamode='id';
                    $place = false;
                } elseif($m[2][0]=='if') {
                    $this->pushop('if');
                    $place = false;
                } elseif($m[2][0]=='elseif') {
                    $this->pushop('elseif');
                    $place = false;
                } elseif($m[2][0]=='else') {
                    $this->pushop('else');
                    $place = false;
                } else
                    $this->pushop($m[2][0]);
            } elseif (!empty($m[3][0])) {
                if ($place) {
                    // операция на месте операнда
                    if ($m[3][0] == '[') {
                        $this->pushop('for');
                    } else if ($m[3][0] == ']') {
                        $this->pushop('endfor');
                    } else if ($m[3][0] == ';') {
                        $this->pushop(';');
                    } else if ($m[3][0] == ')') {
                        $this->pushop(')');
                    } else if ($m[3][0] == '(') {
                        $this->pushop('(');
                    } else {
                        $this->pushoperand($m[3][0], 'call');
                    }
                    $place = false;
                } else {
                    if ($m[3][0] == ']')
                        $place = true;
                    else if ($m[3][0] == '|') {
                        $isfilter = true;
                    }
                    $this->pushop($m[3][0]);
                }
            } elseif (!empty($m[5][0])) {
                $this->pushoperand(str_replace(
                    '\\' . $m[4][0], $m[4][0], $m[5][0]
                ), 'literal');
            } elseif ($m[6][0] !== '') {
                $this->pushoperand($m[6][0], 'literal');
            } elseif (!empty($m[7][0])) {
                if (!empty($m[8][0]) && $m[8][0] == '(') {
                    if (!$isfilter) {
                        $this->pushoperand($m[7][0], 'literal');
                        $this->pushop('_|');
                    } else {
                        $this->pushoperand($m[7][0], 'call');
                    }
                    $this->pushop('(');
                    $place = false;
                } else {
                    $this->pushoperand($m[7][0], 'id');
                }
            }
            $place = !$place;
        }
        $this->pushop(')');

        $echo_result='';

        // синтаксический разбор + калькуляция
        $ops = [];
        $result = $this->calculate(function ($op, &$param, $eval, &$id) use (&$condition, &$ops,$commamode, &$echo_result) {
            if($this->mode=='inactive'){
                if(!in_array($op[1],$this->skip[0][0])) {
                    return ;
                } else {
                    $x=array_shift($this->skip);
                    $this->mode=$x[1];
                    if($this->mode=='inactive')
                        return;
                }
            }
            $currentPrio = $op[2];
            while (count($ops) > 0 && $ops[0][1] != '(' && $op[1] != '(' && ($currentPrio < $ops[0][2]
                    || ($currentPrio == $ops[0][2] && $currentPrio<10) // финт ушами для обхода унарных операций
                )) {
                //$exec(array_shift($ops), $result, $eval);
                $_op = array_shift($ops);
                switch ($_op[1]) {
                    case 'if':
                        $a=$eval(array_shift($param));
                        $data=['_data'=>[]];
                        $data['_if']=$a;
                        $this->addScope('if', $data);
                        if(!$a){
                            array_unshift($this->skip,[['endif','else','elseif'],$this->mode]);
                            $data=['_data'=>[]];
                            $data['_if']=$a;
                            $this->addScope('if', $data);
                        }
                        break;
                    case 'endif':
                        $a=$eval(array_shift($param));
                        $data=['_data'=>[]];
                        $data['_if']=$a;
                        $this->addScope('if', $data);
                        if(!$a){
                            array_unshift($this->skip,[['endif','else','elseif'],$this->mode]);
                            $data=['_data'=>[]];
                            $data['_if']=$a;
                            $this->addScope('if', $data);
                        }
                        break;
                    case 'as':
                        if (count($param) > 1) {
                            //
                            $a = $eval(array_shift($param), 'id');
                            $b = &$param[0];
                            if ($b[1] == ',') {
                                unset($b);
                                array_shift($param);
                                $b = &$param[0];
                                $b = $eval($b, 'id');
                                $b[1] = $this->makearray($b[1], $a[1]);
                            } else {
                                array_unshift($param, $a);
                            }
                            unset($b);
                        } else {
                            // цикл по всем данным
                            $this->error('Что то пошло не так ' . $param[1]);
                        }
                        break;
                    case 'for': // начало цикла
                        $a = '';
                        if (count($param) > 1) {
                            //
                            $b = $eval(array_shift($param), 'id');
                            $items = $b[1];
                            $a = $eval(array_shift($param), 'id');
                        } elseif (count($param) == 1) {
                            //
                            $a = $eval(array_shift($param), 'id');
                            $items = $a[1];
                        } else {
                            $this->error('int: некорректное описание for');
                        }
                        if (is_array($items)){
                            $name = $items[1];
                            $key=$items[0];
                        } else {
                            $name = $items;
                            $key='_index';
                        }
                        if (!isset($this->scoups[$name])) {
                            $data=['_data'=>[]
                                ,'_index'=>1
                                ,'_key'=>$key
                                ,'_goback'=>$id
                            ];
                            $data['_loop']=& $this->findByName($a[1]);
                            reset($data['_loop']);
                            $data['_data'][$name]=current($data['_loop']);
                            $data['_data'][$key]=key($data['_loop']);
                            $this->addScope($name, $data);
                        }
                        break;
                    case 'endfor': // конец цикла
                        if(isset($this->scoupNames[0])) {
                            $name = $this->scoupNames[0];
                        } else {
                            $name = $this->scoupNames[0];
                        }
                        $scoup =& $this->scoups[$name];
                        $scoup['_index']++;
                        if (false===next($scoup['_loop'])) {
                            $this->removeScope($name);
                        } else {
                            $scoup['_data'][$name]=current($scoup['_loop']);
                            $key=$scoup['_key'];
                            $scoup['_data'][$key]=key($scoup['_loop']);
                            $id=$scoup['_goback'];
                        }
                        break;
                    case ';': // 2 параметра берем и делаем из них массив
                        //  сбросить стек операций до скобки
                        while (count($ops)>0 && $ops[0][1]!='(') array_shift($ops);
                        if (count($ops)>0) {
                            $cnt = $ops[0][4];
                            if ($cnt < count($param) - 1) {
                                $a = array_shift($param);
                                while (count($param) > $cnt) array_shift($param);
                                array_unshift($param, $a);
                            }
                        }
                        break;
                    case ',': // 2 параметра берем и делаем из них массив
                        $a = array_shift($param);
                        $b = &$param[0];
                        $b = $eval($b,$commamode);
                        $a = $eval($a,$commamode);
                        $b[1] = $this->makearray($b[1], $a[1]);
                        unset($b);
                        break;
                    case '.': // 2 параметра берем и делаем из них массив
                        $a = array_shift($param);
                        $b = &$param[0];
                        $b = $eval($b, 'id');
                        $a = $eval($a, 'id');
                        $b[1] = $this->makearray($b[1], $a[1]);
                        unset($b);
                        break;
                    case ']':
                        //$a = '';
                        break;
                    case '[':
                        $a = array_shift($param);
                        $b = &$param[0];
                        $b = $eval($b, 'id');
                        $a = $eval($a);
                        $b[1] = $this->makearray($b[1], $a[1]);
                        unset($b);
                        break;
                    case '_|':
                        if (count($param) > 1) {
                            $a = $eval(array_shift($param));
                        } else {
                            $a = [1, []];
                        }
                        $b = &$param[0];
                        $param[0] = $this->_call($b[1], $this->makearray($a[1]));
                        break;
                    case '|':
                        $p = [];
                        $a = array_shift($param);
                        $b = &$param[0];
                        if ($b[2] == 'call') {
                            $a = $eval($a);
                            $p = $a[1];
                            $a = array_shift($param);
                            $b = &$param[0];
                        }
                        if ($a[1] == 'index') {
                            $b = $eval($b, 'id');
                        } else {
                            $b = $eval($b);
                        }
                        $param[0] = $this->_call($a[1], $this->makearray($b[1], $p));
                        break;
                    case '~_u': // унарная операция
                        if (count($param) > 0) {
                            $a = $eval(array_shift($param));
                            $echo_result .= $a[1];
                        }
                        break;
                    case '-_u': // унарная операция
                        if (count($param) > 0) {
                            $a = &$param[0];
                            $a = $eval($a);
                            $a[1]=0-$a[1];
                            unset($a);
                        }
                        break;
                    default:
                        if(isset($this->ops_prio[$_op[1]])){
                            $a = array_shift($param);
                            $b = &$param[0];
                            $b = $eval($b);
                            $a = $eval($a);
                            switch($_op[1]) {
                                case '-': $b[1] = $b[1]-$a[1]; break;
                                case '+': $b[1] = $b[1]+$a[1]; break;
                                case '/': $b[1] = $b[1]/$a[1]; break;
                                case '**': $b[1] = $b[1]**$a[1]; break;
                                case '>': $b[1] = $b[1]>$a[1]; break;
                                case '<': $b[1] = $b[1]<$a[1]; break;
                                case '>=': $b[1] = $b[1]>=$a[1]; break;
                                case '<=': $b[1] = $b[1]<=$a[1]; break;
                                case '==': $b[1] = $b[1]==$a[1]; break;
                            }
                            unset($b);
                        } else {
                            if (count($param) > 1) {
                                $a = array_shift($param);
                                if ($a[0] != 2) $a = [$a];
                                else unset($a[0]);
                                $b = $param[0];
                                if ($b[0] != 2) $b = [$b];
                                else unset($b[0]);
                                $param[0] = array_merge([2], $a, $b, [[0, $_op[1]]]);
                            }
                        }
                }
            }
            if ($op[1] == ')') {
                array_shift($ops); // asset =='('
            } else {
                if ($op[1] == '(') {
                    $op[4] = count($param);
                }
                array_unshift($ops, $op);
            }
        }, function ($a, $type = '') {
            if (isset($a[2])) switch ($a[2]) {
                case 'call':
                    $this->error('Невозможно выполнить ' . $a[1]);
                    break;
                case 'id':
                    if ($type == 'id')
                        return $a;
                    else {
                        $val = $this->findByName($a[1]);
                        return [1, $val, 'literal'];
                    }
                    break;
                default:
                    if ($type == 'id') {
                        $this->error('Невозможно приведение типов');
                    }
            }
            return $a;
        });
        if (is_array($result)) {
            if(is_array($result[1])) {
                $this->error('Не строка');
                return '';
            }
            return $echo_result.$result[1];
        } else
            return $echo_result.$result;
    }

    private function _goto($x,$y){

    }

    private function pushop($operation, $prio=null)
    {
        $this->conditions[] =
            [0, $operation, is_null($prio)?$this->ops_prio[$operation]:$prio, $this->_posx];
    }

    private function pushoperand($operand, $type)
    {
        $this->conditions[] = [1, $operand, $type, $this->_posx];
    }

    /**
     * @param callable $exec
     * @param callable $eval
     * @return bool|mixed
     */
    protected function calculate($exec, $eval = null)
    {
        if (is_null($eval)) $eval = function ($op) {
            return $op;
        };
        if (!empty($this->conditions)) {
            $result = [];
            while($this->cid<count($this->conditions)){
                $cond=$this->conditions[$this->cid];
                if ($cond[0] === 0) {
                    $exec($cond, $result, $eval, $this->cid);
                } else {
                    array_unshift($result, $cond);
                }
                $this->cid++;
            }
            while (count($result) > 1) {
                $eval(array_pop($result));
            }
            if (count($result) == 0)
                return '';
            else
                return $eval($result[0]);
        }
        return true;
    }

    // добавить область видимости

    /**
     * Найти в данных, с определенными сейчас scoup значение переменной $a
     * @param $a - массив из кусочков имени
     * @param int $skip - не проверять в самых верхних областях видимости
     * @return array|string - адрес переменной, соотвествующей чемунадо
     */
    private function &findByName($a, $skip = 0)
    {
        if (!is_array($a)) $a = array($a);
        $val = '';
        foreach ($this->scoupNames as $sidx => $n) {
            if ($sidx < $skip) continue;
            $v =& $this->scoups[$n]['_data'];
            $found = true;
            foreach ($a as $idx => $aa) {
                if (isset($v[$aa])) {
                    $v = &$v[$aa];
                } else if ($aa == $n && $idx == 0) {
                    continue;
                } else {
                    unset($val);
                    $found = false;
                    break;
                }
            }
            if ($found) {
                return $v;
            };
        }
        return $val;
    }

    // убрать область видимости

    private function removeScope($name)
    {
        $this->scoupNames = array_values(array_diff($this->scoupNames, [$name]));
        unset($this->scoups[$name]);
    }

    private function makearray($a, $b = [])
    {
        if (!is_array($b)) $b = array($b);
        if (!is_array($a)) $a = array($a);
        if (count($a) == 0) return $b;
        if (count($b) == 0) return $a;
        return array_merge($a, $b);
    }

    /**
     * вызвать функцию с параметрами
     * @param $l
     * @param $param
     * @return array
     * @throws \Exception
     */
    private function _call($l, $param)
    {
        if (method_exists($this->class, $l)) {
            $val = call_user_func_array(array($this->class, $l), $param);
        } else if (function_exists($l)) {
            if (count($param) == 0)
                $val = call_user_func($l);
            else
                $val = call_user_func_array($l, [$param]);
        } else {
            $this->error('Несуществующий фильтр - ' . $l);
            $val = $param[0];
        }
        return [1, $val, 'literal'];
    }

    /**
     * @param $msg
     * @throws \Exception
     */
    protected function error($msg)
    {
        throw new \Exception($msg);
    }

}

