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
 *   
 *  @todo      Update headers
 *  @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since      2022-12-03T09:02:25 / Erik Bachmann
 *  @version    2024-02-28T06:23:44 / Erik Bachmann
 */
 
//---------------------------------------------------------------------

/**
 *  @brief     Debug message with location
 *  
 *  @param [in] $str	String to write
 *  @param [in] $extend	Flag for file info
 *  @return    VOID
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
 *
 *
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
 *  @brief     Verbose message
 *  
 *  @param [in] $str	String to display on STDERR if in verbose mode
 *  @return    VOID
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
    //global $verbose;
    //if ( $GLOBALS['verbose'] ?? false )
	if ( ( $_SESSION['verbose'] ?? false ) || ( $GLOBALS['verbose'] ?? false ) || ( getenv('VERBOSE') ?? false ) ) 
    {
        //$bt     = debug_backtrace();
        //$caller = array_shift($bt);
        $bt     = debug_backtrace()[1] ?? debug_backtrace()[0] ;
        $caller = $bt;
        $func   = (__FUNCTION__ == $caller['function']) ? 'MAIN' : $caller['function'];

        if ('cli' === PHP_SAPI ) {
            //fprintf( STDERR, "%s[%s](%s): %s\n", basename($caller['file']), $caller['line'], $func, $str );
            fprintf( STDERR, "- %s%s\n", ($extend ?? '?') , $str );
        } else {
            //printf( "%s[%s](%s): %s\n", basename($caller['file']), $caller['line'], $func, $str );
            printf( "<tr><th>%s</th><td>%s</td></tr>\n", ($extend ?? '?') , $str );
        }
    }
}   // verbose()

//---------------------------------------------------------------------

/**
 *  @brief     Generates log intry in $GLOBALS['logfile'] if $GLOBALS['logging'] flag set
 *  
 *  @param [in] $str        Log string
 *  @return    VOID
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
 *  @brief        Return string w. file.function.stringtoken
 *  
 *  @param [in]   $str      Token to return
 *  @return       string w. file.function.string
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
 *  @fn        progress_per
 *  @brief     Generates a user-level message foreach x itterations
 *  
 *  @param [in] $var      Description for $var
 *  @param [in] $limit    Description for $limit
 *  @param [in] $function Description for $function
 *  @param [in] $type     Description for $type
 *  @return    Return description
 *  
 *  @details   For each limit ittrations in $var a status message is generated
 *  
 *  @example   
 *      for ( $x=1 ; $x < 100 ; $x++ )
 *      {
 *         progress_per( $x, 20, 'trigger_error', "loop=[\$var]");
 *         progress_per( $x, 30, 'trigger_error', FALSE, E_USER_WARNING);
 *      }
 *  
 *  Notice (loop=) is given on values 20,40,60,80. Warning (var=) on 30,60,90
 *  
 *  
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
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
function status( $tag, $value )
{
	verbose( sprintf( "%-30.30s [%s]", $tag, $value ) );
}

?>