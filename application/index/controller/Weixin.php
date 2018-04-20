<?php
namespace app\index\controller;
use think\Session;
use think\View;
use think\Db;
use think\Controller;
use think\Log;

use app\index\model\User;
use think\Loader;
Loader::import('weixin.wx_sdk');
class Weixin extends Controller
{  
	public function bond(){
		$bond_url=url("bond_oauth2_access_token");
    	$redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$bond_url;
    	$weixin = new \class_weixin();
    	$jump_url=$weixin->oauth2_authorize($redirect_url,"snsapi_base",'123');
    	$this->redirect($jump_url);
	}
	public function bond_oauth2_access_token($code){
		$weixin = new \class_weixin();
		$OAuth2_Access_Token = $weixin->oauth2_access_token($code);
		$open_id = $OAuth2_Access_Token['openid'];
        $view=new View();
        $view->assign('open_id',$open_id);
        return $view->fetch('bond_view'); 
	}
	public function bond_check(){
        $emp_no = $_POST['emp_no'];
        $psw=$_POST['psw'];
        if (empty($emp_no)) {
            $this -> error('帐号必须！');
        } elseif (empty($psw)) {
            $this -> error('密码必须！');
        }
        $user = User::where('emp_no',$emp_no)->find();
        if(empty($user)){
            $this->error("账号不存在");
        }
        if($user['password']==md5($psw)){
        	$user_wx_info = Db::table('user_wx_info')
        				->where(['user_id'=>$user['id'],
        						 'open_id'=>$_POST['open_id'],
        					])
        				->find();
        	if(!empty($user_wx_info)){
        		$this->error("已经绑定");
        	}else{
        		Db::table('user_wx_info')
        				->insert(['user_id'=>$user['id'],
        						 'open_id'=>$_POST['open_id'],
        						 'name'=>$_POST['name'],
        					]);
        		$this->success("绑定成功");
        	}
        }else{
            $this->error("密码错误");
        }     		
	}
}