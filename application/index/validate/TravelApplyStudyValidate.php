<?php
namespace app\index\validate;
use think\Validate;

class TravelApplyStudyValidate extends Validate{
	protected $rule=[
		'name'=>'require',
		'address'=>'require',
		'start_date'=>'require',
		'end_date'=>'require',
		'content'=>'require',
	];
	protected $message=[
		'name.require'=>'请输入会议名称',
		'address.require'=>'请输入会议地点',
		'start_date.require'=>'请输入会议预计开时间',
		'end_date.require'=>'请输入会议预计结束时间',
		'content.require'=>'请输入会议内容',
	];

}