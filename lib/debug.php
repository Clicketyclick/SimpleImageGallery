<?php
/**
 *  @file       debug.php
 *  @brief      Verbose and debug functions
 *   
 *  @details    Debug and verbose for multi levels of info
 *  Activated from either:
 *  
 *  1.Session
 *  : $_SESSION['debug']
 *  2. Globals
 *  : $GLOBALS['debug']
 *  3. Environment
 *  : getenv('DEBUG')
 *
 *  debug           Debug message with location
 *  verbose         Verbose message
 *  logging         Generates log intry in $GLOBALS['logfile'] if $GLOBALS['logging'] flag set
 *  self            Return string w. file.function.stringtoken
 *  progress_per    Generates a user-level message foreach x itterations
 *  status
 *  timer_set       timer_set starts or ends timer slot in listing
 *  timer_show      Build output from $GLOBALS['timers']
 *   
 *  @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since      2022-12-03T09:02:25 / Erik Bachmann
 *  @version    2024-11-28T12:16:33 / Erik Bachmann
 */
 
//---------------------------------------------------------------------

/**
 *  @fn         debug ( $str, $extend = FALSE ) 
 *  @brief     Debug message with location
 *  
 *  @param [in] $str	String to write
 *  @param [in] $extend	Flag for file info
 *  @retval    VOID
 *  
 *  @details   Displaying in depth information with backtrace 
 *      for debugging if debug flag is active
 *
 *@code
 *      $debug  = TRUE;
 *      debug "this you'll see", "Debug: on");
 *      $debug  = FALSE;
 *      debug( "this you won't", "Debug: on");
 *@endcode
 *
 * Increase visability with a CSS like:
 *
 *@code{css}
summary.debug {
	background-color: black;
	background-color: yellow;
	color: red;
	text-indent: 5px;
}
details.debug {
	background-color: black;
	display: block;
	color: yellow;
	text-indent: 25px;
	/* Preformatted* /
    font-family: monospace;
    white-space: pre;
}
@endcode
 *
 *  @since     2022-12-03T08:57:29 / Erik Bachmann Pedersen
 */
function debug ( $str, $extend = FALSE ) 
{
    if ( ($_SESSION['debug'] ?? false) || ($GLOBALS['debug'] ?? false) || (getenv('DEBUG') ?? false) ) 
	{
        $backtrace  = debug_backtrace()[1] ?? debug_backtrace()[0] ;
        if ( $extend ) 
        {
            // Build backtracking
            $header     = sprintf( "%s[%s](%s) %s "
            ,   basename($backtrace['file'])
            ,   $backtrace['line']
            //,   $backtrace['function']
            ,   (__FUNCTION__ == $backtrace['function']) ? 'MAIN' : $backtrace['function']
            ,   $extend
            );
            // Append msg
            $msg    = sprintf( "%s: %s\n"
            ,   $header
            ,   var_export( $str, TRUE )
            );
        } else {
            // Build backtracking
            $header     = sprintf( "%s[%s](%s) %s "
            ,   basename($backtrace['file'])
            ,   $backtrace['line']
            ,   $backtrace['function']
            //,   (__FUNCTION__ == $backtrace['function']) ? 'MAIN' : $backtrace['function']
            ,   $extend
            );
            // Append msg
            $msg    = sprintf( "%s: %s\n"
            ,   $header
            ,   var_export( $str, TRUE )
            );
        }
        logging( $msg );
        if ('cli' === PHP_SAPI ) {
            fputs( STDERR, $msg );
        } else {
            print( "<details class='debug'><summary class='debug'><kbd>!{$header}</kbd></summary>{$msg}</details>\n" );
        }
    }
}   //*** debug() ***

//---------------------------------------------------------------------

/**
 *  @fn         verbose( $str, $extend = FALSE )
 *  @brief     Verbose message
 *  
 *  @param [in] $str	String to display on STDERR if in verbose mode
 *  @param [in] $extend	Prefix
 *  
 *  @details   Function for debugging with simple backtracking using 
 *      verbose messaged using the verbose flag.
 *      Uses the global value of verbose $GLOBALS['verbose']
 *      CLI writes to STDERR and CGI to browser
 *  
 *  @code
 *      $verbose  = TRUE;
 *      verbose( "this you'll see", "verbose on");
 *      $verbose  = FALSE;
 *      verbose( "this you won't", "Verbose off");
 *  @endcode  
 *  
 *  @since     2022-12-03T08:55:38 / Erik Bachmann Pedersen
 */
