<?php
/**
 * @file        handleStrings.php
 * @brief       String handling rutines
 *
 * @details
 * Function|brief
 * ---|---
 * getBetween                   | Extract substring between start pattern and end pattern
 * bes_html                     | Converting special BES charaters to HTML entries. Decode BESMARC double a
 * getBetweens                  | Extract every substring between start and end pattern
 * dm2_html                     | Encoding danMARC2 special characters to HTML entities
 * utf2latin                    | Encode UTF-8 -> latin1
 * latin2utf                    | Encode latin1 -> UTF-8
 * codeStr                      | Encode latin1 -> UTF-8 -> HTML. Special characters only!
 * controlCharacterReplacement  | Replace Control Characters from string
 * escapeBlanksInStrings        | Escaping blanks, parenteses and quotes in CCL query string
 * strhex                       | String to string of Hex values
 * hexstr                       | String of Hex values to string
 * remove_utf8_bom              | Remove UTF-8 BOM prefix from string
 * expandUnicode2Html           | Expanding Danbib encoded Unicode characters to HTML
 * superSubScript               | Expand superscript and subscript in string
 * get_doc                      | Read Markdown file and parse to HTML
 * getDetailSummary             | Build details/summary block in HTML
 * getNoOfDigits                | Return the no of digits in a number
 * getDoxygenFileHeader         | Extract Doxygen file header from file
 * number_formatted             | Format a number with grouped thousands and decimal separator
 * stringExpand                 | Expand simple variables inside a string
 * expandLocal                  | Expand localisation from global $__local;
 * platformSlashes              | Set slashes in path according to OS
 * expandGlobalVarsRef          | Expand complex variables in string
 * truncate_center_word         | Zap the middle of a string
 *
 * This file MUST be encoded in UTF-8 w/o BOM
 *
 * All headers, diagnose and test results goes to STDERR
 * Notes goes to STDOUT
 *
 * @copyright   http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 * @author      Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 * @since       2018-07-30 10:51:00
 * @version     2024-12-04T08:16:40
 */

/**
 *  @fn         linkTo( $keys )
 *  @brief     Create link from template
 *  
 *  @param [in] $keys 	Description for $keys
 *  @retval    Return description
 *  
 *  @since     2022-11-23T11:10:59 / erba
 */
function linkTo( $keys )
{
    $template   = $GLOBALS['cfg']['display']['linkto_template'];

    foreach( $keys as $key => $value ){
        $template=str_replace( '${'.$key.'}', $value, $template);
    }
    return(
        $template
    );
}   // linkTo()

 
/** 
 * @fn      bes_html( $string )
 * @brief   Converting special BES charaters to HTML entries
 * @details Decode BESMARC double a
 *
 * @param $string    BES string
 * @retval string   HTML string
 * 
 * @todo    This function is hard coded. Need redesign
 *
 * @since           2018-12-17T07:47:19
 */
function bes_html( $string )
{
    $string = str_replace("#O", getIcon('double_a_uppercase'), $string);
    $string = str_replace("#o", getIcon('double_a_lowercase'), $string);
    return( $string );
}   // bes_html()

//---------------------------------------------------------------------

/**
 *  @fn         getBetween($content,$start,$end)
 *  @brief   Extract substring between start pattern and end pattern
 *
 * @details Extract only first substring
 *
 * @param $content   String to analyse
 * @param $start     Start pattern
 * @param $end       End patterne
 * @retval string   Pattern found - or an empty string.
 * 
 * @see          doc/manual.md
 * @see             https://tonyspiro.com/using-php-to-get-a-string-between-two-strings/
 * @since           2018-12-17T07:47:19
 */
function getBetween($content,$start,$end)
{
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return('');
}   // getBetween()

//---------------------------------------------------------------------

/**
 *  @fn         getBetweens($content,$start,$end)
 *  @brief   Extract every substring between start and end pattern
 *
 *
 * @param $content   String to analyse
 * @param $start     Start pattern
 * @param $end       End patterne
 * @retval array    Patterns found - or an empty string.
 * 
 * @see          doc/manual.md
 * @see             https://tonyspiro.com/using-php-to-get-a-string-between-two-strings/
 * @since           2018-12-17T07:48:33
 */
