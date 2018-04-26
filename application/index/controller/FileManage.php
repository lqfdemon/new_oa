<?php
/************公文传阅*************/
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\File;

class FileManage extends CommonController
{
	public function upload_file(){
    $view=new View();
    return $view->fetch('upload_file'); 
	}
	/*文件类型 字段 class
	0  附件文件
	1  上传的常用文件
	*/
	public function set_file_class($file_id,$class){
		$file = File::get($file_id);
		$file->class = $class;
		$file->save();
	}
	public function get_file_list($class,$name){
		$where_map['class']  = $class;
		$where_map['name'] = ['like',"%$name%"];
		$file_list = File::where($where_map)->select()->toArray();
		foreach ($file_list as $key => $file) {
			$file_list[$key]['url']= url('download','file_id='.$file['id']);
		}
		return$file_list;
	}
}