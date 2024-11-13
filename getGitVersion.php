<?php
function getGitCommitInfo()
{
    $gitDir = '.git';

    if (!is_dir($gitDir)) {
        return "Not a Git repository.";
    }

    // Step 1: Get the latest commit hash
    $headFile = $gitDir . '/HEAD';
    if (!file_exists($headFile)) {
        return "HEAD file not found.";
    }

    $headContent = trim(file_get_contents($headFile));
    $commitHash = '';
    $commitInfo = [];

    // If HEAD is a symbolic reference, read the branch file
    if (strpos($headContent, 'ref:') === 0) {
        $refPath = $gitDir . '/' . trim(substr($headContent, 5)); // Remove "ref: " prefix
        if (file_exists($refPath)) {
            $commitHash = trim(file_get_contents($refPath));
        } else {
            return "Reference file for branch not found.";
        }
    } else {
        // HEAD is directly the commit hash (detached state)
        $commitHash = $headContent;
    }

    // Shorten the commit hash for display
    $shortCommitHash = substr($commitHash, 0, 7);

    // Step 2: Read the commit object to get the timestamp
    $commitFile = $gitDir . '/objects/' . substr($commitHash, 0, 2) . '/' . substr($commitHash, 2);
    if (!file_exists($commitFile)) {
        return "Commit object file not found.";
    }
	$commitInfo['shorthash']	= $shortCommitHash;
	$commitInfo['hash']			= $commitHash;

    // Read and decompress the commit file content
    $compressedContent = file_get_contents($commitFile);
    $commitContent = gzuncompress($compressedContent);

    if ($commitContent === false) {
        return "Failed to decompress commit object.";
    }
	//echo "CommitContent:\n";
	//var_export($commitContent);
	//echo "\n";
    // Step 3: Extract the timestamp from the commit object
    //preg_match('/committer .*? (\d+) /', $commitContent, $matches);
    preg_match('/committer (.*?) (\d+) /', $commitContent, $matches);
    if (!isset($matches[1])) {
        return "Timestamp not found in commit object.";
    }
    // Convert timestamp to human-readable format
    //$commitTimestamp = (int)$matches[1];
    //$commitTimestamp = (int)$matches[2];
    $commitInfo['commitdate'] = date('Y-m-d H:i:s', (int)$matches[2] );
    $commitInfo['committer'] = $matches[1] ;

    preg_match('/author (.*?) (\d+) /', $commitContent, $matches);
	$commitInfo['author'] = $matches[1];
    preg_match('/commit (\d+)/', $commitContent, $matches);
	$commitInfo['commitno'] = $matches[1];

    //preg_match('/committer (.*?) (\d+) /', $commitContent, $matches);

//var_export($matches);



    // Display the commit hash and date
    return( $commitInfo );
    return( ["hash" => $shortCommitHash, "date" => $commitDate ] );
    return "Commit Hash: $shortCommitHash, Date: $commitDate";
}

var_export( getGitCommitInfo() );
exit;

/**
 *   @file       getGitVersion.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T08:39:36 / ErBa
 *   @version    2024-11-13T08:39:36
 */


echo PHP_EOL;
var_export( getCurrentGitCommitHash() );
echo PHP_EOL;
var_export( get_current_git_branch() );
echo PHP_EOL;
var_export( get_current_git_datetime() );
echo PHP_EOL;


function get_current_git_branch(){
    $fname = sprintf( '.git/HEAD' );
    $data = file_get_contents($fname);   
    $ar  = explode( "/", $data );
    $ar = array_reverse($ar);
    return  trim ("" . @$ar[0]) ;
}

//function get_current_git_datetime( $branch='master' ) {
function get_current_git_datetime( $branch='main' ) {
      $fname = sprintf( '.git/refs/heads/%s', $branch );
      $time = filemtime($fname);
      if($time != 0 ){
          return date("Y-m-d H:i:s", $time);
        }else{
            return  "time=0";
      }
}

/**
 * Attempt to Retrieve Current Git Commit Hash in PHP.
 *
 * @return mixed
*/
function getCurrentGitCommitHash()
{
    //$path = base_path('.git/');
    $path = '.git/';

    if (! file_exists($path)) {
        return null;
    }

    $head = trim(substr(file_get_contents($path . 'HEAD'), 4));
    $hash = trim(file_get_contents(sprintf($path . $head)));

    return $hash;
}