function getBetweens($content,$start,$end)
{
    $r = explode($start, $content);
    $result = [];

    if (isset($r[1])){
        for ($i = 1 ; $i < count($r) ; $i++) {
            $r2 = explode($end, $r[$i]);
            array_push($result, $r2[0]);
        }
        return $result;
    }
    return $result;;
}   // getBetweens()

//----------------------------------------------------------------------

/**
 *  @fn         dm2_html( $string )
 *  @brief Encoding danMARC2 special characters to HTML entities
 * @details
 * 
 * code | Hex | Name | Note
 * ---|---|---|---
 *| \@*	| U+002A	| asterisk |
 *| \@\@	| U+0040	| commercial at |
 *| \@¤	| U+00A4	| currency code – not used in Danish records (to be confirmed)  |
 *| \@å	| U+A733	| gammelt dansk å - alternative form in dM2 \@A733 |
 *| \@Å	| U+A732	| gammelt dansk Å - alternative form in dM2 \@A732 |
 *
 * This function MUST be encoded in UTF-8 w/o BOM
 *
 * @param string $string   String to encode
 * @retval string       encodet string
 *
 * @see    http://php.net/manual/en/function.htmlentities.php#82534
 * @since 2018-07-30 10:51:00
 */
function dm2_html( $string )
{
    $tests  = [
        "@&Aring;" => "&Aring;",
        "@&aring;" => "&aring;",
        "@*" => "&ast;",
        '@¤' => "&curren;",
        "@@" => "&commat;",
    ];

    foreach( $tests as $test => $value) {
        $string = str_replace( $test, $value, $string);
    };

    return( $string );
}   // dm2_html()

//---------------------------------------------------------------------

/**
 *  @fn         utf2latin($text)
 *  @brief   Encode UTF-8 -> latin1
 *
 * @since 2018-07-30 10:51:00
 *
 *
 * @param string $text  String to encode
 * @retval string       encodet string
 *
 * @see    http://php.net/manual/en/function.htmlentities.php#82534
 * @version 2018-07-30 10:51:00
 */
function utf2latin($text)
{ 
   $text=htmlentities($text,ENT_COMPAT,'UTF-8'); 
   return html_entity_decode($text,ENT_COMPAT,'ISO-8859-1'); 
}   // utf2latin()

//---------------------------------------------------------------------

/**
 *  @fn      latin2utf($text)
 *  @brief   Encode latin1 -> UTF-8
 *
 * @param $text   String to encode
 * @retval string       encodet string
 *
 * @since   2018-07-30 10:51:00
 * @version 2018-07-30 10:51:00
 */
function latin2utf($text)
{
   $text    = htmlentities($text,ENT_COMPAT,'ISO-8859-1'); 
   return html_entity_decode($text,ENT_QUOTES,'UTF-8'); 
}   // latin2utf()

//---------------------------------------------------------------------

/**
 * @fn      codeStr( $str )
 * @brief   Encode latin1 -> UTF-8 -> HTML
 * @details Special characters only!
 *
 *
 * @param string $str   String to encode
 * @retval string       encodet string
 *
 * @since 2018-07-30 10:51:00
 * @version 2018-07-30 10:51:00
 */
function codeStr( $str )
{
    return ( htmlspecialchars( latin2utf( $str ), ENT_DISALLOWED, "UTF-8" ) );
}   // codeStr()

//---------------------------------------------------------------------

/**
 *  @fn      controlCharacterReplacement( $str )
 *  @brief   Replace Control Characters from string
 *
 * @since 2018-07-30 10:51:00
 *
 *
 * @param string $str   from which control characters should be replaced
 * @retval string       string with replacements
 *
 * @version 2018-07-30 10:51:00
 */
function controlCharacterReplacement( $str )
{
    if ( isset( $GLOBALS['config']['controlcharacterpattern'] ) 
        &&
        isset( $GLOBALS['config']['controlcharacterreplacement'] )
    ) {
        $str = preg_replace("/" . $GLOBALS['config']['controlcharacterpattern'] . "/", $GLOBALS['config']['controlcharacterreplacement'], $str);
   } else {
           trigger_error(___('controlcharacterpattern_missing'), E_USER_WARNING);
   }

    return( $str );
}   // controlCharacterReplacement()

