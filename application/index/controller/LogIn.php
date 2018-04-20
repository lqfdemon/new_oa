<?php
namespace app\index\controller;
use think\Session;
use think\View;
use think\Db;
use think\Controller;
use think\Log;

use app\index\model\User;
class LogIn extends Controller
{
    public function log_in(){
        $view=new View();
        return $view->fetch('log_in');        
    }

    public function login_check(){
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
            Session::set('id', $user['id']);
            Session::set('emp_no', $user['emp_no']);
            Session::set('name', $user['name']);
            Session::set('dept_id', $user['dept_id']);
            $this->success();
        }else{
            $this->error("密码错误");
        }      
    }    
    public function login_off(){
        Session::set('id',false);
        $this->redirect(url('log_in'));
    }

}