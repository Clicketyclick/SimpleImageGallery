<?php
/**
 *   @file       flat.php
 *   @brief      Testing: reduce complexity of array.
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-14T12:51:05 / ErBa
 *   @version    2024-11-15T11:40:25
 */

$a	=' {"Multi entries":["entry1","entry2"],"Single entry":["Just one"]}';
$b	= json_decode( $a, TRUE, 512, JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE );

//var_export($b);
var_export( $b );
echo PHP_EOL;
var_export( array_flatten2($b) );
var_dump( array_flatten2($b) );
print_r( array_flatten2($b) );


/**
 *   @brief      Reduce complexity of array.
 *   
 *   @param [in] $arr          Source array
 *   @param [in] $out=array()  Target array
 *   @return     Target array
 *   
 *   details    Reduce sub arrays with only one entry to string
 *   
@code
$a	=' {"Multi entries":["entry1","entry2"],"Single entry":["Just one"]}';
$b	= json_decode( $a, TRUE, 512, JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE );

var_export( $b );
echo PHP_EOL;
var_export( array_flatten2($b) );
@endcode

@verbatim
array (
  'Multi entries' =>
  array (
    0 => 'entry1',
    1 => 'entry2',
  ),
  'Single entry' =>
  array (
    0 => 'Just one',
  ),
)
array (
  'Multi entries' =>
  array (
    0 => 'entry1',
    1 => 'entry2',
  ),
  'Single entry' => 'Just one',
)
@endverbatim
 *   
 *   @since      2024-11-14T13:39:47
 */

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

?>