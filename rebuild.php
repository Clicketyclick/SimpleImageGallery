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

include_once('lib/imageResize.php');
include_once('lib/handleSqlite.php');
include_once('lib/debug.php');
$GLOBALS['verbose']	= 1;
//$GLOBALS['debug']	= 1;

$cfg		= json_get_contents( 'config/config.json' );
$local		= json_get_contents( 'config/local.json' );
$dbCfg		= json_get_contents( 'config/database.json' );
$metatags	= json_get_contents( 'config/meta.json' );
$files	= [];
$db		= FALSE;

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
		$note	.= "Thumb build";
		$thumb =  getResizedImage( $file, 100,100 );
	}
	
	// Rotate EXIF
	if ( 8 == ( $exif['IFD0']['Orientation'] ?? 0 ) )
	{
		//verbose( '-- rotating');
		file_put_contents( "$count.thumb.jpg", $thumb );
		rotateImage( "$count.thumb.jpg", 90, $count. '.rotated.jpg' );
		$thumb	= file_get_contents( $count. '.rotated.jpg' );
	}

	$thumb 		= base64_encode( $thumb );
	//$view 		= base64_encode(exif_thumbnail( $file ));

	// Read image path, convert to base64 encoding
	$view 		= base64_encode( getResizedImage( $file) );
	// Update thumb and view
	debug( sprintf( $dbCfg['sql']['replace_into_images'], $dirname,$basename, $thumb , $view )  );
	$r  = $db->exec( sprintf( $dbCfg['sql']['replace_into_images'], $dirname,$basename, $thumb , $view ) );
	// Update meta
	debug( sprintf($dbCfg['sql']['replace_into_meta'], $dirname,$basename, $exifjson, $iptcjson )  );
	$r  = $db->exec( sprintf($dbCfg['sql']['replace_into_meta'], $dirname,$basename, $exifjson, $iptcjson ) );

	fprintf( STDOUT, "- [%-35.35s] [%s] %sx%s %s %s %s\n"
	,	$exif['FILE']['FileName']
	,	date( 'c', $exif['FILE']['FileDateTime'])
	,	$exif['COMPUTED']['Width']
	,	$exif['COMPUTED']['Height']
	,	$exif['FILE']['MimeType']
	,   $count . ':'. ( $exif['IFD0']['Orientation'] ?? '?')
	,	$note
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
		$db	= createSqlDb($dbfile);
		$r  = $db->exec( $dbCfg['sql']['create_images'] );
		$r  = $db->exec( $dbCfg['sql']['create_meta'] );
	}
	else
	{
		$db	= openSqlDb($dbfile);
	}
    //array_push( $result, $r );

}

/**
 *  @fn        openSqlDb()
 *  @brief     Open or create database
 *  
 *  @details   Wrapper for SQLite3::open()
 *  
 *  @param [in] $dbfile Path and name of database file to open
 *  @return     File handle to database OR FALSE
 *  
 *  @example   $db = openSqlDb( "./my.db" );
 *  
 *  @todo     
 *  @bug     
 *  @warning    An empty database IS valid, but issues a warning
 *  
 *  @see
 *  @since      2019-12-11T07:43:08
 */
function _openSqlDb( $dbfile ) {
    if ( ! file_exists($dbfile) )
    {
        trigger_error( ___('database_not_found'). " [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    if ( ! filesize($dbfile) )
    {
        trigger_error( ___('database_is_empty')." [$dbfile] " . var_export( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2), TRUE ), E_USER_WARNING );
    }
    
    $db = new SQLite3( $dbfile );
    if ( ! $db ) {
        trigger_error( ___('database_not_open')." [$dbfile]", E_USER_ERROR );
        return( FALSE );
    }
    return( $db );
}   // openSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        createSqlDb()
 *  @brief     Create new database if not exists
 *  
 *  @details   Wrapper for SQLite3::open()
 *  
 *  @param [in] $dbfile Path and name of database file to create
 *  @return     File handle to database OR FALSE
 *  
 *  @example   $db = createSqlDb( "./my.db" );
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2024-04-11 13:19:44
 */
function _createSqlDb( $dbfile ) {
    if ( file_exists($dbfile) )
    {
        trigger_error( ___('database_already_exists')." [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    
    $db = new SQLite3( $dbfile );
    if ( ! $db ) {
        trigger_error( ___('database_not_open')." [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    return( $db );
}   // createSqlDb()

//---------------------------------------------------------------------
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

function json_get_contents( $file )
{
	if ( file_exists( $file ) )
	{
		$json	= json_decode( file_get_contents( $file ), TRUE );
	}
	return( $json ?? FALSE );
}	// json_get_contents

//----------------------------------------------------------------------

function json_put_contents( $file, $json )
{
	return( file_put_contents( $file, json_encode( $json, JSON_PRETTY_PRINT ) ) );
}	// json_put_contents
