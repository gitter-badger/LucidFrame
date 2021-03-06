<?php
/**
 * This file is part of the PHPLucidFrame library.
 * Core utility and class required for file processing system
 *
 * @package		LC\Helpers\File
 * @since		PHPLucidFrame v 1.0.0
 * @copyright	Copyright (c), PHPLucidFrame.
 * @author 		Sithu K. <hello@sithukyaw.com>
 * @link 		http://phplucidframe.sithukyaw.com
 * @license		http://www.opensource.org/licenses/mit-license.php MIT License
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.txt
 */

/**
 * This class is part of the PHPLucidFrame library.
 * Helper for file processing system
 */
class File{
	/** @var string The uniqued string ID to append to the file name */
	private $uniqueId;
	/** @var array The dimension to be created for image upload */
	private $dimensions;
	/** @var string The upload directory path */
	private $uploadPath;
	/** @var array The allowed file extensions */
	private $extensions;
	/** @var const Type of file resize */
	private $resize = FILE_RESIZE_BOTH;
	/** @var string The file name generated */
	private $fileNameBased;

	/**
	 * Constructor
	 */
	public function File(){
		$this->extensions = array('jpg', 'jpeg', 'png', 'gif');
	}
	/**
	 * Setter for the class properties
	 * @param string $key The property name
	 * @param mixed $value The value to be set
	 * @return void
	 */
	public function set($key, $value){
		$this->$key = $value;
	}
	/**
	 * Getter for the file name generated
	 */
	public function getFileNameBased(){
		return $this->fileNameBased;
	}
	/**
	 * Move the uploaded file into the given directory.
	 * If the uploaded file is image, this will create the various images according to the given $dimension
	 *
	 * @param array $file The uploaded file information from $_FILES['xxx']
	 * @param array Th array of the uploaded files,
	 *  for example,
	 *  uploaded[dimension] = "file-name" for image files or uploaded[] = "file-name" for the files of other types
	 */
	public function upload($file){
		$fileName 		= stripslashes($file['name']);
		$uploadedFile 	= $file['tmp_name'];
		$info 			= pathinfo($fileName);
		$extension 		= strtolower($info['extension']);
		$uploaded 		= false;
		$path 			= $this->uploadPath;

		if($fileName && $uploadedFile){

			if( !(is_array($this->dimensions) && count($this->dimensions)) ){
				$newFileName = $this->getNewFileName($fileName);
				if(move_uploaded_file($uploadedFile, $path . $newFileName)){
					$uploaded = array($newFileName);
				}
			}else{
				if($extension == "jpg" || $extension == "jpeg" ){
					$img = imagecreatefromjpeg($uploadedFile);
				}elseif($extension == "png"){
					$img = imagecreatefrompng($uploadedFile);
				}elseif($extension == "gif"){
					$img = imagecreatefromgif($uploadedFile);
				}

				if( isset($img) && $img ){
					$uploaded = array();
					foreach($this->dimensions as $dimension){
						$resize = explode('x', $dimension);
						$resizeWidth 	= $resize[0];
						$resizeHeight 	= $resize[1];

						if($this->resize == FILE_RESIZE_WIDTH){
							$tmp = File::resizeImageWidth($img, $uploadedFile, $resizeWidth);
						}elseif($this->resize == FILE_RESIZE_HEIGHT){
							$tmp = File::resizeImageHeight($img, $uploadedFile, $resizeHeight);
						}else{
							$tmp = File::resizeImage($img, $uploadedFile, $resizeWidth, $resizeHeight);
						}

						$newFileName = $this->getNewFileName($fileName, $resizeWidth);

						if($extension == "gif"){
							imagegif($tmp, $path . $newFileName);
						}elseif($extension == "png"){
							imagepng($tmp, $path . $newFileName);
						}else{
							imagejpeg($tmp, $path . $newFileName, 100);
						}

						imagedestroy($tmp);
						$uploaded[$dimension] = $newFileName;
					}
					if($img) imagedestroy($img);
				}
			}
		}
		return $uploaded;
	}
	/**
	 * Get a new file name, e.g., original-file-name-[imageWidth]-[uniqueId].ext
	 * Spaces and periods in the original file names are replaced with dashes.
	 *
	 * @param string $file The uploaded file name
	 * @param int $width The image width if the uploaded file is image, otherwise 0
	 *
	 * @return string The file name
	 */
	private function getNewFileName($file, $width=0){
		$info = pathinfo($file);
		$uniqueId = $this->getUniqueId();
		# get the suffix
		$suffix = '';
		if($width) $suffix .= '-' . $width;
		$suffix .= '-' . $uniqueId;
		# clean spaces and periods and replace with dashes
		$justName = str_replace(array(' ', '.'), '-', $info['filename']);
		$justName = preg_replace('/[\-]+/', '-', $justName);
		$justName = preg_replace("/['\"]+/", '', $justName);
		$fileName = $justName . $suffix . '.' . $info['extension'];
		$this->fileNameBased = $justName . '-' . $uniqueId . $info['extension'];
		return $fileName;
	}
	/**
	 * Get a unique id string from the property $uniqueId or generate a random 5-letters string
	 * @return string
	 */
	private function getUniqueId(){
		if($this->uniqueId) return $this->uniqueId;
		else return substr(md5(time()), 0, 5);
	}
	/**
	 * Resize an image to a desired width and height by given width
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newWidth The new width to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImageWidth(&$img, $file, $newWidth){
		list($width, $height) = getimagesize($file);
		$newHeight = ($height/$width) * $newWidth;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given height
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newHeight The new height to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImageHeight(&$img, $file, $newHeight){
		list($width, $height) = getimagesize($file);
		$newWidth = ($width/$height) * $newHeight;
		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Resize an image to a desired width and height by given width and height
	 *
	 * @param resource $img The image resource identifier
	 * @param string $file The image file name
	 * @param int $newWidth The new width to resize
	 * @param int $newHeight The new height to resize
	 *
	 * @return resource An image resource identifier on success, FALSE on errors
	 */
	public static function resizeImage(&$img, $file, $newWidth, $newHeight){
		list($width, $height) = getimagesize($file);

		$scale = min($newWidth/$width, $newHeight/$height);
		# If the image is larger than the max shrink it
		if ($scale < 1) {
			# new width for the image
			$newWidth = floor($scale * $width);
			# new heigth for the image
			$newHeight = floor($scale * $height);
		}else{
		# if the image is small than than the resized width and height
			$newWidth = $width;
			$newHeight = $height;
		}

		$tmp = imagecreatetruecolor($newWidth, $newHeight);
		imagecopyresampled($tmp, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
		return $tmp;
	}
	/**
	 * Display an image fitting into the desired dimension
	 *
	 * @param string $fileName The file name with an absolute web path
	 * @param string $caption The image caption
	 * @param int $imgWidth The actual image width in pixel
	 * @param int $imgHeight The actual image height in pixel
	 * @param int $desiredWidth The desired image width in pixel
	 * @param int $desiredHeight The desired image height in pixel
	 * @param array $attributes The HTML attributes in array like key => value
	 *
	 * @return string The <img> tag
	 */
	public static function img($fileName, $caption, $imgWidth, $imgHeight, $desiredWidth=0, $desiredHeight=0, $attributes=array()){
		if($imgWidth > $desiredWidth || $imgHeight > $desiredHeight){ # scale down
			if($desiredWidth == 0 && $desiredHeight > 0){ # resized to height
				$desiredWidth 	= floor(($imgWidth/$imgHeight) * $desiredHeight);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;
			}
			elseif($desiredWidth > 0 && $desiredHeight == 0){ # resized to width
				$desiredHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
				$imgWidth 		= $desiredWidth;
				$imgHeight 		= $desiredHeight;
			}
			elseif($desiredWidth > 0 && $desiredHeight > 0){ # resized both
				$scale = min($desiredWidth/$imgWidth, $desiredHeight/$imgHeight);
				# new width for the image
				$imgWidth  = floor($scale * $imgWidth);
				# new heigth for the image
				$imgHeight = floor($scale * $imgHeight);
				if($imgWidth < $desiredWidth || $imgHeight < $desiredHeight){
					$wDiff = $desiredWidth - $imgWidth;
					$hDiff = $desiredHeight - $desiredWidth;
					if($wDiff > $hDiff){ # resize to width
						$imgHeight = floor(($imgHeight/$imgWidth) * $desiredWidth);
						$imgWidth  = $desiredWidth;
					}else{ # resize to height
						$imgWidth = floor(($imgWidth/$imgHeight) * $desiredHeight);
						$imgHeight = $desiredHeight;
					}
				}
			}
		}

		$style = '';
		if($imgWidth > $desiredWidth){
			$marginH = floor(($imgWidth - $desiredWidth)/2);
			$style = 'margin-left:-'.$marginH.'px';
		}
		if($imgHeight > $desiredHeight){
			$marginV = floor(($imgHeight - $desiredHeight)/2);
			$style = 'margin-top:-'.$marginV.'px';
		}
		if(isset($attributes['style']) && $attributes['style']){
			$style .= $attributes['style'];
		}
		$attributes['src']		= $fileName;
		$attributes['alt'] 		= _h($caption);
		$attributes['title'] 	= _h($caption);
		$attributes['width'] 	= $imgWidth;
		$attributes['height'] 	= $imgHeight;
		$attributes['style'] 	= $style;

		$attrHTML = '';
		foreach($attributes as $key => $value){
			$attrHTML .= ' ' . $key . '="' . $value .'"';
		}
		return '<img '.$attrHTML.' />';
	}
}

/**
 * @ignore Flag for image resize to the fitted dimension to the given dimension
 */
define('FILE_RESIZE_BOTH', 'both');
/**
 * @ignore Flag for image resize to the given height, but width is aspect ratio of the height
 */
define('FILE_RESIZE_HEIGHT', 'height');
/**
 * @ignore Flag for image resize to the given width, but height is aspect ratio of the width
 */
define('FILE_RESIZE_WIDTH', 'width');
