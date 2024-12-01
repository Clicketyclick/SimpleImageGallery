<?php
/**
 *   @file       reindex.php
 *   @brief      Rebuild searchindex for metadata,
 *   @details    Recursive processing file tree. 
 *   
 *   @todo		Needs a resume action on broken rebuild (WHERE exif IS NULL)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-18T13:10:55 / ErBa
 *   @version    @include version.txt
 */

include_once('lib/debug.php');
include_once('lib/_header.php');

// Update ALL
$count	= 0;

status( "Image resize type", $GLOBALS['config']['images']['image_resize_type'] );

//----------------------------------------------------------------------


// Resume or process all?
if ( isset( $_REQUEST['resume'] ) )
{	// Resume
	verbose( 'Indexing: Resume processing' );
	$sql	= $GLOBALS['database']['sql']['select_source_meta'];
	debug( $sql, 'SQL:' );
	
	$files 	= querySql( $db, $sql );
	foreach($files as $no => $path)
	{
		$files[$no]	= $path['files'];
	}

    $count  = 0;
    $total  = count( $files );
	foreach ( $files as $file )
	{
		if ( strpos( $file, "\\") )
		{
			unset( $files[$file] );
			$files[]	= str_replace( "\\", '/', "$file");
		}
        echo progressbar($count, $total, $GLOBALS['config']['process']['progressbar_size'], $file );
	}
	status('Resume', count( $files ));
}   //<<< Resume
else
{	// Process all
	$sql	= $GLOBALS['database']['sql']['select_source_meta'];
	debug( $sql, 'SQL:' );
	
	$files 	= querySql( $db, $sql );
	$total	= count( $files );
	debug( $files, 'Files');

	$loadfile	= fopen( 'loadfile.txt', 'w');
    $GLOBALS['tmp']['keycount']    = 0;
    $GLOBALS['tmp']['imagecount']  = 0;
    
	foreach($files as $no => $data)
	{
		$GLOBALS['tmp']['imagecount']++;
		$file           = $data['file'];
        // Parse metadata
		$data['iptc']	= json_decode( $data['iptc'], TRUE);
		//$data['exif']	= json_decode( $data['exif'], TRUE);

		array2breadcrumblist($data['iptc'], $iptc );
		//array2breadcrumblist($data['exif'], $exif );
        
        process_search( $iptc, $data['rowid'], $file );
        //process_search( $exif, $no, $file );

		echo progressbar($GLOBALS['tmp']['imagecount'], $total, $GLOBALS['config']['process']['progressbar_size'], $file );
	}
}   //<<< Process all

//----------------------------------------------------------------------


/**
 *   @brief      remove old entries and add new entries
 *   
 *   @param [in]	$iptc   Data array
 *   @param [in]	$file	File key (source + file)
 *   @return     $(Return description)
 *   
 *   @details    $(More details)
 *   
 *   @since      2024-11-19T22:33:07
 */
function process_search( $iptc, $no, $file )
{
    global $db;

    // Remove old entries!
    $sql	= sprintf( 
                    $GLOBALS['database']['sql']['delete_search']
                ,	$file
            );
    $r  = $db->exec( $sql );

    // Insert new
    foreach( $iptc as $key => $value )
    {
        foreach( $GLOBALS['database']["search"]["iptc"] as $iKey => $iValue )
        {
            if ( str_starts_with( $key, $iKey ) )
            {
                $sql	= sprintf( 
                    $GLOBALS['database']['sql']['insert_search']
                ,	$file
                ,	$no
                ,	"$iValue$value"
                ,	strtolower("$iValue$value")
                );
                //fputs( $loadfile, "$sql\n" );
                $r  = $db->exec( $sql );
                $GLOBALS['tmp']['keycount']++;
            }
        }
    }
}   // process_iptc_search()

//----------------------------------------------------------------------

/**
 *   @brief      Run at shutdown
 *   
 *   @details    
 *    This is our shutdown function, in 
 *    here we can do any last operations
 *    before the script is complete.
 *   
 *   @since      2024-11-15T13:28:32
 */
function shutdown( )
{
	fputs( STDERR, "\n\n");

	status( "Keywords", $GLOBALS['tmp']['keycount'] ?? 0 );
	status( "Images processed", $GLOBALS['tmp']['imagecount'] ?? 0);

    // Session duration
	$Runtime    = microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	status( "Runtime ", microtime2human( $Runtime ) );
	status( "Log", $GLOBALS['logfile']  ?? 'none');
}	// shutdown()

//----------------------------------------------------------------------

?>