//---------------------------------------------------------------------

/**
 *  @fn      escapeBlanksInStrings( $ccl, $mask )
 *  @brief   Escaping blanks, parenteses and quotes in CCL query string
 *
 * @details Inserts blanks in parenteses: (this) -> ( this )
 *
 * @param $ccl       CCL query string
 * @param $mask      masking replacement symbol
 * @retval          Converted string
 * 
 * @see             doc/ccl.example.md 
 * @since           2018-12-21T10:56:35
 */
function escapeBlanksInStrings( $ccl, $mask )
{
    // Escaping blanks in strings
    $inquote = false;
    for ( $i=0 ; $i<strlen( $ccl ) ; $i++ ) {
        switch ( $ccl[$i] ) {
            case '"':
                $inquote ^= 1; 
                break;
            case ' ':
                //$ccl[$i]= $inquote ? $mask : " "; 
                $ccl[$i]= $inquote ? $mask : " "; 
                break;
        }
    }
    
    $ccl = str_replace( "(","( ",$ccl );
    $ccl = str_replace( ")","  )",$ccl);
    $ccl = str_replace( "  "," ",$ccl);
    
    return $ccl;
}   // escapeBlanksInStrings()

//---------------------------------------------------------------------

/**
 *  @fn    strhex($string)
 *  @brief String to string of Hex values
 *  
 *  @param [in] $string Description for $string
 *  @retval Return description
 *  
 *@code
 *  $hex = strhex("test sentence...");
 *  // $hex contains 746573742073656e74656e63652e2e2e
 *  print hexstr($hex);
 *  // outputs: test sentence...
 *@endcode
 *
 *  @see   https://www.php.net/manual/en/language.types.type-juggling.php#45062
 *  @details More details
 */
function strhex($string)
{
   $hex="";
   for ($i=0;$i<strlen($string);$i++)
       $hex.=dechex(ord($string[$i]));
   return $hex;
}   // strhex()

//---------------------------------------------------------------------

/**
 *  @fn    hexstr($hex)
 *  @brief String of Hex values to string
 *  
 *  @param [in] $hex String of Hex values
 *  @retval Return String
 *  
 *@code
 *  $hex = strhex("test sentence...");
 *  // $hex contains 746573742073656e74656e63652e2e2e
 *  print hexstr($hex);
 *  // outputs: test sentence...
 *@endcode
 *
 *  @see https://www.php.net/manual/en/language.types.type-juggling.php#45062
 *  @details More details
 */
function hexstr($hex)
{
   $string="";
   for ($i=0;$i<strlen($hex)-1;$i+=2)
       $string.=chr(hexdec($hex[$i].$hex[$i+1]));
   return $string;
}   // hexstr($hex)

//---------------------------------------------------------------------

/**
 *  @fn        remove_utf8_bom( &$text )
 *  @brief     Remove UTF-8 BOM prefix from string
 *  
 *  @param [in] $text	Text sting to strip BOM from
 *  @retval    $text w/o BOM
 *  
 *  @details   
 *  
 *@code
 *  	$text	= "\xEF\xBB\xBFBOM";
 *  	print bin2hex( remove_utf8_bom( $text ) );
 *  If remove_utf8_bom succedes this will print:
 *  	424f4d
 *  if it fails:
 *  	efbbbf424f4d
 *@endcode
 *
 *  @since     2022-06-19T12:18:28 / erba
 */
function remove_utf8_bom( &$text )
{
    $bom = pack('H*','EFBBBF');
    $text = preg_replace("/^$bom/", '', $text);
    return( $text );
}	//** remove_utf8_bom() ***

//---------------------------------------------------------------------

