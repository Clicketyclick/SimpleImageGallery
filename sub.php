<?php
/**
 *   @file       sub.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-30T23:18:16 / ErBa
 *   @version    2024-11-30T23:18:16
 */


include_once( 'lib/debug.php' );
//include_once( 'lib/handleJson.php' );
//include_once( 'lib/_header.php' );
include_once( 'lib/_header_config.php' );

// Init global variables
$files		= [];
$cfg_normalise    = [
'/\.jpg$/i' => '',  // Extention
'/^(19|20)\d\d-\d\d-\d\d[T,_,\.,\s]\d\d-\d\d-\d\d[_,\.,\s]/' => '', // Full ISO
'/^(19|20)\d\d-\d\d-\d\d[T,_,\.,\s]/' => '',    // Date only
];

echo "<style>body{ color: yellow;};</style>";
//echo "[{$_REQUEST['action']}]";

printf( "<pre>[%s]</pre>", var_export($_REQUEST, TRUE ) );

echo <<<EOL

<script>
var i = 0;

function progress_bar( id, max, value, note ) {
    if (undefined === note) { note = '?'; }
    
    var elem    = parent.document.getElementById( id );
    var status  = parent.document.getElementById( id +"_status");
    pct     = value * 100 / max;

    elem.value          = pct;
    //status.value        = pct +'%';
    status.innerHTML    = Math.trunc( pct ) +'% '+note;
}   // progress_bar()


/*
function move() {
  if (i == 0) {
    i = 1;
    var elem    = parent.document.getElementById("progress");
    var status  = parent.document.getElementById("status");
    var width   = 1;
    var id      = setInterval(frame, 10);

        function frame() {
          if (width >= 100) {
            clearInterval(id);
            console.log('done');
            i = 0;
          } else {
            width++;
            //elem.style.width  = width + "%";
            elem.value          = width;
            status.value        = width +'%';
            status.innerHTML    = width +'%';
          }
        }   // frame()
    
  }
}

//move();
*/

parent.document.getElementById( 'status' ).innerHTML = '[{$_REQUEST['action']}]: {$_REQUEST['database_name']} <- {$_REQUEST['source_dir']}<br>';</script>\n";
</script>

EOL;

timer_set('init_db', 'Open - or create database');
// Open - or create database
//initDatabase( $db, $GLOBALS['config']['database']['file_name'], $GLOBALS['database'] );
initDatabase( $db, $_REQUEST['database_name'] );
timer_set('init_db');


switch( $_REQUEST['action'] ?? '?' )
{
    case 'create_database':
        create_database();
        rebuild_update();
        post_proccessing();
        
//		"replace_into_dirs": "REPLACE INTO dirs( path ) SELECT DISTINCT path FROM images ORDER BY path DESC ;",
//		"update_dirs": "UPDATE dirs SET thumb = ( SELECT thumb FROM images WHERE path LIKE dirs.path ORDER BY path DESC, FILE DESC LIMIT 1 );",
    $r  = $db->exec( $GLOBALS['database']['sql']['replace_into_dirs'] );
    $r  = querySqlSingleValue( $db, "SELECT count(path) FROM dirs;");
    pstatus( "- replace_into_dirs: {$r}" );

    $r  = $db->exec( $GLOBALS['database']['sql']['update_dirs'] );
    $r  = querySqlSingleValue( $db, "SELECT count(thumb) FROM dirs;");
    pstatus( "- update_dirs: {$r}" );

    break;
    case 'load_images':
        load_images();
    break;
    case 'update_images':
        update_images();
    break;
    case 'grouping_images':
        grouping_images();
    break;
    case 'update_index':
        update_index();
    break;
    default:
        echo "Sorry: Don't know how to: [{$_REQUEST['action']}]";
}

	$Runtime	= microtime( TRUE ) - $_SERVER["REQUEST_TIME_FLOAT"];
	pstatus( "Runtime " . microtime2human( $Runtime ) );


pstatus( 'Done' );
//----------------------------------------------------------------------

