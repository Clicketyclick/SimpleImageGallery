<?php
/**
 *   @file       addQueue.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *
 *  array_pop()     | Pop the element off the end of array
 *  array_shift()   | Shift an element off the beginning of array
 *  array_unshift() | Prepend one or more elements to the beginning of an array
 *  array_push      | Push one or more elements onto the end of array
 *   
 *@code   
    php addQueue.php -type=unshift -element="{\"title\":\"first_of_queue\",\"start\":1,\"end\":10,\"action\":\"something 1\"}" -debug=true
    php addQueue.php -type=push -element="{\"title\":\"last_of_queue\",\"start\":1,\"end\":10,\"action\":\"something 1\"}" -debug=true
    php addQueue.php -element="{\"title\":\"Very_last_of_queue\",\"start\":1,\"end\":10,\"action\":\"something 1\"}" -debug=true
 *@endcode   
 *   
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-04T05:45:12 / ErBa
 *   @version    2024-12-04T05:45:12
 */

// Libs
include_once( 'lib/debug.php');
include_once( 'lib/handleArgs.php');
include_once( 'lib/handleStrings.php');
include_once( 'lib/handleJson.php');
fputs( STDERR, getDoxygenFileHeader( __FILE__ ) . PHP_EOL);
$verbose=1;
// Globals
$queue_file  = "Queue.json";

// Arguments
parse_cli2request();
debug( $_REQUEST, "Arguments");

if ( ! file_exists($queue_file) )
{
    verbose( "Queue not found. Creating dummy");
verbose( "Queue empty. Initiating.. {$_REQUEST['init']}");
    initQueue($queue, $_REQUEST['init'] ?? FALSE );
    file_put_json($queue_file, $queue );
}

$queue  = file_get_json( $queue_file );

if (empty($queue))
{
    verbose( "Queue empty. Initiating..");
    initQueue($queue);
    file_put_json( $queue_file, $queue );
}

//debug( json_encode($queue[0]), "Element 0");

if ( empty( $_REQUEST['element'] ) )
{   // No element = no update!
    trigger_error( "No element given: -element=''", E_USER_WARNING );
    exit;
}
    

$element    = json_decode( $_REQUEST['element'], TRUE );
debug( $_REQUEST['element'], "New element");

switch ( $_REQUEST['type'] ?? 'push')
{
    case 'unshift'://Prepend one or more elements to the beginning of an array
        debug( "unshift", 'Type');
        array_unshift( $queue, $element );
    break;
    default:// array_push      | Push one or more elements onto the end of array
        debug( 'push', 'Type');
        array_push( $queue, $element );
}

debug( $queue, "New queue");
file_put_json( $queue_file, $queue );

//----------------------------------------------------------------------

function var_error( $str )
{
    verbose( "Error: $str" );
}

function initQueue( &$queue, $init=FALSE )
{
    if ( $init )
    {
        $queue = [
            [ 'title' => "first",   'start' => 1,    'end' => 10, 'action' => "something 1" ],
            [ 'title' => "second",  'start' => 11,   'end' => 20, 'action' => "something 2" ],
            [ 'title' => "thierd",  'start' => 21,   'end' => 30, 'action' => "something 3" ],
            [ 'title' => "forth",   'start' => 31,   'end' => 40, 'action' => "something 4" ],
        ];
    }
    else 
        $queue = [];
    
}

