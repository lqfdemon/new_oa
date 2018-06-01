<?php
namespace app\index\validate;
use think\Validate;

class TaskPassInfoValidate extends Validate{
	protected $rule=[
		'form_title'=>'require',
		'form_unit'=>'require',
		'form_time'=>'require',
		'form_id'=>'require',
	];
	protected $message=[
		'form_title.require'=>'请输入公文标题',
		'form_unit.require'=>'请输入来文单位',
		'form_time.require'=>'请输入来文时间',
		'form_id.require'=>'请输入来文文号',
	];

}