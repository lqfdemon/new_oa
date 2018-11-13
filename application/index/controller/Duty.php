<?php
/************值班表*************/
namespace app\index\controller;

use think\Controller;
use think\View;
use think\Db;
use think\Session;
use think\Loader;
use think\Log;


class Duty extends CommonController
{
    public function create(){
        return $this->fetch();
    }
}