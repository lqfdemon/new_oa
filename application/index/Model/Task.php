<?php
    namespace  app\index\model;
    use think\Model;
    use think\Session;
    use think\Db;
    use think\Log;
    //
    class Task extends Model{
        protected $table="task"; 
        public function logs()
        {
            return $this->hasMany('TaskLog','task_id','task_id');
        }      
    }