function create_database()
{
    echo "- [{$_REQUEST['action']}]";
//function rebuild_full()
//{	// Process all
	verbose( 'Process all' );
	verbose( 'Clear tables' );
    clear_tables();
    
	$GLOBALS['timers']['rebuild_full']	= microtime(TRUE);

	$GLOBALS['timers']['get_images_recursive']	= microtime(TRUE);
	verbose( '// Find all image files recursive' );
	// Find all image files recursive
	//getImagesRecursive( $GLOBALS['config']['data']['data_root'], $GLOBALS['config']['data']['image_ext'], $files, ['jpg'] );
	getImagesRecursive( $_REQUEST['source_dir'], $GLOBALS['config']['data']['image_ext'], $files, ['jpg'] );
	logging( progress_log( count( $files ), 1, $GLOBALS['timers']['get_images_recursive'], 1 ) );
	debug( $files );

	status( "Processing files", count( $files ));
	$GLOBALS['timers']['put_files_to_database']	= microtime(TRUE);
	
	// Put all files to database: images
	putFilesToDatabase( $files );
	logging( progress_log( count( $files ), 1, $GLOBALS['timers']['put_files_to_database'], 1 ) );
//}   // rebuild_full()

}   // create_database()

function load_images()
{
    echo "- [{$_REQUEST['action']}]";
}   // load_images()

function update_images()
{
    echo "- [{$_REQUEST['action']}]";
}   // update_images()

function grouping_images()
{
    echo "- [{$_REQUEST['action']}]";
}   // grouping_images()

function update_index()
{
    echo "- [{$_REQUEST['action']}]";
}   // update_index()


//----------------------------------------------------------------------


//----------------------------------------------------------------------

function clear_tables()
{
    global $db;
    $r  = $db->exec( $GLOBALS['database']['sql']['delete_all_search'] );
    $r  = $db->exec( $GLOBALS['database']['sql']['delete_all_images'] );
}   //clear_tables()


/*
for ($i = 0; $i <= 10; $i++) {
    $str    = number2word( $i );
    echo "<script>progress_bar( 'progress', 10, {$i}, '{$i}={$str}' );</script>\n";
    ob_flush(); // Flush fluently
    flush();
    sleep(1);
}
    echo "<script>progress_bar( 'progress', 10, 10, 'Done' );</script>\n";
*/




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
	$GLOBALS['tmp']['files_total']	= 0;
	//$it2	= new RecursiveIteratorIterator($it);
	//$it3 = new RegexIterator($it2, '/^.+\.jpg$/i', RecursiveRegexIterator::GET_MATCH);

	foreach(new RecursiveIteratorIterator($it) as $file)
		$GLOBALS['tmp']['files_total']++;
	
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
		
		echo progressbar( ++$count, $GLOBALS['tmp']['files_total'], $GLOBALS['config']['process']['progressbar_size'], $file, $GLOBALS['config']['process']['progressbar_lenght'] );
	}
    echo PHP_EOL;

    pstatus( "Got images: {$count}/{$GLOBALS['tmp']['files_total']}" );
	return( ! empty($files) );
}	// getImagesRecursive()

//----------------------------------------------------------------------
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
    $max    = count($files);
    $count  = 0;
	foreach ( $files as $path )
	{
		['basename' => $basename, 'dirname' => $dirname] = pathinfo( $path );
		//$sql	= sprintf( $GLOBALS['database']['sql']['insert_files'], 'images', $dirname, $dirname, $basename, $basename );
		$sql	= sprintf( $GLOBALS['database']['sql']['insert_files'], 'images', $dirname, 
            str_replace( 
                [$GLOBALS['config']['data']['data_root']] //, trim($GLOBALS['config']['data']['data_root'], '/')
            ,   $GLOBALS['config']['data']['virtual_root']
            ,   $dirname
            )
        ,   $basename
        ,   normalise_name( $GLOBALS['cfg_normalise'], $basename ) 
        );
		debug( $sql );
		$r  = $db->exec( $sql );
        $count++;

        pbar( 'progress', $max, $count, "Put image to database: {$count}/{$max}" );
        //sleep(1);

	}
	$r  = $db->exec( "COMMIT;" );

    pstatus( "Images written to database: {$count}/{$max}" );

}	// putFilesToDatabase()


