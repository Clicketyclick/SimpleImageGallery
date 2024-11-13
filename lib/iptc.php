<?php
/**
 *   @file       iptc.php
 *   @brief      Handling IPTC data from image files
 *   @details    
 *   	parseIPTC	Parse IPTC block and remap to human readable
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T13:39:50 / ErBa
 *   @version    2024-11-13T13:39:50
 */


/**
 *   @fn         parseIPTC
 *   @brief      Parse IPTC block and remap to human readable
 *   
 *   @param [in]	$file	Image file
 *   @return     array with IPTC data
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
 *   @since      2024-11-13T13:38:16
 */
function parseIPTC( $file )
{
	// Remap IPTC tags to human readables
	$iptcHeaderArray	= $GLOBALS['metatags']['iptc'];

	$size = getimagesize($file, $info);
	$iptc = iptcparse($info['APP13']);

	foreach ( $iptc as $key => $value)
	{
		// Coded character set ESC % G = UTF-8
		if ( "1#090" == $key )
		{
			//if ( '1b2547' == bin2hex($iptc[$key][0]) )
			if ( "\x1B%G" == $iptc[$key][0] )
				$iptc[$key][0]	.= " UTF-8";
		}
		$iptc[ $iptcHeaderArray[$key]['tag'] ] = $iptc[$key];
		unset($iptc[$key]);
	}
	return( $iptc );
}	// parseIPTC

//----------------------------------------------------------------------

?>