<html>
    <body>
<?php
$vk_id = '3883430';
$vk_key = '2IiGNyCkkTdMXXDEd2hH';
$my_site = "http://www.oggettoweb.u3986199.cp.regruhosting.ru";
if (isset($_REQUEST['code'])){
   $get_tocken_url = "https://oauth.vk.com/access_token?client_id={$vk_id}&client_secret={$vk_key}&code={$_REQUEST['code']}&redirect_uri={$my_site}";
   $data = json_decode(file_get_contents($get_tocken_url));
   if (isset($data->access_token) && !$_SESSION['access_token']) {
    $_SESSION['access_token'] = $data->access_token;
    $_SESSION['user_id'] = $data->user_id;
   }
   $friends_data_url = "https://api.vk.com/method/friends.get?user_id={$_SESSION['user_id']}&access_token={$_SESSION['access_token']}";
   $friends_data = json_decode(file_get_contents($friends_data_url));
   shuffle($friends_data->response);
   $friends = array_slice($friends_data->response, 1, 5);
   foreach ($friends as $k=>$one_friend) {
       $friends_get_image_url = "https://api.vk.com/method/photos.getProfile?owner_id={$one_friend}&access_token={$_SESSION['access_token']}";
       $images = json_decode(file_get_contents($friends_get_image_url));
       $image_url = $images->response[0]->src_big;
       $file = file_get_contents($image_url );
       $base64 = str_split(base64_encode($file));
       $max = count($base64);
       for($i = 0;$i<10;$i++) {
               $base64[rand(500, $max)] = $base64[rand(500, $max)];
       }
       $file_dt = base64_decode(implode('',$base64));
       $img_extension = end(explode('.', $image_url));
       $local_filename = "$k.$img_extension";
       file_put_contents("images/$local_filename",  $file_dt);
       ?><img src="/images/<?=$local_filename?>"/><?
   }
   exit;
}
?>
<div style="text-align: center">
<a href="http://oauth.vk.com/authorize?client_id=<?=$vk_id?>&scope=friends&wall&redirect_uri=<?=$my_site?>&display=popup&scope=friends,photos,wall&response_type=code"><img src="http://www.oggettoweb.u3986199.cp.regruhosting.ru/1.jpg" align="center"></a>
</div>
</body>
</html>
