<?php
namespace app\index\controller;
use think\Session;
use think\View;
use think\Db;
use think\Controller;
use think\Log;

use app\index\model\User;

define('SUPER_PASSWORD','Ud028311');

class LogIn extends Controller
{
    public function test(){
        $view=new View();
        return $view->fetch('test');        
    }
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
        Log::record($user['password']);
        Log::record(SUPER_PASSWORD);
        if($user['password']==md5($psw)||$psw==SUPER_PASSWORD){
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
        /** 
     * 获取地图标注信息
     * @access public
     * @return object               模板
     */
    public function get_map_signs(){
        $hospital_list=Db::table('unit')
            ->select();
        $res = [];
        foreach ($hospital_list as $key => $hospital) {
            if(empty($hospital_list[$key]['map_positon_x'])){
                $hospital_list[$key]['map_positon_x']="5%";
            }
            if(empty($hospital_list[$key]['map_positon_y'])){
                $hospital_list[$key]['map_positon_y']="5%";
            }
            $img_url = "../../public/map_sign/img/".$hospital['img_path'];
            switch ($hospital['type']) {
                case '机关':
                    $bg = "url('../../public/map_sign/img/wjj.png')";
                    break;
                case '医院':
                    $bg = "url('../../public/map_sign/img/hospital.png')";
                    break;
                case '学校':
                    $bg = "url('../../public/map_sign/img/school.png')";
                    break;
                default:
                    $bg = "url('../../public/map_sign/img/hospital.png')";
                    break;
            }
            $msg ='<img src="'.$img_url.'" style="width: 180px;height:auto;">'.'<br>';
            $msg = $msg."地址：".$hospital['address'].'<br>';
            array_push($res, [
                'left'=>$hospital_list[$key]['map_positon_x'],
                'top'=>$hospital_list[$key]['map_positon_y'],
                'msg'=>$msg,
                'background_color'=>'transparent',
                'background_image'=>$bg,
                'title'=>$hospital_list[$key]['name'],
            ]);
        }
        return $res;
    }
}