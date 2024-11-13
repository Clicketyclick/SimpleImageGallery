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
 *   @brief      Resize and rotate image
 *   
 *   @param [in]	$src	Source file
 *   @param [in]	$dst	Image stream
 *   @param [in]	$width	New max width
 *   @param [in]	$height	New max height
 *   @param [in]	$crop=0	Cropping FALSE/TRUE
 *   @return     New image stream
 *   
 *   @details    
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
function image_resize($src, $dst, $width, $height, $orientation, $crop=0){

	if(!list($w, $h) = getimagesize($src))
	{
		//return "Unsupported picture type!";
		trigger_error( "Unsupported picture type!", E_USER_WARNING );
		return( FALSE );
	}	  

	$type = strtolower(substr(strrchr($src,"."),1));
	if($type == 'jpeg') $type = 'jpg';
	
	switch($type)
	{
		case 'bmp': $img = imagecreatefromwbmp($src); break;
		case 'gif': $img = imagecreatefromgif($src); break;
		case 'jpg': $img = imagecreatefromjpeg($src); break;
		case 'png': $img = imagecreatefrompng($src); break;
		default : 
			//return "Unsupported picture type!";
			trigger_error( "Unsupported picture type: [$type]", E_USER_WARNING );
			return( FALSE );
	}

	// resize
	if($crop)
	{
		if($w < $width or $h < $height) 
		{
			//return "Picture is too small!";
			trigger_error( "Picture is too small: $w < $width or $h < $height", E_USER_WARNING );
			return( FALSE );
		}
		$ratio = max($width/$w, $height/$h);
		$h	= intval($height / $ratio);
		$x	= intval( ($w - $width / $ratio) / 2 );
		$w	= intval($width / $ratio);
	}
	else
	{
		if($w < $width and $h < $height) 
		{
			//return "Picture is too small!";
			trigger_error( "Picture is too small: $w < $width or $h < $height", E_USER_WARNING );
			return( FALSE );
		}

		$ratio	= min($width/$w, $height/$h);
		$width	= intval($w * $ratio);
		$height = intval($h * $ratio);
		$x		= 0;
	}

	$new = imagecreatetruecolor($width, $height);

	// preserve transparency
	if($type == "gif" or $type == "png")
	{
		imagecolortransparent($new, imagecolorallocatealpha($new, 0, 0, 0, 127));
		imagealphablending($new, false);
		imagesavealpha($new, true);
	}

	imagecopyresampled($new, $img, 0, 0, $x, 0, $width, $height, $w, $h);


	// Rotate
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
	$new	= gdReorientateByOrientation( $new, $orientation, $src );
/*
	switch($orientation)
	{
		case 3:
			$degrees	= 180;
			break;
		case 6:
			$degrees	= 270;
			break;
		case 8:
			$degrees	= 90;
			break;
		default:
			$degrees	= 0;
	}
	if ( $degrees )
	{
		 imagerotate($new, 90, 0);
		//trigger_error( "Rotating $orientation / $degrees", E_USER_NOTICE );
		debug( "Rotating $orientation / $degrees [$src]" );
	}
*/	
	ob_start();
	
	switch($type)
	{
		case 'bmp': imagewbmp($new); break;
		case 'gif': imagegif($new); break;
		case 'jpg': 
			imagejpeg($new);
			break;
		case 'png': imagepng($new); break;
		default : 
			//return "Unsupported picture type!";
			trigger_error( "Unsupported picture type: [$type]", E_USER_WARNING );
			return( FALSE );
	}
	$dst = ob_get_contents();
	ob_get_clean();
  
	return( $dst );
}	// image_resize()


/**
 *   @fn         stringcreatefromimage
 *   @brief      Create a string from image stream
 *   
 *   @param [in]	&$new	$(description)
 *   @param [in]	$type='jpg'	$(description)
 *   @return     image string
 *   
 *   @details    The reverse of: imagecreatefromstring — Create a new image from the image stream in the string
 *   
 *   @example    
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-11T16:43:02
 */
function stringcreatefromimage( &$new, $type = 'jpg')
{
	$type = strtolower($type);
	if($type == 'jpeg') $type = 'jpg';
	ob_start();
	switch($type)
	{
		case 'bmp': imagewbmp($new);	break;
		case 'gif': imagegif($new);		break;
		case 'jpg': imagejpeg($new);	break;
		case 'png': imagepng($new);		break;
		default : 	return "Unsupported image type!";
	}
	imagejpeg($new);
	return( ob_get_clean() );
}	// stringcreatefromimage


function gdReorientateByOrientation( $gdImage, $orientation, $note = '' )
{
	switch($orientation)
	{
		case 3:
			$degrees	= 180;
			break;
		case 6:
			$degrees	= 270;
			break;
		case 8:
			$degrees	= 90;
			break;
		default:
			$degrees	= 0;
	}
	if ( $degrees )
	{
		$gdImage	= imagerotate($gdImage, $degrees, 0);
		debug( "Rotating $orientation / $degrees $note" );
	}

	return( $gdImage );
}
//----------------------------------------------------------------------

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

/**
 *   @fn         stringcreatefromimage
 *   @brief      Create a string from image stream
 *   
 *   @param [in]	&$new	$(description)
 *   @param [in]	$type='jpg'	$(description)
 *   @return     image string
 *   
 *   @details    The reverse of: imagecreatefromstring — Create a new image from the image stream in the string
 *   
 *   @example    
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-11T16:43:02
 */
/*
function stringcreatefromimage( &$new, $type = 'jpg')
{
	$type = strtolower($type);
	if($type == 'jpeg') $type = 'jpg';
	ob_start();
	switch($type){
    case 'bmp': imagewbmp($new); break;
    case 'gif': imagegif($new); break;
    case 'jpg': imagejpeg($new); break;
    case 'png': imagepng($new); break;
	default : return "Unsupported image type!";
	}
	imagejpeg($new);
	return( ob_get_clean() );
}	// stringcreatefromimage
*/
//----------------------------------------------------------------------

?>