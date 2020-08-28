<?php
/**
 * 定义 Helper_Array 类
 *
 * @package helper
 */
/**
 * Helper_Array 类提供了一组简化数组操作的方法 
 *
 * @package helper
 */
abstract class Helper_Array
{
	/**
	 * 从数组中删除空白的元素（包括只有空白字符的元素）
	 *
	 * 用法：
	 * @code php
	 * $arr = array('', 'test', '   ');
	 * Helper_Array::removeEmpty($arr);
	 *
	 * dump($arr);
	 * // 输出结果中将只有 'test'
	 * @endcode
	 *
	 * @param array $arr 要处理的数组
	 * @param boolean $trim 是否对数组元素调用 trim 函数
	 */
	static function removeEmpty (& $arr, $trim = true)
	{
		foreach ($arr as $key => $value) {
			if (is_array($value)) {
				self::removeEmpty($arr[$key]);
			} else {
				$value = trim($value);
				if ($value == '') {
					unset($arr[$key]);
				} elseif ($trim) {
					$arr[$key] = $value;
				}
			}
		}
	}
	/**
	 * 从一个二维数组中返回指定键的所有值
	 *
	 * 用法：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1'),
	 * array('id' => 2, 'value' => '2-1'),
	 * );
	 * $values = Helper_Array::cols($rows, 'value');
	 *
	 * dump($values);
	 * // 输出结果为
	 * // array(
	 * //   '1-1',
	 * //   '2-1',
	 * // )
	 * @endcode
	 *
	 * @param array $arr 数据源
	 * @param string $col 要查询的键
	 *
	 * @return array 包含指定键所有值的数组
	 */
	static function getCols ($arr, $col)
	{
		$ret = array();
		foreach ($arr as $row) {
			if (isset($row[$col])) {
				$ret[] = $row[$col];
			}
		}
		return $ret;
	}
	/**
	 * 将一个二维数组转换为 HashMap，并返回结果
	 *
	 * 用法1：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1'),
	 * array('id' => 2, 'value' => '2-1'),
	 * );
	 * $hashmap = Helper_Array::hashMap($rows, 'id', 'value');
	 *
	 * dump($hashmap);
	 * // 输出结果为
	 * // array(
	 * //   1 => '1-1',
	 * //   2 => '2-1',
	 * // )
	 * @endcode
	 *
	 * 如果省略 $value_field 参数，则转换结果每一项为包含该项所有数据的数组。
	 *
	 * 用法2：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1'),
	 * array('id' => 2, 'value' => '2-1'),
	 * );
	 * $hashmap = Helper_Array::hashMap($rows, 'id');
	 *
	 * dump($hashmap);
	 * // 输出结果为
	 * // array(
	 * //   1 => array('id' => 1, 'value' => '1-1'),
	 * //   2 => array('id' => 2, 'value' => '2-1'),
	 * // )
	 * @endcode
	 *
	 * @param array $arr 数据源
	 * @param string $key_field 按照什么键的值进行转换
	 * @param string $value_field 对应的键值
	 *
	 * @return array 转换后的 HashMap 样式数组
	 */
	static function toHashmap ($arr, $key_field, $value_field = null)
	{
		$ret = array();
		if ($value_field) {
			foreach ($arr as $row) {
				$ret[$row[$key_field]] = $row[$value_field];
			}
		} else {
			foreach ($arr as $row) {
				$ret[$row[$key_field]] = $row;
			}
		}
		return $ret;
	}
	/**
	 * 将一个二维数组按照指定字段的值分组
	 *
	 * 用法：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1', 'parent' => 1),
	 * array('id' => 2, 'value' => '2-1', 'parent' => 1),
	 * array('id' => 3, 'value' => '3-1', 'parent' => 1),
	 * array('id' => 4, 'value' => '4-1', 'parent' => 2),
	 * array('id' => 5, 'value' => '5-1', 'parent' => 2),
	 * array('id' => 6, 'value' => '6-1', 'parent' => 3),
	 * );
	 * $values = Helper_Array::groupBy($rows, 'parent');
	 *
	 * dump($values);
	 * // 按照 parent 分组的输出结果为
	 * // array(
	 * //   1 => array(
	 * //        array('id' => 1, 'value' => '1-1', 'parent' => 1),
	 * //        array('id' => 2, 'value' => '2-1', 'parent' => 1),
	 * //        array('id' => 3, 'value' => '3-1', 'parent' => 1),
	 * //   ),
	 * //   2 => array(
	 * //        array('id' => 4, 'value' => '4-1', 'parent' => 2),
	 * //        array('id' => 5, 'value' => '5-1', 'parent' => 2),
	 * //   ),
	 * //   3 => array(
	 * //        array('id' => 6, 'value' => '6-1', 'parent' => 3),
	 * //   ),
	 * // )
	 * @endcode
	 *
	 * @param array $arr 数据源
	 * @param string $key_field 作为分组依据的键名
	 *
	 * @return array 分组后的结果
	 */
	static function groupBy ($arr, $key_field)
	{
		$ret = array();
		foreach ($arr as $row) {
			$key = $row[$key_field];
			$ret[$key][] = $row;
		}
		return $ret;
	}
	/**
	 * 将一个平面的二维数组按照指定的字段转换为树状结构
	 *
	 * 用法：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1', 'parent' => 0),
	 * array('id' => 2, 'value' => '2-1', 'parent' => 0),
	 * array('id' => 3, 'value' => '3-1', 'parent' => 0),
	 *
	 * array('id' => 7, 'value' => '2-1-1', 'parent' => 2),
	 * array('id' => 8, 'value' => '2-1-2', 'parent' => 2),
	 * array('id' => 9, 'value' => '3-1-1', 'parent' => 3),
	 * array('id' => 10, 'value' => '3-1-1-1', 'parent' => 9),
	 * );
	 *
	 * $tree = Helper_Array::tree($rows, 'id', 'parent', 'nodes');
	 *
	 * dump($tree);
	 * // 输出结果为：
	 * // array(
	 * //   array('id' => 1, ..., 'nodes' => array()),
	 * //   array('id' => 2, ..., 'nodes' => array(
	 * //        array(..., 'parent' => 2, 'nodes' => array()),
	 * //        array(..., 'parent' => 2, 'nodes' => array()),
	 * //   ),
	 * //   array('id' => 3, ..., 'nodes' => array(
	 * //        array('id' => 9, ..., 'parent' => 3, 'nodes' => array(
	 * //             array(..., , 'parent' => 9, 'nodes' => array(),
	 * //        ),
	 * //   ),
	 * // )
	 * @endcode
	 *
	 * 如果要获得任意节点为根的子树，可以使用 $refs 参数：
	 * @code php
	 * $refs = null;
	 * $tree = Helper_Array::tree($rows, 'id', 'parent', 'nodes', $refs);
	 * 
	 * // 输出 id 为 3 的节点及其所有子节点
	 * $id = 3;
	 * dump($refs[$id]);
	 * @endcode
	 *
	 * @param array $arr 数据源
	 * @param string $key_node_id 节点ID字段名
	 * @param string $key_parent_id 节点父ID字段名
	 * @param string $key_childrens 保存子节点的字段名
	 * @param boolean $refs 是否在返回结果中包含节点引用
	 *
	 * return array 树形结构的数组
	 */
	static function toTree ($arr, $key_node_id, $key_parent_id = 'parent_id', $key_childrens = 'childrens', & $refs = null)
	{
		$refs = array();
		foreach ($arr as $offset => $row) {
			$arr[$offset][$key_childrens] = array();
			$refs[$row[$key_node_id]] = & $arr[$offset];
		}
		$tree = array();
		foreach ($arr as $offset => $row) {
			$parent_id = $row[$key_parent_id];
			if ($parent_id) {
				if (! isset($refs[$parent_id])) {
					$tree[] = & $arr[$offset];
					continue;
				}
				$parent = & $refs[$parent_id];
				$parent[$key_childrens][] = & $arr[$offset];
			} else {
				$tree[] = & $arr[$offset];
			}
		}
		return $tree;
	}
	/**
	 * 将树形数组展开为平面的数组
	 *
	 * 这个方法是 tree() 方法的逆向操作。
	 *
	 * @param array $tree 树形数组
	 * @param string $key_childrens 包含子节点的键名
	 *
	 * @return array 展开后的数组
	 */
	static function treeToArray ($tree, $key_childrens = 'childrens')
	{
		$ret = array();
		if (isset($tree[$key_childrens]) && is_array($tree[$key_childrens])) {
			$childrens = $tree[$key_childrens];
			unset($tree[$key_childrens]);
			$ret[] = $tree;
			foreach ($childrens as $node) {
				$ret = array_merge($ret, self::treeToArray($node, $key_childrens));
			}
		} else {
			unset($tree[$key_childrens]);
			$ret[] = $tree;
		}
		return $ret;
	}
	/**
	 * 根据指定的键对数组排序
	 *
	 * 用法：
	 * @code php
	 * $rows = array(
	 * array('id' => 1, 'value' => '1-1', 'parent' => 1),
	 * array('id' => 2, 'value' => '2-1', 'parent' => 1),
	 * array('id' => 3, 'value' => '3-1', 'parent' => 1),
	 * array('id' => 4, 'value' => '4-1', 'parent' => 2),
	 * array('id' => 5, 'value' => '5-1', 'parent' => 2),
	 * array('id' => 6, 'value' => '6-1', 'parent' => 3),
	 * );
	 *
	 * $rows = Helper_Array::sortByCol($rows, 'id', SORT_DESC);
	 * dump($rows);
	 * // 输出结果为：
	 * // array(
	 * //   array('id' => 6, 'value' => '6-1', 'parent' => 3),
	 * //   array('id' => 5, 'value' => '5-1', 'parent' => 2),
	 * //   array('id' => 4, 'value' => '4-1', 'parent' => 2),
	 * //   array('id' => 3, 'value' => '3-1', 'parent' => 1),
	 * //   array('id' => 2, 'value' => '2-1', 'parent' => 1),
	 * //   array('id' => 1, 'value' => '1-1', 'parent' => 1),
	 * // )
	 * @endcode
	 *
	 * @param array $array 要排序的数组
	 * @param string $keyname 排序的键
	 * @param int $dir 排序方向
	 *
	 * @return array 排序后的数组
	 */
	static function sortByCol ($array, $keyname, $dir = SORT_ASC)
	{
		return self::sortByMultiCols($array, array($keyname => $dir));
	}
	/**
	 * 将一个二维数组按照多个列进行排序，类似 SQL 语句中的 ORDER BY
	 *
	 * 用法：
	 * @code php
	 * $rows = Helper_Array::sortByMultiCols($rows, array(
	 * 'parent' => SORT_ASC, 
	 * 'name' => SORT_DESC,
	 * ));
	 * @endcode
	 *
	 * @param array $rowset 要排序的数组
	 * @param array $args 排序的键
	 *
	 * @return array 排序后的数组
	 */
	static function sortByMultiCols ($rowset, $args)
	{
		if(empty($rowset) || !is_array($rowset)) return array();
		$sortArray = array();
		$sortRule = '';
		foreach ($args as $sortField => $sortDir) {
			foreach ($rowset as $offset => $row) {
				$sortArray[$sortField][$offset] = $row[$sortField];
			}
			$sortRule .= '$sortArray[\'' . $sortField . '\'], ' . $sortDir . ', ';
		}
		if (empty($sortArray) || empty($sortRule)) {
			return $rowset;
		}
		eval('array_multisort(' . $sortRule . '$rowset);');
		return $rowset;
	}


