<?php

$haystack = array (
  'say hello',
  'hello stackoverflow',
  'hello world',
  'foo bar bas'
);
$matches  = preg_grep ('/^hello (\w+)/i', $haystack);




$haystack = [
'./data/2023/Giza',
'./data/2023',
'./data/2023/Odense',
'./data/2023/Öland',
'./data/2023/Öland/Borgholm',
'./data/2023/Öland/Borgholm/slot',
];

//$matches  = preg_grep ('/^\.\/data\/2023\/[^\/]*$/i', $haystack);
$_REQUEST['path']	= './data/2023';
$_REQUEST['path']	= './data';

// Find subdirs to current
$pattern	= '/^' . SQLite3::escapeString( str_replace( '/', '\/', $_REQUEST['path'] ) ) . '\/[^\/]*$/i';
var_export( $pattern );
$matches  = preg_grep ( $pattern, $haystack);

print_r ($matches);
exit;

//https://magp.ie/2013/04/17/search-associative-array-with-wildcard-in-php/
function array_key_exists_wildcard ( $array, $search, $return = '' ) {
    $search = str_replace( '\*', '.*?', preg_quote( $search, '/' ) );
    $result = preg_grep( '/^' . $search . '$/i', array_keys( $array ) );
    if ( $return == 'key-value' )
        return array_intersect_key( $array, array_flip( $result ) );
    return $result;
}
 
function array_value_exists_wildcard ( $array, $search, $return = '' ) {
    $search = str_replace( '\*', '.*?', preg_quote( $search, '/' ) );
    $result = preg_grep( '/^' . $search . '$/i', array_values( $array ) );
    if ( $return == 'key-value' )
        return array_intersect( $array, $result );
    return $result;
}
 
$array = array(
    'test_123'   => 'sbr123',
    'Test_12345' => 'bbb456',
    'test_222'   => 'bry789',
    'test_ewrwe' => 'abc777',
    't1est_eee'  => 'def950'
);
 
$search = 'test*';
print_r( array_key_exists_wildcard( $array, $search ) );
print_r( array_key_exists_wildcard( $array, $search, 'key-value' ) );
 
$search = 'b*';
print_r( array_value_exists_wildcard( $array, $search ) );
print_r( array_value_exists_wildcard( $array, $search, 'key-value' ) );
 