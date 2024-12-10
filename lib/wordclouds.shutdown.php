<?php
/**
 *   @file       wordclouds.shutdown.php
 *   @brief      Shutdown for `wordclouds.php`
 *   @details    Display details w. runtime etc.
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-10T16:37:01 / ErBa
 *   @version    @include version.txt
 */

function shutdown()
{
    // Footer
    printf( "<br clear=both><hr><small>{$GLOBALS['config']['system']['copyright']} 
    - <a href='{$GLOBALS['config']['display']['home_url']}'>{$GLOBALS['config']['system']['app_name']}</a></small> %s"
    ,   date('Y')
    ,   getGitVersion()
    );

    if ( ! empty( $_REQUEST['slide'] ) )
        echo "<script>slideshow(true, {$_REQUEST['slide']});</script>";

    echo "<details><summary title='Outtro'>&#x1F52C;</summary>";
    echo "<table border=1>\n";
    
    if ( 'da' == $GLOBALS['browser']['language'] ?? 'en' )
        verbose( number_format($GLOBALS['tmp']['no_of_images'], 0, ',', '.') , ___('no_of_images'));
    else
        verbose( number_format($GLOBALS['tmp']['no_of_images']) , ___('no_of_images'));


	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];

	verbose( microtime2human( $Runtime ), "Runtime " );
	verbose( $GLOBALS['config']['logfile']  ?? 'none', 'Log');

    echo "</table>";
    echo timer_show();
    
    printf( "<details><summary>%s</summary><pre>%s</pre></details>"
    ,   'REQUEST'
    ,   var_export( $_REQUEST, TRUE )
    );

    printf( "<details><summary>%s</summary><pre>%s</pre></details>"
    ,   'config'
    ,   var_export( $GLOBALS['config'], TRUE )
    );

    printf( "<details><summary>%s</summary><pre>%s</pre></details>"
    ,   'database'
    ,   var_export( $GLOBALS['database'], TRUE )
    );




echo "<details><summary>\$_SERVER</summary><pre>". var_export( $_SERVER, TRUE ) . "</pre></details>";
echo "<details><summary>\$_REQUEST</summary><pre>". var_export( $_REQUEST, TRUE ) . "</pre></details>";
//exit;


    echo "</details>";
}   // shutdown()

?>