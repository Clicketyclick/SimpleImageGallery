<?php
/**
 *   @file       update_sqlite.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-23T20:45:59 / ErBa
 *   @version    2024-11-23T20:45:59
 */

/*
-- export
.mode csv
.output dirs.csv
SELECT * from dirs;
.output

-- import
.mode csv
DELETE FROM dirs;
.import ./dirs.csv dirs

*/

parse_cli2request();
debug( $_REQUEST, 'Request' );


debug("Opening database",  $_REQUEST['dbfile']);
$db	= openSqlDb($_REQUEST['dbfile']);


//----------------------------------------------------------------------



/**
 *  @brief     Parse cli arguments and insert into $_REQUEST
 *  
 *  @details   Store in global variable
 *  
@code
parse_cli2request(); var_export( $_REQUEST );
@endcode
 *  
 *  @see       https://stackoverflow.com/a/37600661
 *  @since     2024-06-25T09:09:12 / erba
 */
function parse_cli2request()
{
    global $argv;
    
    // CLI or HTTP
    // https://stackoverflow.com/a/37600661
    if (php_sapi_name() === 'cli') {
        // Remove '-' and '/' from keys
        for ( $x = 0 ; $x < count($argv) ; $x++ )
            $argv[$x]   = ltrim($argv[$x], '-/');
        // Concatenate and parse string into $_REQUEST
        parse_str(implode('&', array_slice($argv, 1)), $_REQUEST);
    }
}   // parse_cli2request()
