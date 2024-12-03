<?php
 
/*
 
Copyright (c) 2010, dealnews.com, Inc.
All rights reserved.
 
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
 
 * Redistributions of source code must retain the above copyright notice,
   this list of conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright
   notice, this list of conditions and the following disclaimer in the
   documentation and/or other materials provided with the distribution.
 * Neither the name of dealnews.com, Inc. nor the names of its contributors
   may be used to endorse or promote products derived from this software
   without specific prior written permission.
 
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
 
 */
 
/**
 *  @file       progress_bar.php
 *  @brief      show a status bar in the console
 *  
 *  @details    
 * 
 * 
 * @code
 * for($x=1;$x<=100;$x++){
 *     echo show_status($x, 100);
 *     usleep(100000);
 * }
 * @endcode
 *
 * @param   int     $done   how many items are completed
 * @param   int     $total  how many items are to be done total
 * @param   int     $size   optional size of the status bar
 * @return  void
 *
@code
for($x=1;$x<=100;$x++){
     echo show_status($x, 100);
     usleep(100000);
}
@endcode
 * @note
 *  Updates: 2024-08-22T12:00:37/ErBa
 *  
 *      static $width;  added
 *      sprintf(        added on status
 *      $runtime        in output
 *      $timeframe      as width of runtime, eta and elapsed
 *      getClockface()
 *  
 *  ⧔   U+29D4  🞂<  Runtime Times with Left Half Black. Miscellaneous Mathematical Symbols-B.
 *  ⧕   U+29D5  >⯇  Times with Right Half Black. Miscellaneous Mathematical Symbols-B.
 *  η   eta
 *  
 * @see     https://stackoverflow.com/a/9853018
 */
function show_status($done, $total, $size=30) {
    static $width;
    static $timeframe;
    static $start_time;
 
    // if we go over our bound, just ignore it
    if($done > $total)  return("OVERRUN: $done");
    if( 0 >= $done )    return('UNDEF');
 
    if(empty($start_time)) $start_time=time();
    if(empty($width)) $width    = strlen( $total );
    $now = time();
 
    $perc=(double)($done/$total);
 
    //$bar=floor($perc*$size);
    $bar=floor($perc*$size);
    if ( 0 > $bar )
        $bar    = 1;
 
    $status_bar="\r[";
    $status_bar.=str_repeat("=", $bar);
    if($bar<$size){
        $status_bar.=">";
        $status_bar.=str_repeat(" ", $size-$bar);
    } else {
        $status_bar.="=";
    }
 
    $disp=number_format($perc*100, 0);
 
    $rate = ($now-$start_time)/$done;
    $left = $total - $done;
    $eta = round($rate * $left, 2);
 
    $elapsed = $now - $start_time;
    $runtime    = $elapsed + $eta;
    if(empty($timeframe)) $timeframe    = strlen( $runtime ) + 1;

    //$status_bar.="] $disp%  $done/$total";
    $status_bar.= sprintf( "] %3.3s%% %s %*s/%-10s"
    ,   $disp
    ,   getClockface( $disp )
    ,   $width
    ,   $done
    ,   $total
    );

    //$status_bar.= " remaining: ".number_format($eta)." sec. elapsed: ".number_format($elapsed)." sec.";
    //$status_bar.= sprintf( " remaining \u{023F3}: %*s sec. elapsed \u{023F1}: %*s sec. eta \u{1D702} %*s "
    //$status_bar.= sprintf( " remain.\u{29D5}: %*s sec. elap.\u{29D4}: %*s sec. eta.\u{1D702} %*s "
    $status_bar.= sprintf( " elap.\u{29D4}: %*s sec. remain.\u{29D5}: %*s sec. eta.\u{1D702} %*s "
    ,   $timeframe, number_format($elapsed)
    ,   $timeframe, number_format($eta)
    ,   $timeframe, number_format($elapsed + $eta)  // Runtime
    );
 
    return( "$status_bar" );
}	// show_status

