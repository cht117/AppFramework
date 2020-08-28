<?php
class Helper_Util
{
    /**   
     * 来自 thinkPHP 的内置函数 auto_charset
     * @author liu21st@gmail.com
     * 
     * 自动转换字符集 支持数组转换
     * 需要 iconv 或者 mb_string 模块支持
     * 如果 输出字符集和模板字符集相同则不进行转换
     * @param string $fContents 需要转换的字符串
     * @return string
     */
    static function autoCharset($fContents,$from,$to)
    {
        if( strtoupper($from) === strtoupper($to) || empty($fContents) || (is_scalar($fContents) && !is_string($fContents)) )
        {
            //如果编码相同或者非字符串标量则不转换
            return $fContents;
        }
        
        if( is_string($fContents) ) 
        {
            if(function_exists('mb_convert_encoding'))
            {
                return mb_convert_encoding ($fContents, $to, $from);
            }
            elseif(function_exists('iconv'))
            {
                return iconv($from,$to,$fContents);
            }
            else
            {
                return $fContents;
            }
        }
        elseif(is_array($fContents))
        {
            foreach ( $fContents as $key => $val ) 
            {
                $_key = self::autoCharset($key,$from,$to);
                $fContents[$_key] = self::autoCharset($val,$from,$to);
                if($key != $_key ) 
                {
                    unset($fContents[$key]);
                }
            }
            return $fContents;
        }
        elseif(is_object($fContents)) 
        {
            $vars = get_object_vars($fContents);
            foreach($vars as $key => $val) 
            {
                $fContents->$key = self::autoCharset($val,$from,$to);
            }
            return $fContents;
        }
        else
        {
            return $fContents;
        }
    }


	/** 
     * 根据标准日期分离出年|月|日|时|分|秒
     *
     * @author ligh
     * @param 	string  $datetime:    标准日期格式     
     *
     * @return  array 
     */ 
    public function getYMDHIS($datetime)
    { 
		//首先校验日期格式
		//.....

		if(empty($datetime))
		{
			$datetime = date("Y-m-d H:i:s", time());	
		}

		$shijian = array();	

		$shijian['y'] = substr($datetime, 0, 4);
		$shijian['m'] = substr($datetime, 5, 2);
		$shijian['d'] = substr($datetime, 8, 2);
		$shijian['h'] = substr($datetime, 11, 2);
		$shijian['i'] = substr($datetime, 14, 2);
		$shijian['s'] = substr($datetime, 17, 2);

		return $shijian;
    }   

    /**
     * 根据周单位计算数组单元的差值
     *
     * @author ligh
     * 用法：
     * @code php
     * $rows = Helper_Util::diffArrayValueByWeek($start_time, $range)
     * @endcode
     *
     * @param array $arr 要计算单元差值的数组
     *
     * @return array 
     */
	static function diffArrayValueByWeek($start_time, $range)
	{
		$week_day = date('w', strtotime($start_time));
		
		foreach($range as $key => $value)
		{
			if($week_day == $value)
			{
				$position = $key;
				break;
			}
		}

		$m = array_slice($range, $position);
		$n = array_slice($range, 0, $position);

		$range = array_merge($m, $n);

		$total = count($range);
		
		if($total == 1)
		{
			$tmp = array(7);
		}
		else
		{
			foreach($range as $key => $value)
			{
				if($key == ($total-1))
				{
					if($range[$key] < $range[0])
					{
						$tmp[] = abs($range[$key] - $range[0]);
					}
					else
					{
						$tmp[] = abs($range[$key] - 7) + $range[0];
					}
				}
				else
				{
					$_key = $key;
					$left = $range[$_key];
					$right = $range[++$_key];
					
					if($left < $right)
					{
						$tmp[] = abs($right - $left);
					}
					else
					{
						$tmp[] = abs($left - 7) + $right;
					}
				}
			}
		}
		
		return $tmp;
	}

