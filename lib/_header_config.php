<?php
/**
 *   @file       _header.php
 *   @brief      Global file header
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-19T20:49:51 / ErBa
 *   @version    @include version.txt
 */

// Verbose and debug
$GLOBALS['verbose']    ??= 1;
$GLOBALS['debug']      ??= 0;
$GLOBALS['logging']    ??= 1;
$GLOBALS['timer']      ??= 0;

if ( ! isset($GLOBALS['timers']) )
    $GLOBALS['timers'] = [];
timer_set('_header', 'Full header file');

timer_set('def_io', 'STD..');
if(!defined('STDIN'))  define('STDIN',  fopen('php://stdin',  'rb'));
if(!defined('STDOUT')) define('STDOUT', fopen('php://stdout', 'wb'));
if(!defined('STDERR')) define('STDERR', fopen('php://stderr', 'wb'));

timer_set('def_io');


// Init global variables
//$files		= [];
$db			= FALSE;

timer_set('load_libs', 'Loading libraries');

// Include libraries
foreach ( [ 'debug', 'getGitInfo', 'handleStrings', 'handleJson', 'imageResize', 'handleSqlite', 'iptc', 'jsondb', 'progress_bar'] as $lib )
    include_once("lib/{$lib}.php");
timer_set('load_libs');

timer_set('set_configs', 'Loading configuration');
// Set configuration files
$GLOBALS['cfgfiles']    = ['config'=>'config', 'local'=>'local', 'database'=>'database', 'metatags'=>'meta'];
//$GLOBALS['cfgfiles']    = ['config'=>'config'];
//$GLOBALS['cfgfiles']    = [];
timer_set('set_configs');


timer_set('parse_cli', 'Parse CLI');
// Parse cli arguments and insert into $_REQUEST
parse_cli2request();
debug( $_REQUEST, 'Request' );
timer_set('parse_cli');

timer_set('read_config', 'Read configuration' );
// Read configuration
foreach( $GLOBALS['cfgfiles'] as $config_key => $config_value )
{
    $GLOBALS[$config_key]        = file_get_json( "./config/{$config_value}.json" );
    debug( $GLOBALS );
}

timer_set('read_config');

timer_set('get_language', 'Detect browser language');
getBrowserLanguage( );
timer_set('get_language');


// Print header
timer_set('print_header', 'Print header');
fputs( STDERR, getDoxygenHeader( debug_backtrace()[0]['file'] ) );
timer_set('print_header');

timer_set('shutdown', 'Set shutdown');
register_shutdown_function('shutdown');
timer_set('shutdown');


// Parse $_REQUEST
timer_set('parse_request', 'Parse $_REQUEST');

foreach ( $_REQUEST as $cmd => $cmdvalue )
{
	if ( strpos( $cmd, ':' ) )
	{   // Parse complex arguments
		setPathKey( array_slice(explode(':', $cmd ), 0), $GLOBALS, $cmdvalue);
	}
    else    // Parse simple arguments
        $GLOBALS[$cmd] = $cmdvalue;
    // Convert numbers to numeric
    if ( is_numeric($cmdvalue) )
        $GLOBALS[$cmd] *= 1;
}
/**/
debug( $GLOBALS, 'SESSION:');
timer_set('parse_request');


// Save Query from URL
timer_set('save_query_from_str', 'Save Query from URL');
parse_str( $_SERVER['QUERY_STRING'] ?? 'path=.', $GLOBALS['url']['args'] );
unset($GLOBALS['url']['args']['show']);    // Remove show to avoid dublication

//$debug=1;
debug($GLOBALS['url']['args'], 'URL args');

// Build new query for linking
debug( http_build_query($GLOBALS['url']['args']), 'http_build_query' );
//$debug=0;
timer_set('save_query_from_str');

timer_set('_header');

//----------------------------------------------------------------------

/**
 * @fn      initDatabase( &$db, $dbfile )
 *   @brief      Open or create database w. tables
 *   
 *   @param [in]	&$db	Handle to database
 *   @param [in]	$dbfile	Database file name
 *   @param [in]	&$dbCfg	Database schemas from JSON
 *   @retval     TRUE if open | FALSE
 *   
 *   @details
 *	* Create database if not exists
 *      * Create tables
 *  * Open data if exists
 *   
 *   @since      2024-11-13T13:47:53
 */
function initDatabase( &$db, $dbfile )
{
	if ( ! file_exists( $dbfile ) )
	{
		status(  "Create database", $dbfile );
		$db	= createSqlDb($dbfile);

        pstatus( "Database created &#x1F5BF;" );

		$GLOBALS['tmp']['tables_total']    = count($GLOBALS['database']['tables']);
		$count	= 0;
		$max    = $GLOBALS['tmp']['tables_total'];
		status("Create tables", $GLOBALS['tmp']['tables_total'] );
		foreach( $GLOBALS['database']['tables'] as $action => $sql )
		{
			$GLOBALS['timers'][$action]    = microtime(TRUE);
			
			debug($sql, $action);
			if ( str_starts_with( $action, '_') || str_starts_with( $sql, '--') )
			{
				debug( $sql, 'skip: ') ;
				//$action	= '';
			}
			else
			{
				debug($sql);
				$r  = $db->exec( $sql );
			}
			
			echo progressbar( ++$count, $GLOBALS['tmp']['tables_total'], 30, $action, 30 );

            pbar( 'progress', $max, $count, "Create tables: {$count}/{$max}" );
            
			logging( progress_log( $GLOBALS['tmp']['tables_total'], $count, $GLOBALS['timers'][$action], 1 ) );
		}
        echo "\n";

        pstatus( "Tables added: {$count}/{$max} &#x1F5CD;" );
	}
	else
	{
		debug( $dbfile, "Opening database");
		$db	= openSqlDb($dbfile);

        pstatus( "Database open &#x1F5C1;" );

	}
	return( ! empty( $db ) );
}	// initDatabase()

//----------------------------------------------------------------------

/**
 * @fn      json_encode_db( $arr )
 *   @brief      Encode mixed data for database
 *   
 *   @param [in]	$arr	Mixed data
 *   @retval     json with escaped values
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
 * @fn      parse_cli2request()
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
 * @fn      ___( $key, $lang = FALSE )
 *   @brief      Localisation function
 *   
 *   @param [in]	$key	Lookup key for local
 *   @param [in]	$lang='en'	Language code [Default:en]
 *   @retval     Translation | [$key][$lang]
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
 * @fn      getBrowserLanguage( $acceptLang = ['fr', 'it', 'en', 'da'] )
 *   @brief      Detect browser language
 *   
 *   @param [in]	$acceptLang=['fr'	$(description)
 *   @param [in]	'it'	$(description)
 *   @param [in]	'en'	$(description)
 *   @param [in]	'da']	$(description)
 *   @retval     $(Return description)
 *   
 *   @details    $(More details)
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
}   // getBrowserLanguage()

//----------------------------------------------------------------------

?>