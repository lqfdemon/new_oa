<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\TaskLog;
use app\index\model\Task;
use app\index\model\File;
use app\index\model\User;

Loader::import('weixin.wx_sdk');
/************
公文状态 0  未签收
         20 已签收
         22 拒签
*************/


class TaskManage extends CommonController
{
    public function test(){
        $weixin = new \class_weixin();
        dump($weixin->access_token);
        dump($weixin->expires_time);
    }
    public function send_task(){
        $view=new View();
        $view->assign('address_widget',true);
        $view->assign('upload_widget',true);
        return $view->fetch('send_task'); 
    }
    public function task_list()
    {
        $view=new View();
        return $view->fetch('task_list'); 
    }
    public function get_task_list($type,$user_name,$title){
        if($type == 'not_finished'){
            $where_log['status'] = 0;
            $where_log['executor'] = $this->get_user_id();
        }else if($type == 'finished'){
            $where_log['status'] = 20;
            $where_log['executor'] = $this->get_user_id();
        }else{
            $this->error("公文类型错误");
        }
        $task_id_list = TaskLog::where($where_log)->column('task_id');
        if(empty($task_id_list)){
            return [];
        }
        $where_task['id'] = array('in',$task_id_list);
        if(!empty($user_name)){
            $where_task['user_name'] = array('like',"%$user_name%");
        }
        if(!empty($title)){
            $where_task['name'] = array('like',"%$title%");                
        }
        $task_list = Task::where($where_task)->order('create_time desc')->select()->toArray();
        foreach ($task_list as $key => $task) {
            $task_list[$key]['file_list']= array();
            $task_list[$key]['file_flag']= 0;
            if(!empty($task['add_file'])){
                //有附件
                $task_list[$key]['file_flag']= 1;
                $file_id_list = explode(';', $task['add_file']);
                foreach ($file_id_list as $id_key => $file_id) {
                    $file = File::get($file_id);
                    $file_url= url('download','file_id='.$file_id);
                    if(!empty($file)){
                        array_push($task_list[$key]['file_list'], ['file_name'=>$file->name,'file_url'=>$file_url]);
                    }
                }
            }
        }
        return $task_list;
    }
    public function get_send_task_list($title){
        $where_task['user_id'] = $this->get_user_id();
        if(!empty($title)){
            $where_task['name'] = array('like',"%$title%");               
        }
        $task_list = Task::where($where_task)->order('create_time desc')->select()->toArray();
        foreach ($task_list as $key => $task) {
            $task_list[$key]['file_list']= array();
            $task_list[$key]['file_flag']= 0;
            if(!empty($task['add_file'])){
                //有附件
                $task_list[$key]['file_flag']= 1;
                $file_id_list = explode(';', $task['add_file']);
                foreach ($file_id_list as $id_key => $file_id) {
                    $file = File::get($file_id);
                    $file_url= url('download','file_id='.$file_id);
                    if(!empty($file)){
                        array_push($task_list[$key]['file_list'], ['file_name'=>$file->name,'file_url'=>$file_url]);
                    }
                }
            }
        }
        return $task_list;
    }
    public function get_not_finished_num(){
        $where_log['status'] = 0;
        $where_log['executor'] = $this->get_user_id();
        $task_id_list = TaskLog::where($where_log)->column('task_id');
        if(empty($task_id_list)){
            return 0;
        }
        $where_task['id'] = array('in',$task_id_list);
        $num = Task::where($where_task)->count('id');
        return $num;
    }
    public function get_finished_num(){
        $where_log['status'] = 20;
        $where_log['executor'] = $this->get_user_id();
        $task_id_list = TaskLog::where($where_log)->column('task_id');
        if(empty($task_id_list)){
            return 0;
        }
        $where_task['id'] = array('in',$task_id_list);
        $num = Task::where($where_task)->count('id');
        return $num;
    }
    public function get_send_num(){
        $where_task['user_id'] = $this->get_user_id();
        $num = Task::where($where_task)->count('id');
        return $num;
    }
    public function receice($task_id){
        $where_log['task_id'] = $task_id;
        $where_log['executor'] = $this->get_user_id();
        $task_log_list = TaskLog::where($where_log)->select();
        if(count($task_log_list) == 0){
            $this->error("签收失败");
        }else{
            foreach ($task_log_list as $key => $task_log) {
                $task_log->status = 20;
                $task_log->save();
            }
            $this->success("签收成功");

        }

    }
    public function receive_all($id_list){
        $where_log['task_id'] = array('in',$id_list);
        $where_log['executor'] = $this->get_user_id();
        TaskLog::where($where_log)->update(['status'=>20]);
        $this->success("签收成功");
    }
    public function reject($task_id,$reject_reson){
        $where_log['task_id'] = $task_id;
        $where_log['executor'] = $this->get_user_id();
        $task_log = TaskLog::get($where_log);
        if(empty($task_log)){
            $this->error("操作失败");
        }else{
            $task_log->status = 22;
            $task_log->reject_reson = $reject_reson;
            $task_log->save();
            $this->success("操作成功");
        }

    }
    public function get_receive_status($task_id){
        $where_log['task_id'] = $task_id;
        $log_list=TaskLog::where($where_log)->field('status,executor_name,reject_reson')->select()->toArray();
        return $log_list;
    }
    public function download($file_id){     
        $File = new File();
        $root = FILE_DOWNLOAD_ROOT_PATH;   
        if (false === $File -> download($root, $file_id)) {
            $this -> error($File -> getError());
        }   
    }

