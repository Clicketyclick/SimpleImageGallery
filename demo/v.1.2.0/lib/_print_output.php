<?php
/**
 *  @file       _print_output.php
 *  @brief      Print output to parent
 *  
 *  @details    More details
 *  
 *  @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since      2023-12-04T11:49:35 / Bruger
 *  @version    2024-03-16 01:15:08
 */
//$test_mode  = FALSE;
//$test_mode  = TRUE;

if ( ! isset( $test_mode ) ) $test_mode  = TRUE;

//require_once( "lib/handleFiles.php" );
//require_once( "lib/handleStrings.php");

//if ($test_mode) file_put_contents( "../output1.txt", $output );

    $struct = 
    [
        "language"  => 'en',
        "starttime" => microtime(true),
        "output"    => $output
    ];

    // Write struct to JSON
    // NOTE Use JSON_INVALID_UTF8_SUBSTITUTE or the encoding will break!
    //$output     = json_encode( $struct,  JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE , 9999);
    $output         = json_encode( $struct,   JSON_PARTIAL_OUTPUT_ON_ERROR | JSON_THROW_ON_ERROR | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT );
    
    $dir            = getcwd();
    
    // Check for tmp directory
    $jsonfile       = "../tmp/";
    $jsonfile       = "./";
    if ( ! file_exists( $jsonfile ) )
    {
        trigger_error( "Directory not found: [{$jsonfile}] - created", E_USER_WARNING );
        mkdir( $jsonfile );
    }
    $jsonfile       .= session_id() . ".json";
    debug("$dir/$jsonfile", "JSON-file" );
    file_put_contents( "$jsonfile", $output );

    $tmp_url        = getUrlStub();

    debug( "{$tmp_url}{$jsonfile}");
    print "<script>parent.setOutput( '{$tmp_url}{$jsonfile}' );</script>";

    debug( $_SERVER, "Server" );
//}

?>