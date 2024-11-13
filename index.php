	<?php
/**
 *   @file       index.php
 *   @brief      Gallery Viewer
 *   @details    
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T06:14:19 / ErBa
 *   @version    2024-11-13T00:12:12
 */

echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="config/styles.css">
</head>
<body>
';
$releaseroot	= __DIR__ . '/';
include_once( 'lib/handleJson.php');
include_once( 'lib/handleSqlite.php');
include_once( 'lib/debug.php');

//$debug=1;

$cfg		= file_get_json( 'config.json' );
$local		= file_get_json( 'local.json' );
$dbCfg		= file_get_json( 'database.json' );

$db	= openSqlDb($cfg['database']['file_name']);

if ( empty( $_REQUEST['path']) )
	$_REQUEST['path'] = '%';


echo "<span title='Subdirs to'>&#x1F4C2; {$_REQUEST['path']}</span>:\n";
$dirs	= querySql( $db, "SELECT DISTINCT path FROM images WHERE path LIKE '{$_REQUEST['path']}%'");
$tree	= buildDirTree( $dirs );
verbose( "<br>tree:<br>" );
debug( $tree );


$subdirs	= subdirsToCurrent( array_unique($tree), $_REQUEST['path'] );

foreach( $subdirs as $subdir )
{
	$dir	= pathinfo( $subdir, PATHINFO_BASENAME);
	echo "<a href='?path=$subdir'>&#x1F4C1;$dir</a> ";
}


echo "<p>Breadcrumps: ";
$crumbs	= breadcrumbs( $_REQUEST['path'] );
$trail	= [];
// Ignore first and last element in list
foreach( array_splice($crumbs, 1, -1 ) as $crumb => $crumbtag )
{
	$trail[] = "<a href='?path=$crumb'>[$crumbtag]</a>";
}
echo implode( "&rightarrow;", $trail );


echo "</pre><br clear=both><hr>";

if( empty($_REQUEST['show']) )
{
	$sql 	= "SELECT path, file, thumb FROM IMAGES WHERE path like '{$_REQUEST['path']}'";
	debug( $sql );
	$files	= querySql( $db, $sql );
	debug( "Files:<pre>" );
	debug($files);
	debug( "</pre>" );
	foreach ( $files as $no => $filedata )
	{
		echo show_thumb( $filedata );
	}

}
else
{
	$prev	= $next	= FALSE;
	$sql 	= "SELECT path, file FROM IMAGES WHERE path like '{$_REQUEST['path']}'";
	debug( $sql );
	$files	= querySql( $db, $sql );
	debug("Files:<pre>");
	debug($files);
	debug("</pre>" );

	foreach ( $files as $no => $filedata )
	{
		if ( $_REQUEST['show'] == $filedata['file'] )
		{
			debug( $no );
			if ( 0 < $no )
				$prev	= $files[$no-1]['file'];
			if ( count( $files )-1 > $no )
				$next	= $files[$no+1]['file'];
		}
	}
	$sql 	= "SELECT path, file, display FROM IMAGES WHERE path like '{$_REQUEST['path']}' AND file like '{$_REQUEST['show']}'";
	debug( $sql );
	//echo "<br>\n";
	$file	= querySql( $db, $sql );
	debug("Files:<pre>");
	debug($file[0]);
	debug("</pre>" );
	//echo $prev ? "prev: $prev" : "no prev: $prev";
	echo $prev ? "[<a href='?path={$_REQUEST['path']}&show=$prev'>prev</a>] " : "[<s>prev</s>] $prev ";
	echo $next ? "[<a href='?path={$_REQUEST['path']}&show=$next'>next</a>] " : "[<s>next</s>] $next ";
	echo "[<a href='?path={$_REQUEST['path']}'>Close</a>] ";
	echo show_image( $file[0] );
}

//----------------------------------------------------------------------

function breadcrumbs( $path )
{
	$trail	= [];
	$token	= '';
	foreach( explode( '/', $path ) as $dir )
	{
		$token		.= "/$dir";
		//$trail[]	= trim( $token, '/' );
		$trail[trim( $token, '/')]	= $dir;
	}
	return( $trail  );
}	// breadcrumbs()

//----------------------------------------------------------------------

