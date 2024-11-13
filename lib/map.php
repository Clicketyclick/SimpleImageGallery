<?php
/**
 *   @file       map.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T14:42:02 / ErBa
 *   @version    2024-11-13T14:42:02
 */


/**
 *   @fn         getMapLink
 *   @brief      Build a link to map
 *   
 *   @param [in]	$lat	Lattitude as float
 *   @param [in]	$lon	Longitude as float
 *   @param [in]	$zoom	Zoom level
 *   @return     html link
 *   
 *   @details    
 *   
 *   @example    
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-13T16:15:20
 */
function getMapLink( $lat, $lon, $zoom )
{
	global $cfg;
	if ($cfg['exif']['exif_map_tag'] and $cfg['exif']['exif_map_link_stub'])
	{
		if (isset( $lat ) && isset( $lon ) )
		{
			$tmpStr = sprintf("<a target='_blank' href='{$cfg['exif']['exif_map_link_stub']}'>{$cfg['exif']['exif_map_tag']}</a>");
			$tmpStr = str_replace( ['{$lat}','{$lon}','{$zoom}'], [$lat,$lon,$zoom], $tmpStr);
		}
		return($tmpStr);
	}
	return(FALSE);
}	// getMapLink()

//----------------------------------------------------------------------
/**
 *   @fn         getMapEmbed
 *   @brief      Build an iframe with map
 *   
 *   @param [in]	$lat	Lattitude as float
 *   @param [in]	$lon	Longitude as float
 *   @param [in]	$zoom	Zoom level
 *   @return     html iframe
 *   
 *   @details    $(More details)
 *   
 *   @example    
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-13T16:16:37
 */

function getMapEmbed( $lat, $lon, $zoom )
{
	global $cfg;
	if ($cfg['exif']['exif_map_tag'] and $cfg['exif']['exif_map_link_stub'])
	{
		if (isset( $lat ) && isset( $lon ) )
		{
			$tmpStr = sprintf("<iframe class=\"map_iframe\" src=\"{$cfg['exif']['exif_map_embed_stub']}\"></iframe>" );
			$tmpStr = str_replace( ['{$lat}','{$lon}','{$zoom}'], [$lat,$lon,$zoom], $tmpStr);
		}
		return($tmpStr);
	}
	return(FALSE);
}

//----------------------------------------------------------------------

/**
 *   @fn         getGps
 *   @brief      $(Brief description)
 *   
 *   @param [in]	$exifCoord	$(description)
 *   @param [in]	$hemi	$(description)
 *   @return     $(Return description)
 *   
 *   @details    $(More details)
 *   
 *   @example    
 *       $exif = exif_read_data($filename);
 *       $lon = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
 *       $lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
 *       var_dump($lat, $lon);
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://stackoverflow.com/a/2572991
 *   @since      2024-11-13T15:27:47
 */
function getGps($exifCoord, $hemi)
{
    $degrees = count($exifCoord) > 0 ? gps2Num($exifCoord[0]) : 0;
    $minutes = count($exifCoord) > 1 ? gps2Num($exifCoord[1]) : 0;
    $seconds = count($exifCoord) > 2 ? gps2Num($exifCoord[2]) : 0;

    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);
}	// getGps()

//----------------------------------------------------------------------	

/**
 *   @fn         gps2Num
 *   @brief      $(Brief description)
 *   
 *   @param [in]	$coordPart	$(description)
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
 *   @see        https://
 *   @since      2024-11-13T15:28:40
 */
function gps2Num($coordPart) 
{
    $parts = explode('/', $coordPart);

    if (count($parts) <= 0)
        return 0;

    if (count($parts) == 1)
        return $parts[0];

    return floatval($parts[0]) / floatval($parts[1]);
}	// gps2Num()

//----------------------------------------------------------------------

?>