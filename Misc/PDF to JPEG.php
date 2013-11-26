<?php 
	function addHerald($parent_id, $randomname, $rewriteRule) {
		//Если функция запущена с параметром $rewriteRule со значением true, то перезаписываем путь
		//Это сделано, чтобы при цепочке вызовов функций deleteHerald+addHerald не происходило лишней конкатенации строк
		if($this->nestingFolders == true && $rewriteRule == true) {	
			$this->path=$this->path.$parent_id.'/';
		}

		//Если директива nestingFolders true - приписиваем к пути parent id и создаём папку
		if($this->nestingFolders == true) {	
			mkdir (ROOT_DIR.$this->path, 0777, false);
		}

		//Загружаем pdf файл
		if(empty($_FILES['file']['name'])) {
			return;
		}

		//Получаем имя файла
		$filename = $_FILES['file']['name'];

		//Перемещаем загруженный файл
		move_uploaded_file($_FILES['file']['tmp_name'],ROOT_DIR.$this->path.$randomname);

		//Укащываем imagick'у какой pdf файл нужно конвертировать
		$pdf_file = ROOT_DIR.$this->path.$randomname;

		//Получаем количество страниц pdf файла
		$number = new imagick($pdf_file);

		//Пробегаем все страницы pdf'ки циклом
		for ($i=0; $i < $number->getNumberImages(); $i++) {

			//Устанавливаем настройки разрешения и качества
			$img = new imagick;
			$img->setCompressionQuality($this->compressionQuality);
			$img->setResolution($this->resolutionW, $this->resolutionH);
			

			//Читаем нужную страницу pdf файла
			$img->readImage($pdf_file."[$i]");
			
			//Устанавливаем настройки цвета
			$img->setImageMatte(true);
			$img->setImageMatteColor('white');
			$img->setImageAlphaChannel(Imagick::ALPHACHANNEL_OPAQUE);
			
			/*
			//http://www.php.net/manual/en/imagick.constants.php#imagick.constants.colorspace
			http://php.net/manual/en/imagick.setimagecolorspace.php
			COLORSPACE constants
			12 - всё инвертировно

			imagick::COLORSPACE_UNDEFINED (integer) Всё чёрное
			imagick::COLORSPACE_RGB (integer) Чёрное всё
			imagick::COLORSPACE_GRAY (integer)
			imagick::COLORSPACE_TRANSPARENT (integer) чёрное всё
			imagick::COLORSPACE_OHTA (integer)
			imagick::COLORSPACE_LAB (integer) Синее всё
			imagick::COLORSPACE_XYZ (integer) Тётка на картинке стала рыжей
			imagick::COLORSPACE_YCBCR (integer) Зелёное всё
			imagick::COLORSPACE_YCC (integer)
			imagick::COLORSPACE_YIQ (integer)
			imagick::COLORSPACE_YPBPR (integer)
			imagick::COLORSPACE_YUV (integer)
			imagick::COLORSPACE_CMYK (integer) Совсем не подходит
			imagick::COLORSPACE_SRGB (integer) Получается инверсия белого цвета
			imagick::COLORSPACE_HSB (integer) Розовое всё
			imagick::COLORSPACE_HSL (integer) Чёрные цвета
			imagick::COLORSPACE_HWB (integer)
			imagick::COLORSPACE_REC601LUMA (integer)
			imagick::COLORSPACE_REC709LUMA (integer)
			imagick::COLORSPACE_LOG (integer)
			imagick::COLORSPACE_CMY (integer) Инверсия всех цветов
			*/

			//Циклом пробегаемся по настройке конфига "thumbs"
			foreach ($this->thumbs as $key => $value) {
				//Резайсим
				$img->adaptiveResizeImage($value['w'], $value['h']);
				
				//Пишем c префиксом из конфига, с постфиксом номера страницы
				$img->writeImage(ROOT_DIR.$this->path.$value['prefix'].$randomname.'_'.$i.'.jpg');
			}

			//Пишем фотки в базу
			$this->registry->db->insert($this->name.'_photos', array('image'=>$randomname.'_'.$i.'.jpg', 'id_parent'=>$parent_id, 'sort' => $i, 'lang'=>$_SESSION['lang'])); 
			}
		}	

	function deleteHerald($parent_id) {
		if(!empty($parent_id)) {
		$filename=$this->registry->db->get_single('SELECT file FROM '.$this->name.' WHERE id='.$parent_id);
		}

		//Посчитав количество страниц удаляем jpeg файлы и их записи в базе
		if(file_exists(ROOT_DIR.$this->path.$filename))
		{
			$countPdfPages = new imagick(ROOT_DIR.$this->path.$filename);
			for ($i=0; $i < $countPdfPages->getNumberImages(); $i++) {
				foreach ($this->thumbs as $key => $value) {
					unlink(ROOT_DIR.$this->path.$value['prefix'].$filename.'_'.$i.'.jpg');
				}
			}

			//Удаляем запись из базы
			$this->registry->db->query("DELETE FROM `".$this->name."_photos` WHERE id_parent='".$parent_id."'");

			//Удаляем pdf'ку
			if ( unlink(ROOT_DIR.$this->path.$filename) ) {

			} else {
				$this->addMessageTime("При удалении файла произошла ошибка. Файла".ROOT_DIR.$this->path.$filename." не существует<p>", "error");
			}

				rmdir (ROOT_DIR.$this->path);

		} else {
			$this->addMessageTime("При удалении файла произошла ошибка. Файла".ROOT_DIR.$this->path.$filename." не существует<p>", "error");
		}		
	}