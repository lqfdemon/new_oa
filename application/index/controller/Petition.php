<?php
/************信访*************/
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\PetitionInfo;
use app\index\model\PetitionLog;
use app\index\model\File;

Loader::import('mpdf.mpdf');
Loader::import('weixin.wx_sdk');

class Petition extends CommonController
{
    //首页
	public function index(){
        $view=new View();
        $view->assign('address_widget',true);
        return $view->fetch('index'); 
	}
	public function start(){
        $view=new View();
        $view->assign('address_widget',true);
        $view->assign('upload_widget',true);
        return $view->fetch('start'); 
	}
	public function save_petition(){
		$data = $_POST;
        $result=$this->validate($data,'PetitionInfoValidate');
        if($result !== true){
            $this->error($result);
        }
		$data['creater_id']=Session::get('id');
        $data['creater_name']=Session::get('name');
        $data['create_time']=date("Y-m-d H:i:s");
		$petition = new PetitionInfo();
        $petition->save($data);

        $log_data['sender_id']=Session::get('id');
        $log_data['sender_name']=Session::get('name');
        $log_data['petition_id'] = $petition->id;
        $log_data['time'] = date("Y-m-d H:i:s");
        $executor_list = explode(';', $data['executor']);

        $log_id_list_str = "";
        foreach ($executor_list as $key => $executor) {
        	if(empty($executor)){
        		break;
        	}
        	$id_name_str= explode('|', $executor);
        	$log_data['receiver_id'] = $id_name_str[1];
        	$log_data['receiver_name'] = $id_name_str[0];
        	$log_data['status'] = 0;
            //is_first_step = 1 处理人填写的意见为拟办意见
            $log_data['is_first_step'] = 1;
        	$petition_log = new PetitionLog();
        	$petition_log->save($log_data);
            $log_id_list_str = $log_id_list_str.$petition_log->id.';';
            Log::record($log_id_list_str);
        }
        $this->success("操作成功",'',$log_id_list_str,3);
	}
    public function send_receive_msg_to_mulity_executor($log_id_list_str){
        set_time_limit(120);
        $log_id_list = explode(';', $log_id_list_str);
        foreach ($log_id_list as $key => $log_id) {
            if(!empty($log_id)){
                $petition_log = Db::table('petition_log')
                        ->where('id',$log_id)
                        ->find();
                $petition = Db::table('petition_info')
                        ->where('id',$petition_log['petition_id'])
                        ->find();
                if(empty($petition_log)||empty($petition)){
                    break;
                }
                $this->send_receive_msg_to_executor($petition_log['receiver_id'],$petition_log['sender_name'],$petition['create_time'],$petition['form_title'],$petition['form_id']);
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
                'url'=>"http://www.yeah-use.com/oa/index/petition_mobile/get_auth",
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
    public function delete_task_info($petition_id){
        Db::table('petition_info')->where('id',$petition_id)->delete();
        Db::table('petition_log')->where('petition_id',$petition_id)->delete();
        $this->success("撤回成功");
    }
	public function get_send_list($person_name,$status){
		$where_task['creater_id'] = Session::get('id');
		$recall=[];
        if(!empty($person_name)){
            $where_task['person_name'] = array('like',"%$person_name%");                
        }
        if($status != -1){
            if($status == -2){
                $where_task['status'] = ['in',[0,1]];   
            }else{
                $where_task['status'] = $status;   
            }             
        }
		$task_list = PetitionInfo::where($where_task)->order('id desc')->select()->toArray();		
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
				'petition_id'=>$task['id'],
				'file_list'=>$file_list,
				'file_flag'=>$file_flag,
				'person_name'=>$task['person_name'],
				'index'=>$task['index'],
                'collect_date'=>$task['collect_date'],
				'deadline'=>$task['deadline'],
				'content'=>$task['content'],
				'suggestion'=>$task['suggestion'],
				'creater_name'=>$task['creater_name'],
				'creater_id'=>$task['creater_id'],
				'status_str'=>$status_str,
				'status'=>$task['status'],
			]);
    	}
    	return $recall;
	}
    public function get_not_finished_num(){
        $where_log['status'] = 0;
        $where_log['receiver_id'] = $this->get_user_id();
        return  PetitionLog::where($where_log)->count('id');
    }
                                                    
