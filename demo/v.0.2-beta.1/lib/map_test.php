<?php
/**
 *   @file       map_test.php
 *   @brief      Testing map functions
 *   @details    
 *   @example    http://localhost:8083/lib/map_test.php?zoom=10
 *
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T14:42:02 / ErBa
 *   @version    2024-11-13T14:42:02
 */

echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="../config/styles.css">
</head>
<body>
';

include_once( __DIR__.'/map.php');


$cfg	= [
	"exif_map_tag" => "See on Google map",
	"exif_map_link_stub" => "https://maps.google.com/maps?q={\$lat},{\$lon}",
	"exif_map_link_stub" => "https://maps.google.com/maps?q={\$lat},{\$lon}&ll={\$lat},{\$lon}&z={\$zoom}",
	"exif_map_embed_tag" => "Image map",
	"exif_map_embed_stub" => "https://maps.google.com/maps?q={\$lat},{\$lon}&output=embed",
	"exif_map_embed_stub" => "https://maps.google.com/maps?q={\$lat},{\$lon}&ll={\$lat},{\$lon}&z={\$zoom}&output=embed&hl=en",
];

// Giza
// https://www.google.com/maps?q=29.981293,31.133480&spn=0.05,0.05&t=h&om=1&hl=en
// https://www.google.com/maps?q=29.981293,31.133480&spn=0.05,0.05&t=h&om=1&hl=en&z=3
// W zoom
// https://www.google.com/maps?q=29.981293,31.133480&ll=29.981293,31.133480&z=16
// https://www.google.com/maps/place/29.981293,31.133480/@29.981293,31.133480,15z

// Lima
// https://www.google.com/maps?q=-11.9592469,-77.1071692&spn=0.05,0.05&t=h&om=1&hl=en

// https://maps.google.com/?q=38.6531004,-90.243462&ll=38.6531004,-90.243462&z=3

// http://www.google.com/maps/place/29.981293,31.133480/@29.981293,31.133480,7z

$exif = [
  'GPS' => 
  array (
    'GPSVersion' => '' . "\0" . '' . "\0" . '',
    'GPSLatitudeRef' => 'N',
    'GPSLatitude' => 
    array (
      0 => '29/1',
      1 => '588776/10000',
      2 => '0/1',
    ),
    'GPSLongitudeRef' => 'E',
    'GPSLongitude' => 
    array (
      0 => '31/1',
      1 => '80088/10000',
      2 => '0/1',
    ),
    'GPSAltitudeRef' => '' . "\0" . '',
    'GPSAltitude' => '5010/100',
    'GPSTimeStamp' => 
    array (
      0 => '7/1',
      1 => '28/1',
      2 => '50964/1000',
    )
	),
];

$lon = getGps($exif['GPS']["GPSLongitude"], $exif['GPS']['GPSLongitudeRef']);
$lat = getGps($exif['GPS']["GPSLatitude"], $exif['GPS']['GPSLatitudeRef']);
$zoom	= $_REQUEST['zoom'] ?? 15;

echo getMapLink( $lat, $lon, $zoom );
echo " @ $lon,$lat<br>";
echo getMapEmbed( $lat, $lon, $zoom );


//----------------------------------------------------------------------

?>