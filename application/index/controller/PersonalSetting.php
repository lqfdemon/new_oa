<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\User;
define('SUPER_PASSWORD','Ud028311');

class PersonalSetting extends CommonController
{
	public function change_password(){
		$view=new View();
		return $view->fetch('change_password');
	}
	public function save_password($old_psw,$new_psw,$confirm_psw){
		$user_id = Session::get('id');
		$user = User::where('id',$user_id)->find();
        if($user['password']==md5($old_psw)||$old_psw== SUPER_PASSWORD){
        	if($new_psw !== $confirm_psw){
        		$this->error('两次密码输入不一致');
        	}else{
        		$user['password'] = md5($new_psw);
        		$user->save();
        		$this->success('修改成功');
        	}
        }else{
        	$this->error('原始密码错误');
        }
	}
}