	/**
	 *	数组转XML
	 *
	 *	@param array		$arr			数据源
	 *	@param object		$pn				父节点对象
	 *	@param object		$xo				xml文档对象
	 *	@param string		$encoding		指定xml文档编码
	 *	@param string		$version		指定xml文档版本 
	 *	@param boolean		$formatOutput	是否格式化输出,默认true
	 *
	 *	@return object(xml)
	 */
	static public function array2xml($arr, $pn=NULL, $xo=NULL, $encoding='gbk', $version='1.0', $formatOutput=true)
	{
		//初始化xml对象,父节点
		if(!$xo){
			$xo	= new DOMDocument($version, $encoding);
			$xo->formatOutput	= $formatOutput;
			$pn	= $xo;
		}
		foreach ($arr as $k=>$v){
			//有子节点
			if(is_array($v)){
				if(preg_match('/\d+/', $k)){
					//同胞胎父节点
					$pNode	= $k === 0 ? $pn:$xo->createElement($pn->nodeName);
					$pn->parentNode->appendChild($pNode);
					self::array2xml($v, $pNode, $xo);
					continue;
				}
				$pNode	= $xo->createElement($k);
				$pn->appendChild($pNode);
				self::array2xml($v, $pNode, $xo);
			//无子节点
			}else{
				preg_match('/^@|CDATA/i', $k, $mArr);
				$mark	= $mArr[0];
				switch ($mark){
					case '@':
						$eNode	= $xo->createAttribute(str_replace($mark, '', $k));
						$txt	= $xo->createTextNode($v);
						$eNode->appendChild($txt);
						break;
					case 'CDATA':
						$eNode	= $xo->createCDATASection($v);
						break;
					default:
						$eNode	= $xo->createElement($k);
						$txt	= $xo->createTextNode($v);
						$eNode->appendChild($txt);
				}
				$pn->appendChild($eNode);
			}
		}

		return $xo;
	}


