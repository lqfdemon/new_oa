<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\User;
use app\index\model\Dept;
use app\index\model\MenuInfo;
use app\index\model\MenuSeekInfo;
use app\index\model\MenuStationInfo;

class SystemManage extends CommonController
{
	public function group_view(){
		$view=new View();
		$menu = Db::table('dept')-> field('id,pid,name,is_del') -> order('sort asc') -> select();
		$tree = list_to_tree($menu);
		$view -> assign('menu', popup_tree_menu($tree));

		$list = Db::table('dept_grade') -> where(['is_del'=>0])-> field('id,name') -> order('sort asc') -> select();
		$view -> assign('dept_grade_list', $list);

		$list = Db::table('dept')-> order('sort asc') -> field('id,name')-> select();

		$view -> assign('dept_list', $list);
		return $view->fetch('group_view');
	}
	public function read_dept_info($id){
		return Db::table('dept')->where(['id'=>$id])->find();
	}
	public function save_dept(){
		$id = $_POST['id'];
		if(empty($id)){
			$dept = new Dept();
		}else{
			$dept = Dept::get($id);
		}
		if(empty($dept)){
			$this->error("操作失败");
		}
		$data = $_POST;
		$data['name'] = $_POST['dept_name'];
		unset($data['id']);
		unset($data['dept_name']);
		$dept->save($data);
		$this->success('操作成功');
	}
	public function user_manage(){
		$view=new View();
		$menu = Db::table('dept')-> field('id,pid,name,is_del') -> order('sort asc') -> select();
		$tree = list_to_tree($menu);

		$view -> assign('menu', popup_tree_menu($tree));
		$position_list = Db::table('position') -> where(['is_del'=>0]) -> order('sort asc')->field('id,name')-> select();
		$view -> assign('position_list', $position_list);
		$auth_list=Db::table('menu_station')->select();
		$view -> assign('auth_list', $auth_list);
		return $view->fetch('user_manage');
	}
	public function get_user_list(){
		return  Db::table('user') -> where(['is_del'=>0]) -> select();	
	}
	public function read_user_info($id){
		return Db::table('user')->where(['id'=>$id])->find();
	}
	public function save_user(){
		$id = $_POST['id'];
		if(empty($id)){
			$user = new User();
		}else{
			$user = User::get($id);
		}
		if(empty($user)){
			$this->error("操作失败");
		}
		$data = $_POST;
		unset($data['id']);
		$user->save($data);
		$this->success('操作成功');
	}
	public function del_users(){
		$id_list = $_POST['id_list'];
		foreach ($id_list as $key => $id) {
			$user = User::get($id);
			$user->save(['is_del'=>1]);
		}
		$this->success('操作成功');
	}


	/******************权限管理*************/
	public function auth_manage(){
		$view=new View();
		return $view->fetch('auth_manage');
	}
	public function get_station_list(){
		return Db::table('menu_station')->select();
	}
	public function delete_station($id){
		MenuStationInfo::destroy($id);
		$this->success("操作成功");
	}
	public function save_station(){
		$data = $_POST;
		$station_id = $data['station_id'];
		unset($data['station_id']);
		if($station_id==0){
			$station = new MenuStationInfo();
		}else{
			$station = MenuStationInfo::get($station_id);
		}
		if(empty($station)){
			$this->error("操作失败");
		}else{
			$station->save($data);
			$this->success("操作成功");
		}
	}
	public function get_menu_list_by_station_id(){
		$station_id = $_GET['station_id'];
		$station = MenuStationInfo::get($station_id);
		if(empty($station)){
			$this->error("操作失败");
		}
		$seek_list = Db::table('menu_seek')->where(['station_id'=>$station_id])->select();
		$recall = [];
		foreach ($seek_list as $key => $seek) {
			Log::record($seek);
			array_push($recall,[
								'seek_id'=>$seek['seek_id'],
								'name'=>MenuInfo::name($seek['page_id']),
								'page_id'=>$seek['page_id'],
								]
						);
		}
		return $recall;
	}
	public function get_able_select_by_station_id(){
		$station_id = $_GET['station_id'];

		$station = MenuStationInfo::get($station_id);
		if(empty($station)){
			$this->error("操作失败");
		}
		$list = Db::table('menu_seek')->where(['station_id'=>$station_id])->select();
		$already_list = [];
		foreach ($list as $key => $menu) {
			array_push($already_list,$menu['page_id']);
		}
		$conditon['id'] =array('NOT IN',$already_list);
		$able_select_list=[];
		if(empty($already_list)){
			$able_select_list = Db::table('menu')->select();
		}else{
			$able_select_list = Db::table('menu')->where($conditon)->select();
		}
		return $able_select_list;
	}
	public function station_remove_menu(){
		$station_id=$_POST['station_id'];
		$station = MenuStationInfo::get($station_id);
		if(empty($station)){
			$this->error("请选择角色");
		}
        $iterm_id_list=$_POST['id_list'];
		foreach ($iterm_id_list as $key => $iterm_id){
			$menu_seek = MenuSeekInfo::get(['station_id'=>$station_id,'seek_id'=>$iterm_id]);
			if(!empty($menu_seek)){
				$menu_seek->delete();
			}
		}		
		$this->success("操作成功");		
	}
	public function station_add_menu_iterm(){
		$station_id=$_POST['station_id'];
		$station = MenuStationInfo::get($station_id);
		if(empty($station)){
			$this->error("请选择角色");
		}
        $iterm_id_list=$_POST['id_list'];
		foreach ($iterm_id_list as $key => $iterm_id){
			$menu_seek = new MenuSeekInfo();
			if(!empty($menu_seek)){
				$menu_seek->save(['station_id'=>$station_id,'page_id'=>$iterm_id]);
			}
		}		
		$this->success("操作成功");			
	}
}
