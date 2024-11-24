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

// Include script specific shutdown function. BEFORE _header.php !
include_once( 'lib/'.basename(__FILE__,".php").'.shutdown.php');

include_once('lib/_header.php');

echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/display.js"></script>
  <link rel="icon" type="image/x-icon" href="{$_SESSION[\'config\'][\'system\'][\'favicon\']}">
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
//$trail  = breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' ) ;
$trail  = breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' ) ;
echo ( empty($trail) ? "<span title='".___('empty_breadcrumb_trail')."'>." : $trail ) ;
echo '/'. basename($_REQUEST['path']);
echo "</span><script>path='". dirname( $_REQUEST['path'] ) . "';</script>";


// Change language https://stackoverflow.com/a/22040376/7485823
// https://stackoverflow.com/a/22040376
$url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$url = preg_replace("/&browser:language=../", "", $url);
$escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
echo "<a class='translate' href='{$escaped_url}&browser:language="
.   (
        'en' == $_SESSION['browser']['language'] ? 'da' : 'en'
    )
.   "' title='". ___('shift_language') ."'>A文</span></a>";

// Database name on top
echo "<span class='db_name'>{$_SESSION['config']['database']['file_name']}</span>";

//----------------------------------------------------------------------

// Clear before folders
echo "<br clear=both><hr>";

// Get subdirectories to current directory
$subdirs	= subdirsToCurrent( array_unique($tree), $_REQUEST['path'] );
$count_subdirs  = count($subdirs );
debug($subdirs);
printf( "%s: %s<br>"
,   ___('no_of_subdirs')
,   $count_subdirs
);
$count  = 0;

//echo "<pre>";
$sql	= sprintf( $_SESSION['database']['sql']['thumb_from_dirs'], implode( "', '", $subdirs ) );
debug($sql, 'thumb_from_dirs');
$thumbs	= querySql( $db, $sql );

foreach( $thumbs as $no => $thumbdata )
{
    unset ( $thumbs[$no] );
    $thumbs[$thumbdata['path']] = $thumbdata['thumb'];
}

debug($thumbs, 'thumbs');

foreach( $subdirs as $subdir )
{
    logging( "Start: $subdir");
    $_SESSION['timers'][$subdir]['start']	= microtime(TRUE);
    
	$dir	= $_REQUEST['path'] .'/'. pathinfo( $subdir, PATHINFO_BASENAME);
    debug( $dir);
	// get newest image 	"newest_picture_in_path"
    /*
    $sql	= sprintf( $_SESSION['database']['sql']['thumb_from_dirs'], $dir );
    var_export($sql);
	$thumb	= querySql( $db, $sql );
    */
    //if ( 'array' == gettype($thumb) )
    if ( empty($thumbs[$subdir]) )
    {
        logging( 'newest_picture_in_path' );
        $sql	= sprintf( $_SESSION['database']['sql']['newest_picture_in_path'], $dir );
        $newestthumb	= querySql( $db, $sql );
        $newestthumb	= $newestthumb[0];
    }
    else
    {
        logging( 'dirs' );
        //$newestthumb['thumb']	= $thumb;
        $newestthumb['thumb']	= $thumbs[$subdir];
        $newestthumb['file']	= '$dir';
    }
    
    $newestthumb['path']    = $dir;

    // Print figure for directories
    echo show_thumb( $newestthumb, TRUE );
    
    logging( progress_log( ++$count, $count_subdirs, $_SESSION['timers'][$subdir]['start'], 1 ) );
}
echo "</pre><br clear=both><hr>";

//----------------------------------------------------------------------

