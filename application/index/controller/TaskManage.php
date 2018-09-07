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
    public function get_task_list($type,$user_name,$title,$offset,$limit){
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
        $task_list = Task::where($where_task)
                ->limit($offset,$limit)
                ->order('create_time desc')
                ->select()
                ->toArray();
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
      $result["rows"] = $task_list;
      $result["total"]= Task::where($where_task)
                ->count('id');
      return $result;
    }
    public function get_send_task_list($title,$offset,$limit){
        $where_task['user_id'] = $this->get_user_id();
        if(!empty($title)){
            $where_task['name'] = array('like',"%$title%");               
        }
        $task_list = Task::where($where_task)
                ->limit($offset,$limit)
                ->order('create_time desc')
                ->select()
                ->toArray();
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
        $result["rows"] = $task_list;
        $result["total"]= Task::where($where_task)
                ->count('id');
        return $result;
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
    public function receice($task_id,$executor=''){
        $where_log['task_id'] = $task_id;
        if(empty($executor)){
            $where_log['executor'] = $this->get_user_id();
        }else{
            $where_log['executor'] = $executor;
        }
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
    public function reject($task_id,$reject_reson,$executor=''){
        $where_log['task_id'] = $task_id;
        if(empty($executor)){
            $where_log['executor'] = $this->get_user_id();
        }else{
            $where_log['executor'] = $executor;
        }
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
        $log_id_list_str = "";
        $executor_name_list="";
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
                $executor_name_list .= $executor_info[0]."、";
                $log_id_list_str = $log_id_list_str.$task_log->id.';';
            }
        }
         $this->send_message_to_qq($data['user_name'],$executor_name_list,$data['name']);
        $this->success("发送成功",'',$log_id_list_str,3);
    }
    public function send_receive_msg_to_mulity_executor($log_id_list_str){
        set_time_limit(300);
        $log_id_list = explode(';', $log_id_list_str);
        foreach ($log_id_list as $key => $log_id) {
            if(!empty($log_id)){
                $task_log = Db::table('task_log')
                        ->where('id',$log_id)
                        ->find();
                $task = Db::table('task')
                        ->where('id',$task_log['task_id'])
                        ->find();
                if(empty($task_log)||empty($task)){
                    break;
                }
                $this->send_receive_msg_to_executor($task_log['executor'],$task['user_name'],$task['name']);

            }
        }
        
    }
    public function send_message_to_qq($user_name,$executor_name_list,$title){
        $executor_name=rtrim($executor_name_list, "、");
        $message= "请".$executor_name."在办公网签收【".$user_name."】发送的“".$title."”文件！";
        $message=urlencode($message);
        //$url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>676173297<%26>".$message;  
        if (strstr($user_name,"办公室")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>250645311<%26>".$message;
        }elseif (strstr($user_name,"医政医管科")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>121768437<%26>".$message;
        }elseif (strstr($user_name,"医改办")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>134466183<%26>".$message;
        }elseif (strstr($user_name,"政策法规科")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>543720967<%26>".$message;
        }elseif (strstr($user_name,"中医科")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>618479290<%26>".$message;
        }elseif (strstr($user_name,"政工科")) {
           $url1 = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>193953749<%26>".$message;
            $this->http_request($url1);
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>556956836<%26>".$message; 
        }elseif (strstr($user_name,"公共卫生科")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>105564432<%26>".$message;
        }elseif (strstr($user_name,"财务内审科")) {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>57099324<%26>".$message;
        }
        else {
           $url = "http://localhost:8080/?key=Ud028311&a=<%26%26>SendClusterMessage<%26>676173297<%26>".$message;
        }
        
        $this->http_request($url);


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
                'url'=>"http://www.yeah-use.com/oa/index/task_mobile/task_list_get_auth",
                'data'=>array(
                    'first'=>array('value'=>$user_wx_info['name']."您好，您收到一条新公文",'color'=>"#173177",),                               
                    'keyword1'=>array('value'=>$title,'color'=>"#173177",),
                    'keyword2'=>array('value'=>$sender_name,'color'=>"#173177",),
                    'keyword3'=>array('value'=>$date,'color'=>"#173177",),
                    'remark'=>array('value'=>"点击本消息进行处理！",'color'=>"#173177",),       
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
    public function delete_task($task_id){
        $now =intval(time()); 
        $task= Task::get($task_id);
        $create_time = strtotime($task->create_time);
        $diff = $now-$create_time;
        if($diff<=1800){
            Db::table('task')->where('id',$task_id)->delete();
            Db::table('task_log')->where('task_id',$task_id)->delete();
            $this->success("撤回成功");
        }else{
            $this->error("超过30分钟无法撤回!");
        }
    }
    public function task_count(){
        $view=new View();
        return $view->fetch('task_count');      
    }
    public function get_task_by_dept($start_time,$end_time){
        if(empty($start_time)||empty($end_time)){
            return;
        }
        $user_id_list=Db::table('user')->where(['dept_id'=>['in',[5,67]]])->column('id');
        $res = Db::table('task_log')
                ->alias(['task_log'=>'log'])
                ->join('task','task.id= log.task_id')
                ->field('log.executor_name,task.user_name,task.name')
                ->where(['log.executor'=>['in',$user_id_list]])
                ->where(['log.finish_time'=>['BETWEEN',[$start_time,$end_time]]])
                ->select();
        return $res;
    }
}
