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

// Include script specific shutdown function. BEFORE _header.php !
include_once( 'lib/'.basename(__FILE__,".php").'.shutdown.php');

//timer_set('_header');
include_once('lib/_header.php');
//timer_set('_header');
//include_once("lib/database.php");

timer_set('header', 'Reading header info');
echo '
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>SIGbuild - Simple Image Gallery database builder</title>
  <link rel="stylesheet" href="css/styles.css">
  <script src="js/display.js"></script>
  <link rel="icon" type="image/x-icon" href="{$GLOBALS[\'config\'][\'system\'][\'favicon\']}">
</head>
<body>
';

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
$database   = $_REQUEST['database'] 
    ??  $GLOBALS['config']['database']['file_name']
    ??  'database/data.db' ;
$source     = $_REQUEST['source'] 
    ??  $GLOBALS['config']['data']['data_root'] 
    ??  'C:/TMP/test_data/' ;



//$database   = 'database/new.db';
// Select database

echo <<<EOF

<style>
iframe {
    width: calc( 100% - 60px);
    height: calc( 50% - 60px);
}
</style>
<script>
function clicked( str ) {
    document.getElementById("action").value='sub.php?{$_REQUEST['QUERY_STRING']}&' + str;
    document.getElementById("action").value+='&database_name='  + document.getElementById("database_name").value;
    document.getElementById("action").value+='&source_dir='     + document.getElementById("source_dir").value;

    document.getElementById("action_frame").src=document.getElementById("action").value;
    console.log( 'clicked');
    //alert( 'clicked ' + str);
}
</script>


<h2>Build database for Simple Image Gallery</h2>

<form id="build_form" action="">    <!-- Action to self -->

    <table border=1>
<!-- Database -->
        <tr><th>
            <label for="database_name">Database:</label>
        </th><td>
            <input type="text" id="database_name" name="database_name" size=50 value="{$database}">
        </td><td>
<!-- Create -->
            <button type="button" onClick="clicked('action=create_database');">Create database</button>
        </td></tr>
<!-- Source -->
        <tr><th>
            <label for="source_dir">Source dir:</label>
        </th><td>
            <input type="text" id="source_dir" name="source_dir" size=50 value="{$source}">
        </td><td>
<!-- Load -- >
            <button type="button" onClick="clicked('action=load_images');">Load images</button>
<!-- Update -- >
            <button type="button" onClick="clicked('action=update_images');">Update images</button>
-->
        </td></tr>
<!-- Action -->    
        <tr><th>
            <label for="action">action:</label>
        </th><td>
            <input id='action' name='action' type="hidden" size=50 value='{$_REQUEST['action']}'>
        </td><td>
<!-- Grouping -- >
            <button type="button" onClick="clicked('action=grouping_images');">Grouping images</button>
<!-- Index -- >
            <button type="button" onClick="clicked('action=update_index');">Update index</button>
-->
            <button class="btn btn-success" onclick="window.open('index.php','_blank');return false;">Test</button>
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
<iframe id='action_frame' src="{$_REQUEST['action']}" title="description" width=100%></iframe>
</details>

<div id='status' name='status' class='status'></div>
EOF;



//$GLOBALS['url']['args']

/*
timer_set('open_db', 'Opening database');
$db    = openSqlDb( $_REQUEST['db'] ?? $GLOBALS['config']['database']['file_name']);
timer_set('open_db');
*/




//----------------------------------------------------------------------

?>