<?php

class Thumb {

	/**
	 * create many thumbnails from existing image with max sizes
	 * 
	 * @param string $sourceFilePath
	 * @param array $targetFilePathes
	 * @param array $sizes 
	 */
	public function createThumbnails($sourceFilePath, array $targetFilePathes, array $sizes) {

		$sourceInfo = @getimagesize($sourceFilePath);

		$i = 0;
		foreach ($targetFilePathes as $path) {
			$im = new imagick($sourceFilePath);
			if (!$im) {
				throw new Exception('cant open uploaded file #' . $sourceFilePath);
			}
			$current_sizes = isset($sizes[$i]) ? $sizes[$i] : false;
			if (!$current_sizes) {
				throw new Exception('sizes not set for thumb #' . $i);
			}
			list($maxX, $maxY , $save_dimensions) = $current_sizes;
			if (!$this->createThumbnail($im, $sourceInfo, $path, $maxX, $maxY, $save_dimensions))
				throw new Exception('cant create thumb#' . $i . ' ' . $maxX . ' ' . $maxY . ' ' . $path);
			$i++;
		}
	}

	/**
	 * create thumbnail from existing image with max sizes
	 * 
	 * @param link $im - ImageMagick object
	 * @param string $targetFilePath
	 * @param int $maxX
	 * @param int $maxY 
	 */
	public function createThumbnail($im, $sourceInfo, $targetFilePath, $width, $height, $save_dimensions = false) {
		if ($sourceInfo) {
			if ($width && $height) {
				if ($save_dimensions) {
					$ratio_orig = $sourceInfo[0] / $sourceInfo[1];
					if ($width / $height > $ratio_orig) {
						$width = round($height * $ratio_orig);
					} else {
						$height = round($width / $ratio_orig);
					}
				}


				if ($sourceInfo[0] < $width) {
					$width = $sourceInfo[0];
					$height = $sourceInfo[1];
				}
				($width && $height) ? $im->cropThumbnailImage($width, $height) : false;
			}
			$im->setImageCompression(imagick::COMPRESSION_JPEG);
			$im->setImageCompressionQuality(95);
			$im->stripImage();
			$result = $im->writeImage($targetFilePath);
			$im->destroy();
			return $result;
		}
	}

}

?>
