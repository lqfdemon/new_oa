<!DOCTYPE html>
 <html lang="zh-cn">
     <head>
         <meta charset="UTF-8">
     </head>
    <body>
    
<?php



		
	 @ $db = new mysqli('localhost','root','mysql805','oa');
		 
		if (mysqli_connect_errno()){
			echo '数据库没有连接成功！';
			exit;
		}
		mysqli_set_charset ($db,'utf8');
	    $check = "select * from user where sign_pic != ''";
		$openidarray = $db->query($check);
		$num_results = $openidarray->num_rows;
		 for ($i=0; $i <$num_results; $i++) 
					{
						$pet_det = $openidarray->fetch_assoc();
						$name = $pet_det['name'];
						$sign_pic = $pet_det['sign_pic'];
						$img = "<img src = ".$sign_pic."></img>";
						echo $name.":                ".$img."<BR>";
					}
				
?>

			
                       
     </body>
 </html>