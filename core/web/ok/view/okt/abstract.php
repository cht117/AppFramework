<?php
/**
 * 定义 OK_View_OKT_Abstract 类 - 澳客模板引擎基础类
 *
 */
class OK_View_OKT_Abstract
{
    /**
     * 视图文件所在目录
     *
     * @var string
     */
    public $view_dir;
	
    public $tpl_cache_dir;
    
    /**
     * 视图文件的扩展名
     *
     * @var string
     */
    public $file_extname = 'htm';
	
    /**
     * 视图变量
     *
     * @var array
     */
    protected $_vars;
    /**
     * 模板更新等级
     *
     * @var unknown_type
     */
    public $tplrefresh = 1;
    
    
    function __construct()
    {
    	//! 设置模板路径
    	$this->view_dir = OK::ini('app_config/APP_DIR').DS.'view';
    	
    	$this->tpl_cache_dir = OK::ini('app_config/ROOT_DIR').DS.'tmp'.DS.'tpl_cache';
    }
    
    
	function parse_template($tpl)
	{
		self::$sub_tpls = array($tpl);
		
		$tplfile = $this->view_dir.DS.$tpl.'.'.$this->file_extname;
		
		$tmpfile = $this->tpl_cache_dir.DS.'template_'.str_replace('/','_',$tpl).'.php';
		
		$template = self::sreadfile($tplfile);
		if(empty($template)) {
			exit("Template file : $tplfile Not found or have no access!");
		}
		//模板
		$template = preg_replace("/\<\!\-\-\{template\s+([a-z0-9_\/]+)\}\-\-\>/ie", __CLASS__."::readtemplate('\\1')", $template);
		//处理子页面中的代码
		$template = preg_replace("/\<\!\-\-\{template\s+([a-z0-9_\/]+)\}\-\-\>/ie", __CLASS__."::readtemplate('\\1')", $template);
		//解析模块调用
		$template = preg_replace("/\<\!\-\-\{block\/(.+?)\}\-\-\>/ie", __CLASS__."::blocktags('\\1')", $template);
		//时间处理
		$template = preg_replace("/\<\!\-\-\{date\((.+?)\)\}\-\-\>/ie", __CLASS__."::datetags('\\1')", $template);
		//PHP代码
		$template = preg_replace("/\<\!\-\-\{eval\s+(.+?)\s*\}\-\-\>/ies", __CLASS__."::evaltags('\\1')", $template);
		
		/**
		 * 开始处理
		 */
		
		//变量
		//$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		$var_regexp = "((\\\$[a-zA-Z_\-\>_\x7f-\xff][a-zA-Z0-9_\-\>_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\>_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		$template = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $template);
		$template = preg_replace("/([\n\r]+)\t+/s", "\\1", $template);
		
		$template = preg_replace("/(\\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\.([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)/s", "\\1['\\2']", $template);
		
		$template = preg_replace("/\{(\\\$[a-zA-Z0-9_\-\>_\[\]\'\"\$\.\x7f-\xff]+)\}/s", "<?=\\1?>", $template);
		//pecho($template);
		$template = preg_replace("/$var_regexp/es", "self::addquote('<?=\\1?>')", $template);
		//pecho($template);
		$template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "self::addquote('<?=\\1?>')", $template);
		//pecho($template);
		//逻辑
		$template = preg_replace("/\{elseif\s+(.+?)\}/ies", "self::stripvtags('<?php } elseif(\\1) { ?>','')", $template);
		$template = preg_replace("/\{else\}/is", "<?php } else { ?>", $template);
		
