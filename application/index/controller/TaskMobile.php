<?php
namespace app\index\controller;
use think\Session;
use think\View;
use think\Db;
use think\Controller;
use think\Log;
use think\Loader;

use app\index\model\TaskLog;
use app\index\model\Task;
use app\index\model\File;
use app\index\model\User;

Loader::import('weixin.wx_sdk');

class TaskMobile extends Controller
{
	public function task_list_get_auth(){
        $target_url=url("task_list_mobile");
        $redirect_url = 'http://'.$_SERVER['HTTP_HOST'].$target_url;
        $weixin = new \class_weixin();
        $jump_url=$weixin->oauth2_authorize($redirect_url,"snsapi_base",'123');
        $this->redirect($jump_url);
    }
    public function task_list_mobile($code){
        $open_id = Session::get('open_id');
        if(empty($open_id)){
            $weixin = new \class_weixin();
            $OAuth2_Access_Token = $weixin->oauth2_access_token($code);
            $open_id = $OAuth2_Access_Token['openid'];
            Session::set('open_id',$open_id);
        }
        $view=new View();
        $user_wx_info = Db::table('user_wx_info')
                ->where('open_id',$open_id)
                ->find();
        $task_id_list = Db::table('task_log')
                ->where('executor',$user_wx_info['user_id'])
                ->where('status',0)
                ->column('task_id');
        if(empty($task_id_list)){
            $task_list = [];
        }else{
            $task_list = Db::table('task')
                ->where(['id'=>['in',$task_id_list]])
                ->order('id','desc')
                ->select();
            foreach ($task_list as $key => $task) {
                $task_list[$key]['send_time']=date("Y-m-d H:i:s",$task['create_time']);
            }
        }
        Log::record($task_list);
        $view->assign('user_id',$user_wx_info['user_id']);
        $view->assign('task_list',$task_list);
        return $view->fetch('task_list_mobile'); 
    }
    public function get_file_info_by_id($id)
    {
        return Db::table('file')->where('id',$id)->find();
    }
    public function get_file_list($task_id){
        $task = Db::table('task')
            ->where('id',$task_id)
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
                    array_push($task['file_list'], [
                        'file_name'=>$file->name,
                        'file_url'=>$file_url,
                        'id'=>$file->id,
                    ]);
                }
            }
        }
        return $task['file_list'];
    }
    public function receice($task_id,$executor=''){
        $where_log['task_id'] = $task_id;
        $where_log['executor'] = $executor;
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
    public function reject($task_id,$reject_reson,$executor=''){
        $where_log['task_id'] = $task_id;
        $where_log['executor'] = $executor;
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
    public function download($file_id){     
        $File = new File();
        $root = FILE_DOWNLOAD_ROOT_PATH;   
        if (false === $File -> download($root, $file_id)) {
            $this -> error($File -> getError());
        }   
    }
}