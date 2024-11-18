<?php
/**
 *   @file       index.php
 *   @brief      Gallery Viewer
 *   @details    Listing images with recursive browsing
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T06:14:19 / ErBa
 *   @version    @include version.txt
 */

echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="config/styles.css">

<script>
//>>> Keybord events
	document.onkeydown = function (e) { 
		e = e || window.event; 
		var charCode = e.charCode || e.keyCode, 
			character = String.fromCharCode(charCode); 

	  //console.log(character+"_"+charCode);
	  
	  switch(charCode) {
			case 37:// Left / Previous
				if (typeof(prev_image) === typeof(Function) )
					prev_image();
				break;
			case 38:// Up / Close
				// Close
				if (typeof(close_image) === typeof(Function) )
					close_image();
				break;
			case 39:// Right / Next
				if (typeof(next_image) === typeof(Function) )
					next_image();
				break;
			default:
				// code block
		}
	  
	};
//<<< Keybord events

</script>
</head>
<body>
';

$releaseroot	= __DIR__ . '/';
include_once( 'lib/handleJson.php');
include_once( 'lib/handleSqlite.php');
include_once( 'lib/debug.php');
include_once( 'lib/map.php');

$verbose=1;
//$debug=1;

$cfg		= file_get_json( 'config/config.json' );
$local		= file_get_json( 'config/local.json' );
$dbCfg		= file_get_json( 'config/database.json' );

$db	= openSqlDb($cfg['database']['file_name']);

// Default path = all
if ( empty( $_REQUEST['path']) )
	$_REQUEST['path'] = '%';

//"SELECT DISTINCT path FROM images WHERE path LIKE '%s%%'"
$sql	= sprintf( $dbCfg['sql']['select_path'], $_REQUEST['path']  );
$dirs	= querySql( $db, $sql );

$tree	= buildDirTree( $dirs );
debug( $tree, 'tree' );


// Build breadcrumb trail: 'crumb1/crumb2/file" => [crumb1] -> [crumb2] 
echo "Breadcrumbs: ";
echo breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' );
echo '/'. basename($_REQUEST['path']);
echo "<br>";

// Get subdirectories to current directory
$subdirs	= subdirsToCurrent( array_unique($tree), $_REQUEST['path'] );

foreach( $subdirs as $subdir )
{
	$dir	= pathinfo( $subdir, PATHINFO_BASENAME);
	//echo "<a href='?path=$subdir'>&#x1F4C1;$dir</a> ";
	
	
	// get newest image 	"newest_picture_in_path"
	$sql	= sprintf( $dbCfg['sql']['newest_picture_in_path'], $_REQUEST['path'] .'/'. $dir );
	//var_export($sql);//exit;
	$newestthumb	= querySql( $db, $sql );
	$newestthumb	= $newestthumb[0];
//var_export($newestthumb['file']);exit;
//var_export($newestthumb);exit;
	
	
	printf( "
	<figure class='subfolder'>
		<figcaption>&#x1F4C1;<small>%s</small></figcaption>
		<a href='?path={$subdir}'>
			<img class='subfolder_icon' src='data:jpg;base64, %s' title='%s'>
		</a>
	</figure>"
	//,	$newestthumb['file']
	,	$dir
	,	$newestthumb['thumb']
	,	$newestthumb['path'] . '/' . $newestthumb['file'] 
	);
	
	
	
	//echo "<a href='?path=$subdir'>&#x1F4C1;$dir</a> ";
	
}

echo "</pre><br clear=both><hr>";

