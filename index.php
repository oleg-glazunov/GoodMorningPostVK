<?php

    require "config.php";

	$vk = new Vk($token);

	$images = array	(
	    'pic/1.jpg',
		'pic/2.jpg',
		'pic/3.jpg',
		'pic/4.jpg',
		'pic/5.jpg',
		'pic/6.jpg',
		'pic/7.jpg',
		'pic/8.jpg',
		'pic/9.jpg',
		'pic/10.jpg',
		'pic/11.jpg',
		'pic/12.jpg',
		'pic/13.jpg',
		'pic/14.jpg',
		'pic/15.jpg',
		'pic/16.jpg',
		'pic/17.jpg',
		'pic/18.jpg',
		'pic/19.jpg',
		'pic/20.jpg',
		'pic/21.jpg');

	$i = rand(0, count($images)-1); // функция rand генерирует случайный числовой номер (индекс) массива, 1-й параметр соответствует min, 2-й - max	
	$bg = new Imagick($images[$i]);
    // погода
    $tgn = file_get_contents('http://api.openweathermap.org/data/2.5/weather?id=484907&mode=json&units=metric&appid=4e18471d0de9bf62801d17fb78f2f75d&lang=ru'); // Taganrog
    $tgn_data = json_decode($tgn);
    $tgn_asd = str_replace(".",",",(round(($tgn_data->main->temp),1)));  // показания температуры. Сразу меняем точку на запятую.
    $tgn_weather = $tgn_data->weather[0]->description;  //погода
    // погода с большой буквы
    $tgn_weather_ = mb_strtoupper(substr($tgn_weather,0,2), "utf-8"); // первый символ
    $tgn_weather[0] = $tgn_weather_[0];
    $tgn_weather[1] = $tgn_weather_[1];
    // скорость ветра
    $tgn_wind = str_replace(".",",",(round(($tgn_data->wind->speed),1)));
    // получаем имя картинки состояния погоды
    $tgn_pic = $tgn_data->weather[0]->icon;
    $tgn_ico = new Imagick('https://openweathermap.org/img/wn/'.$tgn_pic.'@2x.png');
    $tgn_ico->adaptiveResizeImage(100,100); // изменим размер иконки

    // Выводим погоду на странице
	echo "Сейчас в Таганроге";
    ?>
    <br>               
    <div class="block1">
        <div> <img src="https://openweathermap.org/img/wn/<? echo $tgn_pic; ?>.png"></div>  <!-- Вставляем картинку погоды -->
    </div>
    <div class="block2"><?php echo $tgn_asd; ?>&deg;</div> <!-- Вставляем температуру воздуха  -->
    <br>
    <?
	
	$bg->compositeImage($tgn_ico, Imagick::COMPOSITE_DEFAULT, 8, 27); // иконка погоды Таганрог
    $bg->setImageFormat("jpg");
    $bg->writeImage('pic/new.jpg');
    $image = imageCreateFromJpeg('pic/new.jpg'); // получаем изображение
	$white = imagecolorallocate($image, 255, 255, 255); // добавляем в палитру белый цвет
    $font = __DIR__ . '/font/RobotoRegular.ttf'; // шрифт
    // вставляем текст в картинку
    imagefttext($image, 20, 0, 20, 40, $white, $font, 'Сейчас в Таганроге');
    imagefttext($image, 30, 0, 110, 93, $white, $font, $tgn_asd.'°');
    imagefttext($image, 20, 0, 20, 135, $white, $font, $tgn_weather);	
    imagefttext($image, 20, 0, 20, 170, $white, $font, 'Ветер '.$tgn_wind.' м/с');	
    imagejpeg($image, 'pic/new.jpg', 100); // сохраняем полученную картинку в 100% качестве
    imagedestroy($image); // освобождаем

	$upload_server = $vk->photosGetWallUploadServer($group_id);
	$upload = $vk->uploadFile($upload_server['upload_url'], 'pic/new.jpg');

	$save = $vk->photosSaveWallPhoto([
			'group_id' => $group_id,
			'photo' => $upload['photo'],
			'server' => $upload['server'],
			'hash' => $upload['hash']
		]
	);

	$attachments = sprintf('photo%s_%s', $save[0]['owner_id'], $save[0]['id']);

	$post = $vk->wallPost([
		'owner_id' => "-$group_id",
		'from_group' => 1,
		'message' => "#сдобрымутром #доброеутро #автошколакурьер #таганрог",
		'attachments' => $attachments
	]);

	class Vk {
		private $token;
		private $v = '5.95';

		public function __construct($token)
		{
			$this->token = $token;
		}

		public function wallPost($data)
		{
			return $this->request('wall.post', $data);
		}

		public function photosGetWallUploadServer($group_id)
		{
			$params = [
				'group_id' => $group_id,
			];
			return $this->request('photos.getWallUploadServer', $params);
		}

		/**
		 * @param $params [user_id, group_id, photo, server, hash]
		 * @return mixed
		 * @throws \Exception
		 */
		
		public function photosSaveWallPhoto($params)
		{
			return $this->request('photos.saveWallPhoto', $params);
		}

		public function uploadFile($url, $path)
		{
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);

			if (class_exists('\CURLFile')) {
				curl_setopt($ch, CURLOPT_POSTFIELDS, ['file1' => new \CURLFile($path)]);
			} else {
				curl_setopt($ch, CURLOPT_POSTFIELDS, ['file1' => "@$path"]);
			}

			$data = curl_exec($ch);
			curl_close($ch);
			return json_decode($data, true);
		}

		private function request($method, array $params)
		{
			$params['v'] = $this->v;

			$ch = curl_init('https://api.vk.com/method/' . $method . '?access_token=' . $this->token);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
			$data = curl_exec($ch);
			curl_close($ch);
			$json = json_decode($data, true);
			if (!isset($json['response'])) {
				throw new \Exception($data);
			}
			usleep(mt_rand(1000000, 2000000));
			return $json['response'];
		}
	}

?>