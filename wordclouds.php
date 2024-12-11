<?php
/**
 *  @file       wordclouds.php
 *  @brief      Display a wordcloud according to specified cloudname
 *  
 *  @details    More details
 *  
 *@code
 *  php showrecord.php Standard bes 1 TEST
 *  php showrecord.php Standard dm2 3
 *@endcode  
 *  
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2024-12-10T16:37:23 / erba
 *  @version   2024-12-10T16:37:14
 */

/** @brief Root for timer data */
$GLOBALS['timer'] = TRUE;
include_once( 'lib/handleJson.php');
include_once( 'lib/handleSqlite.php');
include_once( 'lib/handleFiles.php');
include_once( 'lib/debug.php');
include_once( 'lib/map.php');
include_once( 'lib/push.php');

// Include script specific shutdown function. BEFORE _header.php !
include_once( 'lib/'.basename(__FILE__,".php").'.shutdown.php');
include_once('lib/_header.php');

function redirect($url, $statusCode = 303)
{
   header('Location: ' . $url, true, $statusCode);
   die();
}

/*
echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <title>SIGwords - Simple Image Gallery</title>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/display.js"></script>
  <link rel="icon" type="image/x-icon" href="{$GLOBALS[\'config\'][\'system\'][\'favicon\']}">
</head>
<body>
';
*/
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>".___('sigwords_window_title')."</title>
  <link rel='stylesheet' href='css/styles.css'>
  <!--script src='js/display.js'></script-->
  <link rel='icon' type='image/x-icon' href=\"{$GLOBALS['config']['system']['favicon']}\">

</head>
<body>
";



if ( ! empty( $_REQUEST['path']))
{
    //header("Location: ?path={$_REQUEST['path']}");
    redirect( $_SERVER['HTTP_HOST'] . "?path={$_REQUEST['path']}" );
    //die();
}


$output     = "";
$test_mode  = FALSE;
//$test_mode  = TRUE;

$max_rows   = $GLOBALS['config']['wordclouds']['max_set_size'];

//require_once( "lib/localisation.php");
//require_once( "lib/handleSqlite.php");
//require_once( "lib/handleDatabase.php");

// Get display format
//require_once( "config/displayFormats.php" );

// Get format routines
//require_once( "lib/showMarcRecord.php" );
//require_once( "lib/showBesRecord.php" );
//require_once( "lib/getIcons.php" );

$wordclouds     = json_decode( file_get_contents( "config/wordclouds.json" ), TRUE );

$time_start     = microtime(true);  // Start runtime

$cloud          = explode( ';', $GLOBALS['config']['wordclouds']['default'].";" );

/*
$cloud_name     = $cloud[0];
$cloud_key      = $cloud[1];
$cloud_keys     = explode( ';', $cloud_key );
$cloud_special  = $cloud[2] ?? FALSE;
*/

// Name of cloud: Keywords, People ...
$cloud_name     = $_REQUEST['cloudname'] ?? $_SESSION['wordclouds']['default'] ?? 'keywords';
// ??
$cloud_key      = $_SESSION['current']['wordcloud_key'] ?? FALSE;
// Key: A, B, ...
/*
$cloud_keys     = [
    $_REQUEST['subkey'] ?? NULL
];
*/
$cloud_keys     = [];

// Limmit to range: a, b, c ... or 0-9
if ( isset( $_REQUEST['subkey'] ))
    $cloud_keys    = array_merge( $cloud_keys, explode( ',', $_REQUEST['subkey'] ) ) ;

// Special chars
//$cloud_special  = $_SESSION['current']['wordcloud_spec_key'] ?? FALSE;
$cloud_special  = $_REQUEST['wordcloud_spec_key'] ?? FALSE;

debug( $_REQUEST) ;//exit;

// Check if requested cloud exists - else use first in array
if ( ! array_key_exists( $cloud_name, $wordclouds ) ) {
	//if ( ! empty($_REQUEST['data']) )
	trigger_error( "data: $cloud_name not in cloud list".json_encode($wordclouds), E_USER_ERROR );
    $cloud_name = array_key_first($wordclouds);
}

