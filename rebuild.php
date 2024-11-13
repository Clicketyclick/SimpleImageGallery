<?php
/**
 *   @file       rebuild.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T06:14:36 / ErBa
 *   @version    2024-11-11T06:14:36
 */

// Get DoxyIT header
preg_match('/\/\*\*(.*?)\*\//s', implode( '', file( __FILE__ )), $match); fputs( STDERR, $match[1] . PHP_EOL );

// Dummy root
$releaseroot	= __DIR__ . '/';

// Include libraries
include_once('lib/debug.php');
include_once('lib/handleJson.php');
include_once('lib/imageResize.php');
include_once('lib/handleSqlite.php');
include_once('lib/iptc.php');

// Verbose and debug
$GLOBALS['verbose']	= 1;
//$GLOBALS['debug']	= 1;

// Read configuration
$cfg		= file_get_json( 'config/config.json' );
$local		= file_get_json( 'config/local.json' );
$dbCfg		= file_get_json( 'config/database.json' );
$metatags	= file_get_json( 'config/meta.json' );

// Init global variables
$files		= [];
$db			= FALSE;

//----------------------------------------------------------------------

// Open - or create database
initDatabase( $db, $cfg['database']['file_name'], $dbCfg );

// Find all image files recursive
getImagesRecursive( $GLOBALS['cfg']['data']['data_root'], $GLOBALS['cfg']['data']['image_ext'], $files, ['jpg'] );
debug( $files );

// Put all files to database: images
putFilesToDatabase( $files );


function json_encode_db( $arr )
{
	return( SQLite3::escapeString( json_encode( $arr,  JSON_INVALID_UTF8_IGNORE ) ) );
}

verbose( '// Write meta data for each file' );
// Update ALL
$count	= 0;
$r  = $db->exec( "BEGIN TRANSACTION;" );

//foreach ( $files as $path )
foreach ( $files as $file )
{
	//['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
	['basename' => $basename, 'dirname' => $dirname] = pathinfo( $file );
	//$file	= "$dirname/$basename";
	$note	= "";
	++$count;
	debug( $file );

	// Get image dimentions
	list($width, $height, $type, $attr) = getimagesize($file);

	// Get EXIF
	$exif 		= exif_read_data( $file, 0, true);
	$exifjson 	= json_encode_db( $exif );
	debug($exifjson, 'EXIF_json');

	// Get IPTC
	$iptc		= parseIPTC( $file );
    $iptcjson 	= json_encode_db( $iptc );
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
			,	$crop=0
			);
	}
	else
	{
		if ( $exif['IFD0']['Orientation'] ?? 0 )
		{
			$gdThumb	= imagecreatefromstring( $thumb );
			//if ( $degrees )
			$gdThumb	= gdReorientateByOrientation( $gdThumb, $exif['IFD0']['Orientation'], $file );
			$thumb	= stringcreatefromimage( $gdThumb, 'jpg');
		}
	}
	
	// Rotate EXIF
	$thumb 		= base64_encode( $thumb );

	$dst		= "FALSE";
	$view 		= image_resize( 
					$file
				,	$dst
				,	$cfg['images']['display_max_width']
				,	$cfg['images']['display_max_height']
				,	$exif['IFD0']['Orientation'] ?? 0
				,	$crop=0
				);
	$view 		= base64_encode( $view );

	// Update thumb and view
	debug( sprintf( $dbCfg['sql']['replace_into_images']
	,	$dirname
	,	$basename
	,	$thumb
	,	$view 
	)  );

	// Write to table: images
	$r  = $db->exec( sprintf( 
		$dbCfg['sql']['replace_into_images']
	,	$dirname
	,	$basename
	,	$thumb
	,	$view 
	) );

	// Update meta
	debug( sprintf( $dbCfg['sql']['replace_into_meta'], $dirname,$basename, $exifjson, $iptcjson )  );
	$r  = $db->exec( sprintf($dbCfg['sql']['replace_into_meta'], $dirname,$basename, $exifjson, $iptcjson ) );

	// Display status
	//fprintf( STDOUT, "- [%-35.35s] [%s] %sx%s %s %s %s\n"
	verbose( sprintf( "[%-35.35s] [%s] %sx%s %s %s %s"
		,	$exif['FILE']['FileName']
		,	date( 'c', $exif['FILE']['FileDateTime'] )
		,	$exif['COMPUTED']['Width']
		,	$exif['COMPUTED']['Height']
		,	$exif['FILE']['MimeType']
		,   $count . ':'. ( $exif['IFD0']['Orientation'] ?? '?')
		,	$note
	)
	,	"Image: "
	);
}
$r  = $db->exec( "COMMIT;" );


//----------------------------------------------------------------------

function putFilesToDatabase( $files )
{
	global $db;
	global $dbCfg;
// Write all files to database
	verbose( '// Write all file names to table' );
	$r  = $db->exec( "BEGIN TRANSACTION;" );
	foreach ( $files as $path )
	{
		['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );

		debug( sprintf( $dbCfg['sql']['insert_files'], 'images', $dirname,$basename ) );
		$r  = $db->exec( sprintf( $dbCfg['sql']['insert_files'], 'images', $dirname,$basename ) );
		$r  = $db->exec( sprintf($dbCfg['sql']['insert_files'], 'meta', $dirname,$basename ) );
	}
	$r  = $db->exec( "COMMIT;" );
}	// putFilesToDatabase()


//----------------------------------------------------------------------

/**
 *   @fn         initDatabase
 *   @brief      Open or create database w. tables
 *   
 *   @param [in]	&$db	Handle to database
 *   @param [in]	$dbfile	Database file name
 *   @param [in]	&$dbCfg	Database schemas from JSON
 *   @return     TRUE if open | FALSE
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
 *   @since      2024-11-13T13:47:53
 */
function initDatabase( &$db, $dbfile, &$dbCfg )
{
	if ( ! file_exists( $dbfile ) )
	{
		verbose( $dbfile, 'Create database:\t');
		$db	= createSqlDb($dbfile);
		$r  = $db->exec( $dbCfg['sql']['create_images'] );
		$r  = $db->exec( $dbCfg['sql']['create_meta'] );
	}
	else
	{
		verbose( $dbfile, 'Opening database:\t');
		$db	= openSqlDb($dbfile);
	}
	return( ! empty( $db ) );
}	// initDatabase()

//----------------------------------------------------------------------

/**
 *   @fn         ___()
 *   @brief      Localisation function
 *   
 *   @param [in]	$key	Lookup key for local
 *   @param [in]	$lang='en'	Language code [Default:en]
 *   @return     Translation | [$key][$lang]
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
 *   @since      2024-11-13T13:43:14
 */
function ___( $key, $lang = 'en' )
{
	return( $GLOBALS['local'][$key][$lang] ?? "[$key][$lang]" );
}	// ___()

//----------------------------------------------------------------------

/**
 *   @fn         getImagesRecursive
 *   @brief      Get a list of images recursive from root
 *   
 *   @param [in]	$root		Start of search
 *   @param [in]	$image_ext	File extentions
 *   @param [in]	&$files		Array of files
 *   @param [in]	$allowed=[]	$(description)
 *   @return     TRUE if files found | FALSE
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

?>