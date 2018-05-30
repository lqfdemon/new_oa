<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

use app\index\model\User;

class Index extends CommonController
{

    public function index()
    {
        $this->redirect(url('index/index/show')) ;
    }
    public function show(){
        $view=new View();
        $user_name = $this->get_user_name();
        $view->assign('user_name',$user_name);
        $menu_list=$this->get_menu();
        $view->assign('menu_list',$menu_list);
        return $view->fetch('index');
    }
    public function get_menu(){
        $menu_list = [];
        $user_id = $this->get_user_id();
        $user = User::get($user_id);
        if(empty($user)){
            $this->error("用户不存在");
        }
        $page_can_list =Db::table('menu_seek')
                        ->where('station_id',$user['station_id'])
                        ->column('page_id');
        $map_root_menu = array('leave'=>0,'parent'=>0,'id'=>array('IN',$page_can_list));
        $root_menu_list = Db::table('menu')->where($map_root_menu)
                    ->order('sort_index asc')
                    ->select();
        foreach ($root_menu_list as $root_key => $root_menu) {
            array_push($menu_list, ['data'=>$root_menu,'sub_menu'=>array()]);
            $map_sub_menu = array('leave'=>1,'parent'=>$root_menu['id'],'id'=>array('IN',$page_can_list));
            $sub_menu_list = Db::table('menu')->where($map_sub_menu)
                    ->order('sort_index asc')
                    ->select();
            foreach ($sub_menu_list as $sub_key => $sub_menu) {
                $sub_menu['url']=url($sub_menu['url']);
                array_push($menu_list[$root_key]['sub_menu'],['data'=>$sub_menu]);
            }
        }
        return $menu_list;
    }  
}
