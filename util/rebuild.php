<?php
/**
 *   @file       rebuild.php
 *   @brief      Rebuild database with files and metadata,
 *   @details    Recursive processing file tree. 
 *   
 *   @todo		Needs a resume action on broken rebuild (WHERE exif IS NULL)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-11T06:14:36 / ErBa
 *   @version    @include version.txt
 */

include_once('lib/_header.php');

status( "Image resize type", $_SESSION['config']['images']['image_resize_type'] );
debug( $_SESSION['config'], 'Config after $_REQUEST' );

// Init global variables
$files		= [];
$cfg_normalise    = [
'/\.jpg$/i' => '',  // Extention
'/^(19|20)\d\d-\d\d-\d\d[T,_,\.,\s]\d\d-\d\d-\d\d[_,\.,\s]/' => '', // Full ISO
'/^(19|20)\d\d-\d\d-\d\d[T,_,\.,\s]/' => '',    // Date only
];

//----------------------------------------------------------------------

switch ( $_REQUEST['action'] ?? 'noaction')
{
    case 'update':
        // Rebuild metadata
        // Build indexes
        rebuild_update();
    break;
    case 'resume':
        // Read all files
            // add_new_files
            // Rebuild metadata
            // Build indexes
        rebuild_resume();
    break;
    default:
    case 'full':
        // clear Tables
        // Read all files
        // Rebuild metadata
        // Build indexes
        rebuild_full();
        rebuild_update();
        post_proccessing();
    break;
}

function clear_tables()
{
    global $db;
    $r  = $db->exec( $_SESSION['database']['sql']['delete_all_search'] );
    $r  = $db->exec( $_SESSION['database']['sql']['delete_all_images'] );
}   //clear_tables()

// Resume or process all?
function rebuild_resume()
{	// Resume
    global $db;
	verbose( 'Resume processing' );
	$_SESSION['timers']['resume']	= microtime(TRUE);

	// Select resume files
	$sql	= $_SESSION['database']['sql']['select_files_resume'];
	debug( $sql, 'SQL:' );
	$files 	= querySql( $db, $sql );

	// Isolate file
	foreach($files as $no => $path)
	{
		$files[$no]	= $path['files'];
	}

	// Flatten
	foreach ( $files as $file )
	{
		if ( strpos( $file, "\\") )
		{
			unset( $files[$file] );
			$files[]	= str_replace( "\\", '/', "$file");
		}
	}

	status('Resume', count( $files ));
	logging( progress_log( count( $files ), count( $files ), $_SESSION['timers']['resume'], 1 ) );
}   // rebuild_resume()


function rebuild_full()
{	// Process all
	verbose( 'Process all' );
	verbose( 'Clear tables' );
    clear_tables();
    
	$_SESSION['timers']['rebuild_full']	= microtime(TRUE);

	$_SESSION['timers']['get_images_recursive']	= microtime(TRUE);
	verbose( '// Find all image files recursive' );
	// Find all image files recursive
	getImagesRecursive( $_SESSION['config']['data']['data_root'], $_SESSION['config']['data']['image_ext'], $files, ['jpg'] );
	logging( progress_log( count( $files ), 1, $_SESSION['timers']['get_images_recursive'], 1 ) );
	debug( $files );

	status( "Processing files", count( $files ));
	$_SESSION['timers']['put_files_to_database']	= microtime(TRUE);
	
	// Put all files to database: images
	putFilesToDatabase( $files );
	logging( progress_log( count( $files ), 1, $_SESSION['timers']['put_files_to_database'], 1 ) );
}   // rebuild_full()

