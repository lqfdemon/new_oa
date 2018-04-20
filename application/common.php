<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件


	define('FILE_DOWNLOAD_ROOT_PATH','./Uploads/Download/');


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

	function popup_tree_menu($tree, $level = 0) {
		$level++;
		$html = "";
		if (is_array($tree)) {
			$html = "<ul class=\"tree_menu\">\r\n";
			foreach ($tree as $val) {
				if (isset($val["name"])) {
					$title = $val["name"];
					$id = $val["id"];
					if (empty($val["id"])) {
						$id = $val["name"];
					}
					if (!empty($val["is_del"])) {
						$del_class = "is_del";
					} else {
						$del_class = "";
					}
					if (isset($val['_child'])) {
						$html = $html . "<li>\r\n<a class=\"$del_class\" node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n";
						$html = $html . popup_tree_menu($val['_child'], $level);
						$html = $html . "</li>\r\n";
					} else {
						$html = $html . "<li>\r\n<a class=\"$del_class\" node=\"$id\" ><i class=\"fa fa-angle-right level$level\"></i><span>$title</span></a>\r\n</li>\r\n";
					}
				}
			}
			$html = $html . "</ul>\r\n";
		}
		return $html;
	}

	function fill_option($list, $data = null) {
		$html = "";
		if (is_array($list)) {
			foreach ($list as $key => $val) {

				if (is_array($val)) {
					$id = $val['id'];
					$name = $val['name'];
					if (empty($data)) {
						$selected = "";
					} else {
						$selected = "selected";
					}
					$html = $html . "<option value='{$id}' $selected>{$name}</option>";
				} else {
					if ($key == $data) {
						$selected = "selected";
					} else {
						$selected = "";
					}
					$html = $html . "<option value='{$key}' $selected>{$val}</option>";
				}
			}
		}

		echo $html;
	}
