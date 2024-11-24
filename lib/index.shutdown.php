<?php
/**
 *   @file       index-dev.shutdown.php
 *   @brief      Shutdown for `index.php`
 *   @details    Display details w. runtime etc.
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-23T21:01:30 / ErBa
 *   @version    @include version.txt
 */

function shutdown()
{
    // Footer
    printf( "<br clear=both><hr><small>{$_SESSION['config']['system']['copyright']} 
    - <a href='{$_SESSION['config']['display']['home_url']}'>{$_SESSION['config']['system']['app_name']}</a></small> %s"
    ,   date('Y')
    ,   getVersion()
    );


    if ( ! empty( $_REQUEST['slide'] ) )
        echo "<script>slideshow(true, {$_REQUEST['slide']});</script>";

    //----------------------------------------------------------------------
/*
    echo "<details><summary>Outtro</summary>";
    echo "<table border=1>\n";
	verbose( $_SESSION['tmp']['no_of_images'] , ___('no_of_images'));

	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	//status( "Runtime ", $Runtime );
	verbose( microtime2human( $Runtime ), "Runtime " );
	//status( "Log", $_SESSION['config']['logfile']  ?? 'none');

    echo "</table>";
    echo "</details>";
*/    
}   // shutdown()

?>