/**
 *  @fn         expandUnicode2Html( $str )
 *  @brief      Expanding Danbib encoded Unicode characters to HTML
 *  
 *  @details    Unicode prefixed by @ is expanded to HTML.
 *  
 *  Danbib	|HTML       | note
 *  ---|---|---
 *  \@00AE	| &\#x00AE;	| Registred sign
 *  \@0153	| &\#x0153;	| Little french oe
 *  \@0394	| &\#x0394;	| Greek capital letter delta
 *  
 *  
 *  NOTE: Sequence of 4 hex digits can be matched using either `[0-9a-fA-F]{4}` or `[[:xdigit:]]{4}`
 *  @param [in] $str 	String to expand
 *  @retval     String expanded
 *  
 *@code
 * expandUnicode( "\@00AE" ) == "\&#x00AE";
 *@endcode
 *  
 *  @warning    May conflict with records NOT encoded where \@xxxx can be expanded unintentionally
 *  
 *  @see        https://stackoverflow.com/a/31968439
 *  @since      2020-08-01T22:42:55
 */
function expandUnicode2Html( $str )
{
	$pattern = '/@([[:xdigit:]]{4})/i';
	$replacement = '&#x${1};';
	return( preg_replace($pattern, $replacement, $str) );
}	// expandUnicode2Html()

//----------------------------------------------------------------------

/**
 *  @fn         superSubScript( $str )
 *  @brief      Expand superscript and subscript in string
 *  
 *  @details    String sequences for superscript (¹) and subscript expanded to valid HTML
 *  
 *  	Super{uparrow}s{downarrow}cript and sub{downarrow}s{uparrow}cript
 *  	Super<sup>s</sup>cript and sub<sub>s</sub>cript
 *  
 *  {downarrow}
 *  [▒][226][xE2]
 *  [▒][134][x86]
 *  [▒][147][x93]
 *  
 *  {uparrow}
 *  [▒][226][xE2]
 *  [▒][134][x86]
 *  [▒][145][x91]
 *  
 *  
 *  @param [in] $str 	String with superscript or subscript sequences
 *  @retval     Plain HTML
 *  
 *@code
 *  // Byte sequences:
 *  	$uparrow	= "\xE2\x86\x91";
 *  	$downarrow	= "\xE2\x86\x93";
 *  	$expected	= "Super<sup>s</sup>cript and sub<sub>s</sub>cript";
 *  
 *  	$str	= "Super${uparrow}s${downarrow}cript and sub${downarrow}s${uparrow}cript";
 *  	$result	= superSubScript( $str );
 *  	echo $result == $expected ? "OK" : "Failed";
 *@endcode
 *
 *  @todo       
 *  @bug        
 *  @warning    
 *  
 *  @see        
 *  @since      2020-08-01T22:44:07
 */
function superSubScript( $str )
{
	$str2	= "";
	global $str0;
	$level	= 0;
	$len = strlen($str);
	for ( $i = 0 ; $i < $len ; $i++ ) {
		
		// Up/down [226][134]
		if ( ( chr(226) == $str[$i] ) && ( chr(134) == $str[$i+1] ) ){
			switch ( $str[$i+2] ) {
				case chr(145):	debug( "UP($level)" );
					$i+=2;

					if ( 0 <= $level ) {
						$str2 .= "<sup>";
					} else {
						$str2 .= "</sub>";
					}
					$level++;
					break;
				case chr(147):	debug( "DOWN($level)" );
					$i+=2;

					if ( 0 >= $level ) {
						$str2 .= "<sub>";
					} else {
						$str2 .= "</sup>";
					}
					$level--;
					break;
				default:
					$str2 .= $str[$i];
			}
		} else {
			$str2 .= $str[$i];
			debug( "[{$str[$i]}][".ord($str[$i])."]" );
		}
	}

	debug( ( $str0 == $str2) ? "OK" : "Fail" );
	debug( "Got:      $str2" );
	debug( "Expected: $str0" );
	return( $str2 );
}	// superSubScript()

//>>>-------------------------------------------------------------------


/**
 * @fn      get_doc( $docfile )
 * @brief      Read Markdown file and parse to HTML
 *   
 *   @param [in]	$docfile	File to read and parse
 *   @retval     HTML string
 *   
 *   @since      2024-12-03T11:36:04
 */
function get_doc( $docfile )
{
    $note   = file_get_contents( $docfile );
    $Extra  = new ParsedownExtra();
    return( $Extra->text( $note ) );
}	// get_doc()


