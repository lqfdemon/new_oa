<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\TravelApplyStudy;

Loader::import('mpdf.mpdf');

class Travel extends CommonController
{
	public function manage(){
		$view=new View();
		return $view->fetch('manage'); 	
	}
	public function apply_study(){
		$view=new View();
		$view->assign('address_widget',true);
		return $view->fetch('apply_study'); 	
	}
	public function add_study_apply(){
		$data=$_POST;
    $executor_list = explode(';', $data['executor']);
    unset($data['executor']);
    foreach ($executor_list as $key => $executor) {
    	if(empty($executor)){
    		break;
    	}
    	$id_name_str= explode('|', $executor);
    	$data['manager_check_person_id'] = $id_name_str[1];
    	$data['manager_check_person_name'] = $id_name_str[0];
    }	
		$result=$this->validate($data,'TravelApplyStudyValidate');
		if($result !== true){
			$this->error($result);
		}
		$apply_study = new TravelApplyStudy($data);
		$apply_study->save();
		$this->success("保存成功");
	}
	public function get_study_applys(){
    return TravelApplyStudy::Order('create_time desc')->select()->toArray();
	}
	public function office_check_study_apply(){
		$view=new View();
		return $view->fetch('office_check_study_apply'); 	
	}
	public function get_wait_office_check_study_applys(){
    return TravelApplyStudy::where(['status'=>0])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function get_finished_office_check_study_applys(){
    return TravelApplyStudy::where(['status'=>['in',[-1,1]]])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function study_apply_office_check(){
		$data=$_POST;
		$id=$data['id'];
		unset($data['id']);
		$apply_study = new TravelApplyStudy();
		$apply_study->save($data,['id' => $id]);
		$this->success("操作成功");
	}
	public function manager_check_study_apply(){
		$view=new View();
		return $view->fetch('manager_check_study_apply'); 	
	}
	public function get_wait_manager_check_study_applys(){
    return TravelApplyStudy::where(['status'=>1,'manager_check_person_id'=>Session::get('id')])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function get_finished_manager_check_study_applys(){
    return TravelApplyStudy::where(['status'=>['in',[-2,2]],'manager_check_person_id'=>Session::get('id')])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function leader_check_study_apply(){
		$view=new View();
		return $view->fetch('leader_check_study_apply'); 	
	}
	public function get_wait_leader_check_study_applys(){
    return TravelApplyStudy::where(['status'=>2,'leader_check_person_id'=>Session::get('id')])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function get_finished_leader_check_study_applys(){
    return TravelApplyStudy::where(['status'=>['in',[-3,3]],'leader_check_person_id'=>Session::get('id')])
    			->Order('create_time desc')
    			->select()
    			->toArray();
	}
	public function study_payment_apply($study_id){
		$view=new View();
		$view->assign('user_id',Session::get('id'));
		$view->assign('user_name',Session::get('name'));
		$view->assign('study_id',$study_id);
		$view->assign('address_widget',true);
		return $view->fetch('study_payment_apply'); 	
	}

	public function add_study_payment_apply(){
		$data=$_POST;
    $executor_list = explode(';', $data['executor']);
    unset($data['executor']);
    foreach ($executor_list as $key => $executor) {
    	if(empty($executor)){
    		break;
    	}
    	$id_name_str= explode('|', $executor);
    	$data['manager_id'] = $id_name_str[1];
    }	
		$id = Db::table('travel_study_payment_list')->insertGetId($data);
		$this->success("操作成功",'',$id);
	}
	public function add_study_payment_detail(){
		$id = Db::table('travel_study_payment_detail')->insertGetId($_POST);
		$this->success("操作成功",'',$id);		
	}
	public function get_study_payment_details($study_payment_list_id){
		return Db::table('travel_study_payment_detail')->where(['study_payment_list_id'=>$study_payment_list_id])->select();
	}
	public function submit_study_payment($study_id){
		$apply_study=TravelApplyStudy::get($study_id);
		$apply_study->status = 4;
		$apply_study->save();
		$this->success('操作完成');
	}
	public function print_study_payment($id){
		$payment = Db::table('travel_study_payment_list')->where(['id'=>$id])->find();
		$detail_list = Db::table('travel_study_payment_detail')
					->where(['study_payment_list_id'=>$id])
					->select();
		$user = Db::table('user')->where(['id'=>$payment['user_id']])->find();
		$manager = Db::table('user')->where(['id'=>$payment['manager_id']])->find();
		$financial_staff = Db::table('user')->where(['id'=>$payment['financial_staff_id']])->find();
		$leader = Db::table('user')->where(['id'=>$payment['leader_id']])->find();

		$data_list=[];
		$golable_totoal = 0.00;
		foreach ($detail_list as $key => $detail) {
			$start_month = date("n",strtotime($detail['start_date']));
			$start_day = date("d",strtotime($detail['start_date']));
			$end_month = date("n",strtotime($detail['end_date']));
			$end_day = date("d",strtotime($detail['end_date']));
			$detail_list[$key]['start_month'] = $start_month;
			$detail_list[$key]['start_day'] = $start_day;
			$detail_list[$key]['end_month'] = $end_month;
			$detail_list[$key]['end_day'] = $end_day;
			$detail_list[$key]['total'] = $detail_list[$key]['study_cost']+$detail_list[$key]['trans_cost']
						+$detail_list[$key]['hotel_cost']+$detail_list[$key]['food_cost']+$detail_list[$key]['other_cost'];
			$golable_totoal = $golable_totoal+$detail_list[$key]['total'];
			$detail_list[$key]['total'] = sprintf("%.2f",$detail_list[$key]['total']);
		}
		$golable_totoal =sprintf("%.2f",$golable_totoal);

		$index = count($detail_list);
		for ($i=$index; $i <5 ; $i++) { 
			$detail_list[$i]['name'] = '&nbsp;';
			$detail_list[$i]['start_month'] = '';
			$detail_list[$i]['start_day'] = '';
			$detail_list[$i]['end_month'] = '';
			$detail_list[$i]['end_day'] = '';
			$detail_list[$i]['study_cost'] = '';
			$detail_list[$i]['trans_cost'] = '';
			$detail_list[$i]['hotel_days'] = '';
			$detail_list[$i]['hotel_standard'] = '';
			$detail_list[$i]['hotel_cost'] = '';
			$detail_list[$i]['food_days'] = '';
			$detail_list[$i]['food_cost'] = '';
			$detail_list[$i]['food_standard'] = '';
			$detail_list[$i]['other_days'] = '';
			$detail_list[$i]['other_standard'] = '';
			$detail_list[$i]['other_cost'] = '';
			$detail_list[$i]['total'] = '';
		}
		Log::record($detail_list);
		Log::record($user['sign_pic']);
    $mpdf=new \mPDF('zh-CN',array(210,320),'','宋体');
    $mpdf->useAdobeCJK = true;
    $stylesheet = ' 
			body{font-family:yahei;font-size: 14px;}
			.title{position: relative;width: 100%;text-align: center;font-size: 18px;font-weight: bold;padding-bottom: 20px;}

			.info{font-size: 14px;}
			.info table{font-size: 10px;text-align:left;}
			.info .dept_lable{text-align:right;width:15%;}  
			.info .dept{text-align:left;width:30%;}  
			.info .date_lable{text-align:right;width:15%;}
			.info .date{text-align:left;width:30%;}
			.info .unit{text-align:left;width:10%;}

			.info .label{text-align:left;width:5%;}
			.info .sign{text-align:left;width:15%;}

			.content{position:relative;width:100%;border-top:1px solid;border-left:1px solid;font-size: 8px;}
			.content td{border-right:1px solid;border-bottom:1px solid;padding:2px; text-align: center;}
      .top-1{width: 4%;}
      .top-2{width: 8%;}
      .top-3{width: 12%;}
      .top-4{width: 16%;}
      .td-title{font-size: 14px;}
		'; 
    $mpdf->WriteHTML($stylesheet,1);  
    $html='
			<body>
			  <div class="title">成都市新都区卫生和计划生育局学习培训差旅报销单
			  </div>
			  <div class="info">
					<table width = "100%">
	          <tr>
	          	<td class="dept_lable" >报销科室：</td>
	          	<td class="dept">'.$payment['dept'].'</td>
	          	<td class="date_lable">报销日期：</td>
	          	<td class="date">'.$payment['date'].'</td>
	          	<td class="unit">单位:元</td>
	          </tr>
	        </table>
        </div>
				<table class="content" cellspacing="0">
          <tr>
              <td class="top-2 td-title" rowspan="2">差旅姓名</td>
              <td class="top-2 td-title" colspan="4">起止日期</td>
              <td class="top-2 td-title" rowspan="2">出差事由</td>
              <td class="top-2 td-title" rowspan="2">培训费</td>
              <td class="top-2 td-title" rowspan="2">交通费</td>
              <td class="top-3 td-title" colspan="3">住宿费</td>
              <td class="top-3 td-title" colspan="3">伙食补助</td>
              <td class="top-3 td-title" colspan="3">公杂费</td>
              <td class="top-2 td-title" rowspan="2">小计金额</td>
          </tr>
          <tr>
              <td class="top-1 td-title">月</td>
              <td class="top-1 td-title">日</td>
              <td class="top-1 td-title">月</td>
              <td class="top-1 td-title">日</td>
              <td class="top-2 td-title">天数</td>
              <td class="top-2 td-title">标准</td>
              <td class="top-2 td-title">金额</td>
              <td class="top-2 td-title">天数</td>
              <td class="top-2 td-title">标准</td>
              <td class="top-2 td-title">金额</td>
              <td class="top-2 td-title">天数</td>
              <td class="top-2 td-title">标准</td>
              <td class="top-2 td-title">金额</td>
          </tr>
          <tr>
              <td class="top-1 td-title">'.$detail_list[0]['name'].'</td>
              <td class="top-1 td-title">'.$detail_list[0]['start_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[0]['start_day'].'</td>
              <td class="top-1 td-title">'.$detail_list[0]['end_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[0]['end_day'].'</td>
              <td class="top-2 td-title" rowspan="5">'.$payment['reason'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['study_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['trans_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['hotel_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['hotel_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['hotel_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['food_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['food_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['food_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['other_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['other_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['other_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[0]['total'].'</td>
          </tr>
          <tr>
              <td class="top-1 td-title">'.$detail_list[1]['name'].'</td>
              <td class="top-1 td-title">'.$detail_list[1]['start_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[1]['start_day'].'</td>
              <td class="top-1 td-title">'.$detail_list[1]['end_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[1]['end_day'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['study_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['trans_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['hotel_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['hotel_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['hotel_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['food_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['food_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['food_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['other_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['other_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['other_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[1]['total'].'</td>
          </tr>
          <tr>
              <td class="top-1 td-title">'.$detail_list[2]['name'].'</td>
              <td class="top-1 td-title">'.$detail_list[2]['start_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[2]['start_day'].'</td>
              <td class="top-1 td-title">'.$detail_list[2]['end_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[2]['end_day'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['study_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['trans_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['hotel_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['hotel_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['hotel_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['food_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['food_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['food_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['other_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['other_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['other_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[2]['total'].'</td>
          </tr>
          <tr>
              <td class="top-1 td-title">'.$detail_list[3]['name'].'</td>
              <td class="top-1 td-title">'.$detail_list[3]['start_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[3]['start_day'].'</td>
              <td class="top-1 td-title">'.$detail_list[3]['end_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[3]['end_day'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['study_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['trans_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['hotel_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['hotel_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['hotel_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['food_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['food_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['food_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['other_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['other_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['other_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[3]['total'].'</td>
          </tr>
          <tr>
              <td class="top-1 td-title">'.$detail_list[4]['name'].'</td>
              <td class="top-1 td-title">'.$detail_list[4]['start_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[4]['start_day'].'</td>
              <td class="top-1 td-title">'.$detail_list[4]['end_month'].'</td>
              <td class="top-1 td-title">'.$detail_list[4]['end_day'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['study_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['trans_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['hotel_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['hotel_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['hotel_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['food_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['food_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['food_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['other_days'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['other_standard'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['other_cost'].'</td>
              <td class="top-2 td-title">'.$detail_list[4]['total'].'</td>
          </tr>
          <tr>
            <td class="top-3 td-title" colspan="3">报销单据:</td>
            <td class="top-4 td-title" colspan="4">'.$payment['document_num'].'&nbsp;张</td>
            <td class="top-3 td-title" colspan="3">报销金额合计大写:</td>
            <td class="top-5 td-title" colspan="5">'.$this->NumToCNMoney($golable_totoal).'</td>
            <td class="top-2 td-title" colspan="2">报销金额合计:</td>
            <td class="top-2 td-title" colspan="1">'.$golable_totoal.'</td>
          </tr> 
          <tr>
            <td class="top-3 td-title" colspan="3">备注:</td>
            <td class="top-5 td-title" colspan="12">'.$payment['remark'].'</td>
            <td class="top-2 td-title" colspan="2">资金来源:</td>
            <td class="top-2 td-title" colspan="1">'.$payment['sources_of_funds'].'</td>
          </tr> 
        </table> 
        <div class="info">
					<table width = "100%">
	          <tr>
	          	<td class="label" >报销人：</td>
	      ';
	      if(empty($user)){
	      	$html=$html.'<td class="sign"></td>';
	      }else{
	      	$html=$html.'<td class="sign"><img src="'.$user['sign_pic'].'"></td>';
	      }
	      $html=$html.'<td class="label" >科室主任：</td>';
	      $html=$html.'<td class="sign"></td>';
	      $html=$html.'<td class="label" >分管领导：</td>';
	      if(empty($manager)){
	      	$html=$html.'<td class="sign"></td>';
	      }else{
	      	$html=$html.'<td class="sign"><img src="'.$manager['sign_pic'].'"></td>';
	      }
	      $html=$html.'<td class="label" >单位领导：</td>';
	      if(empty($leader)){
	      	$html=$html.'<td class="sign"></td>';
	      }else{
	      	$html=$html.'<td class="sign"><img src="'.$leader['sign_pic'].'"></td>';
	      }
	      $html=$html.
	      '
	          </tr>
	        </table>
        </div>  
			</body>
    ';
    $mpdf->WriteHTML($html,2);
    $mpdf->Output();   
    exit;    
	}
}