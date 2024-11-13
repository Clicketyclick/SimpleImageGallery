<?php
/**
 *   @file       rotate.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T15:34:31 / ErBa
 *   @version    2024-11-11T15:34:31
 */

$src	= "2023-07-30T13-33-16_IMGP1592.JPG";
$img 	= imagecreatefromjpeg($src);
list($w, $h) = getimagesize($src);
$max	= 500;

$new	= rotsize( $img, $max, $max, $w, $h, 90 );

// Out to file
imagejpeg($new, "out.jpg");


$url = "https://www.php.net/images/logos/new-php-logo.png";
//$url = "https://google.com";


function file_get_contents_curl( $url ) 
{
  $ch = curl_init();
// set URL and other appropriate options
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HEADER, 0);

// grab URL and pass it to the browser
$data	= curl_exec($ch);

// close cURL resource, and free up system resources
curl_close($ch);
  return $data;
}



$result =curl_get($url);
var_export( $result );exit;

$result = file_get_contents_curl( $url );
var_export( $result );exit;
$image = imagecreatefromstring($result);

$str	= stringcreatefromimage( $new );
$next	= imagecreatefromstring( $str );

imagejpeg($next, "next.jpg");






/**

 * Send a POST requst using cURL

 * @param string $url to request

 * @param array $post values to send

 * @param array $options for cURL

 * @return string

 */

function curl_post($url, array $post = NULL, array $options = array())

{

    $defaults = array(

        CURLOPT_POST => 1,

        CURLOPT_HEADER => 0,

        CURLOPT_URL => $url,

        CURLOPT_FRESH_CONNECT => 1,

        CURLOPT_RETURNTRANSFER => 1,

        CURLOPT_FORBID_REUSE => 1,

        CURLOPT_TIMEOUT => 4,

        CURLOPT_POSTFIELDS => http_build_query($post)

    );



    $ch = curl_init();

    curl_setopt_array($ch, ($options + $defaults));

    if( ! $result = curl_exec($ch))

    {

        trigger_error(curl_error($ch));

    }

    curl_close($ch);

    return $result;

}



/**

 * Send a GET requst using cURL

 * @param string $url to request

 * @param array $get values to send

 * @param array $options for cURL

 * @return string

 */

function curl_get($url, array $get = NULL, array $options = array())

{    

    $defaults = array(

        CURLOPT_URL => $url. (strpos($url, '?') === FALSE ? '?' : ''). http_build_query($get),

        CURLOPT_HEADER => 0,

        CURLOPT_RETURNTRANSFER => TRUE,

        CURLOPT_TIMEOUT => 4

    );

    

    $ch = curl_init();

    curl_setopt_array($ch, ($options + $defaults));

    if( ! $result = curl_exec($ch))

    {

        trigger_error(curl_error($ch));

    }

    curl_close($ch);

    return $result;

}



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


/**
 *   @fn         rotsize
 *   @brief      Resample and rotate GdImage
 *   
 *   @param [in]	$img	GdImage image
 *   @param [in]	$width	New max width
 *   @param [in]	$height	New max height
 *   @param [in]	$w		Current width
 *   @param [in]	$h	Current height
 *   @param [in]	$degrees	Rotation
 *   @param [in]	$crop=0	Crop if true
 *   @return     GdImage
 *   
 *   @details    $(More details)
 *   
 *   @example    
 *       $src	= "2023-07-30T13-33-16_IMGP1592.JPG";
 *       $img 	= imagecreatefromjpeg($src);
 *       list($w, $h) = getimagesize($src);
 *       $max	= 500;
 *       // Resize and rotate
 *       $new	= rotsize( $img, $max, $max, $w, $h, 90 );
 *       // Out to file
 *       imagejpeg($new, "out.jpg");
 *       
 *       // Out to string
 *       ob_start();
 *       imagejpeg($new);
 *       $imageString = ob_get_clean();
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-11T16:14:46
 */
function rotsize( $img, $width, $height , $w, $h, $degrees, $crop = 0 )
{
    if($w < $width and $h < $height) return "Picture is too small!";
    $ratio = min($width/$w, $height/$h);
    $width = intval($w * $ratio);
    $height = intval($h * $ratio);
	// Target
	$new	= imagecreatetruecolor($width, $height);
	// Resample                                    dst              source
	imagecopyresampled($new, $img, 0, 0, $crop, 0, $width, $height , $w, $h);
	// Rotate
	if ( $degrees)
		$new = imagerotate($new, $degrees, 0);
	// Return GdImage 
	return($new);
}	// rotsize()

