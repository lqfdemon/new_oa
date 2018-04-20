<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\Meeting;
use app\index\model\User;
/************
公文状态 0  未签收
		 20 已签收
		 22 拒签
*************/
class MeetingManage extends CommonController
{
	public function add(){
		$view=new View();
		$view->assign('meeting_id',0);
		return $view->fetch('add');
	}

	public function audit_save(){
		$data=$_POST;
		$result=$this->validate($data,'MeetingValidate');
		if($result !== true){
			$this->error($result);
		}
		$id=$data['id'];
		if($id==0){
			$meeting = new Meeting();
		}else{
			$meeting=Meeting::get($id);
		}
		unset($data['id']);
		$meeting->save($data);
		$this->success("保存成功");
	}

	public function save(){
		$data=$_POST;
		$result=$this->validate($data,'MeetingValidate');
		if($result !== true){
			$this->error($result);
		}
		$id=$data['id'];
		if($id==0){
			$meeting = new Meeting();
		}else{
			$meeting=Meeting::get($id);
		}
		unset($data['id']);
		$user_id =$this->get_user_id();
		$user=User::get($user_id);
		$data['apply_user'] = $user_id;
		$data['apply_user_name'] = $user->name;
		$data['status']="待审核";
		$meeting->save($data);
		$this->success("保存成功");
	}
	public function manage(){
		$view=new View();
		return $view->fetch('manage');
	}
	public function get_list($status,$name){
		$map=[];
		if($status!="所有"){
			$map['status']=$status;
		}
		if(!empty($name)){
			$map['name']= array('like',"%$name%");
		}
		if(isset($_GET['start_time'])&&!empty($_GET['start_time'])){
			$start_time = $_GET['start_time'];
			$map['start_time']= array('>=',$start_time);
		}
		if(isset($_GET['end_time'])&&!empty($_GET['end_time'])){
			$end_time = $_GET['end_time'];
			$map['end_time']= array('<=',$end_time);
		}
		$list=Meeting::where($map)->order('id','desc')->select();
		if(empty($list)){
			$this->error('没有找到');
		}
		return $list->toArray();
	}

	public function audit($id){
		$view=new View();
		$meeting=Meeting::get($id);
		$view->assign('meeting',$meeting->getData());
		return $view->fetch('audit');
	}
	public function undo(){
		$id = $_GET['id'];
		$res = Meeting::where('id = '.$id)->delete();
		if($res){
			$this->success('撤回成功！');
		}	
	}
	public function pass($id){
		$user_id =$this->get_user_id();
		$user=User::get($user_id);
		$meeting=Meeting::get($id);
		$meeting->status="审核通过";
		$meeting->audit_user = $user_id;
		$meeting->audit_user_name = $user->name;
		$meeting->save();
		$this->success("审核完成");
	}
	public function reject($id){
		$user_id =$this->get_user_id();
		$user=User::get($user_id);
		$meeting=Meeting::get($id);
		$meeting->status="审核不通过";
		$meeting->audit_user = $user_id;
		$meeting->audit_user_name = $user->name;
		$meeting->save();
		$this->success("审核完成");		
	}
	public function my_meeting(){
		$view=new View();
		return $view->fetch('my_meeting');
	}
	public function get_my_meeting($status,$name){
		$map=[];
		if($status!="所有"){
			$map['status']=$status;
		}
		if(!empty($name)){
			$map['name']= array('like',"%$name%");
		}
		$user_id =$this->get_user_id();
		$map['apply_user'] = $user_id;
		Log::record($map);
		$list=Meeting::where($map)->order('start_time','desc')->select();
		if(empty($list)){
			$this->error('没有找到');
		}
		return $list->toArray();
	}
	public function export(){
		$view=new View();
		return $view->fetch('export');		
	}
	public function look($id){
		$view=new View();
		$meeting=Meeting::get($id);
		$view->assign('meeting',$meeting->getData());
		return $view->fetch('look');
	}
}