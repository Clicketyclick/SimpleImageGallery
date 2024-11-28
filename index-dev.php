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


//$GLOBALS['timers']['_INIT']['start']    = $_SERVER["REQUEST_TIME_FLOAT"];


// Include script specific shutdown function. BEFORE _header.php !
include_once( 'lib/'.basename(__FILE__,".php").'.shutdown.php');

//timer_set('_header');
include_once('lib/_header.php');
//timer_set('_header');

//unlink( $_SESSION['timers'] );

timer_set('header');
echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SIG - Simple Image Gallery</title>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/display.js"></script>
  <link rel="icon" type="image/x-icon" href="{$GLOBALS[\'config\'][\'system\'][\'favicon\']}">
</head>
<body>
';

debug( $GLOBALS['browser']['language'], "session language: ");

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

timer_set('header');

timer_set('open_db');
$db	= openSqlDb( $_REQUEST['db'] ?? $GLOBALS['config']['database']['file_name']);
timer_set('open_db');

/*
echo "<pre>";
var_export( $GLOBALS['timers'] );
exit;
*/

timer_set('build_dir_tree');
//"SELECT DISTINCT path FROM images WHERE path LIKE '%s%%'"
$sql	= sprintf( $GLOBALS['database']['sql']['select_path'], $_REQUEST['path']  );
$dirs	= querySql( $db, $sql );

$tree	= buildDirTree( $dirs );
debug( $tree, 'tree' );
timer_set('build_dir_tree');

// >>> Top menu
// Build breadcrumb trail: 'crumb1/crumb2/file" => [crumb1] -> [crumb2] 
echo "<span title='".___('breadcrumptrail')."'>". $GLOBALS['config']['display']['breadcrumptrail'] . "</span>";
//$trail  = breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' ) ;
$trail  = breadcrumbTrail( $_REQUEST['path'], '?path=%s', 0, -1, '/' ) ;
echo ( empty($trail) ? "<span title='".___('empty_breadcrumb_trail')."'>." : $trail ) ;
echo '/'. basename($_REQUEST['path']);
echo "</span><script>path='". dirname( $_REQUEST['path'] ) . "';</script>";


// Language
// Change language https://stackoverflow.com/a/22040376/7485823
// https://stackoverflow.com/a/22040376
//!!! Use: $GLOBALS['url']['args']
/**/
if ( ! empty( $GLOBALS['url']['args']['browser:language'] ))
    unset($GLOBALS['url']['args']['browser:language']);
$escaped_url    = '?'.http_build_query($GLOBALS['url']['args']);

/**/
/* * /
$url =  "//{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$url = preg_replace("/&browser:language=../", "", $url);
$escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
$escaped_url = htmlspecialchars( $url, ENT_QUOTES, 'UTF-8' );
/**/
echo "<a class='translate' href='{$escaped_url}&browser:language="
.   (
        'en' == $GLOBALS['browser']['language'] ? 'da' : 'en'
    )
.   "' title='". ___('shift_language') ."'>A文</span></a>";

// Database name on top
echo "<span class='db_name'>{$GLOBALS['config']['database']['file_name']}</span>";

echo "&nbsp;<span class='welcome'><details class='welcome'><summary class='welcome_summary'>[".___('welcome')."]</summary>".___('welcome_intro')."</details></span>";

// Clear before folders
echo "<br clear=both><hr>";

// <<< Top menu

//----------------------------------------------------------------------


// Get subdirectories to current directory
$subdirs	= subdirsToCurrent( array_unique($tree), $_REQUEST['path'] );
$count_subdirs  = count($subdirs );
debug($subdirs);
printf( "%s: %s<br>"
,   ___('no_of_subdirs')
,   $count_subdirs
);
$count  = 0;

$sql	= sprintf( $GLOBALS['database']['sql']['thumb_from_dirs'], implode( "', '", $subdirs ) );
debug($sql, 'thumb_from_dirs');
$thumbs	= querySql( $db, $sql );

// flatten
foreach( $thumbs as $no => $thumbdata )
{
    unset ( $thumbs[$no] );
    $thumbs[$thumbdata['path']] = $thumbdata['thumb'];
}

debug($thumbs, 'thumbs');

