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

class UserManage extends CommonController
{
	//获取当前部门和所有子部门的人员
    public function get_user_by_dept($dept_id){//部门列表
		$dept_list = $this->get_all_dept($dept_id);
		$dept_id_list =$dept_list['id'];
		$map['dept_id']=array('in',$dept_id_list);
		$user_list=User::where($map)->select()->toArray();
		return $user_list;
    }
    //获取当前部门和所有子部门
    public function get_all_dept($dept_id){
		$dept_list = Dept::where(['is_del'=>0])->select();
		$dept_list = tree_to_list(list_to_tree($dept_list, $dept_id));
		$cur_dept_model = Dept::get(['id'=>$dept_id]);
		if(empty($cur_dept_model)){
			$this->error("当前部门不可用");
		}
		$cur_dept = $cur_dept_model->getData();
		array_push($dept_list,$cur_dept);
		$dept_list = rotate($dept_list);
		return $dept_list;
	}
}

	function rotate($a) {
		$b = array();
		if (is_array($a)) {
			foreach ($a as $val) {
				foreach ($val as $k => $v) {
					$b[$k][] = $v;
				}
			}
		}
		return $b;
	}
	function list_to_tree($list, $root = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
		// 创建Tree
		$tree = array();
		if (is_array($list)) {
			// 创建基于主键的数组引用

			$refer = array();
			foreach ($list as $key => $data) {
				$refer[$data[$pk]] = &$list[$key];
			}

			foreach ($list as $key => $data) {
				// 判断是否存在parent
				$parentId = 0;
				if (isset($data[$pid])) {
					$parentId = $data[$pid];
				}
				if ((string)$root == $parentId) {
					$tree[] = &$list[$key];
				} else {
					if (isset($refer[$parentId])) {
						$parent = &$refer[$parentId];
						$parent[$child][] = &$list[$key];
					}
				}
			}
		}
		return $tree;
	}

	function tree_to_list($tree, $level = 0, $pk = 'id', $pid = 'pid', $child = '_child') {
		$list = array();
		if (is_array($tree)) {
			foreach ($tree as $val) {
				$val['level'] = $level;
				if (isset($val['_child'])) {
					$child = $val['_child'];
					if (is_array($child)) {
						unset($val['_child']);
						$list[] = $val;
						$list = array_merge($list, tree_to_list($child, $level + 1));
					}
				} else {
					$list[] = $val;
				}
			}
			return $list;
		}
	}