		//循环
		for($i = 0; $i < 5; $i++) {
			$template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "self::stripvtags('<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>','\\3<?php } } ?>')", $template);
			$template = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}(.+?)\{\/loop\}/ies", "self::stripvtags('<?php if(is_array(\\1) || is_object(\\1)) { foreach(\\1 as \\2 => \\3) { ?>','\\4<?php } } ?>')", $template);
			$template = preg_replace("/\{if\s+(.+?)\}(.+?)\{\/if\}/ies", "self::stripvtags('<?php if(\\1) { ?>','\\2<?php } ?>')", $template);
		}
		
		
		//常量
		$template = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/s", "<?=\\1?>", $template);
		
		//替换
		/*if(!empty($_SGLOBAL['block_search'])) {
			$template = str_replace($_SGLOBAL['block_search'], $_SGLOBAL['block_replace'], $template);
		}*/
		
		//换行
		$template = preg_replace("/ \?\>[\n\r]*\<\? /s", " ", $template);
		
		//pecho(self::$sub_tpls);
		
		//附加处理
		$template = "<?php self::subtplcheck('".implode('|', self::$sub_tpls)."', '".CURRENT_TIMESTAMP."', '$tpl');?>$template<?php self::ob_out();?>";
		
		//write
		if(!self::swritefile($tmpfile, $template)) {
			exit("File: $tmpfile can not be write!");
		}
			
		
	}
	
	
	
	/**
	 * 获取文件内容
	 *
	 * @param string $filename
	 * @return string
	 */
	static function sreadfile($filename)
	{
		$content = '';
		if(function_exists('file_get_contents')) {
			@$content = file_get_contents($filename);
		} else {
			if(@$fp = fopen($filename, 'r')) {
				@$content = fread($fp, filesize($filename));
				@fclose($fp);
			}
		}
		return $content;
	}
	/**
	 * 写入文件
	 *
	 * @param string $filename
	 * @param string $writetext
	 * @param string $openmod
	 * @return string
	 */
	function swritefile($filename, $writetext, $openmod='w') {
		if(@$fp = fopen($filename, $openmod)) {
			flock($fp, 2);
			fwrite($fp, $writetext);
			fclose($fp);
			return true;
		} else {
			//runlog('error', "File: $filename write error.");
			return false;
		}
	}
	
	/**
	 * 子模板
	 *
	 * @var unknown_type
	 */
	static $sub_tpls = array();
	
	/**
	 * 模板引擎对象
	 *
	 * @var OK_View_OKT_Abstract
	 */
	static $tpl_object;
	
	
	static function addquote ($var)
	{
		return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
	}
	static function striptagquotes ($expr)
	{
		$expr = preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr);
		$expr = str_replace("\\\"", "\"", preg_replace("/\[\'([a-zA-Z0-9_\-\.\x7f-\xff]+)\'\]/s", "[\\1]", $expr));
		return $expr;
	}
	static function evaltags ($php)
	{
		global $_SGLOBAL;
		$_SGLOBAL['i'] ++;
		$search = "<!--EVAL_TAG_{$_SGLOBAL['i']}-->";
		$_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
		$_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php " . self::stripvtags($php) . " ?>";
		return $search;
	}
	static function blocktags ($parameter)
	{
		global $_SGLOBAL;
		$_SGLOBAL['i'] ++;
		$search = "<!--BLOCK_TAG_{$_SGLOBAL['i']}-->";
		$_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
		$_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php block(\"$parameter\"); ?>";
		return $search;
	}
	static function adtags ($pagetype)
	{
		global $_SGLOBAL;
		$_SGLOBAL['i'] ++;
		$search = "<!--AD_TAG_{$_SGLOBAL['i']}-->";
		$_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
		$_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php adshow('$pagetype'); ?>";
		return $search;
	}
	static function datetags ($parameter)
	{
		global $_SGLOBAL;
		$_SGLOBAL['i'] ++;
		$search = "<!--DATE_TAG_{$_SGLOBAL['i']}-->";
		$_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
		$_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php echo sgmdate($parameter); ?>";
		return $search;
	}
	static function avatartags ($parameter)
	{
		global $_SGLOBAL;
		$_SGLOBAL['i'] ++;
		$search = "<!--AVATAR_TAG_{$_SGLOBAL['i']}-->";
		$_SGLOBAL['block_search'][$_SGLOBAL['i']] = $search;
		$_SGLOBAL['block_replace'][$_SGLOBAL['i']] = "<?php echo avatar($parameter); ?>";
		return $search;
	}
	static function stripvtags ($expr, $statement = '')
	{
		$expr = str_replace("\\\"", "\"", preg_replace("/\<\?\=(\\\$.+?)\?\>/s", "\\1", $expr));
		$statement = str_replace("\\\"", "\"", $statement);
		return $expr . $statement;
	}
	static function readtemplate ($name)
	{
		//global $_SGLOBAL, $_SCONFIG;
		//$tpl = strexists($name, '/') ? $name : "template/$_SCONFIG[template]/$name";
		$tpl = $name;
		$tplfile = self::$tpl_object->view_dir.DS.$tpl.'.'.self::$tpl_object->file_extname;
		
		
		//$tplfile = ROOT_DIR . '/' . $tpl . '.htm';
		
		self::$sub_tpls[] = $tpl;
		//$_SGLOBAL['sub_tpls'][] = $tpl;
		if (! file_exists($tplfile)) {
			//$tplfile = str_replace('/' . $_SCONFIG['template'] . '/', '/default/', $tplfile);
			exit('error '.$tplfile);
		}
		$content = self::$tpl_object->sreadfile($tplfile);
		return $content;
	}
	
	/**
	 * 判断字符串是否存在
	 *
	 * @param string $haystack
	 * @param string $needle
	 * @return boolean
	 */
	static function strexists($haystack, $needle) {
		return !(strpos($haystack, $needle) === FALSE);
	}
	
	
	/**
	 * 模板调用
	 *
	 * @param string $tpl
	 * @return string
	 */
	static function template($tpl)
	{
		//pecho($tpl);
		$tplObject = new self();
		self::$tpl_object = $tplObject;
		
		$tplFile = $tplObject->tpl_cache_dir.DS.'template_'.str_replace('/','_',$tpl).'.php';
		if(!file_exists($tplFile)) {
			$tplObject->parse_template($tpl);
		}
		return $tplFile;
	}
	
	/**
	 * 子模板更新检查
	 *
	 * @param string $subfiles
	 * @param string $mktime
	 * @param string $tpl
	 */
	static function subtplcheck($subfiles, $mktime, $tpl)
	{
		//pecho($subfiles);
		if(self::$tpl_object->tplrefresh && (self::$tpl_object->tplrefresh == 1 || mt_rand(1, self::$tpl_object->tplrefresh) == 1)) {
			$subfiles = explode('|', $subfiles);
			foreach ($subfiles as $subfile) {
				//pecho($subfile);
				//$tplfile = ROOT_DIR.'/'.$subfile.'.htm';
				$tplfile = self::$tpl_object->view_dir.DS.$subfile.'.'.self::$tpl_object->file_extname;
				//pecho($tplfile);
				if(!file_exists($tplfile)) {
					//$tplfile = str_replace('/'.$_SCONFIG['template'].'/', '/default/', $tplfile);
					exit('error '.$tplfile);
				}
				@$submktime = filemtime($tplfile);
				if($submktime > $mktime) {
					//include_once ROOT_DIR.'/core/function_template.php';
					self::$tpl_object->parse_template($tpl);
					break;
				}
			}
		}
	}
	
	/**
	 * 调整输出
	 *
	 */
	static function ob_out()
	{
		return null;
	
		$content = ob_get_contents();
	
		$preg_searchs = $preg_replaces = $str_searchs = $str_replaces = array();
	
		if(!empty($_SCONFIG['allowrewrite'])) {
			$preg_searchs[] = "/\<a href\=\"space\.php\?(uid|do)+\=([a-z0-9\=\&]+?)\"/ie";
			$preg_searchs[] = "/\<a href\=\"space.php\"/i";
			$preg_searchs[] = "/\<a href\=\"network\.php\?ac\=([a-z0-9\=\&]+?)\"/ie";
			$preg_searchs[] = "/\<a href\=\"network.php\"/i";
	
			$preg_replaces[] = 'rewrite_url(\'space-\',\'\\2\')';
			$preg_replaces[] = '<a href="space.html"';
			$preg_replaces[] = 'rewrite_url(\'network-\',\'\\1\')';
			$preg_replaces[] = '<a href="network.html"';
		}
		if(!empty($_SCONFIG['linkguide'])) {
			$preg_searchs[] = "/\<a href\=\"http\:\/\/(.+?)\"/ie";
			$preg_replaces[] = 'iframe_url(\'\\1\')';
		}
	
		if($_SGLOBAL['inajax']) {
			$preg_searchs[] = "/([\x01-\x09\x0b-\x0c\x0e-\x1f])+/";
			$preg_replaces[] = ' ';
	
			$str_searchs[] = ']]>';
			$str_replaces[] = ']]&gt;';
		}
	
		if($preg_searchs) {
			$content = preg_replace($preg_searchs, $preg_replaces, $content);
		}
		if($str_searchs) {
			$content = trim(str_replace($str_searchs, $str_replaces, $content));
		}
	
		obclean();
		if($_SGLOBAL['inajax']) {
			xml_out($content);
		} else{
			if(!empty($_SCONFIG['headercharset'])) {
				@header('Content-Type: text/html; charset='.$_SC['charset']);
			}
			echo $content;
			if(D_BUG) {
				@include_once(ROOT_DIR.'/source/inc_debug.php');
			}
		}
	}
	
	
	static function includeTpl($__tpl , $__args)
	{
		
		extract($__args);
		
		include(self::template($__tpl));
		//pecho($blogs);
	}
	
	
	
	
}


?>