function verbose( $str, $extend = FALSE )
{
	if ( ( $_SESSION['verbose'] ?? false ) || ( $GLOBALS['verbose'] ?? false ) || ( getenv('VERBOSE') ?? false ) ) 
    {
        if ('cli' === PHP_SAPI ) {
            fprintf( STDERR, "- %s%s\n", ($extend ?? '?') , $str );
        } else {
            //printf( "<tr><th>%s</th><td>%s</td></tr>XX\n", ($extend ?? '?') , $str );
            printf( "<p>\n%s\t%s</p>\n", ($extend ?? '?') , $str );
        }
    }
}   // verbose()

//---------------------------------------------------------------------

/**
 *  @fn         logging( $str )
 *  @brief     Generates log intry in $GLOBALS['logfile'] if $GLOBALS['logging'] flag set
 *  
 *  @param [in] $str        Log string
 *  @retval    VOID
 *  
 *  @details   More details
 *  
 *  
 *  @see       https://www.php.net/manual/en/function.error-log.php#128965
 *  @since     2024-02-28T06:19:58 / erba
 */
function logging( $str )
{
    //global $logging;
    if ( $_SESSION['logging'] ?? false || $GLOBALS['logging'] ?? false ) 
    {
        $bt     = debug_backtrace()[1] ?? debug_backtrace()[0] ;
        $caller = $bt;
        file_put_contents( $GLOBALS['logfile'] ?? "logFile.txt"
        ,   sprintf( "%s(%s)[%s]: %s\n"
            ,   basename($caller['file'])
            ,   $caller['function']
            ,   $caller['line']
            ,   (
                    is_object($str) 
                ||  is_array($str) 
                ||  is_resource($str) 
                ?   json_encode($str, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) 
                :   $str
                )
            )
        ,   FILE_APPEND
        );
    }
}   // logging()

//---------------------------------------------------------------------

/**
 *  @fn         self( $str = false )
 *  @brief        Return string w. file.function.stringtoken
 *  
 *  @param [in]   $str      Token to return
 *  @retval       string w. file.function.string
 *  
 *  @details      Used to locate function calls and localisation
 *  
 *  @code
 *      print __( self('me') );
 *  @endcode
 *  
@verbatim
    file.function.me
@endverbatim
 *  
 *  @since       2023-11-01T07:57:52 / Erik Bachmann Pedersen
 */
function self( $str = false )
{
    $backtrace  = debug_backtrace()[1] ?? debug_backtrace()[0] ;
    
    $msg    = sprintf( "%s.%s.%s\n"
    ,   pathinfo($backtrace['file'] , PATHINFO_FILENAME)  ?? 'no file'
    ,   (__FUNCTION__ == $backtrace['function']) ? 'MAIN' : $backtrace['function']
    ,   "$str"
    );
    return( $msg );
}   // self()

//---------------------------------------------------------------------

/**
 *  @fn         progress_per( $var, $limit, $function, $msg = FALSE, $type = E_USER_NOTICE )
 *  @brief     Generates a user-level message foreach x itterations
 *  
 *  @param [in] $var        Current value
 *  @param [in] $limit      Max value
 *  @param [in] $function   Name of function
 *  @param [in] $msg        Message
 *  @param [in] $type       Type of message
 *  @retval    Return description
 *  
 *  @details   For each limit ittrations in $var a status message is generated
 *  
 *  @code
 *      for ( $x=1 ; $x < 100 ; $x++ )
 *      {
 *         progress_per( $x, 20, 'trigger_error', "loop=[\$var]");
 *         progress_per( $x, 30, 'trigger_error', FALSE, E_USER_WARNING);
 *      }
 *  
 *  Notice (loop=) is given on values 20,40,60,80. Warning (var=) on 30,60,90
 *  @endcode
 *  
 *  @since     2024-02-28T06:14:47 / erba
 */
function progress_per( $var, $limit, $function, $msg = FALSE, $type = E_USER_NOTICE )
{
    if ( ! ( $var % $limit ) )
    {
        $function( $msg ? $msg : "var=[$var]", $type );
    }
        
}   // progress_per()

/*
function action( $tag, $value )
{
	verbose( sprintf( "%-30.30s [%s]", $tag, $value ) );
}
function state( $tag, $value )
{
	verbose( sprintf( "%-30.30s [%s]", $tag, $value ) );
}
*/