function rebuild_update()
{	// Process all
    global $db;
	verbose( 'Update' );
    verbose( '// Write meta data, thumb and view for each file' );

    $GLOBALS['timers']['add_meta']     = microtime(TRUE);
    
    //var_export($GLOBALS['database']['sql']['select_all_files']);
    //echo($GLOBALS['database']['sql']['select_all_files']);

    $files  = array_flatten( querySql( $db, $GLOBALS['database']['sql']['select_all_files'] ) );
    //var_export($files);
    //exit;
    
    $GLOBALS['tmp']['images_total']    = count($files);
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
            $thumb_width	= $GLOBALS['config']['display']['thumb_max_width'];
            $thumb_height	= $GLOBALS['config']['display']['thumb_max_height'];
            $dst			= "FALSE";
            $thumb			= image_resize( 
                    $file
                ,	$dst
                ,	$GLOBALS['config']['images']['thumb_max_width']
                ,	$GLOBALS['config']['images']['thumb_max_height']
                ,	$exif['IFD0']['Orientation'] ?? 0
                ,	$GLOBALS['config']['images']['image_resize_type']
                ,	$GLOBALS['config']['images']['crop']
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
                    ,	$GLOBALS['config']['images']['display_max_width']
                    ,	$GLOBALS['config']['images']['display_max_height']
                    ,	$exif['IFD0']['Orientation'] ?? 0
                    ,	$GLOBALS['config']['images']['image_resize_type']
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
            $GLOBALS['database']['sql']['replace_into_images']
        ,	$dirname
        ,	$basename
        ,	$thumb
        ,	$view
        ,	$dirname
        );
        $sql	= sprintf( 
            $GLOBALS['database']['sql']['replace_into_images']
        ,	$thumb
        ,	$view
        ,	$dirname
        ,	$basename
        );
        debug( $sql );
        $r  = $db->exec( $sql );

        // Update meta
        $sql	= sprintf($GLOBALS['database']['sql']['replace_into_meta'], $exifjson, $iptcjson, $dirname, $basename );
        debug( $sql  );
        //verbose( $sql  );
        $r  = $db->exec( $sql );

        logging( sprintf( "%s/%s [%-35.35s] [%s] %sx%s %s %s %s"
            ,	$count
            ,	$GLOBALS['tmp']['images_total']
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

        logging( progress_log( $GLOBALS['tmp']['images_total'], $count, $GLOBALS['timers']['add_meta'], 1 ) );
        echo progressbar($count, $GLOBALS['tmp']['images_total'], $GLOBALS['config']['process']['progressbar_size'], $file, $GLOBALS['config']['process']['progressbar_lenght'] );

        $max    = $GLOBALS['tmp']['images_total'];

        pbar( 'progress', $max, $count, "Rebuild: {$count}/{$max}" );
        
    }
    logging( progress_log( $GLOBALS['tmp']['images_total'], $count, $GLOBALS['timers']['add_meta'], 1 ) );

	logging( progress_log( count( $files ), 1, $GLOBALS['timers']['rebuild_full'], 1 ) );
    pstatus( "Metadata added: {$count}/{$GLOBALS['tmp']['images_total']}" );
}   // rebuild_update()

function pbar( $id, $max, $value, $note )
{
    echo "<script>progress_bar( '{$id}', {$max}, {$value}, '{$note}' );</script>\n";
    flush();
    ob_flush();
}
function pstatus( $status )
{
    echo "<script>parent.document.getElementById( 'status' ).innerHTML += '- {$status}<br>';</script>\n";
    flush();
    ob_flush();
}

function post_proccessing()
{
    verbose( "Post-processing", "\n- ");
    // Count all post action - names
    $GLOBALS['tmp']['post_total']  = count( $GLOBALS['database']['post'], COUNT_RECURSIVE ) - count( $GLOBALS['database']['post'] );
    $count	= 0;

    $GLOBALS['timers']['post']	= microtime(TRUE);
    foreach( $GLOBALS['database']['post'] as $group => $actions )
    {
        $GLOBALS['timers']["post_{$group}"]	= microtime(TRUE);
        $action_no	= 0;
        foreach( $actions as $sql )
        {
            $action_no++;
            $GLOBALS['timers']["post_{$group}_{$action_no}"]	= microtime(TRUE);
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
            logging( progress_log( $GLOBALS['tmp']['post_total'], $count, $GLOBALS['timers']["post_{$group}_{$action_no}"], 1 ) );
        echo progressbar($count, $GLOBALS['tmp']['post_total'], $GLOBALS['config']['process']['progressbar_size'], "{$group}: {$sql}", $GLOBALS['config']['process']['progressbar_lenght'] );
        }
        logging( progress_log( $GLOBALS['tmp']['post_total']   , $count, $GLOBALS['timers']["post_{$group}"], 1 ) );
    }
    logging( progress_log( $GLOBALS['tmp']['post_total'], $count, $GLOBALS['timers']["post"], 1 ) );

    pstatus( "Post processing: {$count}/{$GLOBALS['tmp']['post_total']}" );
}   // post_proccessing() 

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


function number2word( $number, $lang = 'en' )
{
    $_1to19 = [
        "one",
        "two",
        "three",
        "four",
        "five",
        "six",
        "seven",
        "eight",
        "nine",
        "ten",
        "eleven",
        "twelve",
        "thirteen",
        "fourteen",
        "fifteen",
        "sixteen",
        "seventeen",
        "eighteen",
        "nineteen",
    ];
    $_teen = [
        "twenty",
        "thirty",
        "forty",
        "fifty",
        "sixty",
        "seventy",
        "eighty",
        "ninety",
    ];
    $_mult = [
        2  => 'hundred',
        3  => 'thousand',
        6  => 'million',
        9  => 'billion',
        12 => 'trillion',
        15 => 'quadrillion',
        18 => 'quintillion',
        21 => 'sextillion',
        24 => 'septillion', // php can't count this high
        27 => 'octillion',
    ];
    $fnBase = function ($n, $x) use (&$fn, $_mult) {
        return $fn($n / (10 ** $x)) . ' ' . $_mult[$x];
    };
    $fnOne = function ($n, $x) use (&$fn, &$fnBase) {
            $y = ($n % (10 ** $x)) % (10 ** $x);
            $s = $fn($y);
            $sep = ($x === 2 && $s ? " and " : ($y < 100 ? ($y ? " and " : '') : ', '));
            return $fnBase($n, $x) . $sep . $s;
        };
        $fnHundred = function ($n, $x) use (&$fn, &$fnBase) {
            $y = $n % (10 ** $x);
            $sep = ($y < 100 ? ($y ? ' and ' : '') : ', ');
            return ', ' . $fnBase($n, $x) . $sep . $fn($y);
        };
        $fn = function ($n) use (&$fn, $_1to19, $_teen, $number, &$fnOne, &$fnHundred) {
            switch ($n) {
                case 0:
                    return ($number > 1 ? '' : 'zero');
                case $n < 20:
                    return $_1to19[$n - 1];
                case $n < 100:
                    return $_teen[($n / 10) - 2] . ' ' . $fn($n % 10);
                case $n < (10 ** 3):
                    return $fnOne($n, 2);
            };
            for ($i = 4; $i < 27; ++$i) {
                if ($n < (10 ** $i)) {
                    break;
                }
            }
            return ($i % 3) ? $fnHundred($n, $i - ($i % 3)) : $fnOne($n, $i - 3);
        };
        $number = $fn((int)$number);
        $number = str_replace(', , ', ', ', $number);
        $number = str_replace(',  ', ', ', $number);
        $number = str_replace('  ', ' ', $number);
        $number = ltrim($number, ', ');

        return $number;
    $fn = function ($n) use (&$fn, $_1to19, $_teen, $number, &$fnOne, &$fnHundred) {
        switch ($n) {
            case 0:
                return ($number > 1 ? '' : 'zero');
            case $n < 20:
                return $_1to19[$n - 1];
            case $n < 100:
                return $_teen[($n / 10) - 2] . ' ' . $fn($n % 10);
            case $n < (10 ** 3):
                return $fnOne($n, 2);
        };
        for ($i = 4; $i < 27; ++$i) {
            if ($n < (10 ** $i)) {
                break;
            }
        }
        return ($i % 3) ? $fnHundred($n, $i - ($i % 3)) : $fnOne($n, $i - 3);
    };
    $number = $fn((int)$number);

    return $number;
}

function shutdown()
{
    echo "done";
}
?>