// List wordclouds
/*
//2024-12-10T16:39:55
$output	.= sprintf( "<h2>%s %s: [%s]</h2>"
,   getIcon( 'cloud' )
,   ___('title')
,   ___( strtolower( $cloud_name ) ) 
    .   (empty($cloud_key) ? "" : " : ". $cloud_key )
    .   (empty($cloud_special) ? "" : " ; ".  $cloud_special )
);
*/

$output	.= "<H2>".___('wordcloud_entries')."</H2>" ;


/*

// Print actionbuttons for each cloud
foreach ( $wordclouds as $cloudname => $entry ) {
    $output	.= "<button type='button' onclick=\"submitAction( 'wordclouds', 'cloudname=$cloudname' );\">".___($cloudname)."</button>";
}

*/

// Count rows of selected cloud
$csql	= sprintf( $sql_config['getcloud_count'], $cloud_name, implode( '%\' OR key LIKE \'', $cloud_keys ) );
$count  = querySqlSingleValue( $db, $csql ); 
//if ($test_mode) $output ."<pre>SQLCOUNT: $csql</pre>";

$sql	= sprintf( $sql_config['getcloud'], $cloud_name, implode( '%\' OR key LIKE \''
,	$cloud_keys )
,	$wordclouds[ $cloud_name ]['order'] // sort order
,	$max_rows ); // ."0"


$output	.= ___('hits'). ": <span id='hitcount'>$count</span>. <span id='truncated'></span><br>";


/*
// Print alfabetic selections
foreach(range('a','z') as $v)
{
    $output	.= sprintf( "<button type=\"button\" onclick=\"document.getElementById('data').value = '$cloud_name;$v'; submitAction( 'wordclouds'
    ,   'cloudname=$cloud_name&subkey=$v' );\">$v</button>" 
    );
}

    
// Numering
$output	.= "<button type=\"button\" onclick=\"document.getElementById('data').value = '{$cloud_name};;num'; submitAction( 'wordclouds', 'cloudname={$cloud_name}&subkey=0,1,2,3,4,5,6,7,8,9' );\">0-9</button>";

// Others
$output	.= "<button type=\"button\" onclick=\"document.getElementById('data').value = '{$cloud_name};;else'; submitAction( 'wordclouds', 'cloudname={$cloud_name}&wordcloud_spec_key=else' );\">æ,ø,å&mldr;</button>";
    
$output	.= "<br>";
*/

switch ($cloud_special ) {
    case 'num':
    {
        $sql	= sprintf( $sql_config['getcloud_num']
		, $cloud_name
		, $wordclouds[ $cloud_name ]['order'] // sort order
		, $max_rows );
    }
    break;
    case 'else':
    {
        $sql	= sprintf( $sql_config['getcloud_else']
		, $cloud_name
		, $wordclouds[ $cloud_name ]['order'] // sort order
		, $max_rows );
    }
    break;
    default:
}

//print "<br><pre>";
debug($sql, "SQL");
    
//trigger_error( "3. [$sql]", E_USER_NOTICE);
$result = $db->query($sql);
$count  = 0;
while( $keydata = $result->fetchArray(SQLITE3_ASSOC) )
{
    $output	.= actionLink( 'find', $keydata['key'], $keydata['entry'], $keydata['entry'], $keydata['count'], $keydata['norm'] );
    $count++;
}

//include_once( 'lib/_print_output.php' );

list($sec, $usec) = explode('.', microtime( TRUE ) - $time_start ); //split the microtime on .
$runtime    = date('H:i:s.', $sec) . $usec;       //appends the decimal portion of seconds

echo $output;

$loc    = [
    'database'              => ___('database'),             // Database
    'search_database'       => ___('search_database'),      // Search
    'source_dir'            => ___('source_dir'),           // Source dir
    'update_images'         => ___('update_images'),        // {$loc['database']}
    'delete_directories'    => ___('delete_directories'),           // {$loc['database']}
];

echo "<br clear=both><hr>";
echo "<form id='build_form' action=''>    <!-- Action to self -->
<input type='hidden' id=ccl name=ccl value='?'>