function rebuild_update()
{	// Process all
    global $db;
	verbose( 'Update' );
    verbose( '// Write meta data, thumb and view for each file' );

    $_SESSION['timers']['add_meta']     = microtime(TRUE);
    
    //var_export($_SESSION['database']['sql']['select_all_files']);
    //echo($_SESSION['database']['sql']['select_all_files']);

    $files  = array_flatten( querySql( $db, $_SESSION['database']['sql']['select_all_files'] ) );
    //var_export($files);
    //exit;
    
    $_SESSION['tmp']['images_total']    = count($files);
    $count      = 0;

    foreach ( $files as $file )
    {
        $r  = $db->exec( "BEGIN TRANSACTION;" );
        $currenttime	= microtime( TRUE );
        ++$count;

        ['basename' => $basename, 'dirname' => $dirname] = pathinfo( $file );
        $note	= "";
        debug( $file );

        // Get image dimentions
        list($width, $height, $type, $attr) = getimagesize($file);

        // Get EXIF - in quiet mode
        $exif 	= @exif_read_data( $file, 0, true);
        if ( empty( $exif ) )
        {
            logging( "$file error in image EXIF" );
            $exifjson 	= '';
        }
        else
        {
            $exifjson 	= json_encode_db( $exif );
        }
        debug($exifjson, 'EXIF_json');

        // Get IPTC
        $iptc		= parseIPTC( $file );
        if ( empty( $iptc ) )
        {
            logging( "$file error in image IPTC" );
            $iptcjson 	= '';
        }
        else
            $iptcjson 	= json_encode_db( $iptc );

        debug($iptcjson, 'IPTC_json');

        // Get thumbnail - in quiet mode
        $thumb 		= @exif_thumbnail( $file );
        if ( empty( $thumb) )
        {
            $note			.= "Thumb build";
            $thumb_width	= $_SESSION['config']['display']['thumb_max_width'];
            $thumb_height	= $_SESSION['config']['display']['thumb_max_height'];
            $dst			= "FALSE";
            $thumb			= image_resize( 
                    $file
                ,	$dst
                ,	$_SESSION['config']['images']['thumb_max_width']
                ,	$_SESSION['config']['images']['thumb_max_height']
                ,	$exif['IFD0']['Orientation'] ?? 0
                ,	$_SESSION['config']['images']['image_resize_type']
                ,	$_SESSION['config']['images']['crop']
                );
        }
        else
        {
            if ( $exif['IFD0']['Orientation'] ?? 0 )
            {
                $gdThumb	= imagecreatefromstring( $thumb );
                //if ( $degrees )
                $gdThumb	= gdReorientateByOrientation( $gdThumb, $exif['IFD0']['Orientation'], $file );
                $thumb		= stringcreatefromimage( $gdThumb, 'jpg');
            }
        }
        
        if ( empty( $thumb ) )
        {
            //$r  = $db->exec( "COMMIT;" );
            logging( "Skipping thumb $file" );
            $thumb	= '';
            //continue;
        }
        //debug(microtime( TRUE ) - $currenttime, 'get thumb');
        // Rotate EXIF
        $thumb 		= base64_encode( $thumb );

        $dst		= "FALSE";
        $view 		= image_resize( 
                        $file
                    ,	$dst
                    ,	$_SESSION['config']['images']['display_max_width']
                    ,	$_SESSION['config']['images']['display_max_height']
                    ,	$exif['IFD0']['Orientation'] ?? 0
                    ,	$_SESSION['config']['images']['image_resize_type']
                    ,	$crop=0
                    );
        if ( empty($view) )
        {
            //$view = file_get_contents($file);
            //$r  = $db->exec( "COMMIT;" );
            logging( "Skipping view $file" );
            //continue;
            $view	= '';
        }
        //debug(microtime( TRUE ) - $currenttime, 'view resize');
        $view 		= base64_encode( $view );

        // Update thumb and view
        $sql	= sprintf( 
            $_SESSION['database']['sql']['replace_into_images']
        ,	$dirname
        ,	$basename
        ,	$thumb
        ,	$view
        ,	$dirname
        );
        $sql	= sprintf( 
            $_SESSION['database']['sql']['replace_into_images']
        ,	$thumb
        ,	$view
        ,	$dirname
        ,	$basename
        );
        debug( $sql );
        $r  = $db->exec( $sql );

        // Update meta
        $sql	= sprintf($_SESSION['database']['sql']['replace_into_meta'], $exifjson, $iptcjson, $dirname, $basename );
        debug( $sql  );
        //verbose( $sql  );
        $r  = $db->exec( $sql );

        logging( sprintf( "%s/%s [%-35.35s] [%s] %sx%s %s %s %s"
            ,	$count
            ,	$_SESSION['tmp']['images_total']
            ,	$exif['FILE']['FileName']
            ,	date( 'c', $exif['FILE']['FileDateTime'] )
            ,	$exif['COMPUTED']['Width']
            ,	$exif['COMPUTED']['Height']
            ,	$exif['FILE']['MimeType']
            ,   $count . ':'. ( $exif['IFD0']['Orientation'] ?? '?')
            ,   number_format( microtime( TRUE ) - $currenttime, 2) . 'sec. '.$note
        )
        ,	"Image: "
        );
        $r  = $db->exec( "COMMIT;" );

        logging( progress_log( $_SESSION['tmp']['images_total'], $count, $_SESSION['timers']['add_meta'], 1 ) );
        echo progressbar($count, $_SESSION['tmp']['images_total'], $_SESSION['config']['process']['progressbar_size'], $file, $_SESSION['config']['process']['progressbar_lenght'] );
    }
    logging( progress_log( $_SESSION['tmp']['images_total'], $count, $_SESSION['timers']['add_meta'], 1 ) );

	logging( progress_log( count( $files ), 1, $_SESSION['timers']['rebuild_full'], 1 ) );
}   // rebuild_full()


