<?php
/**
 *   @file       getGitInfo.php
 *   @brief      Getting information from `.git`.
 *   
 *   @details    
 *   Function|Brief
 *   ---|---
 *   getGitHistory()	| Get history from current `.git`.
 *   getGitCommitInfo()	| Get latest commit from `.git`.
 *   getGitVersion()	| Get software version from `.git`.
 *   getDoxygenHeader()	| Get Doxygen file header expanded for display.
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-13T10:25:46 / ErBa
 *   @version    2024-11-18T09:46:03
 */

//----------------------------------------------------------------------

/**
 *   @brief      Get history from current .git
 *   
 *   @param [in]	$branch	Default branch [main]
 *   @return     array with history
 *   
 *   @details
 *   @code
 *   var_export( getGitHistory() );
 *   @endcode
 *   
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
 *   @brief      Get latest commit from .git
 *   
 *   @return     array w. commit info
 *   
 *   @details    
 *   @code
 *       var_export( getGitCommitInfo() );
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
 *   @endcode
 *
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

/**
 *   @brief      Get software version from Git
 *   
 *   @param [in]	$major=0	Incompatible API changes
 *   @param [in]	$minor=0	Add functionality in a backward compatible manner
 *   @param [in]	$patch=1	Backward compatible bug fixes. Overruled by $GitCommitInfo['commitno']
 *   @param [in]	$level=0	Pre-alpha, Alpha, Beta ...
 *   @return     String with version
 *   
 *   @details

@verbatim
0.0.265π_2024-11-17T21:05:33_ff52b8d
│ │ └┬┘│ └──────────────┬──┘ └──┬──┘
│ │  │ │                │       └─ Hash
│ │  │ │                └───────── Release date
│ │  │ └────────────────────────── Level or life cycle
│ │  └──────────────────────────── Patch or commit no
│ └─────────────────────────────── Minor
└───────────────────────────────── Major
@endverbatim
 *   
 *   Life cycle
 *   
 *   0. π: Pró or Pre-alpha refers to all activities performed during the software project before formal testing.
 *   1. α: Alpha software is not thoroughly tested by the developer before it is released to customers.
 *   2. β: Beta phase generally begins when the software is feature-complete but likely to contain several known or unknown bugs.
 *   3. γ: Gamma or release candidate (RC) is a beta version with the potential to be a stable product, which is ready to release unless significant bugs emerge.
 *   4. ω: Omega or stable release is the last release candidate (RC) which has passed all stages of verification and tests. Any known remaining bugs are considered acceptable. This release goes to production.
 *   5. τ: Tau or EOL When software is no longer sold or supported, the product is said to have reached end-of-life, to be discontinued, retired, deprecated, abandoned, or obsolete
 *   
 *   @code
 *   echo getGitVersion();
 *   @endcode
@verbatim
0.0.265π_2024-11-17T21:05:33_ff52b8d
@endverbatim
 *   
 *@code
 *   echo getGitVersion( );
 *@endcode
 * Will either produce a version from `./version.txt` appended Git commit no
@verbatim
v.0.0.1-alpha_265
@endverbatim
 * OR version no based on arguments + data from `getGitCommitInfo()`
@verbatim
v.0.0.265-π_2024-11-17T21:05:33_ff52b8d
@endverbatim
 *
 *@code
 *   echo getGitVersion( 1, 2, 3, 0);
 *@endcode
@verbatim
1.2.3π_2024-11-17T21:05:33_ff52b8d
@endverbatim
 *
 * @note Patch will be overruled by `$GitCommitInfo['commitno']`
 *
 *   @since      2024-11-18T05:11:25
 */
function getGitVersion($major = 0, $minor = 0, $patch = 1, $level = 0)
{
	$lifecycle	= [
		'π'	// Pró or Pre-alpha refers to all activities performed during the software project before formal testing.
	,	'α'	// Alpha software is not thoroughly tested by the developer before it is released to customers.
	,	'β'	// Beta phase generally begins when the software is feature-complete but likely to contain several known or unknown bugs.
	,	'γ'	// Gamma or release candidate (RC) is a beta version with the potential to be a stable product, which is ready to release unless significant bugs emerge.
	,	'ω'	// Omega or stable release is the last release candidate (RC) which has passed all stages of verification and tests. Any known remaining bugs are considered acceptable. This release goes to production.
	,	'τ'	// Tau or EOL When software is no longer sold or supported, the product is said to have reached end-of-life, to be discontinued, retired, deprecated, abandoned, or obsolete
	];

	$GitCommitInfo	= getGitCommitInfo();
	
	// Get version from `version.txt`: v.0.0.1-alpha
	$gitVersion		= trim( @file_get_contents( './version.txt' ) );
	
	if ( empty( $gitVersion ) )	// Else build
	{
		$gitVersion	= sprintf( "v.%s.%s.%s-%s_%s_%s"
		,	$major
		,	$minor
		,	$GitCommitInfo['commitno'] ?? $patch
		,	$lifecycle[ $level ?? 0 ]
		,	str_replace( ' ', 'T', $GitCommitInfo['commitdate'] )
		,	$GitCommitInfo['shorthash']
		//,	$GitCommitInfo[]
		);
	}
	else
	{
		$gitVersion .= '_' . $GitCommitInfo['commitno'] ?? $patch;
	}
	return( $gitVersion );
}	// getGitVersion()

//----------------------------------------------------------------------

/**
 *   @brief      Get Doxygen file header expanded for display.
 *   
 *   @return     String w. expanded Doxygen header.
 *   
 *   @details    Will expand '@include version.txt' to content of file
 *   Default is build from `.git`:
@verbatim
\@version    v.0.0.265-π_2024-11-17T21:05:33_ff52b8d
@endverbatim
 *   But if  `version.txt` exists this will be include appended with file date:
@verbatim
\@version    v.0.0.1-alpha 2024-11-13T07:26:33+00:00
@endverbatim
 *
 *   @note       '@include version.txt' is implemented.
 *   
 *   @since      2024-11-18T09:42:02
 */
function getDoxygenHeader( $file )
{
	preg_match('/\/\*\*(.*?)\*\//s', implode( '', file( $file )), $match); 
	return( 
		str_replace( 
			'@include version.txt'
		,	file_exists('./version.txt') 
			?	trim(file_get_contents('./version.txt') )
				.	' '
				.	date('c', filectime( $file ))
			:	getGitVersion() 
		,	$match[1]
		)
	.	PHP_EOL 
	);
}	// getDoxygenHeader()

//----------------------------------------------------------------------

?>