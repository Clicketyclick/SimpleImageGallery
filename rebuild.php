<?php
/**
 *   @file       rebuild.php
 *   @brief      Rebuild database with files and metadata,
 *   @details    Recursive processing file tree. 
 *   
 *   @todo		Needs a resume action on broken rebuild (WHERE exif IS NULL)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T06:14:36 / ErBa
 *   @version    @include version.txt
 */

// Parse cli arguments and insert into $_REQUEST
parse_cli2request();

include_once( 'lib/getGitInfo.php');
fputs( STDERR, getDoxygenHeader( __FILE__) );

//exit;
//$GitCommitInfo	= getGitCommitInfo();
//$gitVersion		= getGitVersion();

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


//$GLOBALS['logfile.txt']	= 1;
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

//----------------------------------------------------------------------

// Open - or create database
initDatabase( $db, $cfg['database']['file_name'], $dbCfg );

// Resume or process all?
//if ( ! empty( $cfg['resume '] ) )
if ( isset( $_REQUEST['resume'] ) )
{	// Resume
	verbose( 'Resume processing' );
	$sql	= $dbCfg['sql']['select_files_resume'];
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
	verbose( 'Process all' );
	// Find all image files recursive
	getImagesRecursive( $GLOBALS['cfg']['data']['data_root'], $GLOBALS['cfg']['data']['image_ext'], $files, ['jpg'] );
	debug( $files );

	status( count( $files ), "Processing");
	// Put all files to database: images
	putFilesToDatabase( $files );
}


verbose( '// Write meta data for each file' );


$starttime	= microtime( TRUE );
//foreach ( $files as $path )
$total	= count($files);
foreach ( $files as $file )
{
	$r  = $db->exec( "BEGIN TRANSACTION;" );
	$currenttime	= microtime( TRUE );
	++$count;

	//['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
	['basename' => $basename, 'dirname' => $dirname] = pathinfo( $file );
	//$file	= "$dirname/$basename";
	$note	= "";
	debug( $file );

	//debug(microtime( TRUE ) - $currenttime, "\nStart");
	// Get image dimentions
	list($width, $height, $type, $attr) = getimagesize($file);
	//debug(microtime( TRUE ) - $currenttime, 'Get image dimentions');
	// Get EXIF
	$exif 		= exif_read_data( $file, 0, true);
	if ( empty( $exif ) )
	{
		logging( "$src error in image EXIF" );
		// Write to table: images
		$r  = $db->exec( $sql );
		continue;
	}

	$exifjson 	= json_encode_db( $exif );
	debug($exifjson, 'EXIF_json');
	//debug(microtime( TRUE ) - $currenttime, 'EXIF_json');
	// Get IPTC
	$iptc		= parseIPTC( $file );
	if ( empty( $iptc ) )
	{
		logging( "$src error in image IPTC" );
		// Write to table: images
		$r  = $db->exec( $sql );
		continue;
	}
    $iptcjson 	= json_encode_db( $iptc );
	//debug(microtime( TRUE ) - $currenttime, 'IPTC_json');
	debug($iptcjson, 'IPTC_json');

	// Get thumbnail
	$thumb 		= exif_thumbnail( $file );
	if ( empty( $thumb) )
	{
		$note			.= "Thumb build";
		//list($width, $height, $type, $attr) = getimagesize($file);
		$thumb_width	= 100;
		$thumb_height	= 100;
		$dst			= "FALSE";
		$thumb			= image_resize( 
				$file
			,	$dst
			,	$cfg['images']['thumb_max_width']
			,	$cfg['images']['thumb_max_height']
			,	$exif['IFD0']['Orientation'] ?? 0
			,	$cfg['images']['image_resize_type']
			,	$cfg['images']['crop']
			);
	}
	else
	{
		if ( $exif['IFD0']['Orientation'] ?? 0 )
		{
			$gdThumb	= imagecreatefromstring( $thumb );
			//if ( $degrees )
			$gdThumb	= gdReorientateByOrientation( $gdThumb, $exif['IFD0']['Orientation'], $file );
			$thumb		= stringcreatefromimage( $gdThumb, 'jpg');
		}
	}
	
	if ( empty( $thumb ) )
	{
		$r  = $db->exec( "COMMIT;" );
		logging( "Skipping thumb $file" );
		continue;
	}
	//debug(microtime( TRUE ) - $currenttime, 'get thumb');
	// Rotate EXIF
	$thumb 		= base64_encode( $thumb );

	$dst		= "FALSE";
	$view 		= image_resize( 
					$file
				,	$dst
				,	$cfg['images']['display_max_width']
				,	$cfg['images']['display_max_height']
				,	$exif['IFD0']['Orientation'] ?? 0
				,	$cfg['images']['image_resize_type']
				,	$crop=0
				);
	if ( empty($view) )
	{
		//$view = file_get_contents($file);
		$r  = $db->exec( "COMMIT;" );
		logging( "Skipping view $file" );
		continue;

	}
	//debug(microtime( TRUE ) - $currenttime, 'view resize');
	$view 		= base64_encode( $view );

	// Update thumb and view
	$sql	= sprintf( 
		$dbCfg['sql']['replace_into_images']
	,	$dirname
	,	$basename
	,	$thumb
	,	$view
	,	$dirname
	);
	//debug( $sql );
	//echo( $sql );

	// Write to table: images
	$r  = $db->exec( $sql );

	// Update meta
	$sql	= sprintf($dbCfg['sql']['replace_into_meta'], $exifjson, $iptcjson, $dirname, $basename );
	//debug( $sql  );
	//echo "\n$sql\n";
	
	//$r  = $db->exec( sprintf($dbCfg['sql']['replace_into_meta'], $dirname,$basename, $exifjson, $iptcjson ) );
	$r  = $db->exec( $sql );

	//debug(microtime( TRUE ) - $currenttime, 'DB write');
	
	// Display status
	//fprintf( STDOUT, "- [%-35.35s] [%s] %sx%s %s %s %s\n"
	//verbose( sprintf( "%s/%s [%-35.35s] [%s] %sx%s %s %s %s"
	logging( sprintf( "%s/%s [%-35.35s] [%s] %sx%s %s %s %s"
		,	$count
		,	$total
		,	$exif['FILE']['FileName']
		,	date( 'c', $exif['FILE']['FileDateTime'] )
		,	$exif['COMPUTED']['Width']
		,	$exif['COMPUTED']['Height']
		,	$exif['FILE']['MimeType']
		,   $count . ':'. ( $exif['IFD0']['Orientation'] ?? '?')
		,   number_format( microtime( TRUE ) - $currenttime, 2) . 'sec. '.$note
	)
	,	"Image: "
	);
	$r  = $db->exec( "COMMIT;" );
	
	echo progressbar($count, $total) . $file;
}


