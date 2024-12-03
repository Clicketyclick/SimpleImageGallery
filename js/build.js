/**
 *   @file       build.js
 *   @brief      JavaScript functions for build.php
 *   @details    Updating progress_bar and status
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-12-03T10:24:47 / ErBa
 *   @version    2024-12-03T10:24:47
 */


/**
 *   @brief      Set/update progressbar
 *   
 *   @param [in]	id		ID for bar to update
 *   @param [in]	max		Max value (for pct. calc)
 *   @param [in]	value	Current value
 *   @param [in]	note	Note to append
 *   
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
 *   @since      2024-12-03T10:25:28
 */
function progress_bar( id, max, value, note ) {
    if (undefined === note) { note = '?'; }
    
    var elem    = parent.document.getElementById( id );
    var status  = parent.document.getElementById( id +"_status");
    pct     = value * 100 / max;

    elem.value          = pct;
    status.innerHTML    = Math.trunc( pct ) +'% '+note;
}   // progress_bar()

function setStatus( str )
{
    parent.document.getElementById( 'status' ).innerHTML += str;
}

console.log( 'build.js loaded');