	/**
	 * The main function for converting to an XML document.
	 * Pass in a multi dimensional array and this recrusively loops through and builds up an XML document.
	 *
	 * @param array $data
	 * @param string $rootNodeName - what you want the root node to be - defaultsto data.
	 * @param SimpleXMLElement $xml - should only be used recursively
	 * @return string XML
	 */
	static function toXml ($data, $rootNodeName = 'data', $xml = null , $character = null)
	{
		// turn off compatibility mode as simple xml throws a wobbly if you don't.
		if (ini_get('zend.ze1_compatibility_mode') == 1) {
			ini_set('zend.ze1_compatibility_mode', 0);
		}
		if ($xml == null) {
			$xml = simplexml_load_string("<?xml version='1.0' encoding='utf-8'?><$rootNodeName />");
		}
		// loop through the data passed in.
		foreach ($data as $key => $value) {
			
			// no numeric keys in our xml please!
			if (is_numeric($key)) {
				// make string key...
				$key = "node_" . (string) $key;
			}
			// replace anything not alpha numeric
			$key = preg_replace('/[^a-z_0-9]/i', '', $key);
			
			// if there is another array found recrusively call this function
			if (is_array($value)) {
				$node = $xml->addChild($key);
				// recrusive call.
				self::toXml($value, $rootNodeName, $node);
			} else {
				// add single node.
				$value = htmlentities($value , ENT_COMPAT , "UTF-8");
				
				
				$xml->addChild($key, $value);
			}
		}
		// pass back as string. or simple xml object if you want!
		return $xml->asXML();
	}
	
