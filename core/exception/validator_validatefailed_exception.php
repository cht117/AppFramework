<?php
/**
 * 定义 OK_Validator_ValidateFailedException 异常
 *
 * @package exception
 */

/**
 * OK_Validator_ValidateFailedException 异常封装了验证失败事件
 *
 * @package exception
 */
class OK_Validator_ValidateFailedException extends OK_Exception
{
    /**
     * 被验证的数据
     *
     * @var array
     */
    public $validate_data;

    /**
     * 验证失败的结果
     *
     * @var array
     */
    public $validate_errors;

    /**
     * 构造函数
     *
     * @param array $errors
     * @param array $data
     */
    function __construct(array $errors, array $data = array())
    {
        $this->validate_errors = $errors;
        $this->validate_data = $data;
        parent::__construct($this->formatToString());
    }

    /**
     * 格式化错误信息
     *
     * @param string $key
     *
     * @return string
     */
    function formatToString($key = null)
    {
        if (!is_null($key) && (isset($this->validate_errors[$key])))
        {
            $error = $this->validate_errors[$key];
        }
        else
        {
            $error = $this->validate_errors;
        }

        $arr = array();
        foreach ($error as $messages)
        {
            if (is_array($messages))
            {
                $arr[] = implode(', ', $messages);
            }
            else
            {
                $arr[] = $messages;
            }
        }
        return implode('; ', $arr);
    }

    /**
     * 将异常转换为字符串
     *
     * @return string
     */
    function __toString()
    {
        return $this->formatToString();
    }
}