//echo PHP_EOL . "Images processed: ". $count;

//----------------------------------------------------------------------

/**
 *   @brief      Write all file names to table.
 *   
 *   @param [in]	$files	List of file
 *   @return     VOID
 *   
 *   @details    Write all files to database in one transaction
 *   
 *   @since      2024-11-14T11:09:35
 */
function putFilesToDatabase( $files )
{
	global $db;
	global $dbCfg;
	verbose( '// Write all file names to table' );
	$r  = $db->exec( "BEGIN TRANSACTION;" );
	foreach ( $files as $path )
	{
		['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
		$sql	= sprintf( $dbCfg['sql']['insert_files'], 'images', $dirname, $basename, $dirname );
		debug( $sql );
		$r  = $db->exec( $sql );
	}
	$r  = $db->exec( "COMMIT;" );
}	// putFilesToDatabase()

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
 *   @brief      Get a list of images recursive from root
 *   
 *   @param [in]	$root		Start of search
 *   @param [in]	$image_ext	File extentions
 *   @param [in]	&$files		Array of files
 *   @param [in]	$allowed=[]	$(description)
 *   @return     TRUE if files found | FALSE
 *   
 *   @details    Recursive loop from root
 *   
 *   @since      2024-11-13T13:44:58
 */
function getImagesRecursive( $root, $image_ext, &$files, $allowed = [] )
{
	$it = new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS );
	$display = Array ( 'jpeg', 'jpg' );

	foreach(new RecursiveIteratorIterator($it) as $file)
	{
		if ( ! empty( $allowed ) )
		{	// extention after last . to lowercast
			//if( in_array( strtolower( substr( $file, strrpos($file, '.') + 1) ), $allowed ) ) {
			if( in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $allowed ) ) {
				$files[]	= str_replace( "\\", '/', "$file");
			}
		}
		else
			$files[]	= str_replace( "\\", '/', "$file");
	}
	return( ! empty($files) );
}	// getImagesRecursive()

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
	fputs( STDERR, "\n");
	status( "Images processed", $GLOBALS['count']);
	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	status( "Runtime ", $Runtime );
	status( "Runtime ", microtime2human( $Runtime ) );
	status( "Log", $GLOBALS['logfile']  ?? 'none');

}	// shutdown()


?>