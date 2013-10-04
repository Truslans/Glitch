<?session_start();?>
    <html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
        <script src="http://vk.com/js/api/openapi.js" type="text/javascript"></script>
    </head>
    <body>
    <?
    $user_id = $_SESSION['user_id'];
    $vk_id = '3913264';
    $vk_key = 'ijhTqJF2UJ54lBZHJn81';
    $my_site = "http://oggettoweb.u3986199.cp.regruhosting.ru";
    $folderName = 'default';

    if (isset($_REQUEST['code'])){
        if (!$user_id) {
            $get_tocken_url = "https://oauth.vk.com/access_token?client_id={$vk_id}&client_secret={$vk_key}&code={$_REQUEST['code']}&redirect_uri={$my_site}";
            $data = json_decode(file_get_contents($get_tocken_url));
            $_SESSION['access_token'] = $data->access_token;
            $_SESSION['user_id'] = $data->user_id;
            $user_id = $_SESSION['user_id'];
            $folderName = $user_id;
        }
        if(!is_dir('images/' . $folderName)) {
            mkdir('images/' . $folderName, 0755, TRUE);
        }
        $friends_data_url = "https://api.vk.com/method/friends.get?user_id={$user_id}&access_token={$_SESSION['access_token']}";
        $friends_data = json_decode(file_get_contents($friends_data_url));
    if(isset($friends_data->response)) {
        $friends = array_slice($friends_data->response, 1, 5);
        ?><table><?
        foreach ($friends as $k=>$one_friend) {
            ?><tr><?
            $friends_get_image_url = "https://api.vk.com/method/photos.getProfile?owner_id={$one_friend}&access_token={$_SESSION['access_token']}";
            $images = json_decode(file_get_contents($friends_get_image_url));
            for($j=0;$j<5;$j++){
                ?><td><?
                $image_url = isset($images->response[$j]->src_big) ? $images->response[$j]->src_big : $images->response[0]->src_big;
                if (!$image_url) continue;
                $file = str_split(file_get_contents($image_url));
                $max = count($file);
                for($i = 0;$i<5;$i++) {
                    $file[rand(1000, $max)] = $file[rand(1000, $max)];
                }
                $file_dt = implode('',$file);
                $img_extension = end(explode('.', $image_url));
                $local_filename = "$k$j.$img_extension";
                file_put_contents("images/{$folderName}/{$local_filename}",  implode('',$file));
                ?><img height="100" src="/images/<?=$folderName . '/' . $local_filename?>"/>
                </td>
            <?
            }
            ?></tr><?
        }
        ?></table>
        <a href="?clean">Очистить папку</a></br>
        <a href ="?publish" >Опубликовать на стене</a>
    <?
    exit;
    } else {
        echo '<pre>';
        print_r($friends_data);
        echo '</pre>';
        ?>Не удалось получить список друзей<?
    }
    } else if (isset ($_REQUEST['publish'])) {
    $files = array_slice(scandir('images/' . $folderName), 2);
    $photos_ids = array();
    foreach ($files as $oneFile) {
        $wall_post_server_url = "https://api.vk.com/method/photos.getWallUploadServer?access_token={$_SESSION['access_token']}";
        $data = json_decode(file_get_contents($wall_post_server_url));
        if (isset($data->response->upload_url)) {
            $file_to_upload = array('photo'=>"@images/" . $folderName .'/' . $oneFile);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, $data->response->upload_url);
            curl_setopt($ch, CURLOPT_POST,1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $file_to_upload);
            $result = json_decode(curl_exec($ch));
            curl_close($ch);
            if (isset($result->photo)) {
                $urlencoded = urlencode($result->photo);
                $save_wall_photo_url = "https://api.vk.com/method/photos.saveWallPhoto?server={$result->server}&photo={$urlencoded}&hash={$result->hash}&access_token={$_SESSION['access_token']}";
                $data = json_decode(file_get_contents($save_wall_photo_url));
                if (isset($data->response[0]->id)) {
                    $photos_ids[] = $data->response[0]->id;
                }
            }
        }
    }
    ?>
        <script type="text/javascript">
            VK.init({
                apiId: <?=$vk_id?>
            });
            VK.Api.call('wall.post',
                {message: "Glitch",
                    wall_id: <?=$user_id?>,
                    attachments: "<?=implode(',', (array) $photos_ids)?>"
                },
                function(r) {
                    console.log('downloaded');
                });
        </script>
        <?
        exit;
    }
    if (isset($_REQUEST['clean'])) {
        removeDir('images/' . $folderName);
    }
    ?>
    <a href="http://oauth.vk.com/authorize?client_id=<?=$vk_id?>&scope=friends&redirect_uri=<?=$my_site?>&display=popup&scope=friends,photos,wall&response_type=code"><img src="1.jpg"></a>
    </body>
    </html>
<?
/*Дополнительные функции*/
function removeDir($path) {
    return is_file($path)?
        @unlink($path):
        array_map('removeDir',glob($path."/*"))==@rmdir($path);
}
