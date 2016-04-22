# yii2-imagecrop
Crop images with this class is **very simple**!

## Resources
 - Centralized autocrop;
 - Customized cutting area with json;
 - Watermark;

_**Part of this class code was implemented by a [document](https://github.com/fengyuanchen/cropper/tree/master/examples/crop-avatar) made available by [fengyuanchen](https://github.com/fengyuanchen)_.

## How to use
Add the following code in a controller, **the image that will be used for cutting should already be on your server**, that is, the code should be run after the file upload. **The original image is always preserved**.

```ssh
use app\components\germanoricardi\ImageCrop;

public function actionCrop(){
    $imageCrop = new ImageCrop();
    $imageCrop->imageSourcePath	= '@webroot/medias/';
    $imageCrop->imagesSizes		= [
        ['path' => '@webroot/medias/thumbs/', 'width' => '200', 'height' => '200', 'label' => 'Thumbs'],
        ['path' => '@webroot/medias/large/', 'width' => '800', 'height' => '600', 'label' => 'Large', 'watermark' => '@webroot/watermark.png']
    ];
    $imageCrop->imageFileName	= 'image-to-crop.jpg';
    $imageCrop->init();
}
```

## AJAX
If you are working with a jQuery plugin for crop image parameters can be passed to the class via **AJAX** as follows:
```
    $imageCrop = new ImageCrop();
    $imageCrop->imageSourcePath	= '@webroot/medias/';
    $imageCrop->imagesSizes		= [
        ['path' => '@webroot/medias/thumbs/', 'width' => '200', 'height' => '200', 'label' => 'Thumbs'],
        ['path' => '@webroot/medias/large/', 'width' => '800', 'height' => '600', 'label' => 'Large', 'watermark' => '@webroot/watermark.png']
    ];
    $imageCrop->imageFileName	= 'image-to-crop.jpg';
    
    // AJAX
    $imageCrop->data            = '{"x":220,"y":26,"width":1168,"height":1168,"rotate":0,"scaleX":1,"scaleY":1}';
    
    $imageCrop->init();
```
License
----

MIT