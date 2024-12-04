<?php
/**
 *   @file       processQueue.php
 *   @brief      Processing a job queue
 *   @details    
 *@code

php processQueue.php -debug=1 -verbose=1

@endcode 
 
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-04T06:42:03 / ErBa
 *   @version    2024-12-04T06:42:03
 */

// Libs
include_once( 'lib/debug.php');
include_once( 'lib/handleArgs.php');
include_once( 'lib/handleStrings.php');
include_once( 'lib/handleJson.php');
fputs( STDERR, getDoxygenFileHeader( __FILE__ ) . PHP_EOL );
// Globals
$queue_file     = "Queue.json";
$verbose        = 1;

// Arguments
parse_cli2request();
debug( $_REQUEST, "Arguments");

if ( ! file_exists($queue_file) )
{
    trigger_error( "Queue not found", E_USER_ERROR );
}

while(true)
{
    $queue  = file_get_json( $queue_file );

    if (empty($queue))
    {
        //trigger_error( "Queue empty", E_USER_WARNING );
        //trigger_error( "Queue empty", E_USER_NOTICE );
        verbose( "Queue empty. Processing done." );
        exit;
    }
    
    $element    = array_shift($queue);
    debug( $element, "Element");
    debug($queue, "Updated queue");

    file_put_json( $queue_file, $queue );
    
    verbose( $element['title'], "Processing: " );
    debug( var_export($element, TRUE), "Processing" );
    sleep(1);
    sleep(1);
}


function var_error( $str )
{
    verbose( "Error: $str" );
    //debug( "Error: $str" );
    //var_export($str); exit;
}


