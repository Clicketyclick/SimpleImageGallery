<?php

function reindex_wordclouds()
{
    global $db;
    $wcJson         = "config/wordclouds.json";
    $cloudno        = 0;
    $recno          = 0;
    $cloudscount    = 0;

    echo "- [{$_REQUEST['action']}]";
        
    $GLOBALS['timers']['reindex_all']	= microtime(TRUE);

    $wordclouds     = json_decode( file_get_contents( $wcJson ), TRUE );

    // Clear table: wordclouds
    $sql	= $GLOBALS['database']['sql']['delete_all_wordclouds'];
    $db->exec( $sql );

    // Count all clouds
    foreach ( $wordclouds as $cloud => $details ) {
        $cloudscount++;
        foreach ( $details['keys'] as $key )
        {
            $cloudscount++;
        }
    }

    status( "{$cloudscount}", ___('no_of_clouds') );
        
    //$cloudscount    = array_count_values( $wordclouds );
    //status( "{$cloudscount}", ___('no_of_clouds') );
//exit;
    foreach ( $wordclouds as $cloud => $details ) {
        //setStatus( "<li>[$cloud] [". join( "]+[", $details['keys'] ) . "] </li>" );
        status( join( "]+[", $details['keys'] ) . "]", "[$cloud]" );
     
        $cloudno++;
      
        foreach ( $details['keys'] as $key )
        {
            $recno++;
            //$sql    = sprintf( $sql_config['wordclouds_insert'] 
            $sql    = sprintf( $GLOBALS['database']['sql']['wordclouds_insert'] 
            ,   $cloud
            ,   $key.'%'
            );

            $result = $db->exec( $sql );

            $cloudno++;
            echo progressbar( $cloudno, $cloudscount, $GLOBALS['config']['process']['progressbar_size'], $key );
            //ob_flush(); 
            flush();

            
            if ( ! ( $recno % 1000 ) )
            {
                $currenttime  = microtime( TRUE );
                error_log( 
                    sprintf(
                        "loop: %s : duration: %s  - av. %.5s : %s.5‰"
                        ,   number_formatted($$recno)
                        ,   microtime2human( $currenttime - $starttime ) 
                        ,   microtime2human( ( ($currenttime - $starttime ) / $recno) * 1000 )
                    )
                );
            }
        }
    }

    // Get row count from wordclouds
    //$sql	= sprintf( $sql_config['count_rows'], "wordclouds" );
    $sql	= $GLOBALS['database']['sql']['select_count_wordclouds'];
    //$count	= executeSql( $db, $sql );   // Prepares an SQL statement for execution, execute and return result as array
    $count	= querySqlSingleValue( $db, $sql );   // Prepares an SQL statement for execution, execute and return result as array

    status( var_export($count, TRUE), ___('no_of_rows') );

    $sql    = "SELECT cloudname, entry, count FROM wordclouds;";
    $result = $db->query($sql);

    ob_flush(); 

    status( ___('calculate_norm'),'' );

    $row    = 0;
    $normset    = [ 'high' => 0, 'low' => 9999];

    while( $data = $result->fetchArray(SQLITE3_ASSOC) )
    {
        $norm   = intval( ( 50 *  log((log10( ( $data['count'] )+1)) *100)) - 70);
        
        $normset['high']    = ( $norm > $normset['high'] ? $norm : $normset['high'] );
        $normset['low']     = ( $norm < $normset['low'] ? $norm : $normset['low'] );
        
        $sql    = sprintf( $GLOBALS['database']['sql']['wordclouds_update_norm']
        ,   $norm
        ,   $data['cloudname']
        ,   SQLite3::escapeString( $data['entry'] )
        );
        
        $db->exec( $sql );
            
        $row++;
        //setProgress( $count[0]['count'], $row );
        //echo progressbar( $cloudno, $cloudscount, $GLOBALS['config']['process']['progressbar_size'], $data );

        //ob_flush(); 
        flush();
    }

    status(  $normset['high'], ___('norm_high') );
    status( $normset['low'], ___('norm_low') );

    // Logging ------------------------------------------------------------

    $time_end   = microtime(true);
    //$time       = $time_end - $GLOBALS['timers']['reindex_all'];
    //status( $time, ___('runtime') );

    $noofterms	= getSqlMaxRowId( $db, 'search' );
    status(  $noofterms, ___('no_of_cloudwords') );
    status( ___('done'),'' );

    //insertAudit( $db, "", "RebuildCloud", "", "", $time_start, $time_end, $time );


    list($sec, $usec) = explode('.', microtime( TRUE ) - $GLOBALS['timers']['reindex_all'] ); //split the microtime on .
    $runtime    = date('H:i:s.', $sec) . $usec;       //appends the decimal portion of seconds

    error_log( ___('rebuild_runtime'). ": $runtime ".___('no_of_indexterms').": $noofterms");
    
    pstatus( ___('wordcloud_entries'). " : {$row}/{$count}" );
    //pstatus( var_export($count, TRUE) );
    //trigger_error( var_export($count, TRUE), E_USER_ERROR );
}

?>