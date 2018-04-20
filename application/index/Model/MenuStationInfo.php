<?php
    namespace  app\index\model;
    use think\Model;
    use think\Session;
    use think\Db;
    use think\Log;
    //
    class MenuStationInfo extends Model{
        protected $table="menu_station";    
		static public function name($station_id){
			$name = "";
			$station=Db::table('menu_station')->where('station_id',$station_id)->find();
			if(!empty($station)){
				$name = $station['name'];
			}
			return $name;
		} 
    }