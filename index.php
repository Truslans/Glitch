<?session_start();?>
    <html>
    <body>
    <script src="http://vk.com/js/api/openapi.js" type="text/javascript"></script>
    <?
    //Задаем базовые настройки
    $user_id = $_SESSION['user_id']; // идентификатор пользователя
    $vk_id = '3883430'; //идентификатор вебсайта зареганого в вк
    $vk_key = '2IiGNyCkkTdMXXDEd2hH'; //секретный код вк
    $my_site = "http://www.oggettoweb.u3986199.cp.regruhosting.ru/"; //адрес нашего сайта
    /*Удаляем папку с фотками если задан параметр clean*/
    if (isset($_REQUEST['clean'])) {
        removeDir('images/' . $user_id);
    }
    /*если передан параметр code значит мы прошли авторизацию*/
    if (isset($_REQUEST['code'])){
        //если пользователя не определен значит получаем информацию о нем из вк и загружаем в сессию.
        if (!$user_id) {
            $get_tocken_url = "https://oauth.vk.com/access_token?client_id={$vk_id}&client_secret={$vk_key}&code={$_REQUEST['code']}&redirect_uri={$my_site}";
            //получаем ответ vk api
            $data = file_get_contents($get_tocken_url);
            //парсим ответ vk api
            $data = json_decode($data);
            //Загружаем в сесию пользователя
            $_SESSION['access_token'] = $data->access_token;
            $_SESSION['user_id'] = $data->user_id;
            //реинициализируем переменную с id пользователя для дальнейшего использования в этом же скрипте.
            $user_id = $_SESSION['user_id'];
        }
        //проверяем и если не существует создаем папку images
        mkdir('images/' . $data->user_id, 0755, TRUE);
        /*получаем список друзей*/
        $friends_data_url = "https://api.vk.com/method/friends.get?user_id={$user_id}&access_token={$_SESSION['access_token']}";
        $friends_data = json_decode(file_get_contents($friends_data_url));
        shuffle($friends_data->response);
        $friends = array_slice($friends_data->response, 1, 5);
        ?><table><?
        /*Получаем и выводим фотографии*/
        foreach ($friends as $k=>$one_friend) {
            ?><tr><?
            $friends_get_image_url = "https://api.vk.com/method/photos.getProfile?owner_id={$one_friend}&access_token={$_SESSION['access_token']}";
            $images = json_decode(file_get_contents($friends_get_image_url));
            for($j=0;$j<5;$j++){
                ?><td><?
                $image_url = isset($images->response[$j]->src_big) ? $images->response[$j]->src_big : $images->response[0]->src_big;
                $file = str_split(file_get_contents($image_url));
                $max = count($file);
                /*собственно сам глитч, заменяем случайные куски изображения на другие случайны куски этого же изображения.*/
                for($i = 0;$i<2;$i++) {
                    $file[rand(500, $max)] = $file[rand(500, $max)];
                }
                /*склеиваем файл обратно*/
                $file_dt = implode('',$file);
                /*узнаем расширение файла*/
                $img_extension = end(explode('.', $image_url));
                $local_filename = "$k$j.$img_extension";
                /*записываем получившуюся картинку к себе на диск*/
                file_put_contents("images/{$user_id}/{$local_filename}",  implode('',$file));
                /*выводим картинку*/
                ?><img height="100" src="/images/<?=$user_id . '/' . $local_filename?>"/>
                </td>
            <?
            }
            ?></tr><?
        }
        ?></table>
        <a href="?clean">Очистить папку</a></br>
        <a href ="?publish" >опубликовать на стене</a>
    <?
    exit;
    } else if (isset ($_REQUEST['publish'])) {
    /*Публикуем изображения*/
    //сканируем директорию и загружаем все картинки в вк.
    $files = array_slice(scandir('images/' . $user_id), 2);
    $photos_ids = array();
    foreach ($files as $oneFile) {
        /*узнаем сервер для загрузки*/
        $wall_post_server_url = "https://api.vk.com/method/photos.getWallUploadServer?access_token={$_SESSION['access_token']}";
        $data = json_decode(file_get_contents($wall_post_server_url));
        /*формируем и посылаем post запрос в вк*/
        $file_to_upload = array('photo'=>"@images/" . $oneFile);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $data->response->upload_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $file_to_upload);
        $result = json_decode(curl_exec($ch));
        curl_close($ch);
        /*преобразуем свойство photo в удобоваримый вид*/
        $urlencoded = urlencode($result->photo);
        /*сохраняем фотки*/
        $save_wall_photo_url = "https://api.vk.com/method/photos.saveWallPhoto?server={$result->server}&photo={$urlencoded}&hash={$result->hash}&access_token={$_SESSION['access_token']}";
        $data = json_decode(file_get_contents($save_wall_photo_url));
        /*если фотка загрузилась в вк, добавляем ее в наш массив для публикации*/
        if ($data->response[0]->id) {
            $photos_ids[] = $data->response[0]->id;
        }
    }
    ?>
        <!--js скриптик публикует запись на стене ползователя, предварительно запрашивая разрешение. -->
        <script type="text/javascript">
            VK.Api.call('wall.post',
                {message: "Glitch",
                    wall_id: <?=$user_id?>,
                    attachments: "<?=implode(',', $photos_ids)?>"
                },
                function(r) {});
        </script>
        <?
        exit;
    }?>
    <a href="http://oauth.vk.com/authorize?client_id=<?=$vk_id?>&scope=friends&redirect_uri=<?=$my_site?>&display=popup&scope=friends,photos,wall&response_type=code">авторизоваться в вк</a>
    </body>
    </html>

<?
/*Дополнительные функции*/
function removeDir($path) {
    return is_file($path)?
        @unlink($path):
        array_map('removeDir',glob($path."/*"))==@rmdir($path);
}
