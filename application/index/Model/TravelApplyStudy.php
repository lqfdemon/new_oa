<?php
    namespace  app\index\model;
    use think\Model;
    use think\Session;
    use think\Db;
    use think\Log;
    //
    define('LEADER_ID',45);
    define('LEADER_NAME','何宇生');
    class TravelApplyStudy extends Model{
        // 开启自动写入时间戳字段
        protected $autoWriteTimestamp = 'datetime';
        //表名
        protected $table="travel_apply_study";
        //自动完成项
        protected $insert = ['apply_user_id','apply_user_name','leader_check_person_id','leader_check_person_name'];
        protected function setApplyUserIdAttr()
        {
            return Session::get('id');
        } 
        protected function setApplyUserNameAttr()
        {
            return Session::get('name');
        } 
        protected function setLeaderCheckPersonIdAttr()
        {
            return LEADER_ID;
        }
        protected function setLeaderCheckPersonNameAttr()
        {
            return LEADER_NAME;
        }  
        public function getStatusAttr($value)
        {
            $status = [
                -3=>'局领导不通过',
                -2=>'分管领导不通过',
                -1=>'办公室不通过',
                0=>'待审核',
                1=>'办公室审核通过',
                2=>'分管领导审核通过',
                3=>'审核完成',
                4=>'报销待审核'
            ];
            return $status[$value];
        }
}