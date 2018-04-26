<?php
//公众号appid
define('APPID','wxbf24d1f3e0890348'); 
//公众号secret
define('SECRET','a0f9b6e7e350330f58b92a0c3f6685eb'); 

//获取access_token  GetAccessToken()
function GetAccessToken(){
	$appid = APPID;
	$secret = SECRET;
	$get_access_token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$appid.'&secret='.$secret.'';

	$ch=curl_init();
	curl_setopt($ch, CURLOPT_URL, $get_access_token_url);  
	curl_setopt($ch, CURLOPT_HEADER, 0);  
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  //禁止服务器端SSL加密认证
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$res = curl_exec($ch);
	curl_close($ch);

	$json_obj = json_decode($res,true);
	$access_token = $json_obj['access_token'];

	if(empty($access_token)){
		echo "获取access_token 失败";
		return 0;
	}
	else{
		 
		return $access_token;
	}
}


 class class_weixin
 {
		 var $appid = APPID;
	     var $appsecret = SECRET;
		 
	     //构造函数，获取Access Token
	     public function __construct($appid = NULL, $appsecret = NULL)
	     {
	         if($appid && $appsecret){
	             $this->appid = $appid;
	             $this->appsecret = $appsecret;
	        }
         $res = file_get_contents('access_token.json');
         $result = json_decode($res, true);
         $this->expires_time = $result["expires_time"];
         $this->access_token = $result["access_token"];
         
         if (time() > ($this->expires_time + 3600)){
             $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this->appid."&secret=".$this->appsecret;
             $res = $this->http_request($url);
             $result = json_decode($res, true);
             $this->access_token = $result["access_token"];
             $this->expires_time = time();
             file_put_contents('access_token.json', '{"access_token": "'.$this->access_token.'", "expires_time": '.$this->expires_time.'}');
         }
     }
      //发送模版消息
      public function send_template_message($data)  
      {
      	  $access_token = $this->access_token;
          $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$this->access_token;
          $res = $this->http_request($url, $data);
          return json_decode($res, true);
      }
     public function oauth2_authorize($redirect_url, $scope, $state = NULL)
     {
         $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$this->appid."&redirect_uri=".$redirect_url."&response_type=code&scope=".$scope."&state=".$state."#wechat_redirect";
        
         return $url;
     }
 
     //生成OAuth2的Access Token
     public function oauth2_access_token($code)
     {
         $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$this->appid."&secret=".$this->appsecret."&code=".$code."&grant_type=authorization_code";
         $res = $this->http_request($url);
         return json_decode($res, true);
     }
 
	     //获取用户基本信息（OAuth2 授权的 Access Token 获取 未关注用户，Access Token为临时获取）
	     public function oauth2_get_user_info($access_token, $openid)
	     {
	         $url = "https://api.weixin.qq.com/sns/userinfo?access_token=".$access_token."&openid=".$openid."&lang=zh_CN";
	         $res = $this->http_request($url);
	         return json_decode($res, true);
	     }
 
	     //获取用户基本信息
	     public function get_user_info($openid)
	     {
	         $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$this->access_token."&openid=".$openid."&lang=zh_CN";
	         $res = $this->http_request($url);
	         return json_decode($res, true);
	     }
	     public function createNonceStr($length = 16) {
          $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
          $str = "";
          for ($i = 0; $i < $length; $i++) {
              $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
          }
          return $str;
      }
      
      //获得微信卡券api_ticket
      public function getCardApiTicket()
      {
          $res = file_get_contents('cardapi_ticket.json');
          $result = json_decode($res, true);
          $this->cardapi_ticket = $result["cardapi_ticket"];
          $this->cardapi_expire = $result["cardapi_expire"];
          if (time() > ($this->cardapi_expire + 3600)){
              $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=wx_card&access_token=".$this->access_token;
              $res = $this->http_request($url);
              $result = json_decode($res, true);
              $this->cardapi_ticket = $result["ticket"];
              $this->cardapi_expire = time();
              file_put_contents('cardapi_ticket.json', '{"cardapi_ticket": "'.$this->cardapi_ticket.'", "cardapi_expire": '.$this->cardapi_expire.'}');
          }
          return $this->cardapi_ticket;
      }
      
      //cardSign卡券签名
      public function get_cardsign($bizObj)
      {
          //字典序排序
          asort($bizObj);
          //URL键值对拼成字符串
          $buff = "";
          foreach ($bizObj as $k => $v){
              $buff .= $v;
          }
          //sha1签名
          return sha1($buff);
      }
      
      //获得JS API的ticket
      private function getJsApiTicket() 
      {
          $res = file_get_contents('jsapi_ticket.json');
          $result = json_decode($res, true);
          $this->jsapi_ticket = $result["jsapi_ticket"];
          $this->jsapi_expire = $result["jsapi_expire"];
  
          if (time() > ($this->jsapi_expire + 3600)){
              $url = "https://api.weixin.qq.com/cgi-bin/ticket/getticket?type=jsapi&access_token=".$this->access_token;
              $res = $this->http_request($url);
              $result = json_decode($res, true);
              $this->jsapi_ticket = $result["ticket"];
              $this->jsapi_expire = time();
              file_put_contents('jsapi_ticket.json', '{"jsapi_ticket": "'.$this->jsapi_ticket.'", "jsapi_expire": '.$this->jsapi_expire.'}');
          }
          return $this->jsapi_ticket;
      }
  
      //获得签名包
      public function getSignPackage() {
         $jsapiTicket = $this->getJsApiTicket();
         $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
         $url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
         $timestamp = time();
         $nonceStr = $this->createNonceStr();
         $string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
         $signature = sha1($string);
         $signPackage = array(
                             "appId"     => $this->appid,
                             "nonceStr"  => $nonceStr,
                             "timestamp" => $timestamp,
                             "url"       => $url,
                             "signature" => $signature,
                             "rawString" => $string
                             );
         return $signPackage;
     }
     
	public function SendToAdmin($customerinfo)
	{	
		
		$username = $customerinfo['name'];
		$tel = $customerinfo['tel'];
		$date = date('Y-m-d H:i:s');	
		$template_id="UpotzejmZtQQqcvNiuHd_kenNdBQ-GLub4nnFPsT8aw";
		$data_url = "http://senquanwu.com/sqw/tel.php?customertel=".$tel;
		$hostopenid = "oGWXswcgBLeb7zP8RYkyA5wqpOqc";
		$jsonText = array('touser'=>$hostopenid, 'template_id'=>$template_id ,'url'=>$data_url,
								'data'=>array
								(
									'first'=>array('value'=>"您好，您有泳池预约",'color'=>"#173177",),								
									'keyword1'=>array('value'=>$username,'color'=>"#173177",),
									'keyword2'=>array('value'=>$tel,'color'=>"#173177",),
									'keyword3'=>array('value'=>$date,'color'=>"#173177",),
									'remark'=>array('value'=>"请尽快处理该预约，点击本条提醒可以快速联系客户！",'color'=>"#173177",),		
								)
						);  
		$data = json_encode($jsonText); 
		$this->send_template_message($data);

		}
		public function AcceptCustomer($customerinfo,$petinfo)
		{	
		
			$petkind = $petinfo['kind'];
			$petname = $petinfo['name'];			
			$useropenid = $customerinfo['open_id'];
			$username = $customerinfo['name'];
			$tel = $customerinfo['tel'];
			$date = date('Y-m-d H:i:s');	
			$template_id="So27K1IyBZv0pi371vJqPZ9yyPaWDEW4nX-BHX2lpZY";
			$data_url = "http://f.amap.com/1c9Bw_0262Rgz";
			$jsonText = array('touser'=>$useropenid, 'template_id'=>$template_id ,'url'=>$data_url,
									'data'=>array
									(
										'first'=>array('value'=>"尊敬的".$username."，您好，您的预约已成功",'color'=>"#7FFF00",),								
										'keyword1'=>array('value'=>$petkind.$petname."游泳",'color'=>"#173177",),
										'keyword2'=>array('value'=>"123321",'color'=>"#173177",),
										'keyword3'=>array('value'=>"1000元",'color'=>"#173177",),
										'remark'=>array('value'=>"您的预约已成功，点击本条消息可以在线导航，期待您的光临！",'color'=>"#173177",),		
									)
							);  
			$data = json_encode($jsonText); 
			$this->send_template_message($data);
		}
		public function RefuseCustomer($customerinfo,$reason)
		{	
			$username = $customerinfo['name'];
			$tel = $customerinfo['tel'];	
			$template_id="8aC5w1wMTpLEg41oINywGFZ1Ge8HaX_KOwl2tLz4XAw";
			$data_url = "http://senquanwu.com/sqw/tel.php?customertel=18181955455";
			$jsonText = array('touser'=>$customerinfo['open_id'], 'template_id'=>$template_id ,'url'=>$data_url,
									'data'=>array
									(
										'first'=>array('value'=>"尊敬的".$username."，抱歉，您的预约失败",'color'=>"#FF0000",),								
										'keyword1'=>array('value'=>"森犬之屋宠物俱乐部",'color'=>"#173177",),
										'keyword2'=>array('value'=>$reason,'color'=>"#173177",),					
										'remark'=>array('value'=>"您的预约失败，如有疑问请点击本条消息电话咨询！",'color'=>"#173177",),		
									)
							);  
			$data = json_encode($jsonText); 
			$this->send_template_message($data);
		}
		//从数据库获取个人信息
		public function GetUserInfoByOpenId($openid)
		{
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select * from customer where open_id = '$openid'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			if($num_results == 0){
				return 0;
				$db->close();
				exit();
			}
			$row = $result->fetch_assoc();
			$db->close();
			return $row;
			
		}
		public function GetPetInfoByCustomerId($customer_id){
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select * from pet where customer_id = '$customer_id'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			if($num_results == 0){
				echo "没有您的注册信息";
				$db->close();
				exit();
			}
			$row = $result->fetch_assoc();
			$db->close();
			return $row;
		}
		//从数据库获取投票票数
		public function GetVoteNum($id){
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select * from vote_log where vote_id = '$id'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			return $num_results;
		}	

		// 从数据库获取宠物信息
		public function GetPetInfoById($pet_id)
		{
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select * from pet where pet_id = '$pet_id'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			if($num_results == 0){
				echo "没有您的注册信息";
				$db->close();
				exit();
			}
			$row = $result->fetch_assoc();
			$db->close();
			return $row;
			
		}
		
		public function GetRecharge($customer_id)
		{
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select sum(recharge) as recharge from recharge where customer_id = '$customer_id'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			if($num_results == 0){
				echo "没有您的注册信息";
				$db->close();
				exit();
			}
			$row = $result->fetch_assoc();
			$db->close();
			return $row;
			
		}
		public function GetCost($customer_id)
		{
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select sum(cost) as cost from cost where customer_id = '$customer_id'";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			if($num_results == 0){
				echo "没有您的注册信息";
				$db->close();
				exit();
			}
			$row = $result->fetch_assoc();
			$db->close();
			return $row;
			
		}
		public function CheckVoteInfo($openid){
			@ $db = new mysqli('localhost','root','mysql805','sqw');
			if (mysqli_connect_errno()){
				echo '<h2>数据库没有连接成功！</h2>';
				exit();
			}		
			mysqli_set_charset ($db,'utf8');
			$query = "select * from vote where open_id = '$openid' and status = 1";
			$result = $db->query($query);	
			$num_results = $result->num_rows;
			$db->close();
			return $num_results;
		}
		public function checkvote($id,$openid)
	    {
	        @ $db = new mysqli('localhost','root','mysql805','sqw');
	            if (mysqli_connect_errno()){
	                echo '<h2>数据库没有连接成功！</h2>';
	                exit();
	            }       
	            mysqli_set_charset ($db,'utf8');
	        $date = date('Y-m-d');
	        $sql = "select * from vote_log where vote_id = '$id' and open_id = '$openid' and date = '$date'" ;
	        $res = $db->query($sql);   
	        $num = $res->num_rows;
	        $sql2 = "select * from vote_log where open_id = '$openid' and date = '$date'";
	        $resu = $db->query($sql2);
	        $number = $resu->num_rows;
	        $db->close();
			if($number >= 3){
			$flag = "2";
			}
			if($num == 0)
			{
			$flag = "0";
			}
			else
			{
			$flag = "1";
			}
			return $flag;
	    }
		private function https_request($url, $data = null)
		{
		    $curl = curl_init();
		    curl_setopt($curl, CURLOPT_URL, $url); 
		    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
		    curl_setopt($curl, CURLOPT_POST, 1);
		    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		    $result = curl_exec($curl);
		    if (curl_errno($curl)) {
		        return 'Errno'.curl_error($curl);
		    }
		    curl_close($curl);
		    return $result;
		}

 
     protected function http_request($url, $data = null)
     {
         $curl = curl_init();
         curl_setopt($curl, CURLOPT_URL, $url);
         curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
         curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
         if (!empty($data)){
             curl_setopt($curl, CURLOPT_POST, 1);
             curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
         }
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
         $output = curl_exec($curl);
         curl_close($curl);
         return $output;
     }

}
// 从数据库检测是否注册
 function check_openid($check_openid) {
        
     @ $db = new mysqli('localhost','root','mysql805','sqw');
         
        if (mysqli_connect_errno()){
            echo '数据库没有连接成功！';
            exit;
        }
        mysqli_set_charset ($db,'utf8');
        $check = "select * from customer where open_id = '".$check_openid."'";
        $openidarray = $db->query($check);
        $num_results = $openidarray->num_rows;
        
        if ($num_results > 0){
            
            return true;
        }
        else{
            return false;
        }
        
 }

?>