<?php

class UploadAvatar {

	var $out = false;

	function __construct($filename, $width = 0, $height = 0, $mode = "simple", $targetname = "") {
		return $this->upload_resize($filename, $mode, $width, $height, $targetname);
	}

	// загружает файл, ресайзит и сохраняет в нужной таблице
	function upload_resize($file, $mode, $w, $h, $targetname) {
		if ($mode == "simple")
			return $this->resize($file, $w, $h, $targetname);
	}

	function resize($file, $width, $height, $targetname) {
		$size = getimagesize($file);
		$mime = $size['mime'];
		list($width_orig, $height_orig) = $size;

		if (!$width_orig || !$height_orig)
			return false;
		if ($width == 0)
			$width = $file;
		if ($height == 0)
			$height = $height_orig;

		$ratio_orig = $width_orig / $height_orig;

		if ($width / $height > $ratio_orig) {
			$width = round($height * $ratio_orig);
		} else {
			$height = round($width / $ratio_orig);
		}

		if ($width_orig < $width) {
			$height = $height_orig;
			$width = $width_orig;
		}



		if (strstr($targetname, ".jpg") || strstr($targetname, ".JPG") || strstr($targetname, ".jpeg") || strstr($targetname, ".JPEG"))
			$source = imagecreatefromjpeg($file);else
		if (strstr($targetname, ".gif") || strstr($targetname, ".GIF"))
			$source = imagecreatefromgif($file);else
		if (strstr($targetname, ".png") || strstr($targetname, ".PNG"))
			$source = imagecreatefrompng($file);
		if (!$source) {
			$size = getimagesize($file);
			if (strstr($size['mime'], "jpg") || strstr($size['mime'], "JPG") || strstr($size['mime'], "jpeg") || strstr($size['mime'], "JPEG"))
				$source = imagecreatefromjpeg($file);else
			if (strstr($size['mime'], "gif") || strstr($size['mime'], "GIF"))
				$source = imagecreatefromgif($file);else
			if (strstr($size['mime'], "png") || strstr($size['mime'], "PNG"))
				$source = imagecreatefrompng($file);
		}

		if (!$source) {
			return;
		}


		// Создаем новое изображение
		$target = imagecreatetruecolor($width, $height);

		// Копируем существующее изображение в новое с изменением размера:
		imagecopyresampled(
			$target, // Идентификатор нового изображения
			$source, // Идентификатор исходного изображения
			0, 0, // Координаты (x,y) верхнего левого угла
			// в новом изображении
			0, 0, // Координаты (x,y) верхнего левого угла копируемого
			// блока существующего изображения
			$width, // Новая ширина копируемого блока
			$height, // Новая высота копируемого блока
			$width_orig, // Ширина исходного копируемого блока
			$height_orig  // Высота исходного копируемого блока
		);
		//print($target);
		if (imagejpeg($target, $targetname, 99)) {
			@imagedestroy($target);
			$this->out = $targetname;
			return $targetname;
		}else
			return false;
		// Как всегда, не забываем:
	}

}

?>
