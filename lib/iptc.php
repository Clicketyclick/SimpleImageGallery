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
	if ( empty($info['APP13']) )
		return(FALSE);

	$iptc = iptcparse($info['APP13']);
    if ( empty( $iptc ) )
        return(FALSE);

	foreach ( $iptc as $key => $value)
	{
		// Coded character set ESC % G = UTF-8
		if ( "1#090" == $key )
		{
			//if ( '1b2547' == bin2hex($iptc[$key][0]) )
			if ( "\x1B%G" == $iptc[$key][0] )
				$iptc[$key][0]	.= " UTF-8";
		}
		foreach( $iptc[$key] as $no => $str )
		{
			str_to_utf8( $iptc[$key][$no], $iptc[$key][$no] );
		}
		
		$iptc[ $iptcHeaderArray[$key]['tag'] ] = $iptc[$key];
		unset($iptc[$key]);
	}
	return( $iptc );
}	// parseIPTC

//----------------------------------------------------------------------

	/**
	 *   @fn         str_to_utf8
	 *   @brief      Detects char set and convert any to UTF-8
	 *   
	 *   @param [in]	$fromStr	$(description)
	 *   @param [in]	&$utf8_string	$(description)
	 *   @param [in]	$encoding	$(description)
	 *   @return     TRUE	Converted
	 *   @return     FALSE	Not converted or UTF-8
	 *   
	 *   @details    ['ASCII', 'UTF-8', 'ISO-8859-1']
	 *   
	 *   @example    
	 *   
	 *   @todo       
	 *   @bug        
	 *   @warning    
	 *   
	 *   @see        https://
	 *   @since      2024-11-03T20:35:34
	 */
	function str_to_utf8( $fromStr, &$utf8_string, $encoding = ['ASCII', 'UTF-8', 'ISO-8859-1' ])
	{
		$utf8_string	= $fromStr;
		$charset		= mb_detect_encoding($fromStr, $encoding );
		
		if ( 'UTF-8' != $charset )
		{	// string, to, from
			$utf8_string = mb_convert_encoding($fromStr, 'UTF-8', $charset );
			return( TRUE );
		}
		return( FALSE );
	}	// str_to_utf8()

?>