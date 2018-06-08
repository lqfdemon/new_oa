<?php
namespace app\index\controller;
use think\Session;
use think\View;
use think\Db;
use think\Controller;
use think\Log;
use think\Loader;

use app\index\model\TaskPassInfo;
use app\index\model\TaskPassLog;
use app\index\model\File;
use app\index\model\User;

Loader::import('weixin.wx_sdk');

class TaskPassMobile extends Controller
{
    public function get_auth(){
        $target_url=url("not_finished_list");
        $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$target_url;
        $weixin = new \class_weixin();
        $jump_url=$weixin->oauth2_authorize($redirect_url,"snsapi_base",'123');
        $this->redirect($jump_url);
    }
	public function not_finished_list($code){
		$open_id = Session::get('open_id');
        if(empty($open_id)){
            $weixin = new \class_weixin();
            $OAuth2_Access_Token = $weixin->oauth2_access_token($code);
            $open_id = $OAuth2_Access_Token['openid'];
            Session::set('open_id',$open_id);
        }

		$user_wx_info = Db::table('user_wx_info')
                ->where('open_id',$open_id)
                ->find();
        $where_log['status'] = 0;
        $where_log['receiver_id'] = $user_wx_info['user_id'];
        $task_log_list = TaskPassLog::where($where_log)->select()->toArray();
        foreach ($task_log_list as $key => $task_log) {
        	$task_pass = TaskPassInfo::get($task_log['task_pass_id']);
        	$task_log_list[$key]['form_title'] = $task_pass['form_title'];
        	$task_log_list[$key]['form_unit'] = $task_pass['form_unit'];
        	$task_log_list[$key]['form_time'] = $task_pass['form_time'];
        	$task_log_list[$key]['form_id'] = $task_pass['form_id'];
        }
        $view=new View();
        $view->assign('user_id',$user_wx_info['user_id']);
        $view->assign('task_list',$task_log_list);
        return $view->fetch('not_finished_list'); 
    }
    function get_file_list($task_pass_id){
        $task = Db::table('task_pass_info')
            ->where('id',$task_pass_id)
            ->find();
        $task['file_list']= array();
        $task['file_flag']= 0;
        if(!empty($task['add_file'])){
            $task['file_flag']= 1;
            $file_id_list = explode(';', $task['add_file']);
            foreach ($file_id_list as $id_key => $file_id) {
                $file = File::get($file_id);
                $file_url= url('download','file_id='.$file_id);
                if(!empty($file)){
                    array_push($task['file_list'], ['file_name'=>$file->name,'file_url'=>$file_url]);
                }
            }
        }
        return $task['file_list'];
    }
    public function get_suggest_list($task_pass_id){
        $suggest_list = Db::table('task_pass_log')
            ->where('task_pass_id',$task_pass_id)
            ->select();
        return $suggest_list;
    }
    public function reject_pass($task_pass_id,$task_pass_log_id,$suggestion){
        $task_pass_log = TaskPassLog::get($task_pass_log_id);
        $task_pass_log->save(['suggestion'=>$suggestion,'status'=>2,'finished_time'=>date("Y-m-d H:i:s")]);  
        //是否处理完成
        $where_log = ['task_pass_id'=>$task_pass_id,'status'=>0];
        $not_finished_task_pass_log=TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_task_pass_log)){
            $task_pass = TaskPassInfo::get($task_pass_id);
            $task_pass->status =1;//设置为意见收集完成
            $task_pass->save();
        }

        $this->success("操作成功");        
    }
    public function get_allow_users(){
        $user_name_list=Db::table('user')
            ->where(['dept_id'=>['in',['67','5']]])
            ->field('name')
            ->select();
        return $user_name_list;
    }
    public function download($file_id){     
        $File = new File();
        $root = FILE_DOWNLOAD_ROOT_PATH;   
        if (false === $File -> download($root, $file_id)) {
            $this -> error($File -> getError());
        }   
    }
    public function tansform_pass($task_pass_log_id,$sender_id,$suggestion,$executors_str){

        $task_pass_log = TaskPassLog::get($task_pass_log_id);
        $task_pass_log->save(['suggestion'=>$suggestion,'status'=>1,'finished_time'=>date("Y-m-d H:i:s")]);

        $sender=User::get($sender_id);
        $log_data['sender_id']=$sender_id;
        $log_data['sender_name']=$sender['name'];
        $log_data['task_pass_id'] = $task_pass_log['task_pass_id'];
        $log_data['time'] = date("Y-m-d H:i:s");
        $executor_name_list = explode(',', $executors_str);

        Log::record($executor_name_list);
        $log_id_list_str = "";
        foreach ($executor_name_list as $key => $executor_name) {
            if(empty($executor_name)){
                break;
            }
            $executor = Db::table('user')->where('name',$executor_name)->find();
            Log::record($executor);

            $log_data['receiver_id'] = $executor['id'];
            $log_data['receiver_name'] = $executor['name'];
            $log_data['status'] = 0;
            $log_data['parent_node'] = $task_pass_log_id;
            $task_pass_log = new TaskPassLog();
            $task_pass_log->save($log_data);
            $task_pass = TaskPassInfo::get($task_pass_log['task_pass_id']);
            $log_id_list_str = $log_id_list_str.$task_pass_log->id.';';
            Log::record($log_id_list_str);
        }        
        $where_log = ['task_pass_id'=>$task_pass_log['task_pass_id'],'status'=>0];
        $not_finished_task_pass_log=TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_task_pass_log)){
            $task_pass = TaskPassInfo::get($task_pass_log['task_pass_id']);
            $task_pass->status =1;//设置为意见收集完成
            $task_pass->save();
        }
        $this->success("操作成功",'',$log_id_list_str,3);
    }
    public function send_receive_msg_to_mulity_executor($log_id_list_str){
        set_time_limit(300);
        $log_id_list = explode(';', $log_id_list_str);
        foreach ($log_id_list as $key => $log_id) {
            if(!empty($log_id)){
                $task_pass_log = Db::table('task_pass_log')
                        ->where('id',$log_id)
                        ->find();
                $task_pass = Db::table('task_pass_info')
                        ->where('id',$task_pass_log['task_pass_id'])
                        ->find();
                if(empty($task_pass_log)||empty($task_pass)){
                    break;
                }
                $this->send_receive_msg_to_executor($task_pass_log['receiver_id'],$task_pass_log['sender_name'],$task_pass['create_time'],$task_pass['form_title'],$task_pass['form_id']);
            }
        }
    }
    public function send_receive_msg_to_executor($executor_id,$sender_name,$send_time,$title,$index){
        $user_wx_info_list = Db::table('user_wx_info')
                            ->where(['user_id'=>$executor_id])
                            ->select();
        foreach ($user_wx_info_list as $key => $user_wx_info) {
            $date = date('Y-m-d H:i:s');    
            $template_id="b54O1WKPV6nY_tPylREb1KjGcTKI4vQJRE3fz15nW58";
            $executor_open_id = $user_wx_info['open_id'];
            $jsonText = array(
                'touser'=>$executor_open_id, 'template_id'=>$template_id ,
                'url'=>"http://www.xcwjwx.com/oa/index/task_pass_mobile/get_auth",
                'data'=>array(
                    'first'=>array('value'=>$user_wx_info['name']."您好，您有一条公文流转待处理",'color'=>"#173177",),                               
                    'keyword1'=>array('value'=>$sender_name,'color'=>"#173177",),
                    'keyword2'=>array('value'=>$send_time,'color'=>"#173177",),
                    'keyword3'=>array('value'=>$title,'color'=>"#173177",),
                    'keyword4'=>array('value'=>$index,'color'=>"#173177",),
                    'remark'=>array('value'=>"点击本消息进行处理！",'color'=>"#173177",),       
                )
            );  
            $template_data = json_encode($jsonText);
            $weixin = new \class_weixin();
            $weixin->send_template_message($template_data);
        }      
        $this->success('模板消息发送完成');
    }
}