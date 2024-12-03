<?php
/**
 *   @file       getGitInfo_test.php
 *   @brief      Testing getGitInfo
 *   @details    
 * @var  mixed $GitCommitInfo
 * Description
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T10:33:18 / ErBa
 *   @version    2024-11-13T10:33:18
 */

include_once( __DIR__. '/getGitInfo.php');

echo PHP_EOL . 'getGitHistory: ';
var_export( getGitHistory() );

echo PHP_EOL . 'getGitCommitInfo: ';
$GitCommitInfo	= getGitCommitInfo();
var_export( getGitCommitInfo() );

echo PHP_EOL.'getGitVersion: ';
var_export( getGitVersion() );


echo PHP_EOL.'getGitVersion( 1, 2, 3, 0): ';
var_export( getGitVersion( 1, 2, 3, 0) );

;
/*
array (
  'gitdir' => '.git',
  'refPath' => '.git/refs/heads/main',
  'branch' => 'main',
  'shorthash' => 'ff52b8d',
  'hash' => 'ff52b8def54bd709d8d4fa21da461a8e1ceaa9a8',
  'commitdate' => '2024-11-17 21:05:33',
  'committer' => 'Erik Bachmann <Erik@Clicketyclick.dk>',
  'author' => 'Erik Bachmann <Erik@Clicketyclick.dk>',
  'commitno' => '265',
*/



?>