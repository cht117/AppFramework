<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * 通过比分和盘口判断指数分布
 * 用于比分直播指数标识
 * 
 * Type:     function<br>
 * Name:     exponent<br>
 * @author   hanyf@okooo.net
 * @param float
 * @return string
 */
function smarty_function_exponent($params, &$smarty)
{
    $str = '';
    $params['homeFT'] = trim($params['homeFT']);
    $params['awayFT'] = trim($params['awayFT']);
    if(is_array($params['exponent'])){
        if($params['homeFT'] === '' && $params['awayFT'] === '' || $params['cnResult'] == '延期'){
            foreach($params['exponent'] as $key => $val){
                //竞彩赋值让球胜平负赔率
                if($params['wdlExp'][$key]){
                    $str .= "<span data-num={$params['wdlExp'][$key]}>".$val.'</span>';
                }else{
                    $str .= '<span>'.$val.'</span>';
                }
            }
        }else{
            $goal = $params['homeFT'] - $params['handicapNumber'];  //默认让球胜平负
            if ($goal > $params['awayFT']) {
                $k = 0;
            }elseif ($goal < $params['awayFT']) {
                $k = 2;
            }else {
                $k = 1;
            }
            foreach($params['exponent'] as $key => $val){
                $val = $val === '0.00'? '':$val;
                //附加让球胜平负赔率
                $extraDom = '';
                if($params['wdlExp'][$key]){
                    $extraDom = ' data-num="'.$params['wdlExp'][$key].'"';
                }
				//兼容竞彩非让球玩法没有赔率，让球赔率色块不显示的问题
				if($val === '' ) {
					$str .= "<span{$extraDom}>".$val.'</span>';
					continue;
				}
                //判断首指、次指、末指
                $num = 0;
                foreach($params['exponent'] as $index => $value){
                    if($index != $k){
                        if($value >= $params['exponent'][$k]) $num++;
                    }
                }
                if($key == $k){
                    if($num ==2){       //首指
                        $str .= "<span class='blueblock'{$extraDom}>".$val.'</span>';
                    }elseif($num ==1){  //次指
                        $str .= "<span class='yellowblock'{$extraDom}>".$val.'</span>';
                    }else{              //末指
                        $str .= "<span class='redblock'{$extraDom}>".$val.'</span>';
                    }
                }else{
                    $str .= "<span{$extraDom}>".$val.'</span>';
                }
            }
        }
    }
	return iconv('utf-8','gbk',$str);
}

/* vim: set expandtab: */

?>