<!--button type='button' onClick=\"clicked('action=create_database');\" >&#x1F5CD; {$loc['search_database']}</button-->
<!--input type='submit' value='Submit'-->
</form>
";


if ( ! empty( $_REQUEST['ccl']))
{
    $sql    = "SELECT * FROM wordclouds WHERE entry glob '{$_REQUEST['ccl']}';";
    $r  = querySql( $db, $sql );
    
    //echo "<details><summary>wordcloud hits</summary><pre>".var_export( $r, TRUE )."</pre></details>";

    $sql    = "SELECT DISTINCT rowid, * FROM search WHERE key glob LOWER('{$_REQUEST['ccl']}');";
    //$sql    = "SELECT * FROM search WHERE entry glob '{$_REQUEST['ccl']}' COLLATE NOCASE;";
    $r  = querySql( $db, $sql );
    
    //echo "<pre>$sql\n\n".var_export( $r, TRUE )."</pre>";
    //echo "<details><summary>SQL</summary><pre>$sql\n\n".var_export( $r, TRUE )."</pre></details>";
    
    
    foreach ( $r as $s => $rec )
    {
        $sql    = "SELECT * FROM images WHERE rowid = {$rec['recno']};";
        $sql    = "SELECT DISTINCT rowid, * FROM images WHERE rowid = {$rec['recno']};";
        $r2 = querySql( $db, $sql )[0];
        
        /*
        echo "<pre>"
        .   var_export($r2, TRUE)
        .   "</pre>";
        */
        /**/
        echo <<<EOF
        
        
        <figure class="subfolder">
	<figcaption><span class="figure_icon">📁</span><span class="figcaption_text">{$r2['name']}</span></figcaption>
	<span class="figcaption_image">
	<a href="index.php?path={$r2['path']}&show={$r2['file']}">
		<img class="thumb" src="data:jpg;base64, {$r2['thumb']}" title="{$r2['name']}">
	</a>
	</span>
</figure>
EOF;
        
        /**/
        
    }

    
}





error_log( ___('rebuild_runtime'). ": $runtime ".___('no_of_indexterms').": $count");

//----------------------------------------------------------------------

/**
 *  @fn        actionButton
 *  @brief     Make an action button for ByteMARC functions
 *  
 *  @param [in] $action 	Action to call
 *  @param [in] $key 	Data key to update
 *  @param [in] $value 	Value to add to data key
 *  @param [in] $desc 	Button text
 *  @retvar    Return description
 *  
 *  @since     2022-05-19T09:14:13 / erba
 */
function actionButton( $action, $key, $value, $desc ) 
{
	return sprintf(
		"<button type='button' onclick=\"document.getElementById('%s').value = %s; submitAction( '%s' );\">%s</button>"
	,	$key
	,	is_numeric( $value ) ? $value : "'$value'"
	,	$action
	,	$desc
	);
}   //*** actionButton() ***

/**
 *  @fn        actionLink
 *  @brief     Action link for ByteMARC functions
 *  
 *  @param [in] $action 	Action to call
 *  @param [in] $key 	Data key to update
 *  @param [in] $value 	Value to add to data key
 *  @param [in] $desc 	Button text
 *  @param [in] $count 	Count of entries
 *  @param [in] $norm 	Norm / text size
 *  @retvar    Return description
 *  
 *  
 *  @since     2022-05-19T09:15:36 / erba
 */
function actionLink( $action, $key, $value, $desc, $count, $norm = 100 ) 
{
    //                               2            3    4   5
    //                                          6                                7           8  
    $fmt	= "<a style='font-size: %s%%' title='%s (%s) [%s]' href='' onclick=\"this.removeAttribute('href');"
    .   "document.getElementById('ccl').value = '%s'; document.getElementById('ccl').focus(); document.getElementById('build_form').submit();call_sub('find'); \" >%s</a>&nbsp;(%s) ";

	return sprintf( $fmt
	,	$norm		// 2
	,	$desc		// 3
	,	$count		// 4
	,	$norm		// 5

	,	$value		// 6 ,	is_numeric( $value ) ? $value : "'$value'"

	,	$key		// 7
	,	$count		// 8
	);
}   //*** actionLink() ***

?>