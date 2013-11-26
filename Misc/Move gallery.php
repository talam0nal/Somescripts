<?php
error_reporting(E_ALL);

exit();

set_time_limit(0);
define('IN_SITE', true);

require_once( $_SERVER['DOCUMENT_ROOT'] . '/engine/engine.php');
require_once( $_SERVER['DOCUMENT_ROOT'] . '/engine/class.thumb.php');

$notFinded = 0;
$finded = 0;
$albums = 0;
$thumbsforfile = 0;
$thumbsforalbums = 0;

$time=time();
$data = $db->get_data("SELECT image, id_parent FROM photogallery_photos ORDER by id_parent ASC");

$configForCovers = array('1' => 
						array(
							'prefix' => 'cover_crop_',
							'w' => '',
							'h' => '1000',
							'inner' => false
							),
						'2' => array(
							'prefix' => 'toRename_',
							'w' => '900',
							'h' => '250',
							'inner' => false							
							)
						);

foreach ($data as $key => $value) {
	/* Для отладки
	if($key > 10) {
		exit();
	}	
	*/
		$value['image']=str_replace ( '.png', '.jpg', $value['image'] );
	
	
  if (file_exists($_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'big_'.$value['image'])) {
  	//Если мы нашли файл big_, то увеличиваем счетчик найденных файлов
  	$finded++;

  	//После этого переименовываем файл big_ в файл без префикса
  	if (
  	rename(
  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'big_'.$value['image'], 
  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.$value['image'])) {
  		//rename success
  	} else {
  		echo "Rename file failed";
  	}

  	//Удаляем файлы с префиксами tiny и small
  	if (
  	unlink($_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'tiny_'.$value['image'])) {
  		//unkink success
  	} else {
  		echo "Unlink failed tiny_";
  	}

  	if (
  	unlink($_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'small_'.$value['image'])) {
  		//unlink success
  	} else {
  		echo "Unlink small_ failed";
  	}

  	//Запиливаем s_ и m_
	if (make_thumbs(
		$config['photogallery']['thumbs'], //Array with settings
		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.$value['image'], //Target file
		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/' , //Output folder
		$value['image'])) {
			$thumbsforfile++;
		} //Filename without extension

  } else {
  	$notFinded++;
  	//echo "File <b>/".$value['id_parent'].'/'.'big_'.$value['image']. "</b> not found<p>";
  }

	if ($key>0) {
		$previousKey=$key-1;		
	} else {
		$previousKey="Whats'up man?";
	}

	if ($previousKey < 0) {
		$previousKey=1;
	}

	if ($data[$key]['id_parent'] == $data[$previousKey]['id_parent']) {
	} else {
		//Если это новый альбом, то увеличиваем счетчик альбомов
		$albums++;
		//Запиливаем cover_crop_ и toRename_
		if (
		make_thumbs(
			$configForCovers, //Array with settings
			$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.$value['image'], //Target file
			$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/' , //Output folder
			$value['image'])) {
				$thumbsforalbums++;
			} //Filename without extension

		//Переименовываем toRename_ в имяфайла_с

		$newName=strtr ( $value['image'], array('.jpg'=>'_c.jpg','.jpeg'=>'_c.jpeg')  );
	  	if(rename(
	  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'toRename_'.$value['image'], 
	  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.$newName)) {
	  		
	  	} else {
	  		echo "Rename failed";
	  	}

	  	//Переименовываем в cover_crop_что-то-там_c.jpg и тоже самое значение пишем в базу
		$newName2= strtr ( $value['image'], array('.jpg'=>'_c.jpg','.jpeg'=>'_c.jpeg')  );
	  	if(rename(
	  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'cover_crop_'.$value['image'], 
	  		$_SERVER['DOCUMENT_ROOT'].'/uploads/photogallery/'.$value['id_parent'].'/'.'cover_crop_'.$newName2)) {
	  		
	  	} else {
	  		echo "Rename of _c failed";
	  	}

	  	//Запиливаем в базу новые значения обложек
	  	echo "crop_".$newName2;
	  	echo "<p>";
	  	$edit_data['cover']="crop_".$newName2;
	  	$db->update('photogallery_albums', $edit_data,'id='.$value['id_parent']);
	}	
}

$time2=time();
$different=$time2-$time;

echo "Processed<b> ".$finded." </b>files in ".$different." seconds <b>".$albums."</b> albums<p>";
echo "Not found<b> ".$notFinded." </b>file(s)<p>";
echo "Make thumbs for files ".$thumbsforfile."<p>";
echo "Make thumbs for albums ".$thumbsforalbums."<p>";

unset($finded);
unset($notFinded);
unset($albums);
unset($thumbsforfile);
unset($thumbsforalbums);
?>