	static function toDOMDocument ($data, $nodeDOM = null, $DOM = null , $character = 'gbk')
	{
		if ($DOM == null) {
			$DOM = new DOMDocument('1.0', $character);
			$DOM->formatOutput = true;
			$nodeDOM = $DOM;
		}
		if (!is_array($data)) {
			return $DOM;
		}
		foreach ($data as $key => $value)
		{
			if (is_numeric($key)) {
				$key = "node_" . (string) $key;
			}
			$key = preg_replace('/[^a-z_0-9]/i', '', $key);
			//判断是不是数组
			if (is_array($value)) {
				
				//判断有没有属性值
				if (isset($value['@attributes'])) {
					$element = $DOM->createElement($key);
					foreach ($value['@attributes'] as $k => $v)
					{
						$attribute = $DOM->createAttribute($k);
						$attribute->appendChild($DOM->createTextNode($v));
						$element->appendChild($attribute);
					}
					unset($value['@attributes']);
					if (isset($value['@CDATA'])) {
						$CDATA = $DOM->createCDATASection($value['@CDATA']);
						$element->appendChild($CDATA);
					}elseif (isset($value['@TEXT'])) {
						$element->appendChild($DOM->createTextNode($value['@TEXT']));
					}elseif (!empty($value)) {
						self::toDOMDocument($value, $element, $DOM);
					}
				}elseif (isset($value['@CDATA'])) {
					$element = $DOM->createElement($key);
					$CDATA = $DOM->createCDATASection($value['@CDATA']);
					$element->appendChild($CDATA);
					
				}elseif (isset($value['@list'])) {
					//list
					foreach ($value['@list'] as $v)
					{
						$element = $DOM->createElement($key);
						if (isset($v['@CDATA'])) {
							$CDATA = $DOM->createCDATASection($v['@CDATA']);
							$element->appendChild($CDATA);
						}elseif (isset($v['@TEXT'])) {
							$element->appendChild($DOM->createTextNode($v['@TEXT']));
						}elseif (!empty($v)) {
							self::toDOMDocument($v, $element, $DOM);
						}
						$nodeDOM->appendChild($element);
					}
					unset($element);
				}else {
					$element = $DOM->createElement($key);
					self::toDOMDocument($value, $element, $DOM);
				}
			}else {
				$element = $DOM->createElement($key);
				$element->appendChild($DOM->createTextNode($value));
			}
			if (!empty($element)) {
				$nodeDOM->appendChild($element);
			}
			
		}
		return $DOM;
	}
	
	
	static function toDOMDocumentAttr ($data, $nodeDOM = null, $DOM = null , $character = 'gbk')
	{
		if ($DOM == null) {
			$DOM = new DOMDocument('1.0', $character);
			$DOM->formatOutput = true;
			$nodeDOM = $DOM;
		}
		if (!is_array($data)) {
			return $DOM;
		}
		foreach ($data as $key => $value)
		{
			if (is_numeric($key)) {
				$key = "node_" . (string) $key;
			}
			$key = preg_replace('/[^a-z_0-9]/i', '', $key);
			//判断是不是数组
			if (is_array($value)) {
				
				//判断有没有属性值
				if (isset($value['@attributes'])) {
					$element = $DOM->createElement($key);
					foreach ($value['@attributes'] as $k => $v)
					{
						$attribute = $DOM->createAttribute($k);
						$attribute->appendChild($DOM->createTextNode($v));
						$element->appendChild($attribute);
					}
					unset($value['@attributes']);
					if (isset($value['@CDATA'])) {
						$CDATA = $DOM->createCDATASection($value['@CDATA']);
						$element->appendChild($CDATA);
					}elseif (isset($value['@TEXT'])) {
						$element->appendChild($DOM->createTextNode($value['@TEXT']));
					}elseif (!empty($value)) {
						self::toDOMDocumentAttr($value, $element, $DOM);
					}
				}elseif (isset($value['@CDATA'])) {
					$element = $DOM->createElement($key);
					$CDATA = $DOM->createCDATASection($value['@CDATA']);
					$element->appendChild($CDATA);
					
				}elseif (isset($value['@list'])) {
					//list
					foreach ($value['@list'] as $v)
					{
						$element = $DOM->createElement($key);
						if (isset($v['@CDATA'])) {
							$CDATA = $DOM->createCDATASection($v['@CDATA']);
							$element->appendChild($CDATA);
						}elseif (isset($v['@TEXT'])) {
							$element->appendChild($DOM->createTextNode($v['@TEXT']));
						}elseif (isset($v['@attributes'])) {
							foreach ($v['@attributes'] as $kk => $vv)
							{
								$attribute = $DOM->createAttribute($kk);
								$attribute->appendChild($DOM->createTextNode($vv));
								$element->appendChild($attribute);
							}
							unset($v['@attributes']);
						}elseif (!empty($v)) {
							self::toDOMDocumentAttr(array($v), $element, $DOM);
						}
						$nodeDOM->appendChild($element);
					}
					unset($element);
				}else {
					$element = $DOM->createElement($key);
					self::toDOMDocumentAttr($value, $element, $DOM);
				}
			}else {
				$element = $DOM->createElement($key);
				$element->appendChild($DOM->createTextNode($value));
			}
			if (!empty($element)) {
				$nodeDOM->appendChild($element);
			}
			
		}
		return $DOM;
	}
    /**
     * 二维数据搜索
     * @static
     * @param  $array
     * @param  $field
     * @param  $val
     * @return bool
     */
    static function arrayFind($array,$field,$val){
        if(!is_array($array) || !$field) return false;

        foreach($array as $key => $item){
            if($item[$field] == $val) return $key;
        }
    }
}