/**
 *  @fn         status( $tag, $value )
 *  @brief      Prints formated one-liner with key, value
 *   
 *   @param [in]	$tag	Key
 *   @param [in]	$value	Value
 *   
 *   @code
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @since      2024-11-28T12:18:22
 */
function status( $tag, $value )
{
	verbose( sprintf( "%-30.30s [%s]", $tag, $value ) );
}   // status()

//----------------------------------------------------------------------

/**
 *  @fn         timer_set( $key, $note = FALSE )
 *  @brief      timer_set starts or ends timer slot in listing
 *   
 *   @param [in]	$key	key for identifying entry
 *   @param [in]	$note	Note added to entry
 *   
 *   @details    Creates entry in timer table: Start, end, note
 *   
 *   @code
 *   timer_set('mykey', 'test');
 *   :
 *   timer_set('mykey');
 *   echo timer_show();
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-28T10:07:58
 */
function timer_set( $key, $note = FALSE )
{
    if ( ! $GLOBALS['timer'] )
        return;

    if ( ! isset($GLOBALS['timers'][$key]['start']) )
    {
        $GLOBALS['timers'][$key]['start'] = microtime( TRUE) ;
        $backtrace  = debug_backtrace()[1] ?? debug_backtrace()[0] ;
        $backtrace  = debug_backtrace()[0] ;
        $GLOBALS['timers'][$key]['trace'] = sprintf( "%s[%s](%s)"
            ,   basename($backtrace['file'])
            ,   $backtrace['line']
            //,   (__FUNCTION__ == $backtrace['function']) ? 'MAIN' : $backtrace['function']
            ,   (__FUNCTION__ == $backtrace['function']) ? 'MAIN' : debug_backtrace()[0]['function']
            );
    }
    else
    {
        $GLOBALS['timers'][$key]['end'] = microtime( TRUE) ;
    }
    if ( $note )
        $GLOBALS['timers'][$key]['note']  ??= $note;
}   // timer_set()

//----------------------------------------------------------------------

/**
 *  @fn         timer_show()
 *  @brief      Build output from $GLOBALS['timers']
 *   
 *   @retval     HTML table as string
 *   
 *   @details    List details from timing:
 *   - Start    Time from script start
 *   - End      End time stamp
 *   - Duration Runtime between start and End
 *   - Note     Note added to timer_set()
 *
 *   @code
 *   timer_set('mykey', 'test');
 *   :
 *   timer_set('mykey');
 *   echo timer_show();
 *   @endcode
@verbatim
<table class="timer_table">
<caption>Timing</caption>
<tbody>
<tr><th>Key</th><th>Start</th><th>End</th><th>Duration</th><th>Note</th></tr>
<tr><td>mykey</td><td>0.001</td><td>0.005</td><td>0.003</td><td>test</td></tr><tr></tr>
:
</tbody>
</table>
@endverbatim
 * 
 *  |Key     | Start |End   |Duration | Note |
 *  ---|---|---|---|---
 *  |_header |0.001 | 0.005 | 0.003   | test |
 *   
 *   @since      2024-11-28T10:11:18
 */
function timer_show()
{
    $out    = "<table class='timer_table'>\n<caption>Timing</caption>\n<tr><th>Key</th><th>Start</th><th>End</th><th>Duration</th><th>Note</th><th>Trace</th></tr>\n";

    foreach( $GLOBALS['timers'] as $timer => $timerdata )
    {
        $timerdata['start'] -= $_SERVER["REQUEST_TIME_FLOAT"];
        $timerdata['end']   -= $_SERVER["REQUEST_TIME_FLOAT"];
        $dif                = $timerdata['end'] - $timerdata['start'] ;

        $out    .= sprintf( "<tr><td>%s</td><td>%.3f</td><td>%.3f</td><td>%.3f</td><td>%s</td><td>%s</td><tr>\n"
        ,   $timer ?? '--' 
        ,   isset( $timerdata['start'] ) 
            ?   $timerdata['start'] 
            :   '!!' 
        ,   isset( $timerdata['end'] )
            ?   $timerdata['end'] 
            :   '!!'
        ,   1 < intval($dif)
            ?   $dif . '!!' 
            :   $dif . '_'
        ,   isset( $timerdata['note'] ) 
            ?   $timerdata['note'] 
            :   '?'
        ,   $timerdata['trace']
        );
    }
    $out    .= "\n</table>";

    return( $out );
}   // timer_show()

//----------------------------------------------------------------------

?>