foreach( $subdirs as $subdir )
{
    logging( "Start: $subdir");
    //$GLOBALS['timers'][$subdir]['start']	= microtime(TRUE);
    timer_set( $subdir );
    
	$dir	= $_REQUEST['path'] .'/'. pathinfo( $subdir, PATHINFO_BASENAME);
    debug( $dir);
	// get newest image 	"newest_picture_in_path"
    /*
    $sql	= sprintf( $GLOBALS['database']['sql']['thumb_from_dirs'], $dir );
    var_export($sql);
	$thumb	= querySql( $db, $sql );
    */
    //if ( 'array' == gettype($thumb) )
    if ( empty($thumbs[$subdir]) )
    {
        logging( 'newest_picture_in_path' );
        $sql	= sprintf( $GLOBALS['database']['sql']['newest_picture_in_path'], $dir );
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
    
    timer_set( $subdir );
    //logging( progress_log( ++$count, $count_subdirs, $GLOBALS['timers'][$subdir]['start'] ?? microtime( TRUE ), 1 ) );
}
echo "</pre><br clear=both><hr>";

//----------------------------------------------------------------------

// Thumb list or single image
if( empty($_REQUEST['show']) )
{	// Thumb list
	$sql	= sprintf($GLOBALS['database']['sql']['select_thumb'], $_REQUEST['path'] );
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
    timer_set('select_path_file');

	$sql	= sprintf($GLOBALS['database']['sql']['select_path_file'], $_REQUEST['path'] );
	debug( $sql );

	$files	= querySql( $db, $sql );
    timer_set('select_path_file');

	debug("Files:<pre>");
	debug($files);
	debug("</pre>" );
    timer_set('select_path_file_normalise');

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
    timer_set('select_path_file_normalise');

    timer_set('select_display');

	$sql	= sprintf($GLOBALS['database']['sql']['select_display'], $_REQUEST['path'], $_REQUEST['show'] );
	debug( $sql );

	$file	= querySql( $db, $sql );
    timer_set('select_display');

	debug("Files:<pre>");
	debug($file[0]);
	debug("</pre>" );

    timer_set('buttons');

	// Previous
    $prev_active_button = $prev ? "" : " disabled";
    $next_active_button = $next ? "" : " disabled";
    
    if (empty($next) && $GLOBALS['config']['display']['slide']['loop'] )
    {
        //trigger_error( "no next", E_USER_ERROR );
        $next   = $first;
    }
    
	echo "<button 
        id='prevButton' 
        class='float-left submit-button' 
        onclick = 'goto_image( \"".http_build_query($GLOBALS['url']['args'])."\", \"$prev\" );' 
        title='".___('prev_image')."'
        {$prev_active_button}
    ><big>&#x2BAA;</big></button>";
    echo "<script>
        path=\"".http_build_query($GLOBALS['url']['args'])."\";
        prev=\"$prev\";
        next=\"$next\";
        first=\"$first\";
        last=\"$last\";
    </script>
	";
    
	// Close
	echo "<button id='prevButton' class='float-left submit-button' onclick = 'close_image(\"".http_build_query($GLOBALS['url']['args'])."\");'  title='".___('up_to_index')."'><big>&#x2BAC;</big></button>";

	// Next
	echo "<button 
        id='nextButton' 
        class='float-left submit-button' 
        onclick = 'goto_image( \"".http_build_query($GLOBALS['url']['args'])."\", \"$next\" );' 
        title='".___('next_image')."'
,        {$next_active_button}
    ><big>&#x2BAB;</big></button>
    ";

	// slideshow
	echo "<button id='slideshowButton' class='float-left submit-button' onclick = 'slideshow( true , {$GLOBALS['config']['display']['slide']['delay']}, {$GLOBALS['config']['display']['slide']['loop']} );'  title='".___('slideshow_title')."'><big>&#x1F4FD; ".___('slideshow')." <span id='slide_id' class='slide_id'></span></big></button>";

	// Random image
	echo "<button id='randomButton' class='float-left submit-button' onclick = \"window.location = '".getRandomImage()."'\"  
    title=\"".___('random_title')."\"><big>&#x1F3B2;</big> ".___('random')."</button>";

    timer_set('buttons');
    
    timer_set('show_image');
	echo show_image( $file[0] );
    timer_set('show_image');

	//var_export( strlen( $file[0]['display'] ) );
}

//$GLOBALS['timers']['_INIT']['end']      = microtime(TRUE);
//timer_set('_INIT');

ob_flush();
flush();
//----------------------------------------------------------------------

function timer_set( $key )
{
    if ( ! $GLOBALS['timer'] )
        return;

    if ( ! isset($GLOBALS['timers'][$key]['start']) )
    {
        $GLOBALS['timers'][$key]['start'] = microtime( TRUE) ;
        //debug( $GLOBALS['timers'][$key]['start'] . "   {$_SERVER["REQUEST_TIME_FLOAT"]} ", "$key start");
    }
    else
    {
        $GLOBALS['timers'][$key]['end'] = microtime( TRUE) ;
        //debug( $GLOBALS['timers'][$key]['end'], "$key end" );
    }
        
}
/*
function timer_start( $key )
{
    if ( empty($GLOBALS['timers'][$key]['start']) )
        $GLOBALS['timers'][$key]['start'] = microtime( TRUE) ;
    else
        $GLOBALS['timers'][$key]['end'] = microtime( TRUE) ;
}

function timer_end( $key )
{
    $GLOBALS['timers'][$key]['end'] = microtime( TRUE) ;
}
*/
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
	$sql	= sprintf( $GLOBALS['database']['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	$exif	= json_decode( $meta[0]['exif'] ?? "??", TRUE );
*/
	$output	.= sprintf( 
        // Print figure for thumb display
        //SESSION['config']['display']['figure_template']
        $dir ? $GLOBALS['config']['display']['figure_template_dir'] : $GLOBALS['config']['display']['figure_template']
	,	$dir
        ?   basename($filedata['path']) 
        :   $filedata['name'] // dir ?  dir name : image name
    .   " "
    .   (( $filedata['exif'] ?? FALSE ) ? "<img src='{$GLOBALS['config']['display']['exif']['icon']}' class='type_icon' title='EXIF'>" : '' ) // EXIF icon
    .   (( $filedata['iptc'] ?? FALSE ) ? "<img src='{$GLOBALS['config']['display']['iptc']['icon']}' class='type_icon' title='IPTC'>" : '' ) // IPTC icon

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
	global $db;
	$output	= '';

	debug( $filedata['file']);
	debug( $filedata['path'] );
    
	timer_set( 'show_image_get_meta');
    
	$sql	= sprintf( $GLOBALS['database']['sql']['select_meta'], $filedata['file'], $filedata['path'] );
	$meta	= querySql( $db, $sql );
	debug( $sql );
	timer_set( 'show_image_get_meta');

	timer_set( 'show_image_decode_meta');
	$exif	= json_decode( $meta[0]['exif'] ?? "", TRUE );
	$iptc	= json_decode( $meta[0]['iptc'] ?? "", TRUE );
	timer_set( 'show_image_decode_meta');

	timer_set( 'show_image_show_image');

/**/
	$output	.= sprintf( "<br><small></small><img class='display' src='data:jpg;base64, %s' title='%s'>"
    //$output	.= sprintf( "<br><small></small>[img class='display' src='data:jpg;base64, %s' title='%s'>"
	,	$filedata['display']
	,	$filedata['path'] . '/' . $filedata['file'] 
	);
   	timer_set( 'show_image_show_image');

/** /
echo '
<div style="
        width: 10px;
        height: 10px;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==) no-repeat;">
    A dot.
</div>
';
*/

	timer_set( 'show_image_header');

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

        // Headline
		$headline	= $iptc['Headline'][0] ?? '...';
		$output .= "<span class='headline'>{$headline}</span><br>";

        // Caption
		$caption	= $iptc['Caption-Abstract'][0] ?? '';
		$output .= $caption;

        // Location
        $ContentLocationName	= [
            $iptc['Sub-location'][0] ?? ''                  //Sub-location	Grydehøjvej 62
        ,	$iptc['City'][0] ?? ''                          //City	Gundsømagle
        ,	$iptc['Province-State'][0] ?? ''                //Province-State	DK-4000 Roskilde
        ,	$iptc['Country-PrimaryLocationName'][0] ?? ''   //Country-PrimaryLocationName	Denmark
        ,	$iptc['Country-PrimaryLocationCode'][0] ?? ''   //Country-PrimaryLocationCode	DNK
        ];

        $output .= "<br clear=both><span class='iptc_location_tag'>"
        .   ___('iptc_location_tag')
        .   "</span>: <span class='iptc_location'>"
        .   ( $iptc['ContentLocationName'][0] ?? str_replace( ', , ', ', ', implode( ', ', $ContentLocationName ) ) )
        //.   ( $iptc['ContentLocationName'][0] ?? implode( ', ', $ContentLocationName ) )
        .   '</span>'
        ;

        // Persons
        if ( ! empty( $iptc['SupplementalCategories'] ) )
        {
            $supcat	= implode( ', ', $iptc['SupplementalCategories'] ?? ['']);
            $output .= "<br><span class='iptc_location_tag'>". ___('iptc_SupplementalCategories') . "</span>: <span class='iptc_location'>{$supcat}</span>";
        }


        // Credit and source
        /*
        Credit	E. Christoffersen
        Source	https://www.flickr.com/photos/nationalmuseet/7393192296
        */
        if ( ! empty( $iptc['Credit'] ) )
            $output .= "<br><span class='iptc_credit_tag'>". ___('iptc_Credit') . "</span>: <span class='iptc_credit'>{$iptc['Credit'][0]}</span>";
        
        if ( ! empty( $iptc['Source'] ) )
        {
            $source = $iptc['Source'][0];
            if ( str_starts_with( $source, 'https:') )
                $source = sprintf( "<a href='%s' title='%s'>%s</a>"
                    ,   $source
                    ,   ___('link_to_source')
                    ,   $source
                    );

            $output .= "<br><span class='iptc_source_tag'>". ___('iptc_Source') . "</span>: <span class='iptc_Source'>{$source}</span>";
        }
	}
	timer_set( 'show_image_header');

    echo "<br clear=both>";
    
	timer_set( 'show_image_iptc');
	// IPTC
	$output	.= "<br clear=both><br><details><summary title='"
    .   ___('iptc')
    .   "'><img src='{$GLOBALS['config']['display']['iptc']['icon']}'>IPTC</summary><table border=1>";
    
    foreach ( array_flatten2($iptc) as $iptc_key => $itpc_value )
    {
        if ( 'CodedCharacterSet' == $iptc_key ) continue;
        $output	.= "<tr><td class='iptc_key'>"
        .   ___("iptc_{$iptc_key}")
        .   "</td><td class='iptc_value'>"
        .   (
                ( 'array' == gettype($itpc_value) ) ? implode( ' ; ', $itpc_value) : $itpc_value 
            )
        .   "</td></tr>\n"
        ;
    }
	$output	.= "</table></details>";
	timer_set( 'show_image_iptc');


	timer_set( 'show_image_exif');
	// EXIF
	$output	.= "<br clear=both><details><summary title='"
    .   ___('exif_title')
    .   "'><img src='{$GLOBALS['config']['display']['exif']['icon']}'>EXIF</summary><table border=1>";

    foreach ( array_flatten2($exif) as $exif_section => $exif_block )
    {
        if ( ! is_array( $exif_block ) )
            continue;
        //if ( 'CodedCharacterSet' == $exif_key ) continue;
        $output	.= "<tr><td class='exif_group'>"
        .   ___("exif_{$exif_section}")
        .   "</td><td><table border>"
        ;

        foreach ( $exif_block as $exif_key => $exif_value )
        {
            $output	.= "<tr><td class='exif_key'>"
            .   ___("exif_{$exif_key}")
            .   "</td><td class='exif_value'>"
            .   (
                    ( 'array' == gettype($exif_value) )
                    ?   implode( ' ; ', $exif_value)
                    :   $exif_value 
                )
            .   "</td></tr>\n";
        }

        $output	.= "</table></td></tr>\n";
    }
	$output	.= "</table></details>";
    	$output	.= "<details><summary title='".___('exif_title')."'><!--img src='{$GLOBALS['config']['display']['exif']['icon']}'-->".___('exif_array')."</summary><pre>";
	$output	.= var_export( $exif, TRUE );
	$output	.= "</pre></details>";
	timer_set( 'show_image_exif');

	timer_set( 'show_image_maps');

    // Maps
	if ( ! empty($exif['GPS']["GPSLongitude"]) )
	{
		$lon = getGps($exif['GPS']["GPSLongitude"], $exif['GPS']['GPSLongitudeRef']);
		$lat = getGps($exif['GPS']["GPSLatitude"], $exif['GPS']['GPSLatitudeRef']);
		$zoom	= $_REQUEST['zoom'] ?? 15;

        echo "<br>";
		echo getMapEmbed( $lat, $lon, $zoom, $GLOBALS['config']['maps']['map_source'] );
        echo "<br>";
        echo getMapLink( $lat, $lon, $zoom, $GLOBALS['config']['maps']['map_source'] );
		echo " @ $lon,$lat<br>";

	}
	$output	.= "<br clear=both><hr>";
	timer_set( 'show_image_maps');
	
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

function getRandomImage()
{
    global $db;
    $rand   = rand( 1 , $GLOBALS['tmp']['no_of_images']);
    $img    = querySqlSingleRow( $db, "SELECT file, path FROM images WHERE rowid = {$rand};" );
    
    // ?path=./Gamle%20album/OdenseBilleder/1943-08-19_Asylgade&show=7393192296_9f54c9ff59_h.jpg
    $url    = "?path={$img['path']}&show={$img['file']}";
    //$url    = "<a href='{$url}'>random</a>";
    
    return( $url );
    return( var_export( $url, TRUE ) );
    return( var_export( $img, TRUE ) );
    return( $rand  );
}   //getRandomImage()


?>