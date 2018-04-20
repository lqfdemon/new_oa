<?php
/************公文传阅*************/
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\TaskPassInfo;
use app\index\model\TaskPassLog;
use app\index\model\File;

Loader::import('mpdf.mpdf');

class TaskPass extends CommonController
{
	public function index(){
        $view=new View();
        $view->assign('address_widget',true);
        return $view->fetch('index'); 
	}
	public function start_pass(){
        $view=new View();
        $view->assign('address_widget',true);
        $view->assign('upload_widget',true);
        return $view->fetch('start_pass'); 
	}
	public function save_task_pass(){
		$data = $_POST;
		$data['creater_id']=Session::get('id');
        $data['creater_name']=Session::get('name');
		$task_pass = new TaskPassInfo();
        $task_pass->save($data);

        $log_data['sender_id']=Session::get('id');
        $log_data['sender_name']=Session::get('name');
        $log_data['task_pass_id'] = $task_pass->id;
        $log_data['time'] = date("Y-m-d H:i:s");
        $executor_list = explode(';', $data['executor']);
        foreach ($executor_list as $key => $executor) {
        	if(empty($executor)){
        		break;
        	}
        	$id_name_str= explode('|', $executor);
        	$log_data['receiver_id'] = $id_name_str[1];
        	$log_data['receiver_name'] = $id_name_str[0];
        	$log_data['status'] = 0;
        	$task_pass_log = new TaskPassLog();
        	$task_pass_log->save($log_data);
        }
        $this->success("操作成功");
	}
	public function get_send_pass_list(){
		$where_task['creater_id'] = Session::get('id');
		$recall=[];
		$task_list = TaskPassInfo::where($where_task)->order('id desc')->select()->toArray();		
    	foreach ($task_list as $key => $task) {
            $file_list= array();
            $file_flag= 0;
            if(!empty($task['add_file'])){
                //有附件
                $file_flag= 1;
                $file_id_list = explode(';', $task['add_file']);
                foreach ($file_id_list as $id_key => $file_id) {
                    $file = File::get($file_id);
                    $file_url= url('download','file_id='.$file_id);
                    if(!empty($file)){
                        array_push($file_list, ['file_name'=>$file->name,'file_url'=>$file_url]);
                    }
                }
            }
            $status_str = "";
            if($task['status'] == 0){
            	$status_str = "意见收集中";
            }else if($task['status'] == 1){
            	$status_str = "意见收集完成";
            }else if($task['status'] == 2){
            	$status_str = "办理完成";
            }
            array_push($recall,[
				'task_pass_id'=>$task['id'],
				'file_list'=>$file_list,
				'file_flag'=>$file_flag,
				'form_title'=>$task['form_title'],
				'form_unit'=>$task['form_unit'],
				'form_time'=>$task['form_time'],
				'form_leavel'=>$task['form_leavel'],
				'suggestion'=>$task['suggestion'],
				'creater_name'=>$task['creater_name'],
				'creater_id'=>$task['creater_id'],
				'status_str'=>$status_str,
				'status'=>$task['status'],
			]);
    	}
    	return $recall;
	}
    public function get_task_pass_list($type,$title){
        if($type == 'not_finished'){
            $where_log['status'] = 0;
            $where_log['receiver_id'] = $this->get_user_id();
        }else if($type == 'finished'){
            $where_log['status'] = 1;
            $where_log['receiver_id'] = $this->get_user_id();
        }else{
            $this->error("公文类型错误");
        }

		$task_pass_id_list = TaskPassLog::where($where_log)->column('task_pass_id');
        $where_task['id'] = array('in',$task_pass_id_list);
        if(!empty($title)){
            $where_task['form_title'] = array('like',"%$title%");                
        }
        $task_list = TaskPassInfo::where($where_task)->order('id desc')->select()->toArray();
        $task_log_list = TaskPassLog::where($where_log)->select()->toArray();

        $recall=[];
        foreach ($task_log_list as $key => $log) {
        	foreach ($task_list as $key => $task) {
        		if($log['task_pass_id'] == $task['id']){
		            $file_list= array();
		            $file_flag= 0;
		            if(!empty($task['add_file'])){
		                //有附件
		                $file_flag= 1;
		                $file_id_list = explode(';', $task['add_file']);
		                foreach ($file_id_list as $id_key => $file_id) {
		                    $file = File::get($file_id);
		                    $file_url= url('download','file_id='.$file_id);
		                    if(!empty($file)){
		                        array_push($file_list, ['file_name'=>$file->name,'file_url'=>$file_url]);
		                    }
		                }
		            }
		            $status_str = "";
		            if($task['status'] == 0){
		            	$status_str = "意见收集中";
		            }else if($task['status'] == 1){
		            	$status_str = "意见收集完成";
		            }else if($task['status'] == 2){
		            	$status_str = "处理完成";
		            }
		            array_push($recall,[
        				'id'=>$log['id'],
        				'task_pass_id'=>$log['task_pass_id'],
        				'file_list'=>$file_list,
        				'file_flag'=>$file_flag,
        				'form_title'=>$task['form_title'],
        				'form_unit'=>$task['form_unit'],
        				'form_time'=>$task['form_time'],
        				'form_leavel'=>$task['form_leavel'],
        				'suggestion'=>$task['suggestion'],
        				'creater_name'=>$task['creater_name'],
        				'creater_id'=>$task['creater_id'],
        				'status_str'=>$status_str,
        				'status'=>$task['status'],
        			]);
        		}
        	}
        }
        //dump($recall);
        return $recall;
        /*
        $task_pass_id_list = TaskPassLog::where($where_log)->column('task_pass_id');
        $where_task['id'] = array('in',$task_pass_id_list);
        if(!empty($title)){
            $where_task['form_title'] = array('like',"%$title%");                
        }
        $task_list = TaskPassInfo::where($where_task)->order('id desc')->select()->toArray();
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
        */
        //return $task_list;
    }
    public function get_suggestion_list($task_pass_id){
    	$suggestion_list = [];
    	$task_pass = TaskPassInfo::get($task_pass_id);
    	array_push($suggestion_list, ['user_name'=>$task_pass->creater_name,'user_id'=>$task_pass->creater_id,'suggestion'=>$task_pass->suggestion]);
    	$where_log = ['task_pass_id'=>$task_pass_id];
    	$task_pass_log_list = TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
    	foreach ($task_pass_log_list as $key => $task_pass_log) {
    		array_push($suggestion_list, [
    						'user_name'=>$task_pass_log['receiver_name'],
    						'user_id'=>$task_pass_log['receiver_id'],
    						'suggestion'=>$task_pass_log['suggestion'],
    						'status'=>$task_pass_log['status']
    					]
    		);
    	}
    	if($task_pass->status == 2){
    		array_push($suggestion_list, [
    						'user_name'=>Session::get('name'),
    						'user_id'=>Session::get('id'),
    						'suggestion'=>$task_pass->result,
    						'status'=>2,
    					]
    		);    		
    	}
    	return $suggestion_list;
    }
    public function tansform_pass(){
    	$data = $_POST;
    	$task_pass_log = TaskPassLog::get($data['task_pass_log_id']);
    	$task_pass_log->save(['suggestion'=>$data['suggestion'],'status'=>1,'finished_time'=>date("Y-m-d H:i:s")]);

        $log_data['sender_id']=Session::get('id');
        $log_data['sender_name']=Session::get('name');
        $log_data['task_pass_id'] = $data['task_pass_id'];
        $log_data['time'] = date("Y-m-d H:i:s");
        $executor_list = explode(';', $data['executor']);
        foreach ($executor_list as $key => $executor) {
        	if(empty($executor)){
        		break;
        	}
        	$id_name_str= explode('|', $executor);
        	$log_data['receiver_id'] = $id_name_str[1];
        	$log_data['receiver_name'] = $id_name_str[0];
			$log_data['status'] = 0;
			$log_data['parent_node'] = $data['task_pass_log_id'];
        	$task_pass_log = new TaskPassLog();
        	$task_pass_log->save($log_data);
        } 
        $where_log = ['task_pass_id'=>$data['task_pass_id'],'status'=>0];
        $not_finished_task_pass_log=TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_task_pass_log)){
        	$task_pass = TaskPassInfo::get($data['task_pass_id']);
        	$task_pass->status =1;//设置为意见收集完成
        	$task_pass->save();
        }

        $this->success("操作成功");   	
    }
    public function reject_pass(){
        $data = $_POST;
        $task_pass_log = TaskPassLog::get($data['task_pass_log_id']);
        $task_pass_log->save(['suggestion'=>$data['suggestion'],'status'=>2,'finished_time'=>date("Y-m-d H:i:s")]);  
        //是否处理完成
        $where_log = ['task_pass_id'=>$data['task_pass_id'],'status'=>0];
        $not_finished_task_pass_log=TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_task_pass_log)){
            $task_pass = TaskPassInfo::get($data['task_pass_id']);
            $task_pass->status =1;//设置为意见收集完成
            $task_pass->save();
        }

        $this->success("操作成功");        
    }
    function save_result(){
    	$task_pass = TaskPassInfo::get($_POST['task_pass_id']);
    	$task_pass->status =2;//设置为处理完成
    	$task_pass->result =$_POST['result'];
        $task_pass->save();
        $this->success("操作成功"); 
	}
	function show_process(){
        $view=new View();
        return $view->fetch('test'); 		
	}
	function get_pass_log_status($task_pass_id){
		$task_pass = TaskPassInfo::get($task_pass_id);
		if(empty($task_pass)){
			return $this->errot("获取公文传阅信息失败");
		}

		$log_status_list = [];
		$status_data=[
			'id'=>0,
			'text'=>"发起人：".$task_pass['creater_name']."&nbsp;&nbsp;&nbsp;&nbsp;拟办意见：".$task_pass['suggestion'],
			'parent'=>"#",
			'icon'=>'fa fa-share-square-o',
			'state'=>['opened'=> true],
		];
		array_push($log_status_list,$status_data);
    	$where_log = ['task_pass_id'=>$task_pass_id];
		$task_pass_log_list = TaskPassLog::where($where_log)->order('id desc')->select()->toArray();
    	foreach ($task_pass_log_list as $key => $task_pass_log) {
			$text="";
			$icon ="";
			$suggestion = "";
			if($task_pass_log['status'] == 0){
				$text = "(待处理) ".$task_pass_log['receiver_name'];
				$icon = "fa fa-hourglass-o";
			}elseif ($task_pass_log['status'] == 1) {
				$text = "(已处理) ".$task_pass_log['receiver_name']."&nbsp;&nbsp;&nbsp;&nbsp;意见：".$task_pass_log['suggestion'].'&nbsp;&nbsp;&nbsp;&nbsp;'.$task_pass_log['finished_time'];
				$icon = "fa fa-check-square-o";
			}else {
				$text = "(已拒绝) ".$task_pass_log['receiver_name']."&nbsp;&nbsp;&nbsp;&nbsp;意见：".$task_pass_log['suggestion'].'&nbsp;&nbsp;&nbsp;&nbsp;'.$task_pass_log['finished_time'];
				$icon = "fa fa-close";
			}
			$status_data=[
				'id'=>$task_pass_log['id'],
				'text'=>$text,
				'parent'=>$task_pass_log['parent_node'],
				'icon'=>$icon,
				'state'=>['opened'=> true],
			];
			array_push($log_status_list,$status_data);
    	}
		return $log_status_list;
	}
    function get_have_sended_unite_list(){
        $unit_list=Db::table("task_pass_info")
                    ->field('form_unit')
                    ->group('form_unit')
                    ->select();      
        return $unit_list;  
    }
    public function test($task_pass_id){
        $leader_suggestion ="";
        $other_suggestion ="";
        $task_log_list = TaskPassLog::where(['task_pass_id'=>$task_pass_id])->select()->toArray();
        dump($task_log_list);
        foreach ($task_log_list as $key => $task_log) {
            $user_info = Db::table('user')->where(['id'=>$task_log['receiver_id']])->find();
            if($user_info['dept_id'] === 67){
                $leader_suggestion = $leader_suggestion.$task_log['suggestion'].'<img src="'.$user_info['sign_pic'].'"><br>';
            }else{
                $other_suggestion = $other_suggestion.$task_log['suggestion'].'<img src="'.$user_info['sign_pic'].'"><br>';
            }
        }
        dump($leader_suggestion);
        dump($other_suggestion);
    }
    public function download($file_id){     
        $File = new File();
        $root = FILE_DOWNLOAD_ROOT_PATH;   
        if (false === $File -> download($root, $file_id)) {
            $this -> error($File -> getError());
        }   
    }
    public function print_task_pass_result($task_pass_id){
        $task_pass = TaskPassInfo::get($task_pass_id);
        $form_time = date_format(date_create($task_pass['form_time']),"Y.m.d");
        $mpdf=new \mPDF('zh-CN',array(210,320),'','宋体');
        $mpdf->useAdobeCJK = true;
        $leader_suggestion ="";
        $other_suggestion ="";
        $task_log_list = TaskPassLog::where(['task_pass_id'=>$task_pass_id])->select()->toArray();
        foreach ($task_log_list as $key => $task_log) {
            $user_info = Db::table('user')->where(['id'=>$task_log['receiver_id']])->find();
            $time = date_format(date_create($task_log['finished_time']),"y.m.d");
            if($user_info['dept_id'] === 67){
                $leader_suggestion = $leader_suggestion.$task_log['suggestion'].'<span class="time">&nbsp;&nbsp;'.$time.'</span>&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><br>';
                /*
                $leader_suggestion = $leader_suggestion.$task_log['suggestion'].'&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><span class="time">&nbsp;&nbsp;'.$time.'</span><br>';
                */
            }else{
                $other_suggestion = $other_suggestion.$task_log['suggestion'].'<span class="time">&nbsp;&nbsp;'.$time.'</span>&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><br>';
                /*
                $other_suggestion = $other_suggestion.$task_log['suggestion'].'&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><span class="time">&nbsp;&nbsp;'.$time.'</span><br>';
                */
            }
        }

        $stylesheet = ' 
            body{font-size: 16px;color:black;line-height:30px;font-family:方正大标宋简体}
            ul{list-style: none;}ul li{float: left;}
            img {display:block;top:2px;}
            .time{font-size: 16px;color:black}
            .title{position: relative;width: 100%;text-align: center;font-size: 24px;font-weight: bold;padding-bottom: 20px;color:red;padding-bottom:35px;}
            .check-info{position:relative;width:100%;border-top:3px solid red;border-left:3px solid red; }
            .check-info td{border-right:3px solid red;border-bottom:3px solid red;padding:20px; text-align: center;}
            .top-1{width: 16%;}
            .top-2{width: 22%;}
            .top-3{width: 16%;}
            .top-4{width: 12%;}
            .top-5{width: 16%;}
            .top-6{width: 14%;}
            .td-title{font-size: 16px;color:red}
            .td-content{font-size: 16px;color:black;font-family:方正大标宋简体}
            .td-suggestion{font-size: 16px;color:black;line-height:30px;font-family:方正大标宋简体}
            .bottom-1{width: 20%;}.bottom-2{width: 30%;}.bottom-3{width: 20%;}.bottom-4{width: 30%;}
            .sign-box{position: relative;width:100%;padding-top: 30px;padding-left:0px;}
            .sign-box li{text-align: left;position:relative;width:25%;}
            .time-box{position: relative;width:100%;padding-top:30px;}
            .time-box li{position: relative;width:100%;text-align: right;}
            td{font-size: 10px;}
            .bottom-ps{position:relative;width:100%;padding-top: 60px;}'
        ; 
        $mpdf->WriteHTML($stylesheet,1);  
        $html=' 
        <body>
            <div class="title">成都市新都区卫生和计划生育局公文处理单
            </div>
            <table class="check-info" cellspacing="0">
                <tr >
                    <td class="top-1 td-title">来文单位</td>
                    <td class="top-2 td-content">'.$task_pass['form_unit'].'</td>
                    <td class="top-3 td-title">来文时间</td>
                    <td class="top-4 td-content">'.$form_time.'</td>
                    <td class="top-5 td-title">来文字号</td>
                    <td class="top-6 td-content">'.$task_pass['form_id'].'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title" >来文标题</td>
                    <td class="top-2 td-content" colspan="3">'.$task_pass['form_title'].'</td>
                    <td class="top-3 td-title">等级</td>
                    <td class="top-4 td-content">'.$task_pass['form_leavel'].'</td>             
                </tr>
                <tr>
                    <td class="top-1 td-title">拟<br>办<br>意<br>见</td>
                    <td class="top-2 td-suggestion" colspan="5">'.$task_pass['suggestion'].'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">领<br><br><br>导<br><br><br>批<br><br><br>示</td>
                    <td class="top-2 td-suggestion" colspan="5">'.$leader_suggestion.'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">传<br><br><br>阅<br><br><br>人<br><br><br>签<br><br><br>章</td>
                    <td class="top-2 td-suggestion" colspan="5">'.$other_suggestion.'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">办<br>理<br>结<br>果</td>
                    <td class="top-2 td-content" colspan="5">'.$task_pass['result'].'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">备<br><br>注</td>
                    <td class="top-2 " colspan="5"></td>
                </tr>
            </table>
        </body>
        ';
        $mpdf->WriteHTML($html,2);
        $mpdf->Output();   
                exit;       
    }

}