/**
 * @fn          getDetailSummary( $summary, $detail, $preformat = FALSE )
 *   @brief      Build details/summary block in HTML
 *   
 *   @param [in]	$summary	Summary/headline
 *   @param [in]	$detail	    Details/body
 *   @param [in]	$preformat	On TRUE preformatted
 *   @retval     HTML string
 *   
 *   
 *   @since      2024-12-03T11:37:10
 */
function getDetailSummary( $summary, $detail, $preformat = FALSE )
{
	$text	= sprintf( "<details><summary>%s</summary><span id='%s'>%s%s%s</span></details>"
	,	$summary
	,	$summary
	,	($preformat ? "<PRE>" : "")
	,	$detail
	,	($preformat ? "</PRE>" : "")
	);
	return( $text );
}	//*** getDetailSummary() ***


/**
 *  @fn         getNoOfDigits( $num )
 *  @brief     Return the no of digits in a number
 *  
 *  @param [in] $num 	Number to process
 *  @retval    Number of digits
 *  
 *  @details   
 *  
 *@code
 *  $count   = getNoOfDigits( 12357 ); // 5 digits
 *@endcode
 *  
 *  
 *  @see       https://stackoverflow.com/a/28434327/7485823
 *  @since     2023-02-15T12:46:44 / erba
 */
function getNoOfDigits( $num )
{
    return( $num !== 0 ? floor(log10($num) + 1) : 1 );
}   //*** getNoOfDigits() ***

/**
 *  @fn        getDoxygenFileHeader( $file )
 *  @brief     Extract Doxygen file header from file
 *  
 *  @param [in] $file	File to extract header from
 *  @retval    Description header as string
 *  
 *  @details   More details
 *  
 *  
 *@code
 *  fputs( STDERR, getDoxygenFileHeader( __FILE__ ) );
 *@endcode
 *  
 *	* [file]      filename.php
 *	* [brief]     Brief description
 *  *
 *	* [details]   More details
 *  *
 *	* [copyright] http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *	* [author]    Author Name \<email\>
 *	* [since]     2022-05-26T17:45:18 / Author Name
 *	* [version]   2022-11-30T10:15:55 / Author Name
 *
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2022-12-01T08:56:27 / Erik Bachmann
 */
function getDoxygenFileHeader( $file )
{
    $source = file_get_contents( $file );
    $header = getBetween($source, "/**", "*/");
    return( trim(var_export( preg_replace( "/\n \*\s*\@(\w*)/", "\n * [$1]", $header ), true ), "'" ));
}	// getDoxygenFileHeader()

//---------------------------------------------------------------------

/**
 *  @fn        number_formatted( $no, $dec = 0 )
 *  @brief     Format a number with grouped thousands and decimal separator
 *  
 *  @param [in] $no     Number to format
 *  @param [in] $dec    Number of decimals
 *  @retval    Formated number as string
 *  
 *  @details   Formatting a number by localisation
 *          en: 1,000.00
 *          da: 1.000,000
 *  
 *  
 *  @todo      
 *  @bug       
 *  @warning   This function requires localisation: __();
 *  
 *  @see       https://
 *  @since     2023-03-24T06:27:30 / Bruger
 */
function number_formatted( $no, $dec = 0 )
{
    if ( is_string( $no ) )
        $no = intval( $no );
    return( number_format( $no, $dec, ___('decimal_separator'), ___('thousands_separator') ) );
}   //number_formatted()

//---------------------------------------------------------------------

/**
 *  @fn        stringExpand($subject, array $vars)
 *  @brief     Expand simple variables inside a string
 *  
 *  @param [in] $subject 	Description for $subject
 *  @param [in] $vars 	Description for $vars
 *  @retval    Return description
 *  
 *  @details   Even in single quoted strings
 *  
 *@code
 *      $str    = 'Just to say {$__local_hello} ${__local_hello} $__local_hello to you';
 *      print stringExpand( $str, [ "__local_hello" => "Hello"  ] );
 *@endcode
 *  
 *  will produce:
@verbatim
 *      Just to say Hello Hello Hello to you
@endverbatim
 *  
 *  Or a more complex example:
 *@code
 *
 *      // Language
 *      $lang   = 'en';
 *      // Localisation
 *      $__local    = [
 *          'hello' => [
 *              'da'    => 'Hej',
 *              'en'    => 'Hello'
 *          ]
 *      ];
 *      // The string - single qouted!
 *      $str    = ' {$__local_hello}    $__local_hello    world';
 *      
 *      // Expand any localised string
 *      foreach( $__local as $token => $value )
 *      {
 *          $str    = stringExpand( $str, [ "__local_$token" => $value[$lang] ] );
 *      }
 *  
 *      echo $str ;
 *@endcode
 *  
 *  Produces:
@verbatim
Hello    Hello    world
@endverbatim
 *  
 *  @see       https://bugs.php.net/bug.php?id=43901
 *  @see       https://stackoverflow.com/a/5241845/7485823
 *  @since     2023-11-06T15:45:27 / erba
 */
