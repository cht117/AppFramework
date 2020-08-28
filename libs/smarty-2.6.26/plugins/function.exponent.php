<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */


/**
 * ͨ���ȷֺ��̿��ж�ָ���ֲ�
 * ���ڱȷ�ֱ��ָ����ʶ
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
        if($params['homeFT'] === '' && $params['awayFT'] === '' || $params['cnResult'] == '����'){
            foreach($params['exponent'] as $key => $val){
                //���ʸ�ֵ����ʤƽ������
                if($params['wdlExp'][$key]){
                    $str .= "<span data-num={$params['wdlExp'][$key]}>".$val.'</span>';
                }else{
                    $str .= '<span>'.$val.'</span>';
                }
            }
        }else{
            $goal = $params['homeFT'] - $params['handicapNumber'];  //Ĭ������ʤƽ��
            if ($goal > $params['awayFT']) {
                $k = 0;
            }elseif ($goal < $params['awayFT']) {
                $k = 2;
            }else {
                $k = 1;
            }
            foreach($params['exponent'] as $key => $val){
                $val = $val === '0.00'? '':$val;
                //��������ʤƽ������
                $extraDom = '';
                if($params['wdlExp'][$key]){
                    $extraDom = ' data-num="'.$params['wdlExp'][$key].'"';
                }
				//���ݾ��ʷ������淨û�����ʣ���������ɫ�鲻��ʾ������
				if($val === '' ) {
					$str .= "<span{$extraDom}>".$val.'</span>';
					continue;
				}
                //�ж���ָ����ָ��ĩָ
                $num = 0;
                foreach($params['exponent'] as $index => $value){
                    if($index != $k){
                        if($value >= $params['exponent'][$k]) $num++;
                    }
                }
                if($key == $k){
                    if($num ==2){       //��ָ
                        $str .= "<span class='blueblock'{$extraDom}>".$val.'</span>';
                    }elseif($num ==1){  //��ָ
                        $str .= "<span class='yellowblock'{$extraDom}>".$val.'</span>';
                    }else{              //ĩָ
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
