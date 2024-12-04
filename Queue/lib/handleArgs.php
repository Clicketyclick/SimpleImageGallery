<?php
/**
 *   @file       handleArgs.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-04T05:50:18 / ErBa
 *   @version    2024-12-04T05:50:18
 */
//

/**
 *  @brief     Parse cli arguments and insert into $_REQUEST
 *  
 *  @details   Store in global variable
 *  
@code
parse_cli2request(); var_export( $_REQUEST );
@endcode
 *  
 *  @see       https://stackoverflow.com/a/37600661
 *  @since     2024-06-25T09:09:12 / erba
 */
function parse_cli2request()
{
    global $argv;
    
    // CLI or HTTP
    // https://stackoverflow.com/a/37600661
    if (php_sapi_name() === 'cli') {
        // Remove '-' and '/' from keys
        for ( $x = 0 ; $x < count($argv) ; $x++ )
            $argv[$x]   = ltrim($argv[$x], '-/');
        // Concatenate and parse string into $_REQUEST
        parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
    }
}   // parse_cli2request()

//----------------------------------------------------------------------

/**
 *   @brief      Encode mixed data for database
 *   
 *   @param [in]	$arr	Mixed data
 *   @return     json with escaped values
 *   
 *   @details    
 *   - json_encode w. JSON_INVALID_UTF8_IGNORE
 *   - SQLite3::escapeString
 *   
 *   @code
 *   $exifjson 	= json_encode_db( $exif );
 *   @endcode
 *   
 *   @since      2024-11-16T10:56:57
 */
function json_encode_db( $arr )
{
	return( SQLite3::escapeString( json_encode( $arr,  JSON_INVALID_UTF8_IGNORE ) ) );
}	// json_encode_db()

//----------------------------------------------------------------------

/**
 *   @brief      Localisation function
 *   
 *   @param [in]	$key	Lookup key for local
 *   @param [in]	$lang='en'	Language code [Default:en]
 *   @return     Translation | [$key][$lang]
 *   
 *   @since      2024-11-13T13:43:14
 */
function ___( $key, $lang = FALSE )
{
    if ( FALSE === $lang )
        $lang   = $GLOBALS['browser']['language'] ?? 'en';
	return( $GLOBALS['local'][$key][$lang] ?? "[$key][$lang]" );
}	// ___()

//----------------------------------------------------------------------


/**
 *   @brief      Detect browser language
 *   
 *   @param [in]	$acceptLang=['fr'	$(description)
 *   @param [in]	'it'	$(description)
 *   @param [in]	'en'	$(description)
 *   @param [in]	'da']	$(description)
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
 *   @see        https://stackoverflow.com/a/3770616
 *   @since      2024-11-20T13:02:55
 */
function getBrowserLanguage( $acceptLang = ['fr', 'it', 'en', 'da'] )
{
    $lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en', 0, 2);

    $lang = in_array($lang, $acceptLang) ? $lang : 'en';
    $GLOBALS['browser']['language']    = $lang;
    //require_once "index_{$lang}.php"; 
}
//----------------------------------------------------------------------