function stringExpand($subject, array $vars)
{
	// loop over $vars map
	foreach ($vars as $name => $value) {
		// use preg_replace to match ${`$name`} or $`$name`
        $subject = preg_replace(sprintf('/\{?\$\{?%s\}/', $name), $value, $subject);
	}
	// return variable expanded string
	return $subject;
}   // stringExpand()


//---------------------------------------------------------------------

/**
 *  @fn        expandLocal( $str )
 *  @brief     expand localisation from global $__local;
 *  
 *  @param [in] $str     String to expand
 *  @retval    Expanded string
 *  
 *  @details   
 *  
 *  @example   
 *  
 *  @todo      Replace w. expandGlobalVarsRef
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2024-07-10T14:30:13 / erba
 */
function expandLocal( $str )
{
    // Skip if no '$' in string
    if ( FALSE === strpos( $str, '$' ) )
        return( $str );
    
    global $__local;
    $lang   = $GLOBALS['lang'];

    // Expand all locals
    foreach( $__local as $token => $value )
    {
        $str    = stringExpand( $str, [ "__local_$token" => $value[$lang] ] );
    }
    
    return( $str );
}   // expandLocal()

//---------------------------------------------------------------------

/**
 *  @fn        platformSlashes($path)
 *  @brief     Set slashes in path according to OS
 *  
 *  @param [in] $path   Path to be normalised
 *  @retval    Normalised path
 *  
 *  @details   
 *  
 *@code
 platformSlashes( "/tmp/subdir\myfile.txt");
 *@endcode
 *
 *  - Windows:  "\tmp\subdir\myfile.txt"
 *  - Linux:    "/tmp/subdir/myfile.txt"
 *  
 *  @since     2024-03-16T16:30:26 / Bruger
 */
function platformSlashes($path)
{
    return str_replace(['/','\\'], DIRECTORY_SEPARATOR, $path);
}   // platformSlashes()

//---------------------------------------------------------------------

/**
 *  @fn         expandGlobalVarsRef( $str, $refs = FALSE )
 *  @brief      Expand complex variables in string
 *  
 *  @param [in] $str    Reference to string
 *  @param [in] $ref    Reference to associative array OR GLOBALS
 *  @retval     Returns the expanded string
 *  
 *  @details    Expands complex variables (imbedded in {}) using
 *      $GLOBALS or references from an associative array
 *
 *      "Simple" variable names (like '$x' or '${x}') are not expanded.
 *  
 *@code
 *     $str='hello $x {$x} {\$x} {$x}';
 *     $x="<globalX>";
 *     $arr = [ 'x' => "<localX>" ];
 *     
 *     echo "start: [$str]\n";
 *     echo expandGlobalVarsRef( $str ).PHP_EOL;       # Use GLOBALS
 *     echo expandGlobalVarsRef( $str, $arr ).PHP_EOL; # Use $arr
 *@endcode
 *  Will produce:
@verbatim
 *     start: [hello $x {$x} {\$x} {$x}]
 *     hello $x <globalX> {\$x} <globalX>
 *     hello $x <localX> {\$x} <localX>
@endverbatim
 *
 *  @note   Replaces: resolve_vars_in_str(), replaceVariablesInTemplate()
 *  
 *  @since      2024-07-10T13:19:10 / erba
 */
