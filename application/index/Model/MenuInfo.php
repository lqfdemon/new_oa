<?php
    namespace  app\index\model;
    use think\Model;
    use think\Session;
    use think\Db;
    use think\Log;
    //
    class MenuInfo extends Model{
        protected $table="menu";
        static public function name($id){
        	$name = "";
        	$menu = Db::table('menu')->where('id',$id)->find();
        	if(!empty($menu)){
        		$name = $menu['name'];
        	}
        	return $name;
        }       
    }