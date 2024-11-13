<?php
/**
 *   @file       resample.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T10:00:38 / ErBa
 *   @version    2024-11-11T10:00:38
 */
//


// The file
$filename = 'IMG20240128114147.jpg';
$filename = '2023-04-11T09-28-50_IMGP0824.JPG';


resampleImage(

$width = 1200,
$height = 1200
)
// Set a maximum height and width
//$width = 1200;
//$height = 1200;

// Content type
//header('Content-Type: image/jpeg');

// Get new dimensions
list($width_orig, $height_orig) = getimagesize($filename);

$ratio_orig = $width_orig/$height_orig;

if ($width/$height > $ratio_orig) {
   $width = intval($height*$ratio_orig);
} else {
   $height = intval($width/$ratio_orig);
}

// Resample
$image_p = imagecreatetruecolor($width, $height);
$image = imagecreatefromjpeg($filename);
imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

// Output
imagejpeg($image_p, "out.jpg", 100);
//file_put_contents( "out.jpg", imagejpeg($image_p, null, 100) );
//file_put_contents( "out.jpg", $image_p );
?>