function expandGlobalVarsRef( $str, $refs = FALSE )
{
    if ( FALSE == $refs ) $refs = $GLOBALS;

    $result = preg_match_all('/{\$([^\}]*)}/s', $str, $strings );

    foreach( $strings[0] as $key => $val )
    {
        $str = str_replace( "{$val}" , "{$refs[ $strings[1][$key] ]}" , $str);
    }

    return( $str );
}   // expandGlobalVarsRef()

//---------------------------------------------------------------------

/**
 *  @fn         expandGlobalVarsRefquery_prefix( $str, $reference = FALSE, $delimit = ':='  )
 *  @brief      Expand complex variables in string w. delimiter
 *  
 *  @param [in] $str        Reference to string
 *  @param [in] $ref        Reference to associative array OR GLOBALS
 *  @param [in] $delimit    Delimiters as string
 *  @retval     Returns the expanded string
 *  
 *  @details
 *      Expands complex variables separated by delimiter using
 *      $GLOBALS or references from an associative array
 *      Delimiters are replaced by the first in string
 *
 *      "Simple" variable names (like '$x' or '${x}') are not expanded.
 *  
 *@code
 *     $str='keyword=x y z';
 *     $arr = [ 'keyword' => "subject" ];
 *     
 *     echo "start: [$str]\n";
 *     echo expandGlobalVarsRef_prefix( $str, $arr ).PHP_EOL; # Use $arr
 *@endcode
 *  Will produce:
@verbatim
start: [keyword=x y z]
subject:x y z
@endverbatim
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see        https://
 *  @since      2024-07-14T22:11:44 / erba
 */
function expandGlobalVarsRefquery_prefix( $str, $reference = FALSE, $delimit = ':=' )
{
    if ( FALSE == $reference ) $reference = $GLOBALS;

    $exp    = '/\b([\w^'.$delimit.']*)['.$delimit.']/is';

    $result = preg_match_all( $exp, $str, $strings );
    
    foreach( $strings[0] as $key => $val )
    {
        if (isset( $reference[ $strings[1][$key] ] ))
            $str = str_replace( "{$val}" 
            ,   "{$reference[ $strings[1][$key] ]}".substr($delimit,0,1)
            ,   $str
            );
    }

    return( $str );
}   // expandGlobalVarsRefquery_prefix()

//----------------------------------------------------------------------

/**
 * @fn          truncate_center_word($string,$length=100,$wordw=FALSE,$mask=FALSE) 
 *   @brief      Zap the middle of a string
 *   
 *   @param [in]	$string     Source string
 *   @param [in]	$length	    Max length
 *   @param [in]	$wordw      Preserve words
 *   @param [in]	$mask       Mask to insert
 *   @retval     String truncated
 *   
 *   @details    
 *   Update 4:
 *   If you really want to complicate things: Zap the middle of a string
 *   
@code
    $str	= "Shouting 'Hello' to the World";
    echo truncate_center_word( $str, 10 ) . PHP_EOL;
    echo truncate_center_word( $str, 10, FALSE, TRUE ) . PHP_EOL;
@endcode
 *   
@verbatim
    Shou...orld
    Shouting...World
@endverbatim
 *   
 *   @see        https://stackoverflow.com/a/3161830/7485823
 *   @since      2024-11-19T10:57:07
 */
function truncate_center_word($string,$length=100,$wordw=FALSE,$mask=FALSE) 
{
  if ( FALSE === $mask )    // Default mask
    $mask = (php_sapi_name() === 'cli') ? '...' : '&hellip;';

  $string = trim($string);

  if(strlen($string) > $length) {
    if ( $wordw )
    {   // Preserve words
        $stub = wordwrap($string, intval($length/2)-1 );
        $newstring = explode("\n", $stub, 2);
        $newstring = $newstring[0] . $mask;
      
        $stub = wordwrap($string, intval($length/2)-1 );
        $stub = explode("\n", $stub);
      
        $stub = array_pop($stub);
        $newstring .= $stub;
    }
    else
    {
        $newstring  = substr( $string, 0, intval($length/2) - 1 )
        .   $mask
        .   substr( $string, -1 * intval($length/2) + 1 );
    }
    return($newstring);
  }

  return($string);
}   //truncate_center_word

//----------------------------------------------------------------------

?>