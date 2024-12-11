<?php
/**
 *   @file       push.php
 *   @brief      Push to parent window
 *   @details    
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-03T21:20:20 / ErBa
 *   @version    2024-12-03T21:27:36
 */

//----------------------------------------------------------------------

/**
 *   @fn        pbar( $id, $max, $value, $note )
 *   @brief      Push to progressbar
 *   
 *   @param [in]	$id	    Bar ID
 *   @param [in]	$max	Max value
 *   @param [in]	$value	Current value
 *   @param [in]	$note	Note
 *   
 *   @since      2024-12-03T21:20:56
 */
function pbar( $id, $max, $value, $note )
{
    echo "<script>progress_bar( '{$id}', {$max}, {$value}, '{$note}' );</script>\n";
    echo "progress_bar( '{$id}', {$max}, {$value}, '{$note}' );\n";
    flush();
    ob_flush();
}   // pbar()

//----------------------------------------------------------------------

/**
 *   @fn        pstatus( $status )
 *   @brief      Status entry to status
 *   
 *   @param [in]	$status	Entry
 *   
 *   
 *   @code
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @since      2024-12-03T21:22:06
 */
function pstatus( $status )
{
    echo "<script>parent.document.getElementById( 'status' ).innerHTML += '- {$status}<br>';</script>\n";
    flush();
    ob_flush();
}   // pstatus()

//----------------------------------------------------------------------

/**
 *   @fn        pstate( $status )
 *   @brief      Plain text added to status
 *   
 *   @param [in]	$status	$(description)
 *   @retval     $(Return description)
 *   
 *   @details    $(More details)
 *   
 *   @code
 *   @endcode
@verbatim
@endverbatim
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-12-03T21:24:55
 */
function pstate( $status )
{
    echo "<script>parent.document.getElementById( 'status' ).innerHTML += '{$status}';</script>\n";
    flush();
    ob_flush();
}   // pstate()

//----------------------------------------------------------------------

?>