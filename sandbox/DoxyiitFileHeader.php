<?php
/**
 *  @file       DoxyiitFileHeader.php
 *  @brief      Get DoxyIT file header and function headers
 */

getDoxyFileHeader();
echo "\n---\n";
getDoxyFunctionHeaders();

/**
 *   @fn         getDoxyFileHeader()
 *   @brief      Get DoxyIT file header
 */
function getDoxyFileHeader()
{
	preg_match('/\/\*\*(.*?)\*\//s', implode( '', file( __FILE__ )), $match); fputs( STDERR, $match[0] . PHP_EOL );
}

/**
 *   @fn         getDoxyFunctionHeaders
 *   @brief      Function headers only
 */
function getDoxyFunctionHeaders()
{
	preg_match_all('/\/\*\*(.*?)\*\//s', implode( '', file( __FILE__ )), $match);
	var_export( implode( "\n\n", array_slice($match[0], 1) ) );
}
?>