<?php
/**
 *   @file       map.php
 *   @brief      Links to map and GPS encoding
 *   @details    getMapLink		Build a link to map
 *   getMapEmbed	Build an iframe with map
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T14:42:02 / ErBa
 *   @version    2024-11-13T14:42:02
 */


/**
 *  @fn         getMapLink( $lat, $lon, $zoom, $map_source )
 *  @brief     Build a link to map.
 *  @details   Build link to external map page
 *
 * - Google
 *
 *  @param[in] $lat Lattitude as float
 *  @param[in] $lon Longitude as float
 *  @param[in] $zoom Zoom level
 *  @param [in]	$map_source Source: Google, OpenStreetMap ...
 *
 *  @retval     html link
 *  @since      2024-11-13T16:15:20
 */
function getMapLink( $lat, $lon, $zoom, $map_source )
{
	$lat_margin_lower	= $GLOBALS['config']['maps']['map_window_margin'];
	
	if ( $GLOBALS['config']['maps']['map_types'][$map_source]['tag'] and $GLOBALS['config']['maps']['map_types'][$map_source]['link_stub'])
	{
		if (isset( $lat ) && isset( $lon ) )
		{
			$mstub	= $GLOBALS['config']['maps']['map_types'][$map_source]['link_stub'];
			$mtag	= ___('see_on_map').' '. $GLOBALS['config']['maps']['map_types'][$map_source]['tag'];
			$tmpStr = sprintf("<a target='_blank' href='{$mstub}' title='".___('link_to_external_map')."'>{$mtag}</a>");
			$tmpStr = str_replace( 
				[
					'{$lat}','{$lon}','{$zoom}'
				,	'{$lat_margin_higher}'
				,	'{$lon_margin_higher}'
				,	'{$lat_margin_lower}'
				,	'{$lon_margin_lower}' 
				]
			, 	[
					$lat,$lon,$zoom
				,	$lat + $lat_margin_lower
				,	$lon + $lat_margin_lower
				,	$lat - $lat_margin_lower
				,	$lon - $lat_margin_lower
				]
			,	$tmpStr
			);
		}
		return($tmpStr);
	}
	return(FALSE);
}	// getMapLink()

//----------------------------------------------------------------------
/**
 *   fn         getMapEmbed( $lat, $lon, $zoom, $map_source )
 *   @brief      Build an iframe with map
 *   
 *   @param [in]	$lat	Lattitude as float
 *   @param [in]	$lon	Longitude as float
 *   @param [in]	$zoom	Zoom level
 *   @param [in]	$map_source Source: Google, OpenStreetMap ...
 *   @retval     html iframe
 *   @since      2024-11-13T16:16:37
 */
function getMapEmbed( $lat, $lon, $zoom, $map_source )
{
	$lat_margin	= $GLOBALS['config']['maps']['map_window_margin'];
	//if ($GLOBALS['config']['exif']['exif_map_tag'] and $GLOBALS['config']['exif']['exif_map_link_stub'])
	if ( $GLOBALS['config']['maps']['map_types'][$map_source]['tag'] and $GLOBALS['config']['maps']['map_types'][$map_source]['link_stub'])
	{
		if (isset( $lat ) && isset( $lon ) )
		{
			$mstub	= $GLOBALS['config']['maps']['map_types'][$map_source]['embed_stub'];
			//var_export($mstub);
			$mtag	= $GLOBALS['config']['maps']['map_types'][$map_source]['tag'];

			$tmpStr = sprintf("<iframe class=\"map_iframe\" src=\"{$mstub}\"></iframe>" );

			$tmpStr = str_replace( 
				[
					'{$lat}','{$lon}','{$zoom}'
				,	'{$lat_margin_higher}'
				,	'{$lon_margin_higher}'
				,	'{$lat_margin_lower}'
				,	'{$lon_margin_lower}' 
				]
			, 	[
					$lat
				,	$lon
				,	$zoom
				,	($lat + $lat_margin)
				,	($lon + $lat_margin)
				,	($lat - $lat_margin)
				,	($lon - $lat_margin)
				]
			,	$tmpStr
			);
		}
		return($tmpStr);
	}
	return(FALSE);
}   // getMapEmbed()

//----------------------------------------------------------------------

/**
 *  @fn         getGps($exifCoord, $hemi)
 *   @brief      Convert EXIF GPS data to float
 *   
 *@details       Example:
 *@code
 *       $exif = exif_read_data($filename);
 *       $lon = getGps($exif["GPSLongitude"], $exif['GPSLongitudeRef']);
 *       $lat = getGps($exif["GPSLatitude"], $exif['GPSLatitudeRef']);
 *       var_dump($lat, $lon);
 *@endcode
 *@details       Output:
 @verbatim
	float(-33.8751666667)
	float(151.207166667)
 @endverbatim
 *   @details    Float values are use when calling maps
 *
 *   @param [in] $exifCoord EXIF coordinate
 *   @param [in] $hemi GPS Reference ['N','S','E','W']
 *   @retval     Coordinate as float
 *   
 *   @see        https://stackoverflow.com/a/2572991 gak
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
 *  @fn         gps2Num($coordPart)
 *   @brief      Reformat GPS to float
 *   
 *   @param[in]	$coordPart	Coordinates with '/' parts
 *   @retval	Coordinate as float
 *   
 *   @details    
 *   
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