    /**
     * 根据日期计算星期几
     *
     * @author ligh
     * 用法：
     * @code php
     * $rows = Helper_Util::getWeekdayByDate("2010-11-15")
     * @endcode
     *
     * @param array $date	标准日期格式
     *
     * @return	int	
     */
	function getWeekdayByDate($date) 
	{
		$year = substr($date, 0, 4);
		$month = substr($date, 5, 2);
		$day = substr($date, 8, 2);

		if($month) $month = date("n", strtotime("2006-$month-12"));
		if($month == 1 || $month == 2) {$month += 12; $year -= 1;}
		if($day)   $day = date("j", strtotime("2006-7-$day"));
		
		# w=y+[y/4]+[c/4]-2c+[26(m+1)/10]+d-1
		$y = $year % 100;
		$c = floor($year / 100);
		$m = $month;
		$d = $day;
		$w = $y+ floor($y/4) + floor($c/4) -2*$c+ floor(26*($m+1)/10) +$d-1;
		$w = $w % 7;
		$weekdays = array(7, 1, 2, 3, 4, 5, 6);

		return $weekdays[abs($w)];
	}



    /**
	 * 查找一个字符$find第$n次出现在$haystack的位置
     *
     * 用法：
     * @code php
     * $rows = Helper_Util::findPosition("where are you from? from china", "from", 2)
     * @endcode
     *
     * @param array $date	标准日期格式
     *
     * @return	int	
     */
	function findPosition($haystack, $find, $n)
	{
		$haystack = '@'.$haystack;
		$j = $x = $y = 0;

		for($i = 0; $i < strlen($haystack); $i++)
		{
			if($index = strpos($haystack, $find, $y? ($y + 1):$y))
			{
				$j++;
				if($j == $n){
					$x = $index;
					break;
				}else{
					$y = $index;
				}
			}
		}

		return $x - 1;
	}

	/**
	 * 将数组转换成按照某种分隔符分隔的字符串
	 *
	 * @param  array	$input_array
	 * @param  string  	$seprator
	 *
	 * @return string
	 */
	function convertToSeparatorString($input_array, $seprator = ",")
	{
		$output = "";

		if(!is_array($input_array))
		{
			return $output;
		}

		foreach($input_array as $key => $value)
		{
			$output .= $value . $seprator;
		}

		$output = substr($output, 0, -1);

		return $output;
	}

	/**
	 * 根据某个字段从一个2维数组中格式化出一个1维数组: 仅针对2维数组
	 *
	 * @param  array   $input_array
	 * @param  string  $field
	 *
	 * @return array
	 */
	function getArrayByOneField($input_array, $field)
	{
		$output = array();
		//$depth = self::getArrayDepth($input_array);

		if(!is_array($input_array))
		{
			return $output;
		}

		foreach($input_array as $key => $value)
		{
			if(array_key_exists($field, $value))			
			{
				$output[] = $value[$field];
			}
		}
	
		return $output;	
	}

	/**
	 * 获取数组维度
	 *
	 * @param  array	$Array
	 * @param  int		$DepthCount
	 * @param  array	$DepthArray
	 *
	 * @return array 
	 */
	static public function getArrayDepth($Array,$DepthCount=-1,$DepthArray=array()) 
	{
		$DepthCount++;

		if (is_array($Array))
		{
			foreach ($Array as $Key => $Value)
			{
				$DepthArray[] = self::getArrayDepth($Value,$DepthCount);
			}
		}
		else
		{
			return $DepthCount;
		}

		foreach($DepthArray as $Value)
		{
			$Depth = $Value>$Depth? $Value:$Depth;
		}

		return $Depth;
	}
    