    public function save_task(){
        $data =$_POST;
        //task_no
        $sql = "SELECT CONCAT(year(now()),'-',LPAD(count(*)+1,4,0)) task_no FROM `task` WHERE 1 and year(FROM_UNIXTIME(create_time))>=year(now())";
        $rs = DB::query($sql);
        if ($rs) {
            $data['task_no'] = $rs[0]['task_no'];
        } else {
            $data['task_no'] = date('Y') . "-0001";
        }
        //user_id user_name
        $data['user_id']=$this->get_user_id();
        $data['user_name']=$this->get_user_name();
        //dept_id dept_name
        $data['dept_id'] = $this->get_user_dept_id();
        $data['dept_name'] = $this->get_user_dept_name($data['dept_id']);
        //create_time
        $data['create_time'] = time();
        $task = new Task();
        Log::record($data);
        $task->save($data);
        $executor_list = explode(';', $data['executor']);
        Log::record($executor_list);
        foreach ($executor_list as $key => $executor) {
            if(!empty($executor)){
                $executor_info = explode('|', $executor);
                Log::record($executor_info);
                $task_log_data=['task_id'=>$task->id,
                                'type'=>1,
                                'assigner'=>$data['user_id'],
                                'executor_name'=>$executor_info[0],
                                'executor'=>$executor_info[1],
                                'status'=>0,
                ];
                Log::record($task_log_data);
                $task_log = new TaskLog();
                $task_log->save($task_log_data);
                $executor_id=$executor_info[1];
                $this->send_receive_msg_to_executor($executor_id,$task->user_name,$task->name);
            }
        }
        $this->success("发送成功");
    }

    public function send_receive_msg_to_executor($executor_id,$sender_name,$title){
        $user_wx_info_list = Db::table('user_wx_info')
                            ->where(['user_id'=>$executor_id])
                            ->select();
        foreach ($user_wx_info_list as $key => $user_wx_info) {
            $date = date('Y-m-d H:i:s');    
            $template_id="8ybh3vSB7VNeiJZCMMC0_lDXt2Fg33n-y9dBenOiM18";
            $executor_open_id = $user_wx_info['open_id'];
            $jsonText = array(
                'touser'=>$executor_open_id, 'template_id'=>$template_id ,
                'url'=>'',
                'data'=>array(
                    'first'=>array('value'=>$user_wx_info['name']."您好，您收到一条新公文",'color'=>"#173177",),                               
                    'keyword1'=>array('value'=>$title,'color'=>"#173177",),
                    'keyword2'=>array('value'=>$sender_name,'color'=>"#173177",),
                    'keyword3'=>array('value'=>$date,'color'=>"#173177",),
                    'remark'=>array('value'=>"请登录办公网查看！",'color'=>"#173177",),       
                )
            );  
            $template_data = json_encode($jsonText);
            Log::record($template_data);
            $weixin = new \class_weixin();
            $weixin->send_template_message($template_data);
        }      
    }
    public function get_user_name_list(){
        $res=Db::table('user')
        ->field('name')
        ->select();
        return $res;  
    }
    
}
