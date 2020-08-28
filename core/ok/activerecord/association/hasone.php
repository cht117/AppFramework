<?php
/**
 * 定义 OK_ActiveRecord_Association_HasOne 类
 *
 * @package orm
 */

/**
 * OK_ActiveRecord_Association_HasOne 类封装了对象见的一对一关系
 *
 * @package orm
 */
class OK_ActiveRecord_Association_HasOne extends OK_ActiveRecord_Association_HasMany
{
	public $one_to_one = true;
	public $on_save = 'replace';

    function onSourceSave(OK_ActiveRecord_Abstract $source, $recursion)
    {
        $this->init();
        $mapping_name = $this->mapping_name;
        if ($this->on_save === 'skip' || $this->on_save === false || !isset($source->{$mapping_name}))
        {
            return $this;
        }

        $source_key_value = $source->{$this->source_key};
        $obj = $source->{$mapping_name};
        /* @var $obj OK_ActiveRecord_Abstract */
        $obj->changePropForce($this->target_key, $source_key_value);
        $obj->save($recursion - 1, $this->on_save);

        return $this;
    }

    function addRelatedObject(OK_ActiveRecord_Abstract $source, OK_ActiveRecord_Abstract $target)
    {
    	return $this;
    }
}