function buildDirTree( $dirs )
{
	$tree		= [];
	// 0->path=>dir 0->dir
	foreach( $dirs as $no => $dirinfo )
		$dirs2[] = $dirinfo['path'];

	foreach ( $dirs2 as $dir )
	{
		$dirlist	= explode( '/', $dir );
		$stub	= '';
		foreach ($dirlist as $d)
		{
			$stub	.= "$d/";
			if ( ! isset($tree[$stub]) )
				$tree[] = rtrim( $stub, '/' );
		}
	}
	return( array_unique($tree) );
}

//----------------------------------------------------------------------

function subdirsToCurrent( $haystack, $current )
{
	$pattern	= '/^' . SQLite3::escapeString( str_replace( '/', '\/', $current ) ) . '\/[^\/]*$/i';
	$matches  = preg_grep( $pattern, array_values($haystack) );
	return($matches);
}

//----------------------------------------------------------------------

/*

Implement as figure

<figure class="getrecordfigure" title="{path}{file}">
<img 
	class="figureimage" 
	src="{path}/{thumb}" 
	onerror="this.onerror=null;this.title+=this.src;this.src=&quot;icons/no_cover_available.jpg&quot;" 
	onclick="call_sub( 'setSession', 'recno=1&amp;rowno=32' );  "
	> 
<br clear="both">
<figcaption class="getrecordfigurecaption">
	{file}
</figcaption>
</figure>


*/
function show_thumb( $filedata )
{
	$output	= '';
	global $db;
	$meta	= querySql( $db, "SELECT exif, iptc FROM meta WHERE file = '{$filedata['file']}' AND path = '{$filedata['path']}'");
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );

	//$output	.= sprintf( "%s<a href='?path={$filedata['path']}&show={$filedata['file']}'><img class='cover' src='data:jpg;base64, %s' style='float: right;' title='%s'></a>"
	$output	.= sprintf( "<figure style='float: left;border=1;' width=32px><figcaption><small>%s</small></figcaption><a href='?path={$filedata['path']}&show={$filedata['file']}'><img class='cover' src='data:jpg;base64, %s' title='%s'></a></figure>"
	//,	$filedata['path'] . '/<br>' . $filedata['file']
	,	$filedata['file']
	,	$filedata['thumb']
	,	$filedata['path'] . '/' . $filedata['file'] 
	);
/*
	// EXIF
	$output	.= "<details><summary>EXIF</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";
	// IPTC
	$iptc	= json_decode( $meta[0]['iptc'] ?? "", TRUE );
	$output	.= "<details><summary>IPTC</summary><pre>";
	$output	.= var_export( $iptc, TRUE );
	$output	.= "</pre></details>";
*/
	
	return( $output );
}
//----------------------------------------------------------------------

function show_image( $filedata )
{
	$output	= '';
	global $db;
	debug( $filedata['file']);
	debug( $filedata['path'] );
	
	$meta	= querySql( $db, "SELECT exif, iptc FROM meta WHERE file = '{$filedata['file']}' AND path = '{$filedata['path']}'");
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );

	$output	.= sprintf( "<small>%s</small><img class='display' src='data:jpg;base64, %s' title='%s'>"
	,	$filedata['path'] . '/ ' . $filedata['file']
	,	$filedata['display']
	,	$filedata['path'] . '/' . $filedata['file'] 
	);

	// EXIF
	$output	.= "<details><summary>&#x1F5BB;EXIF</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";

	// IPTC
	$iptc	= json_decode( $meta[0]['iptc'] ?? "", TRUE );
	$output	.= "<details><summary>&#x1F5BA;IPTC</summary><pre>";
	$output	.= var_export( $iptc, TRUE );
	$output	.= "</pre></details>";

	// Flag
	$flag	= $iptc['Country-PrimaryLocationCode '][0] ?? '00';
	$output	.= "<img "
	.	"src='config/.flags/{$flag}.svg' "
	.	"onerror=\"this.onerror=null; this.className='flag_mini'; if (this.src != 'config/.flags/ZZ.svg') this.src = 'config/.flags/ZZ.svg'; \" "
	.	"class='flag' "
	.">";

	$output	.= "<br clear=both><hr>";
	
	return( $output );
}

//----------------------------------------------------------------------
?>