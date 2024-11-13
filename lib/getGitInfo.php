<?php
/**
 *   @file       getGitInfo.php
 *   @brief      $(Brief description)
 *   @details    $(More details)
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T10:25:46 / ErBa
 *   @version    2024-11-13T10:36:38
 */

//----------------------------------------------------------------------

/**
 *   @fn         getGitHistory
 *   @brief      Get history from current .git
 *   
 *   @param [in]	$branch='main'	Default branch
 *   @return     array with history
 *   
 *   @details    $(More details)
 *   
 *   @example    var_export( getGitHistory() );
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-13T10:25:52
 */
function getGitHistory( $branch = 'main' )
{
	$path		= '.git/logs/refs/heads/';
	$data		= file( $path. $branch);
	$history	= [];

	foreach( $data as $entry)
	{
		/*
			1 => '17ad123d3602a331bd78c31c61002422bdd6bffb',
			2 => 'ab86908a1e2adbb97961ef08bc29eb012124c4a2',
			3 => 'Erik Bachmann <Erik@Clicketyclick.dk>',
			4 => '1731488115',
			5 => '+0100',
			6 => 'commit',
			7 => 'Get Git commit info',
		*/
		preg_match('/^(\w+) (\w+) (.*?) (\d+) ([+-]*\d+)\t(\w+): (.*?)$/', $entry, $matches);
		$history[]	= [
			'from'		=> $matches[1],
			'to'		=> $matches[2],
			'committer'	=> $matches[3],
			'date'		=> date('Y-m-d H:i:s', (int)$matches[4] ) . $matches[5],
			'action'	=> $matches[6],
			'comment'	=> $matches[7],
		];
	}
	return( $history );
}	// getGitHistory()

//----------------------------------------------------------------------

/**
 *   @fn         getGitCommitInfo
 *   @brief      Get latest commit from .git
 *   
 *   @param [in]	VOID
 *   @return     array w. commit info
 *   
 *   @details    
 *   
 *   @example    var_export( getGitCommitInfo() );
 *        array (
 *         0 =>
 *         array (
 *           'from' => '0000000000000000000000000000000000000000',
 *           'to' => '1234567fffffffffffffffffffffffffffffffff',
 *           'committer' => 'Erik Bachmann <Erik@Clicketyclick.dk>',
 *           'date' => '2024-11-13 07:26:33+0100',
 *           'action' => 'clone',
 *           'comment' => 'from https://github.com/....git',
 *         )
 *   
 *   @todo       
 *   @bug        
 *   @warning    
 *   
 *   @see        https://
 *   @since      2024-11-13T10:26:31
 */
function getGitCommitInfo()
{
    $gitDir 	= '.git';
    $commitHash = '';
    $commitInfo = [];

    if (!is_dir($gitDir)) {
        return "Not a Git repository.";
    }

    // Step 1: Get the latest commit hash
    $headFile = $gitDir . '/HEAD';
    if (!file_exists($headFile)) {
        return "HEAD file not found.";
    }

    $headContent = trim(file_get_contents($headFile));

    // If HEAD is a symbolic reference, read the branch file
    if (strpos($headContent, 'ref:') === 0) {
        $refPath = $gitDir . '/' . trim(substr($headContent, 5)); // Remove "ref: " prefix
        if (file_exists($refPath)) {
            $commitHash = trim(file_get_contents($refPath));
        } else {
            trigger_error( "Reference file for branch not found  [{$refPath}]", E_USER_WARNING);
            return FALSE;
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
		trigger_error( "Commit object file not found [{$commitFile}]", E_USER_WARNING);
        return FALSE;
    }

    // Read and decompress the commit file content
    $compressedContent = file_get_contents($commitFile);
    $commitContent = gzuncompress($compressedContent);

    if ($commitContent === false) {
		trigger_error( "Failed to decompress commit object.", E_USER_WARNING);
        return FALSE;
    }
    // Step 3: Extract the timestamp from the commit object
    preg_match('/committer (.*?) (\d+) /', $commitContent, $matches);
    if (!isset($matches[1])) {
		trigger_error( "Timestamp not found in commit object.", E_USER_WARNING);
        return FALSE;
    }

	$commitInfo['gitdir']		= $gitDir;
	$commitInfo['refPath']		= $refPath;
	$commitInfo['branch']		= pathinfo($refPath, PATHINFO_BASENAME);
	
	// Hash
	
	$commitInfo['shorthash']	= $shortCommitHash;
	$commitInfo['hash']			= $commitHash;

    // Convert timestamp to human-readable format
    $commitInfo['commitdate'] 	= date('Y-m-d H:i:s', (int)$matches[2] );
    $commitInfo['committer']	= $matches[1] ;
	// Author
    preg_match('/author (.*?) (\d+) /', $commitContent, $matches);
	$commitInfo['author']		= $matches[1];
	// Commit no
    preg_match('/commit (\d+)/', $commitContent, $matches);
	$commitInfo['commitno']		= $matches[1];

    return( $commitInfo );
}	// getGitCommitInfo()

//----------------------------------------------------------------------

?>