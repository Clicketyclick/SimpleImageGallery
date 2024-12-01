<?php
include_once( '../lib/debug.php' );
include_once( '../lib/handleJson.php' );
//include_once( '../lib/_header.php' );

echo "<style>body{ color: yellow;};</style>";
//echo "[{$_REQUEST['action']}]";

//printf( "<pre>[%s]</pre>", var_export($_REQUEST, TRUE ) );

echo <<<EOL

<script>
var i = 0;

function progress_bar( id, max, value, note ) {
    if (undefined === note) { note = '?'; }
    
    var elem    = parent.document.getElementById( id );
    var status  = parent.document.getElementById( id +"_status");
    pct     = value * 100 / max;

    elem.value          = pct;
    status.value        = pct +'%';
    status.innerHTML    = pct +'% '+note;
}   // progress_bar()



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
</script>

EOL;


switch( $_REQUEST['action'] ?? '?' )
{
    case 'create_database':
        create_database();
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
	getImagesRecursive( $GLOBALS['config']['data']['data_root'], $GLOBALS['config']['data']['image_ext'], $files, ['jpg'] );
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
?>