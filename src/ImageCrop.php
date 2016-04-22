<?php

/**
* Resize and crop one or many images
* Suported types: gif, jpeg and png
* @author Germano Ricardi <germanoricardi7@gmail.com>
* @version 1.0
*/

namespace germanoricardi\imagecrop;

use Yii;
use yii\base\Exception;
use yii\helpers\FileHelper;

class ImageCrop
{
	/**
	* @var string full path and file
	* @example @webroot/upload
	*/
	public $imageSourcePath;

	/**
	* @var array with path and sizes: ['fullPath' => [width, height]]
	* @example ['/var/www/upload/thumb' => [150, 100], ['/var/www/upload/medium' => [320, 240]]]
	*/
	public $imagesSizes;

    public $data;
    
    /**
    * @var boolean|string to generate watermark. Default is false
    */
    public $watermark = false;

	/**
	* @var string image file name
	* @example picture.jpg
	*/
	public $imageFileName;

	/**
	* @var resource image type
	*/
	private $imageType;

	public function init()
	{
		if(!is_array($this->imagesSizes))
			throw new Exception('"imagesSizes" param is not array');

		$this->imageType();

		$this->resize();
	}

	/**
	* Check is image
	* @return image attributes when true
	*/
	public function isImage($path)
	{
		$fileInfo = getimagesize($path);
		return $fileInfo ? $fileInfo : false;
	}

	/**
	* Check is dir
	* Try to create if not exists
	*/
	public function isOrCreateDir($path)
	{
        $_path = Yii::getAlias('@webroot' . str_replace('@webroot', null, $path));

		if(!is_dir($_path))
			try {
				FileHelper::createDirectory($path);
			} catch (Exception $e) {
				throw new Exception('Please create "' . $path . '" path with write permission.');
			}

		return $_path;
	}

	/**
	* @return get and create image from type
	* @throws exception if image format not in gif, jpeg or png
	*/
	private function imageType($_imagePath = null)
	{
        $imagePath = isset($_imagePath) ? $_imagePath : $this->isOrCreateDir($this->imageSourcePath) . $this->imageFileName;
		// $imagePath = 
		$type = exif_imagetype($imagePath);
        switch ($type) {
            case IMAGETYPE_GIF:
	            $this->imageType = imagecreatefromgif($imagePath);
	            break;

        	case IMAGETYPE_JPEG:
	            $this->imageType = imagecreatefromjpeg($imagePath);
	            break;

            case IMAGETYPE_PNG:
	            $this->imageType = imagecreatefrompng($imagePath);
	            break;
        }

        if(!$this->imageType){
        	throw new Exception($imagePath . '" is not gif, jpeg or png.');
        }

        return $this->imageType;
	}

	/**
	* @return object with original and new values for width, height, x, y 
	*/
	private function cropCenter()
	{
		foreach ($this->imagesSizes as $key => $_image) {
            $image = (object) $_image;

			$_path = $this->isOrCreateDir($image->path);

			$boxWidth	= $image->width;
			$boxHeight	= $image->height;

            $originalImageDimension 	= $this->isImage($this->isOrCreateDir($this->imageSourcePath) . $this->imageFileName);
            $originalImageDimensionW	= $originalImageDimension[0];
            $originalImageDimensionH	= $originalImageDimension[1];

            if($originalImageDimensionW > $originalImageDimensionH){                
                $finalImageHeight	= $originalImageDimensionH;
                $finalImageWidth	= round(($finalImageHeight / $boxHeight) * $boxWidth);
                $x = ($originalImageDimensionW - $finalImageWidth) / 2;
                
                if($finalImageWidth > $originalImageDimensionW){
                    $finalImageWidth	= $originalImageDimensionW;
                    $finalImageHeight	= round(($finalImageWidth / $boxWidth) * $boxHeight);
                    $x = 0;
                    $y = ($originalImageDimensionH - $finalImageHeight) / 2;
                }
            }else{                
                $finalImageWidth	= $originalImageDimensionW;
                $finalImageHeight	= round(($finalImageWidth / $boxWidth) * $boxHeight);
                $y = ($originalImageDimensionH - $finalImageHeight) / 2;

                if($finalImageHeight > $originalImageDimensionH){
                    $finalImageHeight	= $originalImageDimensionH;
                    $finalImageWidth	= round(($finalImageHeight / $boxHeight) * $boxWidth);
                    $x = ($originalImageDimensionW - $finalImageWidth) / 2;
                    $y = 0;
                }
            }

            $data = [
 				'dst_image' => $_path . $this->imageFileName,
 				'type' => $this->imageType,
 				'dst_x' => 0,
 				'dst_y' => 0,
 				'src_x' => $x,
 				'src_y' => $y,
 				'dst_w' => $boxWidth,
 				'dst_h' => $boxHeight,
 				'src_w' => $finalImageWidth,
 				'src_h' => $finalImageHeight
            ];


            $this->watermark = isset($image->watermark) ? $image->watermark : false;

            $this->crop($data);
        }
	}

