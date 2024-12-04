<?php
/**
 *   @file       build.php
 *   @brief      Gallery Builder
 *   @details    Builing database, loading images and indexing
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-30T07:13:29 / ErBa
 *   @version    @include version.txt
 */

$GLOBALS['timer'] = TRUE;
include_once( 'lib/handleJson.php');
include_once( 'lib/handleSqlite.php');
include_once( 'lib/debug.php');
include_once( 'lib/map.php');
include_once( 'lib/push.php');

// Include script specific shutdown function. BEFORE _header.php !
include_once( 'lib/'.basename(__FILE__,".php").'.shutdown.php');

//timer_set('_header');
include_once('lib/_header.php');
//timer_set('_header');

timer_set('header', 'Reading header info');
echo "
<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <title>SIGbuild - Simple Image Gallery database builder</title>
  <link rel='stylesheet' href='css/styles.css'>
  <!--script src='js/display.js'></script-->
  <link rel='icon' type='image/x-icon' href=\"{$GLOBALS['config']['system']['favicon']}\">

<script>
// [How to prevent form resubmission when page is refreshed (F5 / CTRL+R)](https://stackoverflow.com/a/45656609)
    if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
    }


    function clicked( str ) {
        document.getElementById('action').value='build_sub.php?' + str;
        document.getElementById('action').value+='&database_name='  + document.getElementById('database_name').value;
        document.getElementById('action').value+='&source_dir='     + document.getElementById('source_dir').value.replace('/\\/g', '/');;

        document.getElementById('action_frame').src=document.getElementById('action').value;
        console.log( 'clicked');
        //alert( 'clicked ' + str);
    }
//----------------------------------------------------------------------

    function replace_slash( str ) {
        //console.log('change ' + str);
        
        // Replace backslash
        str = str.replaceAll( /\\\\/g, \"/\");
        
        // Remove qoutes
        str = str.replaceAll( /['\"]+/g, '' );
        
        //console.log('changed ' + str);
        
        return str;
    }   // replace_slash()

</script>


</head>
<body>
";

debug( $GLOBALS['browser']['language'], "session language: ");

$releaseroot    = __DIR__ . '/';

$verbose=1;
//$debug=1;

// Default path = all
if ( empty( $_REQUEST['path']) )
    $_REQUEST['path'] = '.';
if ( empty( $_REQUEST['action']) )
    $_REQUEST['action'] = '';
if ( empty( $_REQUEST['QUERY_STRING']) )
    //$_REQUEST['QUERY_STRING'] = '';
    $_REQUEST['QUERY_STRING'] = http_build_query($_REQUEST);

timer_set('header', 'end');

// Argument / config / hard coded default
$database_name   = $_REQUEST['db'] 
    ??  $GLOBALS['config']['database']['file_name']
    ??  'database/data.db' ;
$source     = $_REQUEST['source'] 
    ??  $GLOBALS['config']['data']['data_root'] 
    ??  'C:/TMP/test_data/' ;



//$database   = 'database/new.db';
// Select database

echo <<<EOF


<h2>Build database for Simple Image Gallery</h2>

<form id="build_form" action="">    <!-- Action to self -->

    <table border=1>
<!-- Database -->
        <tr><th>
            <label for="database_name">Database:</label>
        </th><td>
            <input type="text" id="database_name" name="database_name" onchange="this.value = replace_slash(this.value);" size=50 value="{$database_name}">
        </td><td>
<!-- Create -->
            <button type="button" onClick="clicked('action=create_database');" >&#x1F5CD; Create database</button>
        </td></tr>
<!-- Source -->
        <tr><th>
            <label for="source_dir">Source dir:</label>
        </th><td>
            <input type="text" id="source_dir" name="source_dir" size=50  onchange="this.value = replace_slash(this.value);" value="{$source}">
        </td><td>
<!-- Update -->
            <button type="button" onClick="clicked('action=update_images');">&#x1F5D8; Update images</button>
            <button type="button" onClick="clicked('action=delete_images');">&#x2326; Delete directories &#x1F5BE;</button>
        </td></tr>
<!-- Action -->    
        <tr><th>
            <label for="action">action:</label>
        </th><td>
            <input id='action' name='action' type="text" size=50 value='{$_REQUEST['action']}'>
        </td><td>
            <button class="btn btn-success" onclick="window.open('index.php','_blank');return false;">&#x1F5BD; Test</button>
        </td></tr>

    </table>
    <p>&nbsp;</p>
<!-- Submit -->
      <input type="submit" value="Submit">
      <input type="button" value="Clear" onClick='document.getElementById("build_form").reset();'>
</form> 


<!-- Progress -->
    <label for="progress">progress:</label>
    <progress id="progress" value="0" max="100"> 0% </progress>
    <span id='progress_status'></span>
<br>

<details>
<summary>Iframe</summary>
<iframe id='action_frame' src="{$_REQUEST['action']}" title="description" width=600 height=600></iframe>
</details>

<div id='status' name='status' class='status'></div>
EOF;

echo "<script>document.getElementById( 'status' ).innerHTML = '';</script>\n";

if ( !empty( $_REQUEST['action'] ) )
{
    pstate( "<div>action: {$_REQUEST['action']}</div>");
    switch( $_REQUEST['action'])
    {
        case 'delete_action':
            if ( ! empty($_REQUEST['files'] ))
            {
                pstate( "<div>Deleting directories: ");
                foreach($_REQUEST['files'] as $dir)
                {
                    // Get count of images in dir
                    $sql    = sprintf( $GLOBALS['database']['sql']['select_count_source_dir'], $dir );
                    $count  = querySqlSingleValue( $db, $sql );
                    // Delete by dir
                    $sql    = sprintf( $GLOBALS['database']['sql']['delete_image_by_source'], $dir );
                    $db->exec( $sql );
                    pstate( "<div>- &#x2326; $count $dir</div>");
                }
                pstate( "<div>done</div>");
                
            }
            else
                pstate("<div>No files to delete</div>");
        break;
        default:
        pstate( "<div>do nothing</div>" );
    }
}


//----------------------------------------------------------------------

?>