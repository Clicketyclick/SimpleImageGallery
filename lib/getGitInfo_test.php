<?php
/**
 *   @file       getGitInfo_test.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T10:33:18 / ErBa
 *   @version    2024-11-13T10:33:18
 */

include_once( __DIR__. '/getGitInfo.php');

var_export( getGitCommitInfo() );
var_export( getGitHistory() );

?>