/**
 *  @fn        getClockface
 *  @brief     Convert pct value to clock face
 *  
 *  @param [in] $pct Pct value
 *  @return    Clock face Unicode character
 *  
 *  @details   More details
 *  🕐  U+1F550  CLOCK FACE ONE OCLOCK
 *  🕜  U+1F55C  CLOCK FACE ONE-THIRTY
 *  🕑  U+1F551  CLOCK FACE TWO OCLOCK
 *  🕝  U+1F55D  CLOCK FACE TWO-THIRTY
 *  🕒  U+1F552  CLOCK FACE THREE OCLOCK
 *  🕞  U+1F55E  CLOCK FACE THREE-THIRTY
 *  🕓  U+1F553  CLOCK FACE FOUR OCLOCK
 *  🕟  U+1F55F  CLOCK FACE FOUR-THIRTY
 *  🕔  U+1F554  CLOCK FACE FIVE OCLOCK
 *  🕠  U+1F560  CLOCK FACE FIVE-THIRTY
 *  🕕  U+1F555  CLOCK FACE SIX OCLOCK
 *  🕡  U+1F561  CLOCK FACE SIX-THIRTY
 *  🕖  U+1F556  CLOCK FACE SEVEN OCLOCK
 *  🕢  U+1F562  CLOCK FACE SEVEN-THIRTY
 *  🕗  U+1F557  CLOCK FACE EIGHT OCLOCK
 *  🕣  U+1F563  CLOCK FACE EIGHT-THIRTY
 *  🕘  U+1F558  CLOCK FACE NINE OCLOCK
 *  🕤  U+1F564  CLOCK FACE NINE-THIRTY
 *  🕙  U+1F559  CLOCK FACE TEN OCLOCK
 *  🕥  U+1F565  CLOCK FACE TEN-THIRTY
 *  🕚  U+1F55A  CLOCK FACE ELEVEN OCLOCK
 *  🕦  U+1F566  CLOCK FACE ELEVEN-THIRTY
 *  🕛  U+1F55B  CLOCK FACE TWELVE OCLOCK
 *  🕧  U+1F567  CLOCK FACE TWELVE-THIRTY
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2024-08-28T11:27:21 / erba
 *  
 */
function getClockface( $pct )
{
    switch( TRUE ) {
        case $pct >= 99: $clock  = "\u{1F55B}"; break;
        case $pct >= 98: $clock  = "\u{1F566}"; break;
        case $pct >= 95: $clock  = "\u{1F564}"; break;
        case $pct >= 90: $clock  = "\u{1F558}"; break;
        case $pct >= 85: $clock  = "\u{1F563}"; break;
        case $pct >= 80: $clock  = "\u{1F557}"; break;
        case $pct >= 75: $clock  = "\u{1F562}"; break;
        case $pct >= 70: $clock  = "\u{1F556}"; break;
        case $pct >= 65: $clock  = "\u{1F561}"; break;
        case $pct >= 60: $clock  = "\u{1F555}"; break;
        case $pct >= 55: $clock  = "\u{1F560}"; break;
        case $pct >= 50: $clock  = "\u{1F554}"; break;
        case $pct >= 45: $clock  = "\u{1F55F}"; break;
        case $pct >= 40: $clock  = "\u{1F553}"; break;
        case $pct >= 35: $clock  = "\u{1F55E}"; break;
        case $pct >= 30: $clock  = "\u{1F552}"; break;
        case $pct >= 25: $clock  = "\u{1F55D}"; break;
        case $pct >= 20: $clock  = "\u{1F551}"; break;
        case $pct >= 15: $clock  = "\u{1F55C}"; break;
        case $pct >= 10: $clock  = "\u{1F550}"; break;
        default:         $clock  = "\u{1F567}";
    }
    return( $clock );
}   // getClockface()


/**
 *   @brief      simple progress bar
 *   
 *   @param [in]	$done		Count
 *   @param [in]	$total		Total 
 *   @param [in]	$value	    Current value
 *   @param [in]	$trailer    String to append
 *   @param [in]	$length=30	Max. length of string incl. trailer
 *   @return     Bar as string
 *   
 *   @details    
 *   
@code
for($x=1;$x<=100;$x++){
     echo progressbar($x, 100) . "count[$x]";
     usleep(100000);
}
@endcode
 *   
 *   @since      2024-11-16T13:04:08
 */
function progressbar($done, $total, $size=30, $trailer = FALSE, $length = 50 ) 
{
    include_once( __DIR__.'./handleStrings.php');
    static $width;
	static $wheel	= ['-','\\', '|','/'];
	
    // if we go over our bound, just ignore it
    if($done > $total)  return("OVERRUN: $done");
    if( 0 >= $done )    return('UNDEF');
 
    if(empty($width)) $width    = strlen( $total );
 
    $perc=(double)($done/$total);
 
    $bar=floor($perc*$size);
    
    if ( 0 > $bar )
        $bar    = 1;
 
    $progressbar="\r[";
    $progressbar.=str_repeat("=", $bar);
    if($bar<$size){
        //$progressbar.=">";
        $progressbar.= $wheel[ $done % 4];
        $progressbar.=str_repeat(" ", $size - $bar);
    } else {
        $progressbar.="=";
    }
 
    $disp=number_format($perc*100, 0);
 
    $left = $total - $done;

    $progressbar.= sprintf( "] %3.3s%% %*s/%-*s %s"
    ,   $disp
    ,   $width
    ,   $done
    ,   $width
    ,   $total
    ,   ( FALSE === $trailer )    // Format trailing string
        ?   ''
        :   str_pad( truncate_center_word( "$trailer", $length - 5), $length)
    );

    return( "$progressbar" );
}	// progressbar

?>