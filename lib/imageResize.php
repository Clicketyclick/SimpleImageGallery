<?php
/**
 *   @file       imageResize.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T10:11:41 / ErBa
 *   @version    2024-11-11T10:11:41
 */
 
 

/**
 *   @fn         image_resize
 *   @brief      $(Brief description)
 *   
 *   @param [in]	$src	$(description)
 *   @param [in]	$dst	$(description)
 *   @param [in]	$width	$(description)
 *   @param [in]	$height	$(description)
 *   @param [in]	$crop=0	$(description)
 *   @return     $(Return description)
 *   
 *   @details    $(More details)
 *   
 *   @example    
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://www.php.net/manual/en/function.imagecopyresampled.php#104028
 *   @since      2024-11-11T10:18:57
 */
function image_resize($src, $dst, $width, $height, $crop=0){

  if(!list($w, $h) = getimagesize($src)) return "Unsupported picture type!";

  $type = strtolower(substr(strrchr($src,"."),1));
  if($type == 'jpeg') $type = 'jpg';
  switch($type){
    case 'bmp': $img = imagecreatefromwbmp($src); break;
    case 'gif': $img = imagecreatefromgif($src); break;
    case 'jpg': $img = imagecreatefromjpeg($src); break;
    case 'png': $img = imagecreatefrompng($src); break;
    default : return "Unsupported picture type!";
  }

  // resize
  if($crop){
    if($w < $width or $h < $height) return "Picture is too small!";
    $ratio = max($width/$w, $height/$h);
    $h = $height / $ratio;
    $x = ($w - $width / $ratio) / 2;
    $w = $width / $ratio;
  }
  else{
    if($w < $width and $h < $height) return "Picture is too small!";
    $ratio = min($width/$w, $height/$h);
    $width = $w * $ratio;
    $height = $h * $ratio;
    $x = 0;
  }

  $new = imagecreatetruecolor($width, $height);

  // preserve transparency
  if($type == "gif" or $type == "png"){
    imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
    imagealphablending($new, false);
    imagesavealpha($new, true);
  }

  imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);

  switch($type){
    case 'bmp': imagewbmp($new, $dst); break;
    case 'gif': imagegif($new, $dst); break;
    case 'jpg': imagejpeg($new, $dst); break;
    case 'png': imagepng($new, $dst); break;
  }
  //fopen( "xx.jpg", $new);
  return true;
}	// image_resize()

/*
$pic_type = '.jpg';
$pic_name = '2023-04-11T09-28-50_IMGP0824.JPG';

//if (true !== ($pic_error = @image_resize($pic_name, "100x100$pic_type", 1000, 1000, 1))) {
if (true !== ($pic_error = @image_resize($pic_name, "100x100$pic_type", 1000, 1000))) {
    echo $pic_error;
    unlink($pic_name);
  }
  else echo "OK!";
*/
// https://www.php.net/manual/en/function.imagecopyresampled.php#85929

// 
/*
1 = Horizontal (normal)
2 = Mirror horizontal
3 = Rotate 180
4 = Mirror vertical
5 = Mirror horizontal and rotate 270 CW
6 = Rotate 90 CW
7 = Mirror horizontal and rotate 90 CW
8 = Rotate 270 CW

*/
function rotateImage( $filename, $degrees, $out )
{
//	$rotated_img = imagerotate($src_img, 45, $color)
	// Load
	$source = imagecreatefromjpeg($filename);

	// Rotate
	$rotate = imagerotate($source, $degrees, 0);

	// Output
	imagejpeg($rotate, $out);
}
//----------------------------------------------------------------------

function getResizedImage( $file, $width = 1000, $height = 1000 )
{
	$pathinfo	= pathinfo( $file );
	$view_file	= "view.".$pathinfo['extension'];
	if (true !== ($pic_error = @image_resize($file, $view_file, $width, $height))) 
	{
		echo $pic_error;
		//unlink($pic_name);
		return( FALSE );
	}
	$view = file_get_contents($view_file);
	//unlink($view_file);
	return( $view );
}	//getResizedImage

