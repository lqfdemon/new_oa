<?php
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;

class Map extends CommonController
{
	/** 
     * 地图编辑
     * @access public
     * @return object               模板
     */
	public function map_edit(){
		$view=new View();
        return $view->fetch('map_edit');
	}
	/** 
     * 获取地图标注信息
     * @access public
     * @return object               模板
     */
    public function get_map_signs(){
        $hospital_list=Db::table('unit')
            ->select();
        $res = [];
        foreach ($hospital_list as $key => $hospital) {
            if(empty($hospital_list[$key]['map_positon_x'])){
                $hospital_list[$key]['map_positon_x']="5%";
            }
            if(empty($hospital_list[$key]['map_positon_y'])){
                $hospital_list[$key]['map_positon_y']="5%";
            }
            $img_url = "../../public/map_sign/img/".$hospital['img_path'];
            switch ($hospital['type']) {
            	case '机关':
            		$bg = "url('../../public/map_sign/img/wjj.png')";
            		break;
            	case '医院':
            		$bg = "url('../../public/map_sign/img/hospital.png')";
            		break;
            	case '学校':
            		$bg = "url('../../public/map_sign/img/school.png')";
            		break;
            	default:
            		$bg = "url('../../public/map_sign/img/hospital.png')";
            		break;
            }
            $msg ='<img src="'.$img_url.'" style="width: 100px;height:auto;">'.'<br>';
            $msg = $msg."地址：".$hospital['address'].'<br>';
            array_push($res, [
                'left'=>$hospital_list[$key]['map_positon_x'],
                'top'=>$hospital_list[$key]['map_positon_y'],
                'msg'=>$msg,
                'background_color'=>'transparent',
                'background_image'=>$bg,
                'title'=>$hospital_list[$key]['name'],
            ]);
        }
        return $res;
    }
    /** 
     * 保存地图标注信息
     * @access public
     * @param string     $_post['data'] 要保存的数据
     * @return object               返回信息
     */
    public function save_map_signs(){
        $data =json_decode($_POST['data']);
        foreach ($data as $key => $iterm) {
            $sign_data = object_to_array($iterm);
            $hospital =[
                'name'=>$sign_data['title'],
                'map_positon_x'=>$sign_data['left'],
                'map_positon_y'=>$sign_data['top'],
            ];
            Log::record($hospital);
            Db::table('unit')
                ->where(['name'=>$hospital['name']])
                ->update($hospital);
        }
        $this->success('保存完成');
    }
}
/** 
 * 用json传过来的数组并不是标准的array,所以需要用这个函数进行转换
 * @param array     $array json传过来的数组
 * @return array               标准的array
 */  
function object_to_array($array)
{
   if(is_object($array))
   {
    $array = (array)$array;
   }
   if(is_array($array))
   {
    foreach($array as $key=>$value)
    {
     $array[$key] = object_to_array($value);
    }
   }
   return $array;
}