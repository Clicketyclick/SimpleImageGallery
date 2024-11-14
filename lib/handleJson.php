<?php
/**
 *  @file      handleJson.php
 *  @brief     Brief description
 *  
 *  @details   isJson()			- Test if string is a valid JSON
 *  @details   file_get_json	- Reads entire JSON file into a struct
 *  @details   file_put_json	- Write a structure to a JSON file
 *  @details   json_validate	- Full program to check the exact JSON ERROR
 *  @details   array_diff_assoc_recursive	- Computes the difference of arrays - recursive
 *  @details   ksort_recursive	- Sort an array by key in ascending order recursive
 *  @details   xml2json			- Convert XML structure to JSON
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2023-06-16T07:28:22 / Bruger
 *  @version   2024-11-04T13:10:19
 */

require_once( __DIR__ . "/datestamp.php");
require_once( __DIR__ . "/debug.php");
//require_once( __DIR__ . "/Test-More/Test-More.php");

// Flags for decoding JSON
define("JSON_DECODE_FLAGS", JSON_OBJECT_AS_ARRAY 
|   JSON_BIGINT_AS_STRING
|   JSON_NUMERIC_CHECK
|   JSON_INVALID_UTF8_SUBSTITUTE
|   JSON_THROW_ON_ERROR 
);

// Flags for incoding JSON
define("JSON_ENCODE_FLAGS", JSON_INVALID_UTF8_SUBSTITUTE
|	JSON_BIGINT_AS_STRING
|   JSON_PRETTY_PRINT
|   JSON_THROW_ON_ERROR
);


//---------------------------------------------------------------------

/**
 *	isJson
 *  @brief      Test if string is a valid JSON
 *  
 *  @param [in] $string JSON string
 *  @return     TRUE if JSON, else FALSE
 *  
 *  @details    Simple match with boolean result
 *  
 *  @see       https://stackoverflow.com/a/6041773/7485823
 *  @since     2023-06-16T07:28:27 / Bruger
 */
function isJson($string) {
   json_decode($string);
   return json_last_error() === JSON_ERROR_NONE;
}	// isJson()

//---------------------------------------------------------------------

/**
 *  @brief     Reads entire JSON file into a struct
 *  
 *  @param [in] $file Description for $file
 *  @return    Return description
 *  
 *  @details   Similar to file_get_contents() but decodes the JSON
 *  
 *  @see       https://
 *  @since     2023-03-08T11:19:43 / Bruger
 */
function file_get_json( $file ) {
    debug( "Read JSON data: $file");
    $start      = microtime(true);
    debug( "start: $start\n");

    if (! file_exists( $file ))
    {
        trigger_error( "File not found: [$file]", E_USER_WARNING );
        return;
    }

    $mix        = json_decode( file_get_contents( $file ), TRUE, 512, JSON_DECODE_FLAGS );
    if ( ! $mix ) var_error("JSON not read" );
    $end        = microtime(true);
    debug( "end: $end\n");
    $duration    = $end - $start;
    debug( "Duration: ". microtime2human( $end - $start ) );

    if ( $verbose ?? false ) trigger_error( "Data read", E_USER_NOTICE );
    //if ( $debug ?? false ) print_r( $local );
    if ( $verbose ?? false ) var_dump( $mix );
    return( $mix );
}   // file_get_json()

//---------------------------------------------------------------------

/**
 *  @brief     Write a structure to a JSON file
 *  
 *  @param [in] $file   File to write to
 *  @param [in] $data   Struct to write
 *  @return    Return description
 *  
 *  @details   Similar to file_put_contents() but encodes the JSON
 *  
 *  @since     2023-03-08T11:19:43 / Bruger
 */