    public function get_petition_list($type,$person_name){
        if($type == 'not_finished'){
            $where_log['status'] = 0;
            $where_log['receiver_id'] = $this->get_user_id();
        }else if($type == 'finished'){
            $where_log['status'] = ['IN',[1,2]];
            $where_log['receiver_id'] = $this->get_user_id();
        }else{
            $this->error("公文类型错误");
        }

		$petition_id_list = PetitionLog::where($where_log)->column('petition_id');
        $where_task['id'] = array('in',$petition_id_list);
        if(empty($petition_id_list)){
            return [];
        }
        if(!empty($person_name)){
            $where_task['person_name'] = array('like',"%$person_name%");                
        }
        $task_list = PetitionInfo::where($where_task)
                ->order('id desc')
                ->select()
                ->toArray();
        if(empty($task_list)){
            return [];
        }
        $task_log_list = PetitionLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($task_log_list)){
            return [];
        }
        $recall=[];
        foreach ($task_log_list as $key => $log) {
        	foreach ($task_list as $key => $task) {
        		if($log['petition_id'] == $task['id']){
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
        				'petition_id'=>$log['petition_id'],
        				'file_list'=>$file_list,
        				'file_flag'=>$file_flag,
                        'index'=>$task['index'],
        				'person_name'=>$task['person_name'],
        				'collect_date'=>$task['collect_date'],
        				'deadline'=>$task['deadline'],
        				'content'=>$task['content'],
        				'creater_name'=>$task['creater_name'],
        				'creater_id'=>$task['creater_id'],
        				'status_str'=>$status_str,
        				'status'=>$task['status'],
        			]);
        		}
        	}
        }
        return $recall;
    }
    public function get_suggestion_list($petition_id){
    	$suggestion_list = [];
    	$petition = PetitionInfo::get($petition_id);
    	array_push($suggestion_list, ['user_name'=>$petition->creater_name,'user_id'=>$petition->creater_id,'suggestion'=>$petition->suggestion]);
    	$where_log = ['petition_id'=>$petition_id];
    	$petition_log_list = PetitionLog::where($where_log)->order('id desc')->select()->toArray();
    	foreach ($petition_log_list as $key => $petition_log) {
    		array_push($suggestion_list, [
    						'user_name'=>$petition_log['receiver_name'],
    						'user_id'=>$petition_log['receiver_id'],
    						'suggestion'=>$petition_log['suggestion'],
    						'status'=>$petition_log['status']
    					]
    		);
    	}
    	if($petition->status == 2){
    		array_push($suggestion_list, [
    						'user_name'=>Session::get('name'),
    						'user_id'=>Session::get('id'),
    						'suggestion'=>$petition->result,
    						'status'=>2,
    					]
    		);    		
    	}
    	return $suggestion_list;
    }
    public function tansform(){
    	$data = $_POST;
    	$petition_log = PetitionLog::get($data['petition_log_id']);
    	$petition_log->save(['suggestion'=>$data['suggestion'],'status'=>1,'finished_time'=>date("Y-m-d H:i:s")]);

        $log_data['sender_id']=Session::get('id');
        $log_data['sender_name']=Session::get('name');
        $log_data['petition_id'] = $data['petition_id'];
        $log_data['time'] = date("Y-m-d H:i:s");
        $executor_list = explode(';', $data['executor']);
        $log_id_list_str = "";
        foreach ($executor_list as $key => $executor) {
        	if(empty($executor)){
        		break;
        	}
        	$id_name_str= explode('|', $executor);
        	$log_data['receiver_id'] = $id_name_str[1];
        	$log_data['receiver_name'] = $id_name_str[0];
			$log_data['status'] = 0;
			$log_data['parent_node'] = $data['petition_log_id'];
        	$petition_log = new PetitionLog();
        	$petition_log->save($log_data);
            $petition = PetitionInfo::get($data['petition_id']);
            $log_id_list_str = $log_id_list_str.$petition_log->id.';';
            Log::record($log_id_list_str);
        } 
        $where_log = ['petition_id'=>$data['petition_id'],'status'=>0];
        $not_finished_petition_log=PetitionLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_petition_log)){
        	$petition = PetitionInfo::get($data['petition_id']);
        	$petition->status =1;//设置为意见收集完成
        	$petition->save();
        }

        $this->success("操作成功",'',$log_id_list_str,3); 	
    }
    public function reject(){
        $data = $_POST;
        $petition_log = PetitionLog::get($data['petition_log_id']);
        $petition_log->save(['suggestion'=>$data['suggestion'],'status'=>2,'finished_time'=>date("Y-m-d H:i:s")]);  
        //是否处理完成
        $where_log = ['petition_id'=>$data['petition_id'],'status'=>0];
        $not_finished_petition_log=PetitionLog::where($where_log)->order('id desc')->select()->toArray();
        if(empty($not_finished_petition_log)){
            $petition = PetitionInfo::get($data['petition_id']);
            $petition->status =1;//设置为意见收集完成
            $petition->save();
        }

        $this->success("操作成功");        
    }
    function save_result(){
    	$petition = PetitionInfo::get($_POST['petition_id']);
    	$petition->status =2;//设置为处理完成
    	$petition->result =$_POST['result'];
        $petition->save();
        $this->success("操作成功"); 
	}
	function show_process(){
        $view=new View();
        return $view->fetch('test'); 		
	}
	function get_pass_log_status($petition_id,$type){
		$petition = PetitionInfo::get($petition_id);
		if(empty($petition)){
			return $this->errot("获取公文传阅信息失败");
		}

		$log_status_list = [];
		$status_data=[
			'id'=>0,
			'text'=>"发起人 <span class='tree_iterm_name'>：".$petition['creater_name']."</span>",
			'parent'=>"#",
			'icon'=>'fa fa-share-square-o',
			'state'=>['opened'=> true],
		];
		array_push($log_status_list,$status_data);
    	$where_log = ['petition_id'=>$petition_id];
		$petition_log_list = PetitionLog::where($where_log)->order('id desc')->select()->toArray();
    	foreach ($petition_log_list as $key => $petition_log) {
			$text="";
			$icon ="";
			$suggestion = "";
			if($petition_log['status'] == 0){
				$text = "(待处理) <span class='tree_iterm_name'>".$petition_log['receiver_name'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;'."<a  onclick='send_msg(".$petition_log['receiver_id'].");'>";
                if($type == 'send'){
                    $text .= "<i class='fa fa-envelope'></i>短信提醒</a>";
                }
				$icon = "fa fa-hourglass-o";
			}elseif ($petition_log['status'] == 1) {
				$text = "(已处理) <span class='tree_iterm_name'>".$petition_log['receiver_name']."</span>&nbsp;&nbsp;&nbsp;&nbsp;意见：<span class='tree_iterm_suggestion'>".$petition_log['suggestion'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;'.$petition_log['finished_time'];
				$icon = "fa fa-check-square-o";
			}else {
				$text = "(已拒绝) <span class='tree_iterm_name'>".$petition_log['receiver_name']."</span>&nbsp;&nbsp;&nbsp;&nbsp;意见：<span class='tree_iterm_suggestion'>".$petition_log['suggestion'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;'.$petition_log['finished_time'];
				$icon = "fa fa-close";
			}
			$status_data=[
				'id'=>$petition_log['id'],
				'text'=>$text,
				'parent'=>$petition_log['parent_node'],
				'icon'=>$icon,
				'state'=>['opened'=> true],
			];
			array_push($log_status_list,$status_data);
    	}
		return $log_status_list;
	}
    public function send_warning_msg($receiver_id){
        $user_info = Db::table('user')->where(['id'=>$receiver_id])->find();
        if(empty($user_info['mobile_tel'])){
            $this->error("用户手机号码为空");
        }else{
            $msg = "您有一条公文流转等待处理，请登录卫计OA系统处理";
            $res = $this->send_sginal_msg($user_info['mobile_tel'],$msg);
            Log::record($res);
            $this->success("发送完成");
        }
    }
    function get_have_sended_unite_list(){
        $unit_list=Db::table("petition_info")
                    ->field('form_unit')
                    ->group('form_unit')
                    ->select();      
        return $unit_list;  
    }
    public function print_petition_result($petition_id){
        $petition = PetitionInfo::get($petition_id);
        $mpdf=new \mPDF('zh-CN',array(210,320),'','宋体');
        $mpdf->useAdobeCJK = true;
        $first_suggestion ="";
        $leader_suggestion ="";
        $other_suggestion ="";
        $task_log_list = PetitionLog::where(['petition_id'=>$petition_id])->select()->toArray();
        foreach ($task_log_list as $key => $task_log) {
            $user_info = Db::table('user')->where(['id'=>$task_log['receiver_id']])->find();
            $time = date_format(date_create($task_log['finished_time']),"y.m.d");
            if($task_log['is_first_step'] == 1){
                //拟办意见
                $first_suggestion = $first_suggestion.$task_log['suggestion'].'<span class="time">&nbsp;&nbsp;'.$time.'</span>&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><br>';
            }else{
                if($user_info['dept_id'] === 67){
                    //领导批示
                    $leader_suggestion = $leader_suggestion.$task_log['suggestion'].'<span class="time">&nbsp;&nbsp;'.$time.'</span>&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><br>';
                }else{
                    //传阅签章
                    $other_suggestion = $other_suggestion.$task_log['suggestion'].'<span class="time">&nbsp;&nbsp;'.$time.'</span>&nbsp;&nbsp;<img src="'.$user_info['sign_pic'].'"><br>';
                }
            }
        }

        $stylesheet = ' 
            body{font-size: 16px;color:black;line-height:30px;font-family:方正大标宋简体}
            ul{list-style: none;}ul li{float: left;}
            img {display:block;top:2px;}
            .time{font-size: 14px;color:black}
            .sub_title{position: relative;width: 100%;text-align: right;font-size: 16px;padding-bottom: 10px;color:red;}
            .title{position: relative;width: 100%;text-align: center;font-size: 24px;font-weight: bold;padding-bottom: 20px;color:red;padding-bottom:35px;}
            .check-info{position:relative;width:100%;border-top:3px solid red;border-left:3px solid red; }
            .check-info td{border-right:3px solid red;border-bottom:3px solid red;padding:20px; text-align: center;}
            .top-1{width: 14%;}
            .top-2{width: 30%;}
            .top-3{width: 23%;}
            .top-4{width: 23%;}
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
            <div class="sub_title">新都卫计信访编号：  '.$petition['id'].'号
            </div>
            <div class="title">成都市新都区卫生和计划生育局办公室信访处理笺
            </div>
            <table class="check-info" cellspacing="0">
                <tr >
                    <td class="top-1 td-title">姓名</td>
                    <td class="top-2 td-title" >信访时间/办理期限</td>
                    <td class="top-3 td-title" >信访来源</td>
                    <td class="top-4 td-title">工单编号</td>
                </tr>
                <tr>
                    <td class="top-1 td-content">'.$petition['person_name'].'</td>
                    <td class="top-2 td-content">'.$petition['collect_date'].'/'.$petition['deadline'].'</td>
                    <td class="top-3 td-content">'.$petition['source'].'</td>
                    <td class="top-4 td-content" >'.$petition['index'].'</td>             
                </tr>
                <tr>
                    <td class="top-1 td-title"><br><br>信<br><br>访<br><br>题<br><br>目<br><br><br></td>
                    <td class="top-2 " colspan="3">'.$petition['content'].'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">拟<br><br>办<br><br>意<br><br>见</td>
                    <td class="top-2 td-suggestion" colspan="3">'.$first_suggestion.'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title">领<br><br>导<br><br>批<br><br>示</td>
                    <td class="top-2 td-suggestion" colspan="3">'.$leader_suggestion.'</td>
                </tr>
                <tr>
                    <td class="top-1 td-title"><br><br>承<br><br>办<br><br>情<br><br>况<br><br><br></td>
                    <td class="top-2 td-content" colspan="3">'.$petition['result'].'</td>
                </tr>
            </table>
        </body>
        ';
        $mpdf->WriteHTML($html,2);
        $mpdf->Output();   
                exit;       
    }

}