// Thumb list or single image
if( empty($_REQUEST['show']) )
{	// Thumb list
	$sql	= sprintf($_SESSION['database']['sql']['select_thumb'], $_REQUEST['path'] );
	debug( $sql );

	$files	= querySql( $db, $sql );
	debug($files, 'Files:"');

printf( "%s: %s<br>"
,   ___('no_of_images')
,   count( $files )
);

	foreach ( $files as $no => $filedata )
	{
		echo show_thumb( $filedata, FALSE, TRUE );
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
    $first  = $files[0]['file'];
    $last   = $files[count($files)-1]['file'];
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
    
    if (empty($next) && $_SESSION['config']['display']['slide']['loop'] )
    {
        //trigger_error( "no next", E_USER_ERROR );
        $next   = $first;
    }
    
	echo "<button 
        id='prevButton' 
        class='float-left submit-button' 
        onclick = 'goto_image( \"".http_build_query($_SESSION['url']['args'])."\", \"$prev\" );' 
        title='".___('prev_image')."'
        {$prev_active_button}
    ><big>&#x2BAA;</big></button>";
    echo "<script>
        path=\"".http_build_query($_SESSION['url']['args'])."\";
        prev=\"$prev\";
        next=\"$next\";
        first=\"$first\";
        last=\"$last\";
    </script>
	";
    
	// Close
	echo "<button id='prevButton' class='float-left submit-button' onclick = 'close_image(\"".http_build_query($_SESSION['url']['args'])."\");'  title='".___('up_to_index')."'><big>&#x2BAC;</big></button>";

	// Next
	echo "<button 
        id='nextButton' 
        class='float-left submit-button' 
        onclick = 'goto_image( \"".http_build_query($_SESSION['url']['args'])."\", \"$next\" );' 
        title='".___('next_image')."'
        {$next_active_button}
    ><big>&#x2BAB;</big></button>
    ";

	// slideshow
	echo "<button id='slideshowButton' class='float-left submit-button' onclick = 'slideshow( true , {$_SESSION['config']['display']['slide']['delay']}, {$_SESSION['config']['display']['slide']['loop']} );'  title='".___('slideshow_title')."'><big>&#x1F4FD; ".___('slideshow')." <span id='slide_id' class='slide_id'></span></big></button>";
    
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
    debug( $haystack, '<pre>$haystack' );
    debug( $current, '$current' );

	$pattern	= '/^' . SQLite3::escapeString( str_replace( '/', '\/', $current ) ) . '\/[^\/]*$/i';
	$matches  = preg_grep( $pattern, array_values($haystack) );
    rsort( $matches );
    debug( $matches, '$matches' );
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
function show_thumb( $filedata, $dir = false, $show = false )
{
	global $db;
	$output	= '';
    
    //var_export($filedata['path']);
    //var_export($dir);
    //var_export( $filedata['path'] );
/*
	$sql	= sprintf( $_SESSION['database']['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );
*/
	$output	.= sprintf( 
        // Print figure for thumb display
        //SESSION['config']['display']['figure_template']
        $dir ? $_SESSION['config']['display']['figure_template_dir'] : $_SESSION['config']['display']['figure_template']
	,	$dir
        ?   basename($filedata['path']) 
        :   $filedata['name'] // dir ?  dir name : image name
    .   " "
    .   (( $filedata['exif'] ?? FALSE ) ? "<img src='{$_SESSION['config']['display']['exif']['icon']}' class='type_icon' title='EXIF'>" : '' ) // EXIF icon
    .   (( $filedata['iptc'] ?? FALSE ) ? "<img src='{$_SESSION['config']['display']['iptc']['icon']}' class='type_icon' title='IPTC'>" : '' ) // IPTC icon

    ,   $filedata['path'] . ( $show ? '&show=' . $filedata['file'] : '' ) // Link
	,	$filedata['thumb']  // thump to display
	//,	$filedata['path'] 
    ,''
    .   '/'
    .   $filedata['file']
	);
//var_export($filedata['file']);
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

	// Flag
	$flag	= $iptc['Country-PrimaryLocationCode'][0] ?? 'ZZ';
	$output	.= "<img "
	.	"src='config/.flags/{$flag}.svg' "
	.	"onerror=\"this.onerror=null; this.className='flag_mini'; if (this.src != 'config/.flags/ZZ.svg') this.src = 'config/.flags/ZZ.svg'; \" "
	.	"class='flag' "
	.">";

		$headline	= $iptc['Headline'][0] ?? '...';
		$output .= "<span class='headline'>{$headline}</span><br>";

		$caption	= $iptc['Caption-Abstract'][0] ?? '';
		$output .= $caption;

		$supcat	= implode( ', ', $iptc['SupplementalCategories'] ?? ['']);

		$output .= "<br><small>"
		.	$supcat
		.	"</small>";
	}

	// IPTC
	$output	.= "<br clear=both><details open><summary title='".___('iptc')."'><img src='{$_SESSION['config']['display']['iptc']['icon']}'>IPTC</summary><table border=1>";
    foreach ( array_flatten2($iptc) as $iptc_key => $itpc_value )
    {
        if ( 'CodedCharacterSet' == $iptc_key ) continue;
        $output	.= "<tr><td class='iptc_key'>".___("iptc_{$iptc_key}"). "</td><td class='iptc_value'>". (
        ( 'array' == gettype($itpc_value) ) ? implode( ' ; ', $itpc_value) : $itpc_value )
        .   "</td></tr>\n";
    }
	$output	.= "</table></details>";

	// EXIF
/** /
	$output	.= "<details><summary title='".___('exif')."'><img src='{$_SESSION['config']['display']['exif']['icon']}'>EXIF</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";
/**/
	$output	.= "<details><summary title='".___('exif_title')."'><img src='{$_SESSION['config']['display']['exif']['icon']}'>exif</summary><table border=1>";
    foreach ( array_flatten2($exif) as $exif_section => $exif_block )
    {
        if ( ! is_array( $exif_block ) )
            continue;
        //if ( 'CodedCharacterSet' == $exif_key ) continue;
        $output	.= "<tr><td class='exif_group'>".___("exif_{$exif_section}"). "</td><td><table border>";

        foreach ( $exif_block as $exif_key => $exif_value )
        {
            $output	.= "<tr><td class='exif_key'>".___("exif_{$exif_key}"). "</td><td class='exif_value'>". (
            ( 'array' == gettype($exif_value) ) ? implode( ' ; ', $exif_value) : $exif_value )
            .   "</td></tr>\n";
        }

        $output	.= "</table></td></tr>\n";
    }
	$output	.= "</table>";
    	$output	.= "<details><summary title='".___('exif_title')."'><!--img src='{$_SESSION['config']['display']['exif']['icon']}'-->".___('exif_array')."</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";



    // Maps
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
    if (empty($arr))
    {
        $out=[];
        return $out;
    }
	foreach( $arr as $key => $item ) {
		if ( is_array( $item ) && 1 < count( $item ) ) {
			$out[$key] = $item;
		} else {
			$out[$key] = $item[0] ?? '';
		}
	}
	return $out;
}	// array_flatten2()

//----------------------------------------------------------------------

?>