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

$releaseroot	= __DIR__ . '/';

include_once('lib/handleJson.php');
include_once('lib/imageResize.php');
include_once('lib/handleSqlite.php');
include_once('lib/debug.php');
$GLOBALS['verbose']	= 1;
//$GLOBALS['debug']	= 1;

$cfg		= file_get_json( 'config/config.json' );
$local		= file_get_json( 'config/local.json' );
$dbCfg		= file_get_json( 'config/database.json' );
$metatags	= file_get_json( 'config/meta.json' );

$files		= [];
$db			= FALSE;

initDatabase( $db, $cfg['database']['file_name'], $dbCfg );

getImagesRecursive( $GLOBALS['cfg']['data']['data_root'], $GLOBALS['cfg']['data']['image_ext'], $files, ['jpg'] );
debug( $files );

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

//----------------------------------------------------------------------

verbose( '// Write meta data for each file' );
// Update ALL
$count	= 0;
$r  = $db->exec( "BEGIN TRANSACTION;" );

foreach ( $files as $path )
{
	['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
	$file	= "$dirname/$basename";
	$note	= "";
	++$count;
	debug( $file );

	// Get image dimentions
	list($width, $height, $type, $attr) = getimagesize($file);

	// Get EXIF
	$exif 		= exif_read_data( $file, 0, true);
	$exifjson 	= SQLite3::escapeString( json_encode( $exif,  JSON_INVALID_UTF8_IGNORE ) );
	debug($exifjson);

	// Get IPTC
	$iptc		= parseIPTC( $file );
    $iptcjson 	= SQLite3::escapeString( json_encode( $iptc,  JSON_INVALID_UTF8_IGNORE ) );
	debug($iptcjson);

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
				$iptc[$key][0]	.= "UTF-8";
		}
		$iptc[ $iptcHeaderArray[$key]['tag'] ] = $iptc[$key];
		unset($iptc[$key]);
	}
	//file_put_contents( "$file.json", var_export($iptc, TRUE)  );
	return( $iptc );
}	// parseIPTC

/*
CREATE TABLE IF NOT EXISTS images (
    filename    TEXT not null,
    thumb       TEXT,    -- BLOB
    display     TEXT,    -- BLOB,
    PRIMARY KEY (file)
);
CREATE INDEX idx_images( file );

CREATE TABLE IF NOT EXISTS meta (
    file    TEXT not null,
    exif    TEXT,
    iptc    TEXT,
    PRIMARY KEY (file)
);
CREATE INDEX idx_meta( file );


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
    //array_push( $result, $r );

}

//----------------------------------------------------------------------

function ___( $key, $lang = 'en' )
{
	return( $GLOBALS['local'][$key][$lang] );
}
//----------------------------------------------------------------------

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
}	// getImagesRecursive()

//----------------------------------------------------------------------

?>