    /**
      * 提取字符串，支持中文。
      *
      * @param string  $strVar 变量名。
      * @param int     $Length 提取的长度。
      * @param booble  $Append 是否需要追加省略号false|true
      * @return string         提取的字符串。
      */
    function cutStr($StrVar,$Length,$Append = false)
    {
        if (strlen($StrVar) <= $Length )  //如果指定的长度超过了字符串本身的长度。
        {
            return $StrVar;
        }
        else
        {
            $I = 0;
            while ($I < $Length)
            {
                $strTMP = substr($StrVar,$I,1);  //首先从0开始取1,如果这个字符为英文，则$I加一，如果为中文，则加2
                if ( ord($strTMP) > 127 )
                {
                    $strTMP = substr($StrVar,$I,2);
                    $I = $I + 2;
                }
                else
                {
                    $I = $I + 1;
                }
                $strLast[] = $strTMP;               //通过数组来存储每次提取的结果。
            }
            $strLast = implode("",$strLast);         //将数组合并成字符串
            if($Append)
            {
                $strLast .= "...";
            }
            return $strLast;
        }
    }
   /**
    * 设置Header头缓存
    *
    * @param    int     $life_time  缓存时间
    */
	function setHeaderCache($life_time=600){
		@header("Cache-Control: max-age=$life_time ,must-revalidate");
    	@header('Pragma:');
    	@header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT' );
    	@header("Expires: " .gmdate ('D, d M Y H:i:s', time() + $life_time). ' GMT');
	}
   /**
    * 二维数组排序
    *
    * @param    array   $array      变量名。
    * @param    string  $sort_field 根据哪个字排序
    * @param    const   $type       排序类型
    */
    function arrayMultiSort(&$array,$sort_field,$type=SORT_DESC){
        if(empty($array) || !is_array($array)) return array();
        
        $sort = array();
        foreach($array as $key => $val){
            $sort[] = $val[$sort_field];
        }
        array_multisort($sort,$type,$array);
    }

    /**
     * 将指定的文本的每一行前面都增加一个自定义的UBB标签。
     *
     * @param  string   $Text      要处理的文本信息。
     * @param  string   $Tag       自定义标签，默认为[Space]
     * @return string              处理过的文本。
     */
    function addUbbTag($Text,$Tag="[Space]")
    {
        $Text = explode("\n",$Text);

        //在取得的每一行的文字前面加入$Tag标记。
        for($I = 0;$I <count($Text);$I ++){
            $Text[$I] = $Tag.$Text[$I];
        }
        return join("\n",$Text);
    }
    /**
     * 解析UBB代码。
     *
     * @author                     王春生 <wangcs@okooo.net>
     * @last                       王春生 <wangcs@okooo.net>
     * @param  text   $Text        要处理的文本信息。
     * @return text                处理过的文本。
     */
    function parseUBB($Text)
    {
        if (empty($Text)) return "";

        //定义自定义标签格式和替换格式。
        $Partern[] = "/\[b\](.*?)\[\/b\]/si";  //[B]Text[/B]
        $Partern[] = "/\[i\](.*?)\[\/i\]/si";  //[I]Text[/I]
        $Partern[] = "/\[u\](.*?)\[\/u\]/si";
        $Partern[] = "/\[p\](.*?)\[\/p\]/si";
        $Partern[] = "/\[code\](.*?)\[\/code\]/si";
        $Partern[] = "/\[quote\](.*?)\[\/quote\]/si";
        $Partern[] = "/\[color=(\S+?)\](.*?)\[\/color\]/si";
        $Partern[] = "/\[size=(\S+?)\](.*?)\[\/size\]/si";
        $Partern[] = "/\[font=(\S+?)\](.*?)\[\/font\]/si";
        $Partern[] = "/\[fly\](.*?)\[\/fly\]/si";
        $Partern[] = "/\[Space\]/si";
        $Partern[] = "/\[Left\](.*?)\[\/Left\]/si";
        $Partern[] = "/\[center\](.*?)\[\/center\]/si";
        $Partern[] = "/\[right\](.*?)\[\/right\]/si";
        $Partern[] = "/\[url\](http|https|ftp)(:\/\/\S+?)\[\/url\]/si";
        $Partern[] = "/\[url\](\S+?)\[\/url\]/si";
        $Partern[] = "/\[url=(http|https|ftp)(:\/\/\S+?)\](.*?)\[\/url\]/si";
        $Partern[] = "/\[url=(\S+?)\](\S+?)\[\/url\]/si";
        $Partern[] = "/\[email\](\S+?@\S+?\\.\S+?)\[\/email\]/si";
        $Partern[] = "/\[email=(\S+?)\](.*?)\[\/email\]/si";
        $Partern[] = "/\[img\](\S+?)\[\/img\]/si";

        $Replace[] = "<b>\\1</b>";
        $Replace[] = "<i>\\1</i>";
        $Replace[] = "<u>\\1</u>";
        $Replace[] = "<p>\\1</p>";
        $Replace[] = "<blockquote><pre>\\1</pre></blockquote>";
        $Replace[] = "<blockquote>\\1</blockquote>";
        $Replace[] = "<font color=\"\\1\">\\2</font>";
        $Replace[] = "<font size=\"\\1\">\\2</font>";
        $Replace[] = "<font face=\"\\1\">\\2</font>";
        $Replace[] = "<marquee behavior=alternate scrollamount=3 width=\"90%\">\\1</marquee>";
        $Replace[] = iconv('UTF-8','GBK',"");
        $Replace[] = '<span align="left">\\1</span>';
        $Replace[] = '<span align="center">\\1</span>';
        $Replace[] = '<span align="right">\\1</span>';
        $Replace[] = "<a href=\"\\1\\2\" target=\"_blank\">\\1\\2</a>";
        $Replace[] = "<a href=\"http://\\1\" target=\"_blank\">\\1</a>";
        $Replace[] = "<a href=\"\\1\\2\" target=\"_blank\">\\3</a>";
        $Replace[] = "<a href=\"http://\\1\" target=\"_blank\">\\2</a>";
        $Replace[] = "<a href=\"mailto:\\1\">\\1</a>";
        $Replace[] = "<a href=\"mailto:\\1\">\\2</a>";
        $Replace[] = '<img src="\\1" border="0" onload="if(this.width>screen.width-480) {this.width=screen.width-480;this.alt=\'Click Here to Open New Window\';}" onmouseover="if(this.alt) this.style.cursor=\'hand\';" onclick="if(this.alt) window.open(\'\\1\');">';
        return preg_replace($Partern,$Replace,self::addUbbTag($Text));
    }

