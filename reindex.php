<?php
/**
 *   @file       reindex.php
 *   @brief      Rebuild searchindex for metadata,
 *   @details    Recursive processing file tree. 
 *   
 *   @todo		Needs a resume action on broken rebuild (WHERE exif IS NULL)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-18T13:10:55 / ErBa
 *   @version    @include version.txt
 */

// Parse cli arguments and insert into $_REQUEST
parse_cli2request();

include_once( 'lib/getGitInfo.php');
fputs( STDERR, getDoxygenHeader( __FILE__) );

register_shutdown_function('shutdown');

// Dummy root
$releaseroot	= __DIR__ . '/';

// Include libraries
include_once('lib/debug.php');
include_once('lib/handleJson.php');
include_once('lib/imageResize.php');
include_once('lib/handleSqlite.php');
include_once('lib/iptc.php');
include_once('lib/jsondb.php');
include_once('lib/progress_bar.php');
// Verbose and debug
$GLOBALS['verbose']	= 1;
//$GLOBALS['debug']	= 1;
$GLOBALS['logging']	= 1;
debug( $_REQUEST, 'Request' );

// Read configuration
$cfg		= file_get_json( 'config/config.json' );
$local		= file_get_json( 'config/local.json' );
$dbCfg		= file_get_json( 'config/database.json' );
$metatags	= file_get_json( 'config/meta.json' );
// Update ALL
$count	= 0;

// Parse CLI / $_REQUEST
/*
-cfg:images:image_resize_type=scale
-cfg:images:image_resize_type=resized
-cfg:images:image_resize_type=resampled

-cfg:resume=1

*/
foreach ( $_REQUEST as $cmd => $cmdvalue )
{
	if ( str_starts_with( $cmd, 'cfg' ) )
	{
		setPathKey( array_slice(explode(':', $cmd ), 1), $cfg, $cmdvalue);
	}
}

status( "Image resize type", $cfg['images']['image_resize_type'] );
debug( $cfg, 'Config after $_REQUEST' );

// Init global variables
$files		= [];
$db			= FALSE;
$keycount	= 0;

//----------------------------------------------------------------------

// Open - or create database
initDatabase( $db, $cfg['database']['file_name'], $dbCfg );

// Resume or process all?
//if ( ! empty( $cfg['resume '] ) )
if ( isset( $_REQUEST['resume'] ) )
{	// Resume
	verbose( 'Indexing: Resume processing' );
	$sql	= $dbCfg['sql']['select_source_meta'];
	debug( $sql, 'SQL:' );
	
	$files 	= querySql( $db, $sql );
	foreach($files as $no => $path)
	{
		$files[$no]	= $path['files'];
	}
	//var_export($files);exit;

	foreach ( $files as $file )
	{
		if ( strpos( $file, "\\") )
		{
			unset( $files[$file] );
			$files[]	= str_replace( "\\", '/', "$file");
		}
	}
	status('Resume', count( $files ));
}
else
{	// Process all
	$sql	= $dbCfg['sql']['select_source_meta'];
	debug( $sql, 'SQL:' );
	
	$files 	= querySql( $db, $sql );
	$total	= count( $files );
	debug( $files, 'Files');

	$loadfile	= fopen( 'loadfile.txt', 'w');
	
	foreach($files as $no => $data)
	{
		$count++;
		$file	= $data['file'];
		$data['iptc']	= json_decode( $data['iptc'], TRUE);
		//$data['exif']	= json_decode( $data['exif'], TRUE);
		array2breadcrumblist($data['iptc'], $iptc );
		//array2breadcrumblist($data['exif'], $exif );
		foreach( $iptc as $key => $value )
		{
			foreach( $dbCfg["search"]["iptc"] as $iKey => $iValue )
			{
				if ( str_starts_with( $key, $iKey ) )
				{
					//fputs( $loadfile, "$iValue$value\n" );
					
					$sql	= sprintf( 
						$dbCfg['sql']['insert_search']
					,	$file
					,	$no
					,	"$iValue$value"
					,	strtolower("$iValue$value")
					);
					fputs( $loadfile, "$sql\n" );
					$r  = $db->exec( $sql );
					$keycount++;
				}
			}
		}
		/*
		foreach( $exif as $key => $value )
		{
			foreach( $dbCfg["search"]["iptc"] as $iKey => $iValue )
			{
				if ( str_starts_with( $key, $iKey ) )
				{
					fputs( $loadfile, "$iValue$value\n" );
				}
			}
		}
		*/
		echo progressbar($count, $total) . $file;
	}
}

//----------------------------------------------------------------------

/**
 *            initDatabase
 *   @brief      Open or create database w. tables
 *   
 *   @param [in]	&$db	Handle to database
 *   @param [in]	$dbfile	Database file name
 *   @param [in]	&$dbCfg	Database schemas from JSON
 *   @return     TRUE if open | FALSE
 *   
 *   @details
 *	* Create database if not exists
 *      * Create tables
 *  * Open data if exists
 *   
 *   @since      2024-11-13T13:47:53
 */
function initDatabase( &$db, $dbfile, &$dbCfg )
{
	if ( ! file_exists( $dbfile ) )
	{
		verbose( $dbfile, "Create database:\t" );
		$db	= createSqlDb($dbfile);
		$r  = $db->exec( $dbCfg['sql']['create_images'] );
		//$r  = $db->exec( $dbCfg['sql']['create_meta'] );
	}
	else
	{
		//verbose( $dbfile, "Opening database:\t");
		status("Opening database",  $dbfile);
		$db	= openSqlDb($dbfile);
	}
	return( ! empty( $db ) );
}	// initDatabase()

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
function ___( $key, $lang = 'en' )
{
	return( $GLOBALS['local'][$key][$lang] ?? "[$key][$lang]" );
}	// ___()

//----------------------------------------------------------------------


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
 *   @brief      Run at shutdown
 *   
 *   @param [in]		$(description)
 *   @return     $(Return description)
 *   
 *   @details    
 *    This is our shutdown function, in 
 *    here we can do any last operations
 *    before the script is complete.
 *   
 *   @since      2024-11-15T13:28:32
 */
function shutdown( )
{
	/*
	if ( ! empty($args) )
	{
		echo PHP_EOL . implode( "\n", $args ) . PHP_EOL ;
	}
	* /
	echo PHP_EOL . "Images processed: ". $GLOBALS['count'];
	echo PHP_EOL . "Runtime: " . microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"] .  PHP_EOL;
    echo PHP_EOL . 'Script executed with success', PHP_EOL;
	*/
	fputs( STDERR, "\n\n");

	status( "Keywords", $GLOBALS['keycount'] );
	//status( "Files", $GLOBALS['total'] );

	status( "Images processed", $GLOBALS['count']);
	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	//status( "Runtime ", $Runtime );
	status( "Runtime ", microtime2human( $Runtime ) );
	status( "Log", $GLOBALS['logfile']  ?? 'none');

}	// shutdown()


?>