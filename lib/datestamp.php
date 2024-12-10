<?php
/**
 * @file      ./lib/datestamp.php
 *
 * @brief     Handling creating and converting dates and date stamps.
 * @details
 * Function | brief
 * ---|---
 * timestamp_iso8601()          | UNIX timestamp to ISO 8601 (GMT)
 * timestamp_iso8601_local()    | UNIX timestamp to ISO 8601 (local time)
 * iso8601_timestamp()          | ISO8601 to UNIX timestamp
 * now_timestamp()              | Return UNIX timestamp for NOW
 * now_iso8601()                | Return ISO 8601 timestamp for NOW
 * now_iso8601_filestamp()      | Return ISO 8601 timestamp for NOW formated for filename
 * microTimestamp()             | Microtime to human readable.
 * microtime_float()            | Return microtime as float.
 * microtime2human              | Return a human readable version of microtime (float)
 * 
 * @copyright   http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 * @author      Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 * @deprecated  no
 * @since       2019-02-01T07:24:04
 * @version     2023-03-06T16:08:12
 */

//---------------------------------------------------------------------

/**
 * @fn          timestamp_iso8601($timestamp)
 * @brief           UNIX timestamp to ISO 8601 (GMT)
 *
 * @param $timestamp UNIX timestamp        
 * @retval string   Date in ISO 8601 format [YYYY-MM-DDThh:mm:ss]
 *
 * @note tutorial doc/manual.md
 *
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function timestamp_iso8601($timestamp)
{
    $idate = date_format(date_create('@'. $timestamp), 'c') ;
    return($idate);
}   // timestamp_iso8601

//---------------------------------------------------------------------

/**
 * @fn              timestamp_iso8601_local($timestamp) 
 * @brief           UNIX timestamp to ISO 8601 (local time)
 *
@code
// Current files creation date in ISO format
echo timestamp_iso8601_local( filectime( $argv[0] ) );
@endcode
@verbatim
2019-09-06T05:45:31+00:00
@endverbatim
 *
 * @param $timestamp UNIX timestamp        
 * @retval string   Date in ISO 8601 format (YYYY-MM-DDThh:mm:ss)
 * @note tutorial        doc/manual.md
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function timestamp_iso8601_local($timestamp) 
{
    //2022-11-14T14:22:33/EBP  Implicit conversion from float: (int) $timestamp
    $idate = date_format(date_timestamp_set(new DateTime(), (int) $timestamp), 'c');
    return($idate);
}   // timestamp_iso8601_local()

//---------------------------------------------------------------------

/**
 * @fn          iso8601_timestamp($idate) 
 * @brief           ISO8601 to UNIX timestamp
 *
 * @param $idate    ISO time string
 * @retval string   UNIX timestamp
 *
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function iso8601_timestamp($idate) 
{
    return strtotime($idate);
}   // iso8601_timestamp()

//---------------------------------------------------------------------

/**
 * @fn          now_timestamp()
 * @brief           Return UNIX timestamp for NOW
 *
 * @retval string   UNIX timestamp
 *
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function now_timestamp() 
{
    return date_format( date_create(), 'U');
}   // now_timestamp()

//---------------------------------------------------------------------

/**
 * @fn          now_iso8601() 
 * @brief           Return ISO 8601 timestamp for NOW
 *
 * @retval string   ISO 8601 timestamp
 *
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function now_iso8601() 
{
    $timestamp = date_timestamp_get( date_create() );
    return date_format(date_timestamp_set(new DateTime(), $timestamp), 'c');
}   // now_iso8601()

//---------------------------------------------------------------------

/**
 * @fn          now_iso8601_filestamp() 
 * @brief           Return ISO 8601 timestamp for NOW formated for filename
 * Time formated as "hh-mm-ss" in stead of "hh:mm:ss"
 *

 * @retval string   ISO 8601 filename
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function now_iso8601_filestamp() 
{
    //$timestamp = date_timestamp_get( date_create() );
    //return date_format(date_timestamp_set(new DateTime(), $timestamp), 'c');
    return( gmDate( "Y-m-d\TH-i-s" ) );;
}   // now_iso8601_filestamp()

//---------------------------------------------------------------------

/**
 * @fn          microTimestamp($mt) 
 * @brief           Microtime to human readable.
 * @note     Date information is truncated. Only hour, min, etc. are returned
 *
@code
 * $mt = microtime_float();
 * $mtiso  = microTimestamp( $mt );
 * echo  "[$mt] [$mtiso]" . PHP_EOL;
 * echo microTimestamp( microtime_float(), 8 );
@endcode
@verbatim
[1567748216.2247] [05:36:56,22469592]
@endverbatim
 *
 * @param $mt        Micro time stamp
 * @retval string   ISO 8601 time stamp
 *
 * @see             https://stackoverflow.com/a/35523439/7485823
 * @since           2019-02-04T08:53:59
 */
function microTimestamp($mt) 
{
    return( substr( timestamp_iso8601_local($mt) , 11, 8) . "," . substr( $mt - intval($mt), 0, 4 ));
}   // microTimestamp()

