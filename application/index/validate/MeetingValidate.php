<?php
namespace app\index\validate;
use think\Validate;

class MeetingValidate extends Validate{
	protected $rule=[
		'name'=>'require',
		'address'=>'require',
		'start_time'=>'require',
		'end_time'=>'require',
		'leader'=>'require',
		'manager'=>'require',
		'viewer'=>'require',
	];
	protected $message=[
		'name.require'=>'请输入会议名称',
		'address.require'=>'请输入会议地点',
		'start_time.require'=>'请输入会议预计开时间',
		'end_time.require'=>'请输入会议预计结束时间',
		'leader.require'=>'请输入参会领导',
		'manager.require'=>'请输入会议主持人',
		'viewer.require'=>'请输入参会人员名单',
	];

}