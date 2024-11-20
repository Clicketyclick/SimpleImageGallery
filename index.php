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
/*
session_name('SimpleImageGallery_'.trim(file_get_contents('version.txt')) ?? '' );
session_start();
*/
include_once('lib/_header.php');



echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="config/styles.css">

<script>
home=\'.\';

//>>> Keybord events
	document.onkeydown = function (e) { 
		e = e || window.event; 
		var charCode = e.charCode || e.keyCode, 
			character = String.fromCharCode(charCode); 

	  //console.log(character+"_"+charCode);
	  
	  switch(charCode) {
			case 36:// Home
				// Home
				if (typeof(close_image) === typeof(Function) )
                    close_image( home );
				break;
			case 37:// Left / Previous
				if (typeof(prev_image) === typeof(Function) )
					prev_image( path, prev );
				break;
			case 38:// Up / Close
				// Close
				if (typeof(close_image) === typeof(Function) )
					close_image( path );
				break;
			case 39:// Right / Next
				if (typeof(next_image) === typeof(Function) )
					next_image( path, next );
				break;
			default:
				// code block
                console.log(charCode);
		}
	  
	};
//<<< Keybord events


function prev_image( path, prev ) { 
    console.log( "?path="+path+"&show="+prev ); 
    document.location.href = "?path="+path+"&show="+prev;
}   // prev_image()

function close_image( path ) { 
    console.log( "?path="+path ); 
    document.location.href = "?path="+path;
}   // close_image()

function next_image( path, next ) { 
    console.log( "?path="+path+"&show="+next ); 
    document.location.href = "?path="+path+"&show="+next;
}   // function next_image()

</script>
</head>
<body>
';

debug( $_SESSION['browser']['language'], "session language: ");

$releaseroot	= __DIR__ . '/';
include_once( 'lib/handleJson.php');
include_once( 'lib/handleSqlite.php');
include_once( 'lib/debug.php');
include_once( 'lib/map.php');

$verbose=1;
//$debug=1;
/*
$cfg		= file_get_json( 'config/config.json' );
$local		= file_get_json( 'config/local.json' );
$dbCfg		= file_get_json( 'config/database.json' );
*/

// Default path = all
if ( empty( $_REQUEST['path']) )
	$_REQUEST['path'] = '.';


$db	= openSqlDb( $_REQUEST['db'] ?? $_SESSION['config']['database']['file_name']);

//"SELECT DISTINCT path FROM images WHERE path LIKE '%s%%'"
$sql	= sprintf( $_SESSION['database']['sql']['select_path'], $_REQUEST['path']  );
$dirs	= querySql( $db, $sql );

$tree	= buildDirTree( $dirs );
debug( $tree, 'tree' );


// Build breadcrumb trail: 'crumb1/crumb2/file" => [crumb1] -> [crumb2] 
echo "<span title='".___('breadcrumptrail')."'>". $_SESSION['config']['display']['breadcrumptrail'] . "</span>";
echo breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' );
echo '/'. basename($_REQUEST['path']);
echo "<script>path='". dirname( $_REQUEST['path'] ) . "';</script>";

// Change language https://stackoverflow.com/a/22040376/7485823
$url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$url = preg_replace("/&browser:language=../", "", $url);
$escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
echo "<a class='translate' href='{$escaped_url}&browser:language="
.   (
        'en' == $_SESSION['browser']['language'] ? 'da' : 'en'
    )
.   "' title='". ___('shift_language') ."'>A文</span></a>";

// Get subdirectories to current directory
$subdirs	= subdirsToCurrent( array_unique($tree), $_REQUEST['path'] );

foreach( $subdirs as $subdir )
{
	$dir	= pathinfo( $subdir, PATHINFO_BASENAME);

	// get newest image 	"newest_picture_in_path"
	$sql	= sprintf( $_SESSION['database']['sql']['newest_picture_in_path'], $_REQUEST['path'] .'/'. $dir );

	$newestthumb	= querySql( $db, $sql );
	$newestthumb	= $newestthumb[0];

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
}

echo "</pre><br clear=both><hr>";

