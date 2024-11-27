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
    ,   getGitVersion()
    );


    if ( ! empty( $_REQUEST['slide'] ) )
        echo "<script>slideshow(true, {$_REQUEST['slide']});</script>";

    echo "<details open><summary title='Outtro'>&#x1F52C;</summary>";
    echo "<table border=1>\n";
    
    if ( 'da' == $_SESSION['browser']['language'] ?? 'en' )
        verbose( number_format($_SESSION['tmp']['no_of_images'], 0, ',', '.') , ___('no_of_images'));
    else
        verbose( number_format($_SESSION['tmp']['no_of_images']) , ___('no_of_images'));


	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	//status( "Runtime ", $Runtime );
	verbose( microtime2human( $Runtime ), "Runtime " );
	//status( "Log", $_SESSION['config']['logfile']  ?? 'none');
	verbose( $_SESSION['config']['logfile']  ?? 'none', 'Log');

	verbose( getRandomImage()  , 'Random');

    echo "</table>";
    echo "<table>";

foreach( $_SESSION['timers'] as $timer => $timerdata )
{
    $valid_start    = $timerdata['start'] ?? FALSE;
    $valid_end      = $timerdata['end'] ?? FALSE;
    $dif1            = ( $timerdata['end'] ?? 0 ) - ($timerdata['start'] ?? 0 );
    $dif            = $timerdata['end'] - $timerdata['start'] ;
    //printf( "<tr><td>%s</td><td>%.2f</td><td>%.2f</td><td>%.2f</td><tr>"
    //printf( "<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><tr>"
    printf( "<tr><td>%s</td><td>%s</td><td>%s</td><td>%f</td><td>%s</td><tr>"
    ,   $timer ?? '--'
    ,   isset( $timerdata['start'] ) ? $timerdata['start'] : '!!' 
    ,   isset( $timerdata['end'] ) ? $timerdata['end'] : '!!'
    ,   1 < intval($dif) ? $dif . '!!' : $dif . '_'
    ,   isset( $timerdata['note'] ) ? $timerdata['note'] : '?'
    //,   var_export( $timerdata, TRUE )
    
    );
}

    echo "</table>";


    echo "</details>";
}   // shutdown()

?>