	private function cropByData()
	{
		$data = (object) json_decode($this->data);
		
        $size = $this->isImage($this->isOrCreateDir($this->imageSourcePath) . $this->imageFileName);
        $size_w = $size[0]; // natural width
        $size_h = $size[1]; // natural height

        $src_img_w = $size_w;
        $src_img_h = $size_h;
        $finalImage	= $this->isOrCreateDir(array_keys($this->imagesSizes)[0]) . $this->imageFileName;
        $finalSizes	= (object) array_values($this->imagesSizes)[0];

        $degrees = $data -> rotate;

        $src_img = $this->imageType;
        
        // flip scaleX
        if($data->scaleX == -1)
        	imageflip($src_img, IMG_FLIP_HORIZONTAL);

        // flip scaleY
        if($data->scaleY == -1)
        	imageflip($src_img, IMG_FLIP_VERTICAL);

        // Rotate the source image
        if (is_numeric($degrees) && $degrees != 0) {
            // PHP's degrees is opposite to CSS's degrees
            $new_img = imagerotate( $src_img, -$degrees, imagecolorallocatealpha($src_img, 0, 0, 0, 127) );

            imagedestroy($src_img);
            $src_img = $new_img;

            $deg = abs($degrees) % 180;
            $arc = ($deg > 90 ? (180 - $deg) : $deg) * M_PI / 180;

            $src_img_w = $size_w * cos($arc) + $size_h * sin($arc);
            $src_img_h = $size_w * sin($arc) + $size_h * cos($arc);

            // Fix rotated image miss 1px issue when degrees < 0
            $src_img_w -= 1;
            $src_img_h -= 1;
        }

        $tmp_img_w = $data -> width;
        $tmp_img_h = $data -> height;
        $dst_img_w = $finalSizes->width;
        $dst_img_h = $finalSizes->height;

        $src_x = $data -> x;
        $src_y = $data -> y;

        if ($src_x <= -$tmp_img_w || $src_x > $src_img_w) {
            $src_x = $src_w = $dst_x = $dst_w = 0;
        } else if ($src_x <= 0) {
            $dst_x = -$src_x;
            $src_x = 0;
            $src_w = $dst_w = min($src_img_w, $tmp_img_w + $src_x);
        } else if ($src_x <= $src_img_w) {
            $dst_x = 0;
            $src_w = $dst_w = min($tmp_img_w, $src_img_w - $src_x);
        }

        if ($src_w <= 0 || $src_y <= -$tmp_img_h || $src_y > $src_img_h) {
            $src_y = $src_h = $dst_y = $dst_h = 0;
        } else if ($src_y <= 0) {
            $dst_y = -$src_y;
            $src_y = 0;
            $src_h = $dst_h = min($src_img_h, $tmp_img_h + $src_y);
        } else if ($src_y <= $src_img_h) {
            $dst_y = 0;
            $src_h = $dst_h = min($tmp_img_h, $src_img_h - $src_y);
        }

        // Scale to destination position and size
        $ratio = $tmp_img_w / $dst_img_w;
        $dst_x /= $ratio;
        $dst_y /= $ratio;
        $dst_w /= $ratio;
        $dst_h /= $ratio;

        $data = [
			'dst_image' => $finalImage,
			'type' => $src_img,
			'dst_x' => $dst_x,
			'dst_y' => $dst_y,
			'src_x' => $src_x,
			'src_y' => $src_y,
			'dst_w' => $dst_w,
			'dst_h' => $dst_h,
			'src_w' => $src_w,
			'src_h' => $src_h
        ];

        $this->crop($data);
	}

	private function crop($_cropData){
		$cropData = (object) $_cropData;

		$newImage = imagecreatetruecolor($cropData->dst_w, $cropData->dst_h);
        // Add transparent background to destination image
        imagefill($newImage, 0, 0, imagecolorallocatealpha($newImage, 0, 0, 0, 127));
        imagesavealpha($newImage, true);
        imagecopyresampled($newImage, $cropData->type, $cropData->dst_x, $cropData->dst_y, $cropData->src_x, $cropData->src_y, $cropData->dst_w, $cropData->dst_h, $cropData->src_w, $cropData->src_h);

        try {
            imagepng($newImage, $cropData->dst_image);
            if($this->watermark)
                $this->watermark($cropData->dst_image);
        } catch (Exception $e) {
            throw new Exception('Error saving final image "' . $cropData->dst_image);               
        }

        imagedestroy($newImage);
    }

    private function watermark($dstImage)
    {
        // Load the stamp and the photo to apply the watermark to
        $stamp = imagecreatefrompng(Yii::getAlias($this->watermark));
        $im = $this->imageType($dstImage);

        // Set the margins for the stamp and get the height/width of the stamp image
        $marge_right = 0;
        $marge_bottom = 10;
        $sx = imagesx($stamp);
        $sy = imagesy($stamp);

        // Copy the stamp image onto our photo using the margin offsets and the photo 
        // width to calculate positioning of the stamp. 
        imagecopy($im, $stamp, imagesx($im) - $sx - $marge_right, imagesy($im) - $sy - $marge_bottom, 0, 0, imagesx($stamp), imagesy($stamp));

        imagepng($im, $dstImage);
        imagedestroy($im);
    }

	private function resize()
	{
		$imageInfo = !is_null($this->data) ? (object) $this->cropByData() : (object) $this->cropCenter();
	}
}