if( empty($_REQUEST['show']) )
{	// Thumb list
	$sql	= sprintf($_SESSION['database']['sql']['select_thumb'], $_REQUEST['path'] );
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
	$sql	= sprintf($_SESSION['database']['sql']['select_path_file'], $_REQUEST['path'] );
	debug( $sql );

	$files	= querySql( $db, $sql );
	debug("Files:<pre>");
	debug($files);
	debug("</pre>" );
	foreach ( $files as $no => $filedata )
	{
		if ( $_REQUEST['show'] == $filedata['file'] )
		{
            debug( "count: "
            .   count( $files ) 
            .   " no: "
            .   $no 
            .   "<pre>"
            .   var_export($files, TRUE )
            .   "</pre>"
            );
			if ( 0 < $no )
				$prev	= $files[$no-1]['file'];
			if ( count( $files )-1 > $no )
				$next	= $files[$no+1]['file'];
            
            echo( ($no+1).'/'.count( $files ) );
		}
	}

	$sql	= sprintf($_SESSION['database']['sql']['select_display'], $_REQUEST['path'], $_REQUEST['show'] );
	debug( $sql );

	$file	= querySql( $db, $sql );
	debug("Files:<pre>");
	debug($file[0]);
	debug("</pre>" );

	// Previous
    $prev_active_button = $prev ? "" : " disabled";
    $next_active_button = $next ? "" : " disabled";
	echo "<button 
        id='prevButton' 
        class='float-left submit-button' 
        onclick = 'prev_image( \"{$_REQUEST['path']}\", \"$prev\" );' 
        title='".___('prev_image')."'
        {$prev_active_button}
    ><big>&#x2BAA;</big></button>
    <script>
        path=\"{$_REQUEST['path']}\";
        prev=\"$prev\";
        next=\"$next\";
    </script>
	";
    
	// Close
	echo "<button id='prevButton' class='float-left submit-button' onclick = 'close_image(\"{$_REQUEST['path']}\");'  title='".___('up_to_index')."'><big>&#x2BAC;</big></button>";
    
	// Next
	echo "<button 
        id='nextButton' 
        class='float-left submit-button' 
        onclick = 'next_image( \"{$_REQUEST['path']}\", \"$next\" );' 
        title='".___('next_image')."'
        {$next_active_button}
    ><big>&#x2BAB;</big></button>
    ";
	echo show_image( $file[0] );
}

printf( "<br clear=both><hr><small>{$_SESSION['config']['display']['copyright']} - <a href='{$_SESSION['config']['display']['home_url']}'>{$_SESSION['config']['display']['app_name']}</a></small>", date('Y'));

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
	global $db;
	$output	= '';

	$sql	= sprintf( $_SESSION['database']['sql']['select_meta'], $filedata['file'], $filedata['path'] );
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
	$output	= '';
	global $db;
	debug( $filedata['file']);
	debug( $filedata['path'] );
	
	$sql	= sprintf( $_SESSION['database']['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	debug( $sql );
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );
	$iptc	= json_decode( $meta[0]['iptc'] ?? "", TRUE );

	$output	.= sprintf( "<br><small></small><img class='display' src='data:jpg;base64, %s' title='%s'>"
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
	$output	.= "<details><summary title='".___('exif')."'>&#x1F5BB;EXIF</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";

	// IPTC
	$output	.= "<details open><summary title='".___('iptc')."'>&#x1F5BA;IPTC</summary><table border=1>";
    foreach ( array_flatten2($iptc) as $iptc_key => $itpc_value )
    {
        if ( 'CodedCharacterSet' == $iptc_key ) continue;
        $output	.= "<tr><td class='iptc_key'>".___("iptc_{$iptc_key}"). "</td><td class='iptc_value'>". (
        ( 'array' == gettype($itpc_value) ) ? implode( ' ; ', $itpc_value) : $itpc_value )
        .   "</td></tr>\n";
    }
	$output	.= "</table></details>";


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

		echo getMapLink( $lat, $lon, $zoom, $_SESSION['config']['maps']['map_source'] );
		echo " @ $lon,$lat<br>";
		echo getMapEmbed( $lat, $lon, $zoom, $_SESSION['config']['maps']['map_source'] );
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

function shutdown()
{
    //echo "Goodnight";
}
?>