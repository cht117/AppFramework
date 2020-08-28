<?php
class API_Filter_utf8Togbk extends API_Filter_Abstract
{
        function execute()
        {
                $_POST = $this->format($_POST);
                $_GET = $this->format($_GET);
        }


        public function format($array)
        {
                return @eval("return ".iconv('utf-8','gbk//IGNORE', var_export($array,1) ).';' );
        }
}

