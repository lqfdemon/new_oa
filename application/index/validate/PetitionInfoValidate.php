<?php
namespace app\index\validate;
use think\Validate;

class PetitionInfoValidate extends Validate{
	protected $rule=[
		'person_name'=>'require',
		'source'=>'require',
		'deadline'=>'require',
		'index'=>'require',
	];
	protected $message=[
		'person_name.require'=>'请输入信访人姓名',
		'source.require'=>'请输入信访来源',
		'deadline.require'=>'请输入信访办理期限',
		'index.require'=>'请输入来工单编号',
	];

}