function file_put_json( $file, $data ) {
    debug( "Writting JSON data: $file");
    $start      = microtime(true);
    debug( "start: $start\n");
    $mix        = file_put_contents( $file, json_encode( $data, JSON_ENCODE_FLAGS, 512 ) );
    $end        = microtime(true);
    debug( "end: $end\n");
    $duration   = $end - $start;
    debug( "Duration: ". microtime2human( $end - $start ) );

    if ( $verbose ?? false ) trigger_error( "Data written", E_USER_NOTICE );
    //if ( $debug ?? false ) print_r( $local );
    if ( $verbose ?? false ) var_dump( $mix );

    return( $mix );
}   // file_put_json()

//---------------------------------------------------------------------

if (PHP_VERSION_ID < 80300) { // Internal command PHP v. 8.3+
/**
 *              json_validate
 *  @brief      Full program to check the exact JSON ERROR
 *  
 *  @param [in] $string JSON string
 *  @return     Return description
 *  
 *  @details    
 *      Testing with Valid JSON INPUT
 *@code      
 *      	$json = '[{"user_id":13,"username":"stack"},{"user_id":14,"username":"over"}]';
 *      	$output = json_validate($json);
 *      	print_r($output);
 *@endcode
 *      Valid OUTPUT
@verbatim
 *      	Array
 *      	(
 *      		[0] => stdClass Object
 *      			(
 *      				[user_id] => 13
 *      				[username] => stack
 *      			)
 *      
 *      		[1] => stdClass Object
 *      			(
 *      				[user_id] => 14
 *      				[username] => over
 *      			)
 *      	)
@endverbatim
 *  @details    
 *      Testing with invalid JSON
 *@code      
 *      	$json = '{background-color:yellow;color:#000;padding:10px;width:650px;}';
 *      	$output = json_validate($json);
 *      	print_r($output);
 *@endcode
 *  @details    
 *      	Invalid OUTPUT
 *      
 *      	Syntax error, malformed JSON.
 *      
 *      Extra note for (PHP >= 5.2 && PHP < 5.3.0)
 *      
 *      Since json_last_error is not supported in PHP 5.2, you can check if the encoding or decoding returns boolean FALSE. Here is an example
 *@code     
 *      	// decode the JSON data
 *      	$result = json_decode($json);
 *      	if ($result === FALSE) {
 *      		// JSON is invalid
 *      	}
 *@endcode
 *  @author		Madan Sapkota https://stackoverflow.com/users/782535/madan-sapkota
 *  @see       	https://stackoverflow.com/a/15198925/7485823
 *  @since     	2023-06-15T07:27:02 / Bruger
 */
function json_validate($string, $associative = TRUE, $max_depth	= 512)
{
    // decode the JSON data
    $result = json_decode($string);
    //$result = json_decode($string, TRUE, 512, JSON_THROW_ON_ERROR);
    $result = json_decode($string, $associative, $max_depth);

    // switch and check possible JSON errors
    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            $error = FALSE; // No error detected (JSON is valid // No error has occurred)
            break;
        case JSON_ERROR_DEPTH:
            //$error = 'The maximum stack depth [$max_depth] has been exceeded.';
            $error = json_last_error_msg() . " [$max_depth].";
            break;
/*
        case JSON_ERROR_STATE_MISMATCH:
            $error = 'Invalid or malformed JSON.';
            break;
        case JSON_ERROR_CTRL_CHAR:
            $error = 'Control character error, possibly incorrectly encoded.';
            break;
        case JSON_ERROR_SYNTAX:
            $error = 'Syntax error, malformed JSON.';
            break;
        // PHP >= 5.3.3
        case JSON_ERROR_UTF8:
            $error = 'Malformed UTF-8 characters, possibly incorrectly encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_RECURSION:
            $error = 'One or more recursive references in the value to be encoded.';
            break;
        // PHP >= 5.5.0
        case JSON_ERROR_INF_OR_NAN:
            $error = 'One or more NAN or INF values in the value to be encoded.';
            break;
        case JSON_ERROR_UNSUPPORTED_TYPE:
            $error = 'A value of a type that cannot be encoded was given.';
            break;
*/
        default:
            //$error = 'Unknown JSON error occured.';
            $error = json_last_error_msg();
            break;
    }
/*	
	if ( ! $error )
	{
		$json_new	= json_encode( $result );
		$string		= preg_replace( '/\s*\r\n\s* /', '', $string );
		$string		= preg_replace( '/"\s*:\s*"/', '":"', $string );
		if( 0 != strcmp( $string, $json_new) ) {
				$error	.= "Not matching. Doublet keys??"; // Doublet keys??
		}
		
		/* * /
		//print_r($result);
		print_r($string);
		file_put_contents("string-in.txt", $string);
		print "\n--\n";
		print_r($json_new);
		file_put_contents("string-out.txt", $json_new);
		print "\n--\n";
		/ * * /
		is_deeply ( $string, $json_new, "test") ;
	}
*/
	return( $error );
    if ($error !== '') {
        // throw the Exception or exit // or whatever :)
        exit($error);
    }

    // everything is OK
    return $result;
}	// json_validate()
}
//---------------------------------------------------------------------