//---------------------------------------------------------------------

/**
 * @fn          microtime_float() 
 * @brief           Return microtime as float.
 *
 * @details           Replicate PHP 5 behaviour
 *
@code
 * echo microtime_float();
@endcode
@verbatim
1567748785.9552
@endverbatim
 *
 * @retval float    microtime as float
 *
 * @deprecated  Use microtime( TRUE );
 *
 * @see             https://php.net/microtime
 * @since           2019-09-06T07:50:46
 */
function microtime_float() 
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

//---------------------------------------------------------------------

/**
 * @fn          microtime2human( $microtime )
 *  @brief     Return a human readable version of microtime (float)
 *  
 *  @details   Uses ISO-86001 for periode: P[n]Y[n]M[n]DT[n]H[n]M[n]S
 *
 *  @param [in] $microtime    microtime value
 *  @retval    ISO formated string
 *  
 *  
@code
 *      echo microtime2human( "86399.0" ) .PHP_EOL;
 *      echo microtime2human( "87000.0" ) .PHP_EOL;
 *      echo microtime2human( "870000.0" ) .PHP_EOL;
 *      echo microtime2human( "8700000.0" ) .PHP_EOL;
 *      echo microtime2human( "87000000.0" ) .PHP_EOL;
@endcode
@verbatim
23:59:59.0
P1DT00:10:00.0
P10DT01:40:00.0
P3M10DT16:40:00.0
P2Y9M6DT22:40:00.0
@endverbatim
 *  
 *  @see       https://stackoverflow.com/a/16825451/7485823 - How to convert microtime() to HH:MM:SS:UU
 *  @see       https://en.wikipedia.org/wiki/ISO_8601#Durations
 *  @since     2023-01-27T12:35:18 / erba
 */
function microtime2human( $microtime )
{
    if ( ! isset($microtime)) return( "?");
    if (   empty($microtime)) return( "?");
    
    list($sec, $usec) = explode('.', $microtime);   //split the microtime on .
    
    $usec   = str_replace("0.", ".", $usec);          //remove the leading '0.' from usec
    
    $date   = "";
    if ( $sec > (365*24*60*60) )    // Years
    {
        $date   .= intval( $sec / (365*24*60*60) )
        .   "Y";
        $sec %= (365*24*60*60);
    }
    if ( $sec > (30*24*60*60) )    // Months
    {
        $date   .= intval( $sec / (30*24*60*60) )
        .   "M";
        $sec %= (30*24*60*60);
    }
    if ( $sec > (24*60*60) )        // Days
    {
        $date   .= intval( $sec / (24*60*60) )
        .   "D";
        $sec %= (24*60*60);
    }
    return (
        ( $date ? "P{$date}T" : "" )
        .   date('H:i:s', $sec) 
        .   '.' 
        . $usec
        );       //appends the decimal portion of seconds
}   // microtime2human()


//---------------------------------------------------------------------

/**
 * @fn          progress_log( $max, $count, $starttime, $level = 1000 )
 *  @brief      Log progress
 *  
 *  @param[in] $max       Max value in progress
 *  @param[in] $count     Current value
 *  @param[in] $starttime Start time in mcrotime
 *  @param[in] $level     Per xx (Defalt: 1/1000)
 *  @retval     Log entry
 *  
@code
	$GLOBALS['timers']['resume']	= microtime(TRUE);
	::
	progress_log( $max, $count, $GLOBALS['timers']['resume'], $level = 1000 )
@endcode
 *
@code
 *      $starttime  = microtime( TRUE );
 *      for ( $count = 1 ; $count <= $max ; $count++ ) 
 *      {
 *          progress_log( $max, $count, $starttime);
 *          time_nanosleep(0, 5000);
 *      }
@endcode
@verbatim
 *      [Tue Apr 16 10:49:35 2024] loop: 1,000/12,275 : duration: 00:00:40.556  - av. 00:00 : 00:00:40.556.5‰: ETA 00:07:37.278
@endverbatim
 *  
 *  
 *  @see        https://
 *  @since      2024-04-22T22:54:29 / Bruger
 */
function progress_log( $max, $count, $starttime, $level = 1000 )
{
    $count  = $count ? $count  : 1;

    if ( ! ( $count % $level ) )
    {
        $currenttime  = microtime( TRUE );
        $av         = ( $currenttime - $starttime ) / $count;
        $remainder  = $max - $count;
        $msg        = sprintf(
                "loop: %s/%s : duration: %s  - av. %.5s : %s.5‰: ETA %s"
                ,   number_formatted($count)
                ,   number_formatted($max)
                ,   substr(microtime2human( $currenttime - $starttime ), 0, 12)
                ,   microtime2human( $av )
                ,   substr(microtime2human( $av * $level ), 0, 12)
                ,   substr(microtime2human( $av * $remainder ), 0, 12)
            );
        //error_log( $msg );
        //setStatus( $msg );
        return( $msg );
    }
}   // progress_log()

//---------------------------------------------------------------------

?>