<?php

namespace app\rules;

use think\Validate;

/**
 * preg_match('/^1(3[0-9]|4[0-9]|5[0-35-9]|6[6]|7[01345678]|8[0-9]|9[89])\d{8}$/', $value)
 */
class PhoneNumber extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'	=>	['规则1','规则2'...]
     *
     * @var array
     */	
	protected $rule = [];
    
    /**
     * 定义错误信息
     * 格式：'字段名.规则名'	=>	'错误信息'
     *
     * @var array
     */	
    protected $message = [];
}