function post_proccessing()
{
    verbose( "Post-processing", "\n- ");
    // Count all post action - names
    $_SESSION['tmp']['post_total']  = count( $_SESSION['database']['post'], COUNT_RECURSIVE ) - count( $_SESSION['database']['post'] );
    $count	= 0;

    $_SESSION['timers']['post']	= microtime(TRUE);
    foreach( $_SESSION['database']['post'] as $group => $actions )
    {
        $_SESSION['timers']["post_{$group}"]	= microtime(TRUE);
        $action_no	= 0;
        foreach( $actions as $sql )
        {
            $action_no++;
            $_SESSION['timers']["post_{$group}_{$action_no}"]	= microtime(TRUE);
            if ( str_starts_with( $sql, '--') )
            {
                debug( $sql, 'skip:' );
                //$group	= '';
            }
            else
            {
                debug($sql);
                $r  = $db->exec( $sql );
            }
            $count++;
            //logging( "$count/$post_total: $group " . microtime2human( microtime( TRUE ) - $microtime_start ));
            logging( progress_log( $_SESSION['tmp']['post_total'], $count, $_SESSION['timers']["post_{$group}_{$action_no}"], 1 ) );
        echo progressbar($count, $_SESSION['tmp']['post_total'], $_SESSION['config']['process']['progressbar_size'], "{$group}: {$sql}", $_SESSION['config']['process']['progressbar_lenght'] );
        }
        logging( progress_log( $_SESSION['tmp']['post_total']   , $count, $_SESSION['timers']["post_{$group}"], 1 ) );
    }
    logging( progress_log( $_SESSION['tmp']['post_total'], $count, $_SESSION['timers']["post"], 1 ) );
}   // post_proccessing() 

//----------------------------------------------------------------------

/**
 *   @brief      Write all file names to table.
 *   
 *   @param [in]	$files	List of file
 *   @return     VOID
 *   
 *   @details    Write all files to database in one transaction
 *   
 *   @since      2024-11-14T11:09:35
 */
function putFilesToDatabase( $files )
{
	global $db;
	verbose( '// Write all file names to table' );
	$r  = $db->exec( "BEGIN TRANSACTION;" );
	foreach ( $files as $path )
	{
		['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
		//$sql	= sprintf( $_SESSION['database']['sql']['insert_files'], 'images', $dirname, $dirname, $basename, $basename );
		$sql	= sprintf( $_SESSION['database']['sql']['insert_files'], 'images', $dirname, 
            str_replace( 
                [$_SESSION['config']['data']['data_root']] //, trim($_SESSION['config']['data']['data_root'], '/')
            ,   $_SESSION['config']['data']['virtual_root']
            ,   $dirname
            )
        ,   $basename
        ,   normalise_name( $GLOBALS['cfg_normalise'], $basename ) 
        );
		debug( $sql );
		$r  = $db->exec( $sql );
	}
	$r  = $db->exec( "COMMIT;" );
}	// putFilesToDatabase()

//----------------------------------------------------------------------


/**
 *   @brief      Get a list of images recursive from root
 *   
 *   @param [in]	$root		Start of search
 *   @param [in]	$image_ext	File extentions
 *   @param [in]	&$files		Array of files
 *   @param [in]	$allowed=[]	$(description)
 *   @return     TRUE if files found | FALSE
 *   
 *   @details    Recursive loop from root
 *   
 *   @since      2024-11-13T13:44:58
 */
function getImagesRecursive( $root, $image_ext, &$files, $allowed = [] )
{
	$it = new RecursiveDirectoryIterator( $root, RecursiveDirectoryIterator::SKIP_DOTS );
	$display = Array ( 'jpeg', 'jpg' );

	
	$count	= 0;
	$_SESSION['tmp']['files_total']	= 0;
	//$it2	= new RecursiveIteratorIterator($it);
	//$it3 = new RegexIterator($it2, '/^.+\.jpg$/i', RecursiveRegexIterator::GET_MATCH);

	foreach(new RecursiveIteratorIterator($it) as $file)
		$_SESSION['tmp']['files_total']++;
	
	//foreach( $it3 as $file)
	foreach(new RecursiveIteratorIterator($it) as $file)
	{
		if ( ! empty( $allowed ) )
		{	// extention after last . to lowercast
			//if( in_array( strtolower( substr( $file, strrpos($file, '.') + 1) ), $allowed ) ) {
			if( in_array( strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ), $allowed ) ) {
				$files[]	= str_replace( "\\", '/', "$file");
			}
		}
		else
			$files[]	= str_replace( "\\", '/', "$file");
		
		echo progressbar( ++$count, $_SESSION['tmp']['files_total'], $_SESSION['config']['process']['progressbar_size'], $file, $_SESSION['config']['process']['progressbar_lenght'] );
	}
    echo PHP_EOL;
	return( ! empty($files) );
}	// getImagesRecursive()

//----------------------------------------------------------------------

function normalise_name( &$cfg, $name )
{
    $r  = $name;
    foreach ( $cfg as $pattern => $replacement)
    {
        $r  = preg_replace( $pattern, $replacement, $r);
    }
    return $r;
}   // normalise_name

//----------------------------------------------------------------------

/**
 *   @brief      Run at shutdown
 *   
 *   @param [in]		$(description)
 *   @return     $(Return description)
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
	fputs( STDERR, "\n");
	status( "Tables created",   $_SESSION['tmp']['tables_total'] ?? 0);
	status( "Images processed", $_SESSION['tmp']['images_total'] ?? 0);
	status( "Post processes",   $_SESSION['tmp']['post_total'] ?? 0);
	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	//status( "Runtime ", $Runtime );
	status( "Runtime ", microtime2human( $Runtime ) );
	status( "Log", $_SESSION['config']['logfile']  ?? 'none');
}	// shutdown()

?>