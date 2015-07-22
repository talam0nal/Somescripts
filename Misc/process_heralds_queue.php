<?php
error_reporting(E_ALL);
define('IN_SITE', true);
define('IN_ACP', true);
require_once($_SERVER['DOCUMENT_ROOT'].'/engine/engine.php');

$table = 'imagick_processing_queue';
$offset = 1;
$compressionQuality = 100;
$resolutionW = 300;
$resolutionH = 300;

$unprocessedFiles = $db->get_data(
	'SELECT 
		* 
	FROM 
		'.$table.'
	WHERE 
		page_processed != total_pages 
	LIMIT 1'
);
if (!count($unprocessedFiles)) exit();

//Указываем imagick'у какой pdf файл нужно конвертировать
$pdf_file = ROOT_DIR.'/uploads/'.$unprocessedFiles[0]['entity'].'/'.$unprocessedFiles[0]['entity_id'].'/'.$unprocessedFiles[0]['filename'];

//Получаем количество страниц pdf файла
$total = (int) $unprocessedFiles[0]['total_pages']; 
$pageStart = (int) $unprocessedFiles[0]['page_processed']; 

$pageEnd = $pageStart+$offset;

$target = (int) $pageStart+$pageEnd;

if ($pageEnd > $total) {
	$pageEnd = (int) $unprocessedFiles[0]['total_pages'];
} 

$thumbs = 
	array (
	'0' => array(
		'prefix'=>'',
		'w'=>'1302',
		'h'=>'1842',
		'inner'=>false,
		'watermark'=>false,
		'ratio' => '0.5'
		),
	'1' =>
	array (
		'prefix' => 's_',
		'w' => '450',
		'h' => '655',
		'inner' => true,
		'ratio' => '0.4'
	),
);

//Пробегаем все страницы pdf'ки циклом
for ($i=$pageStart; $i < $pageEnd; $i++) {
	//Устанавливаем настройки разрешения и качества
	$img = new imagick;
	$img->setCompressionQuality($compressionQuality);

	//Устанавливаем разрешение картинки по высоте и ширине
	$img->setResolution($resolutionW, $resolutionH);
	
	//Определяем коэфициэент изменения resoltion
	$resolutionRatio = $resolutionW/72;

	//Читаем нужную страницу pdf файла
	$img->readImage($pdf_file."[$i]");

	//Ширина pdf-изображения
	$imageWidth = $img->getImageWidth();

	//Высота pdf-изображения
	$imageHeight = $img->getImageHeight();

	//Определяем изначальный размер изображения
	$initialImageSizeW = $imageWidth/$resolutionRatio;
	$initialImageSizeH = $imageHeight/$resolutionRatio;

	//Устанавливаем максимальный размер изображения
	$imageSizeDelimiter = 2000;
	$enhanceRatio = 1;

	//Определяем ориентацию изображения
	$imageAspectRatio = $imageWidth / $imageHeight;
	if ($imageAspectRatio > 1) {
		$imageOrientation = true;
	} else {
		$imageOrientation = false;
	}

	if (!$imageOrientation == true) {
		//Если изображение вертикальное - это означает, что меньшая сторона это ширина
		//Определяем размер большей стороны. То есть высоты
		$m = $imageSizeDelimiter/$initialImageSizeW;
		$newHeight = $m * $initialImageSizeH;
		$newWidth = $imageSizeDelimiter;
	} else { 
		//Если изображение горизонтальное это ознfчает, что меньшая сторона это высота
		$m=$imageSizeDelimiter/$initialImageSizeH;
		$newWidth = $m * $initialImageSizeW;
		$newHeight = $imageSizeDelimiter;
	}

	//Устанавливаем настройки цвета
	$img->setImageMatte(true);
	$img->setImageMatteColor('white');
	$img->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE); 

	//Устанавливаем image gravity
	$img->setGravity(Imagick::GRAVITY_CENTER);

	//Циклом пробегаемся по настройке конфига "thumbs"
	foreach ($thumbs as $key => $value) {
		if(!$value['prefix']=='') { 
			//Если есть префикс, следовательно это тумб, поэтому кропаем и ресайзим
			$img->cropThumbnailImage ( $value['w'] , $value['h'] );	
		} else { 
			//Если префикса нет, значит это оригинал и подставляем ratio из конфига
			//Но если же высота и ширина картинки меньше n пикселей, то коэфициент ратио будет x1
			if ( ($initialImageSizeH < $imageSizeDelimiter) || ($initialImageSizeW < $imageSizeDelimiter) ) { 
			//На самом деле изначальные параметры 1 200px, но мы всё домножаем на 4,166 за счет резолюшина 300
				$value['ratio'] = 1*$enhanceRatio;
			}
			$img->adaptiveResizeImage($newWidth, $newHeight);
		}
		//Пишем файл c префиксом из конфига и с постфиксом номера страницы
		$img->writeImage(ROOT_DIR.'uploads/'.$unprocessedFiles[0]['entity'].'/'.$unprocessedFiles[0]['entity_id'].'/'.$value['prefix'].$unprocessedFiles[0]['filename'].'_'.$i.'.jpg');
	}

	//Пишем фотки в базу
	$db->insert(
		$unprocessedFiles[0]['entity'].'_photos', 
		array(
			'image' => $unprocessedFiles[0]['filename'].'_'.$i.'.jpg', 
			'id_parent' => $unprocessedFiles[0]['entity_id'], 
			'sort' => $i
			)
		); 
	}

	$db->update(
		$table, 
		array('page_processed' => $pageEnd),
		'entity_id='.$unprocessedFiles[0]['entity_id']
	);
	unset($img);
?>