	/**
	 * 对二维数组按照多项进行排序。
	 * 注意: 定义在NewOkCom_Vote::sysSortArray()里是不合适的,所以在本类重新定义
	 *
	 * KeyName和SortOrder、SortType可以为多个。
	 * sysSortArray($Array,"Key1","SORT_ASC","SORT_RETULAR","Key2"……)
	 * @author                      王春生 <wangcs@okooo.net>
	 * @param  array   $ArrayData   要进行排序的数组。
	 * @param  string  $KeyName1    进行排序的键名。
	 * @param  string  $SortOrder1  排序顺序，可选值为SORT_ASC|SORT_DESC 必须用引号引起来。
	 * @param  string  $SortType1   排序类型，可选值为SORT_REGULAR|SORT_NUMERIC|SORT_STRING，必须用引号引起来。
	 * @return array                经过排序的数组。
	 */
	function sysSortArray($ArrayData,$KeyName1,$SortOrder1 = "SORT_ASC",$SortType1 = "SORT_REGULAR")
	{
		if(!is_array($ArrayData))
		{
			return $ArrayData;
		}

		//获得函数参数个数。
		$ArgCount = func_num_args();

		//获得用于排序的KeyName列表。并将各个参数放到SortRule数组里面去，作为array_multisort()函数的参数。
		for($I = 1;$I < $ArgCount;$I ++)
		{
			$Arg = func_get_arg($I);
			if(!eregi("SORT",$Arg))
			{
				$KeyNameList[] = $Arg;
				$SortRule[]    = '$'.$Arg;
			}
			else
			{
				$SortRule[]    = $Arg;
			}
		}

		//依次取出各个KeyName对应的值，生成数组。
		foreach($ArrayData AS $Key => $Info)
		{
			foreach($KeyNameList AS $KeyName)
			{
				${$KeyName}[$Key] = $Info[$KeyName];
			}
		}

		//生成eval执行语句。
		$EvalString = 'array_multisort('.join(",",$SortRule).',$ArrayData);';
		@eval ($EvalString);
		return $ArrayData;
	}

	/**
	 * 将浮点数或0格式化成百分比
	 *
	 * @param  float|int	$float_number	浮点数 或 0
	 * @param  int			$precision		精确度
	 *
	 * @return  
	 */
	static	public	function fomatFloat2Percent($float_number, $precision = 3)
	{
		if(is_numeric($float_number))
		{
			$float_number = round($float_number, $precision);	
			$float_number = $float_number * 100 . "%";
		}

		return $float_number;
	}

    /** 
     * 格式化参数为数组类型
     *
     * @param  array    $input
     *
     * @return array 
     */
    static public function toArray(&$input)
    {   
        !is_array($input) && $input = array($input);
    }   

}
