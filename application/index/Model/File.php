<?php
    namespace  app\index\model;
    use think\Model;
    use think\Session;
    use think\Db;
    use think\Log;
    //
    class File extends Model{
        protected $table="file"; 
		/**
		 * 下载指定文件
		 * @param  number  $root 文件存储根目录
		 * @param  integer $id   文件ID
		 * @param  string   $args     回调函数参数
		 * @return boolean       false-下载失败，否则输出下载文件
		 */
		public function download($root, $id, $callback = null, $args = null) {
			/* 获取下载文件信息 */
			$file = $this -> find($id);
			if (!$file) {
				$this -> error = '不存在该文件！';
				return false;
			}

			/* 下载文件 */
			switch ($file['location']) {
				case 0 :
					//下载本地文件
					$file['rootpath'] = $root;
					return $this -> downLocalFile($file, $callback, $args);
				/*
				case 1 :
					//下载FTP文件
					$file['rootpath'] = $root;
					return $this -> downFtpFile($file, $callback, $args);
					break;
				*/
				default :
					$this -> error = '不支持的文件存储类型！';
					return false;
			}
		}
		/**
		 * 下载本地文件
		 * @param  array    $file     文件信息数组
		 * @param  callable $callback 下载回调函数，一般用于增加下载次数
		 * @param  string   $args     回调函数参数
		 * @return boolean            下载失败返回false
		 */
		private function downLocalFile($file, $callback = null, $args = null) {
			$file_path = $file['rootpath'] . $file['savepath'] . $file['savename']; 
			if (is_file($file_path)) {
				/* 调用回调函数新增下载数 */
				is_callable($callback) && call_user_func($callback, $args);

				/* 执行下载 */ //TODO: 大文件断点续传
				header("Content-Description: File Transfer");
				header("Content-type: text/html; charset=utf-8");
				header('Content-Length:' . $file['size']);
				$ua=$_SERVER['HTTP_USER_AGENT'];
				if (preg_match('/MSIE/',$ua) || preg_match("/Trident\/7.0/", $ua)) {
					header('Content-Disposition: attachment; filename="' . rawurlencode($file['name']) . '"');
				} else {
					header('Content-Disposition: attachment; filename="' . $file['name'] . '"');
				}
				readfile($file['rootpath'] . $file['savepath'] . $file['savename']);
				exit ;
			} else {
				$this -> error = '文件已被删除！';
				return false;
			}
		}
    }