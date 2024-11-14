<?php
/**
 *   @file       flat.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-14T12:51:05 / ErBa
 *   @version    2024-11-14T12:51:05
 */
//

$a	=' {"Multi entries":["entry1","entry2"],"Single entry":["Just one"]}';
$b	= json_decode( $a, TRUE, 512, JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE );



//var_export($b);
var_export( $b );
echo PHP_EOL;
var_export( array_flatten2($b) );

function array_flatten2( $arr, $out=array() )  {
	foreach( $arr as $key => $item ) {
		if ( is_array( $item ) && 1 < count( $item ) ) {
			$out[$key] = $item;
		} else {
			$out[$key] = $item[0];
		}
	}
	return $out;
}

exit;
/*
//var_dump(array_reduce($b, "sum")); // int(15)
var_dump(
array_reduce(
        array_keys($b),
        $out = function ($carry, $key) use ($b) 
		{    // ... then we 'use' the actual array here
		$c=[];
		/*
	echo "\ncarry:";
	var_export( $carry );
	echo "\nkey:";
	var_export( $key );
	echo "\nattr:";
	var_export( $b[$key] );
	echo "\n";
        * /
	if ( is_array( $b[$key] ) && 1 < count( $b[$key] ) ) {
		//return( $b[$key] );
		$carry[$key]	= $b[$key];
		return( $carry);
	}
	else
	{
		//return( $b[$key][0] );
		$carry[$key]	= $b[$key][0];
		return( $carry);
	}	
	return( $carry);
        },
        ''
	
    )

); // int(15)

function sum($carry, $item)
{
	echo "carry:";
	var_export( $carry );
	echo "item:";
	var_export( $item );
    //$carry += $item;
    //return $carry;
}

exit;
*/
/*
function flatten2(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}
function flatten1(array $array) {
	$return = array();
	
	foreach( $array as $no => $data)
		array_push( $return, $data);
    
    //array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}
/* * /
function array_flatten2($array) {
    $return = array();
    foreach ($array as $key => $value) {
        if (is_array($value)){
            $return = array_merge($return, array_flatten($value));
        } else {
            $return[$key] = $value;
        }
    }

    return $return;
}
function array_reduce2( $a )
{
	return( array_flatten3( $a) );
	return( $a );
	return( flatten1( $a) );
		//return( array_merge(array_keys($a),array_values($a)) );
		return( array_flatten2( $a) );
		return( flatten2( $a) );
	
	$it = new RecursiveIteratorIterator(new RecursiveArrayIterator($a));
$b=[];
foreach ($it as $key => $value) {
  $b[$key] = $value;
}

	return( $b );
}

*/