/**
 *              array_diff_assoc_recursive
 *  @brief      Computes the difference of arrays - recursive
 *  
 *  @param [in] $aArray1 	Primary array
 *  @param [in] $aArray2 	Secondary array
 *  @return    array with diff values
 *  
 *  @details   Returns an array containing all the entries from array 
 *  	that are not present in any of the other arrays. 
 *		Keys in the array array are preserved.
 *  
 *  @note   Original: arrayRecursiveDiff()
 *  
 *  @see       https://www.php.net/manual/en/function.array-diff.php#91756
 *  @since     2023-08-29T17:12:47 / erba
 */
function array_diff_assoc_recursive($aArray1, $aArray2) { 
    $aReturn = array(); 

    foreach ($aArray1 as $mKey => $mValue) { 
        if (array_key_exists($mKey, $aArray2)) { 
            if (is_array($mValue)) { 
                $aRecursiveDiff = array_diff_assoc_recursive($mValue, $aArray2[$mKey]); 
                if (count($aRecursiveDiff)) { $aReturn[$mKey] = $aRecursiveDiff; } 
            } else { 
                if ($mValue != $aArray2[$mKey]) { 
                    $aReturn[$mKey] = $mValue; 
                } 
            } 
        } else { 
            $aReturn[$mKey] = $mValue; 
        } 
    } 

    return $aReturn; 
}   // array_diff_assoc_recursive()

//---------------------------------------------------------------------

 /**
 *  fn        ksort_recursive
 *  @brief      Sort an array by key in ascending order recursive
 *  
 *  @param [in] $array 	Associative array to be sorted
 *  @return    VOID
 *  
 *  @details   Implements ksort recursive
 *  
 *@code
 *		ksort_recursive($data);print_r($data);
 *@endcode  
 *  
 *  @see       https://stackoverflow.com/a/15669150 PHP sort array alphabetically
 *  @since     2013-03-27T20:49:00 / [Baba](https://stackoverflow.com/users/1226894/baba)
 */
 function ksort_recursive(&$array) {
    ksort($array);
    foreach ( $array as &$a ) {
        is_array($a) && ksort_recursive($a);
    }
}   // ksort_recursive()

//---------------------------------------------------------------------


/**
 *   fn         xml2json
 *   @brief      Convert XML structure to JSON
 *   
 *   @param [in]	$xml	XML structure
 *   @param [in]	$struct	Return JSON string or PHP data structure
 *   @return     TRUE:	PHP data structure
 *               FALSE:	JSON string
 *   
 *   @see        https://sergheipogor.medium.com/convert-xml-to-json-like-a-pro-in-php-603d0a3351f1
 *   @since      2024-11-04T13:03:56
 */
function xml2json( $xml, $struct = FALSE )
{
	$xml 	= simplexml_load_string($xmlString);
	$json	= json_encode($xml, JSON_PRETTY_PRINT);
	if ( $struct )
	{
		$data	= json_decode($json, true);
		return( $data );
	}
	return( $json );
}

//---------------------------------------------------------------------

?>