if( empty($_REQUEST['show']) )
{	// Thumb list
	//$sql 	= "SELECT path, file, thumb FROM IMAGES WHERE path like '{$_REQUEST['path']}'";
	$sql	= sprintf($dbCfg['sql']['select_thumb'], $_REQUEST['path'] );
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
{	// Show image
	$prev	= $next	= FALSE;
	//$sql 	= "SELECT path, file FROM IMAGES WHERE path like '{$_REQUEST['path']}'";
	$sql	= sprintf($dbCfg['sql']['select_path_file'], $_REQUEST['path'] );
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
	//$sql 	= "SELECT path, file, display FROM IMAGES WHERE path like '{$_REQUEST['path']}' AND file like '{$_REQUEST['show']}'";
	$sql	= sprintf($dbCfg['sql']['select_display'], $_REQUEST['path'], $_REQUEST['show'] );
	debug( $sql );

	$file	= querySql( $db, $sql );
	debug("Files:<pre>");
	debug($file[0]);
	debug("</pre>" );
	// Previous
	echo $prev ? "[<a href='?path={$_REQUEST['path']}&show=$prev'>prev</a>] 
	<script>
		function prev_image() { 
			console.log( '?path={$_REQUEST['path']}&show=$prev' ); 
			document.location.href = '?path={$_REQUEST['path']}&show=$prev';
		}
	</script>
	" : "[<s>prev</s>] $prev  
	<script>
		function prev_image(){
			console.log('skip prev')
		}
	</script>";
	// Next
	echo $next ? "[<a href='?path={$_REQUEST['path']}&show=$next'>next</a>]
	<script>
		function next_image() { 
			console.log( '?path={$_REQUEST['path']}&show=$next' ); 
			document.location.href = '?path={$_REQUEST['path']}&show=$next';
		}</script>
	" : "[<s>next</s>] $next 
	<script>
		function next_image() {
			console.log('skip next')
		}
	</script>";
	// Close
	echo "[<a href='?path={$_REQUEST['path']}'>Close</a>] 
	<script>
		function close_image() { 
			console.log( '?path={$_REQUEST['path']}&show=$next' ); 
			document.location.href = '?path={$_REQUEST['path']}';
		}</script>
	";
	echo show_image( $file[0] );
}

//----------------------------------------------------------------------

/**
 *   @brief      Build breadcrumb trail
 *   
 *   @param [in]	$path		Path to break up
 *   @param [in]	$urlstub='?path=%s'	URL stub to crumbs
 *   @param [in]	$start=1	Start dir
 *   @param [in]	$end=-1		End dir
 *   @param [in]	$delimiter='&rightarrow;'	Delimiter between crumbs [Default: "&rightarrow;"]
 *   @return     Trail as HTML string
 *   
 *   @details    'crumb1/crumb2/file" => [crumb1] -> [crumb2] 
 *
 *   @since      2024-11-13T14:15:32
 */
function breadcrumbTrail( $path, $urlstub = '?path=%s', $start = 1, $end = -1, $delimiter = '&rightarrow;')
{
	$crumbs	= breadcrumbs( $path );
	$trail	= [];
	// Ignore first and last element in list
	foreach( array_splice($crumbs, $start, $end ) as $crumb => $crumbtag )
	{
		//$trail[] = "<a href='?path=$crumb'>[$crumbtag]</a>";
		$trail[] = sprintf( "<a href='{$urlstub}'>[%s]</a>"
		,	$crumb
		,	$crumbtag
		);
	}
	return( implode( $delimiter, $trail ) );
}	//breadcrumbTrail()



//----------------------------------------------------------------------


/**
 *   @brief      Build a breadcrumb trail from a file path
 *   
 *   @param [in]	$path	$(description)
 *   @return     $(Return description)
 *
 *   @since      2024-11-13T14:10:11
 */
function breadcrumbs( $path )
{
	$trail	= [];
	$token	= '';
	foreach( explode( '/', $path ) as $dir )
	{
		$token		.= "/$dir";
		$trail[trim( $token, '/')]	= $dir;
	}
	return( $trail  );
}	// breadcrumbs()

//----------------------------------------------------------------------


/**
 *   @brief     Build directory tree
 *   
 *   @param [in]	&$dirs	Root
 *   @return     array of directory names
 *   
 *   @details    Recursive scandir
 *   
 *   
 *   @see        https://
 *   @since      2024-11-15T01:24:07
 */
function buildDirTree( &$dirs )
{
	$tree		= [];
	$dirs2		= [];
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


/**
 *   @brief      Get subdirectories to current directory
 *   
 *   @param [in]	$haystack	Haystack of directories
 *   @param [in]	$current	Current directory
 *   @return     List of subdirectories to current directory
 *   
 *   @since      2024-11-15T01:29:30
 */
function subdirsToCurrent( $haystack, $current )
{
	$pattern	= '/^' . SQLite3::escapeString( str_replace( '/', '\/', $current ) ) . '\/[^\/]*$/i';
	$matches  = preg_grep( $pattern, array_values($haystack) );
	return($matches);
}

//----------------------------------------------------------------------

/**
 *   @brief      Build thumb display
 *   
 *   @param [in]	$filedata	Source file
 *   @return     HTML figure
 *   
 *   @since      2024-11-15T01:31:11
 */
function show_thumb( $filedata )
{
	global $dbCfg;
	global $db;
	$output	= '';

	$sql	= sprintf( $dbCfg['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );

	$output	.= sprintf( "
	<figure style='float: left;border=1;' width=32px>
		<figcaption><small>%s</small></figcaption>
		<a href='?path={$filedata['path']}&show={$filedata['file']}'>
			<img class='cover' src='data:jpg;base64, %s' title='%s'>
		</a>
	</figure>"
	,	$filedata['file']
	,	$filedata['thumb']
	,	$filedata['path'] . '/' . $filedata['file'] 
	);

	return( $output );
}
//----------------------------------------------------------------------


/**
 *   @brief      Build image display
 *   
 *   @param [in]	$filedata	Source file
 *   @return     HTML figure
 *   
 *   
 *   @since      2024-11-15T01:32:29
 */
function show_image( $filedata )
{
	global $cfg;
	$output	= '';
	global $db;
	global $dbCfg;
	debug( $filedata['file']);
	debug( $filedata['path'] );
	
	//$meta	= querySql( $db, "SELECT exif, iptc FROM meta WHERE file = '{$filedata['file']}' AND path = '{$filedata['path']}'");
	//$meta	= querySql( $db, "SELECT exif, iptc FROM images WHERE file = '{$filedata['file']}' AND path = '{$filedata['path']}'");
	$sql	= sprintf( $dbCfg['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	debug( $sql );
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );
	$iptc	= json_decode( $meta[0]['iptc'] ?? "", TRUE );

	$output	.= sprintf( "<br><small></small><img class='display' src='data:jpg;base64, %s' title='%s'>"
	//,	$filedata['path'] . '/ ' . $filedata['file']
	,	$filedata['display']
	,	$filedata['path'] . '/' . $filedata['file'] 
	);

	// Header
	if($iptc)
	{
		$headline	= $iptc['Headline'][0] ?? '...';
		$output .= "<span class='headline'>{$headline}</span><br>";
		$caption	= $iptc['Caption-Abstract'][0] ?? '';

		$output .= $caption;
		$supcat	= implode( ', ', $iptc['SupplementalCategories'] ?? ['']);

		$output .= "<br><small>"
		.	$supcat
		.	"</small>";
	}
	// EXIF
	$output	.= "<details><summary>&#x1F5BB;EXIF</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";

	// IPTC
	$output	.= "<details><summary>&#x1F5BA;IPTC</summary><pre>";
	$output	.= var_export( array_flatten2($iptc), TRUE );
	$output	.= "</pre></details>";

	// Flag
	$flag	= $iptc['Country-PrimaryLocationCode'][0] ?? '00';
	$output	.= "<img "
	.	"src='config/.flags/{$flag}.svg' "
	.	"onerror=\"this.onerror=null; this.className='flag_mini'; if (this.src != 'config/.flags/ZZ.svg') this.src = 'config/.flags/ZZ.svg'; \" "
	.	"class='flag' "
	.">";

	if ( ! empty($exif['GPS']["GPSLongitude"]) )
	{
		$lon = getGps($exif['GPS']["GPSLongitude"], $exif['GPS']['GPSLongitudeRef']);
		$lat = getGps($exif['GPS']["GPSLatitude"], $exif['GPS']['GPSLatitudeRef']);
		$zoom	= $_REQUEST['zoom'] ?? 15;

		echo getMapLink( $lat, $lon, $zoom, $cfg['maps']['map_source'] );
		echo " @ $lon,$lat<br>";
		echo getMapEmbed( $lat, $lon, $zoom, $cfg['maps']['map_source'] );
	}
	$output	.= "<br clear=both><hr>";
	
	return( $output );
}

//----------------------------------------------------------------------

/**
 *   @brief      reduce complexity of array
 *   
 *   @param [in]	)	$(description)
 *   @return     $(Return description)
 *   
 *   @details    Reduce sub arrays with only one entry to string
 *   
@code
$a	=' {"Multi entries":["entry1","entry2"],"Single entry":["Just one"]}';
$b	= json_decode( $a, TRUE, 512, JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE );

var_export( $b );
echo PHP_EOL;
var_export( array_flatten2($b) );
@endcode

@verbatim
array (
  'Multi entries' =>
  array (
    0 => 'entry1',
    1 => 'entry2',
  ),
  'Single entry' =>
  array (
    0 => 'Just one',
  ),
)
array (
  'Multi entries' =>
  array (
    0 => 'entry1',
    1 => 'entry2',
  ),
  'Single entry' => 'Just one',
)
@endverbatim
 *   
 *   @since      2024-11-14T13:39:47
 */
function array_flatten2( $arr, $out=array() )  {
	foreach( $arr as $key => $item ) {
		if ( is_array( $item ) && 1 < count( $item ) ) {
			$out[$key] = $item;
		} else {
			$out[$key] = $item[0];
		}
	}
	return $out;
}	// array_flatten2()

//----------------------------------------------------------------------

?>