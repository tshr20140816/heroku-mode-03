<?php

$md5 = md5($_SERVER['QUERY_STRING']);
  
error_log("{$md5} START");

const width = 200;
$width = width;
//$image_file = "https://license.carp.co.jp/assets/upload/goods/37af278351baabcc33cc5e0a7f9cfc11.jpg";
$image_file = $_GET['url'];

error_log("{$md5} {$image_file}");

list($w, $h) = getimagesize($image_file);

error_log("{$md5} w : {$w}, h : {$h}");

if ($w < $width)
{
  $width = $w;
  $height = $h;
}
else
{
  $height = $width * $h / $w;
}

$file_type = strtolower(end(explode('.', $image_file)));
if ($file_type === 'jpg' || $file_type === 'jpeg')
{
  $original_image = ImageCreateFromJPEG($image_file);
  $new_image = ImageCreateTrueColor($width, $height);
}
elseif ($file_type === 'png')
{
  $original_image = ImageCreateFromPNG($image_file);
  $new_image = ImageCreateTrueColor($width, $height);
  imagealphablending($new_image, false);
  imagesavealpha($new_image, true);
}

ImageCopyResampled($new_image, $original_image, 0, 0, 0, 0, $width, $height, $w, $h);

if ($file_type === 'jpg' || $file_type === 'jpeg')
{
  header('Content-Type: image/jpeg');
  ImageJPEG($new_image);
}
elseif ($file_type === 'png')
{
  header('Content-Type: image/png');
  ImagePNG($new_image);
}

imagedestroy($new_image);
imagedestroy($original_image);

error_log("{$md5} FINISH");
?>
