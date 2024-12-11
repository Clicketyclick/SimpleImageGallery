 <?php

/**
 *  @file      handleFiles.php
 *  @brief     Get doc file
 *  
 *  @details   More details
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2023-12-06T16:25:34 / erba
 *  @version   2024-03-07 07:47:28
 */
//namespace bytemarc;
/**
 *  @file       ./lib/handleFiles.php
 *  @brief      Various operations at logical file level
*
 * Various operations at logical file level

 *  @details   More details

 * getFilesRecursively()    Recursivly get a list of files matching a pattern
 * countTextLines()         Count no of lines in a text file
 * readlastline()           Read last line from text file
 * getFileModified()        Get date for latest file modification
 * languageFlag()           Get path to image representing language
 * nationalFlag()           Get path to image representing national flag
 * upload_file()            Sende local file to server
 * insertIntoArray()        Insert element into stack at pos 2
 * getDocFile()             Get doc file - MarkDown help
 * getContextHelp()         Get context help from file
 * getUrlStub               Get protocol, host, port and path to current file
 * 
 * @todo 
 * url          https://www.sitepoint.com/list-files-and-directories-with-php/
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 * @author      Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 * @deprecated  no
 * @link        
 * @since       2019-02-01T07:24:04
 * @version     2024-04-06 09:19:14
 */

//include_once "config/config.php";
//include_once "lib/local.php";
//include_once "lib/sort.php";


/** 
 * @subpackage  getFilesRecursively()
 *
 * Recursivly get a list of files matching a pattern
 *
 * @example         getFilesRecursively( "./lib/", '.+\.php' );
 * @param path      Root path of search
 * @param path      Pattern of file name
 * @return          List of files
 * 
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function getFilesRecursively( $path, $pattern = '.+\.php' ) {
    $path = realpath($path);
    $pattern = '/' . $pattern . '/i';

    // The prefix "\" means "in global namespace"
    $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
    $Regex = new \RegexIterator($objects, $pattern, \RecursiveRegexIterator::GET_MATCH);

    $phparray = array();
    foreach($objects as $name => $object){
        if ( preg_match($pattern, $name) )  array_push($phparray, $name);
    }
    return( $phparray );
    //setlocale(LC_ALL, $GLOBALS['config']['LC_ALL']);
    //return( CustomSort( $phparray ) );
}   // getFilesRecursively()

//---------------------------------------------------------------------

/** 
 * @subpackage  countTextLines()
 *
 * Count no of lines in a text file
 *
 * @param filename  Name of file to count
 * @return          No of lines found
 * @URL https://stackoverflow.com/a/19329629
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */

function _getLines($file) {
            $f = fopen($file, 'r');

            // read each line of the file without loading the whole file to memory
            while ($line = fgets($f)) {
                yield $line;
            }
        }
function countTextLines($filename) {
    // This function implements a generator to load individual lines of a large file
    return( iterator_count(_getLines($filename)) ); // the number of lines in the file
}   // countTextLines()

//---------------------------------------------------------------------

/** 
 * @subpackage  readlastline()
 *
 * Read last line from text file
 *
 * @param filename  File to read from
 * @param pos       Default range from EOF to start searching
 * @return          Last line (incl new line) or <code>FALSE</code> otherwise.
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function readlastline($filename, $pos = 1024) {
    if ( ! file_exists($filename) || ($fp = fopen($filename, "r"))== FALSE ) {
        trigger_error( __('cannot_open_file') . " [$filename]", E_USER_WARNING);
        return FALSE;
    }

    $lastline   = FALSE;
    fseek($fp, 0, SEEK_END);
    $fsize   = ftell($fp);

    // Check if range is larger than file size
    if ( $fsize < $pos ){
        // start from begining
        fseek($fp, 0, SEEK_SET);
    } else {
        // Start pos from EOF
        fseek($fp, $pos * -1, SEEK_END);
    }
    
    // read ontil EOF
    while( ( $line    = fgets($fp) ) !== false) {
        $lastline   = $line;
    };
    
    return($lastline);
}   // readlastline()

//---------------------------------------------------------------------

/** 
 * @subpackage  countLines()
 *
 * Count no of lines in a text file
 ' Not efficient on large no of lines
 *
 * @todo    countLines() replaced by countTextLines()
 *
 * @param filename  Name of file to count
 * @return          No of lines found
 * @deprecated      YES
 * @URL https://stackoverflow.com/a/20537130
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function countLines($filename) {
    trigger_error( __('deprecated_function') . " [".__FUNCTION__."@".__FILE__."]", E_USER_WARNING);
    return( countTextLines($filename) );
/*
    $lines = 0;
    if ( ! file_exists($filename) || ($fp = fopen($filename, "rb"))==false ) {
        trigger_error( _('Unable to open file') . " [$filename]", E_USER_WARNING);
        return $lines;
    }

    while (!feof($fp)) {
        $lines += substr_count(fread($fp, 8192), "\n");
    }

    fclose($fp);

    return $lines;
*/
}   // countLines()

//---------------------------------------------------------------------

/** 
 * @subpackage  getFileModified()
 *
 * Get date for latest file modification
 *
 * @param filename  Name of file to test
 * @param default   String / value to return if file not found
 * @return          File stamp in ISO8601 format or <code>FALSE</code> otherwise.
 * 
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function getFileModified($filename, $default ="Not performed" ) {
    return(file_exists( $filename ) ? date( "Y-m-d\TH:i:sP", filemtime( $filename )) : $default );
}   // getFileModified()

//---------------------------------------------------------------------

/** 
 * @subpackage  languageFlag()
 *
 * Get path to image representing language
 *
 * @URL https://en.wikipedia.org/wiki/List_of_ISO_639-2_codes
 * @param language  ISO language code (ISO3)
 * @return          Path to image
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function languageFlag( $language ) {
    //$flag   = "icons/flags/iso/png/16x16/" . _( "flag_of_" . $language );
    $flag   = $GLOBALS['htmlroot'] . "/icons/flags/iso/png/16x16/" . _( "flag_of_" . $language );
    $unknownflag    = $GLOBALS['htmlroot'] . "/icons/flags/iso/png/16x16/flag-unknown.png";
    return( "<img src='$flag' title='". _('Language') ."=$language' onerror=\"this.src='$unknownflag'\" alt='$flag'>" );
}   // languageFlag()

/** 
 * @subpackage  nationalFlag()
 *
 * Get path to image representing national flag
 *
 * @param iso2      ISO country code (ISO2)
 * @return          Path to image
 * @URL https://en.wikipedia.org/wiki/ISO_3166-1_alpha-2
 * @URL https://en.wikipedia.org/wiki/ISO_3166-1_alpha-3
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-02-04T08:53:59
 */
function nationalFlag( $iso2 ) {
    $flag   = $GLOBALS['htmlroot'] . "/icons/flags/iso/png/16x16/" .  _( "flag_of_" . $iso2 ) ;
    $alt    = $flag;
    //if (! file_exists($flag) ) $flag = $GLOBALS['htmlroot'] . "/icons/flags/iso/png/16x16/flag-unknown.png";
    $unknownflag    = $GLOBALS['htmlroot'] . "/icons/flags/iso/png/16x16/flag-unknown.png";
    return( "<img src='$flag' title='". _('Language') ."={$_SESSION['bytemarc']['language']}' onerror=\"this.src='$unknownflag'\" alt='$alt'>" );
}   // nationalFlag()


/** 
 * @subpackage  upload_file()
 *
 * Sende local file to server
 *
 * @param target_file   Name of file to upload
 * @param target_dir    Directory on server to store file
 * @param target_config Configuration: hash:
 *      maxFileSize         Max file size in bytes
 *      hasMime             Must the file include a Mime description
 *      AllowedFiletypes    Accepted file extentions
 * @return string       Status message
 * @URL 
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-05-06T15:17:21
 */
function upload_file( $target_file, $target_dir, $target_config = ['maxFileSize' => 500000, 'hasMime'=> TRUE, 'AllowedFiletypes' => array("jpg", "png") ] ) {
    $msg    = "";
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));

    // Check if image file is a actual image or fake image (Has Mime content)
    if(isset($_GET["submit"])) {
        if ($target_config['hasMime'] ) {
            $check = getimagesize( $target_file );
            if($check !== false) {
                $msg    .= ""; //"File is an image - " . $check["mime"] . ".";
            } else {
                $msg    .= "File is not an image [{$target_file}]. No mime type";
                throw new Exception( $msg );
            }
        }
    }

    if ( file_exists( $target_dir . $target_file ) )  {// Check if file already exists
        if (isset($_GET['overwrite'])) {
            echo "_GET['overwrite']:";
            print_r($_GET['overwrite']);
            unlink($target_file);
        } else {
            echo "No overwrite";
            $msg    .= "File already exists";
            throw new Exception( $msg );
        }
    }

    if ($_FILES["fileToUpload"]["size"] > $target_config['maxFileSize']) {// Check file size
        $msg    .= "File is too big [> {$target_config['maxFileSize']}]";
        throw new Exception( $msg );
    }

    // Allow only specific file formats
    if ( ! in_array( strtolower($imageFileType), $target_config['AllowedFiletypes'] ) ) {
        $msg    .= "Not an allowed file type: " . var_export( $target_config['AllowedFiletypes'], TRUE);
        throw new Exception( $msg );
    }

    // Check if $uploadOk is set to 0 by an error
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
        $msg    .= "The file ". basename( $_FILES["fileToUpload"]["name"]). " has been uploaded.";
    } else {
        $msg    .= "There was an error uploading your file.";
        throw new Exception( $msg );
    }
    return( $msg );
}   // upload_file()

//---------------------------------------------------------------------

/** 
 * @subpackage  insertIntoArray()
 *
 * Insert element into stack at pos 2
 *
 * @example $queue = array("apple", "banana", "citrus", "date", "orange");
 * @example insertIntoQueue( $queue, "RASPberry", 2 );
 * @example var_export($queue);
 * @example 
 * @example array (
 * @example   0 => 'apple',
 * @example   1 => 'banana',
 * @example   2 => 'RASPberry',
 * @example   3 => 'citrus',
 * @example   4 => 'date',
 * @example   5 => 'orange',
 * @example )
 *
 * @URL
 * @param           Queue
 * @param           New element
 * @param           Position of new element
 * @return          <code>VOID</code>
 * @tutorial        doc/manual.md
 * @see             
 * @since           2019-09-30T06:28:08
 */
function insertIntoQueue( &$queue, $element, $pos = 0) {
    $queue  = array_merge( 
        array_slice($queue, 0, $pos), 
        array( $element ), 
        array_slice($queue, $pos) );
}   // insertIntoQueue()

//---------------------------------------------------------------------

/**
 *  @fn        getDocFile
 *  @brief     Get doc file - MarkDown help
 *  
 *  @param [in] $topic 	Description for $topic
 *  @param [in] $lang 	Description for $lang
 *  @return    Return description
 *  
 *  @details   More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-12-06T16:25:14 / erba
 */
function getDocFile( $topic, $lang = 'da' )
{
    $docroot    = $_SESSION['releaseroot'] . "/doc/context-help/";
    //$docroot    = str_replace( "/", "\\", $docroot );

    $docfile    = "{$docroot}{$topic}.{$lang}.md";

    if ( $_REQUEST['debug'] ) trigger_error( __LINE__." File:[$docfile]", E_USER_NOTICE );

    if ( file_exists( $docfile ) ) {    // Language dependant
        return( $docfile );
    } else {    // No language?
        $docfile = "{$docroot}{$topic}.md";
        if ( file_exists( $docfile ) ) {
            return( $docfile );
        } else {    // Force default: en
            $docfile = "{$docroot}{$topic}.en.md";
            if ( file_exists( $docfile ) ) {
                return( $docfile );
            } else  // Give up
                //trigger_error("Cannot open document: [{$docroot}$docfile]", E_USER_WARNING);
                error_log( "out of luck");
        }
    }
    return FALSE;
}   // getDocFile()

//---------------------------------------------------------------------

/**
 *  @fn        getContextHelp
 *  @brief     Get context help from file
 *  
 *  @param [in] $docfile 	Description for $docfile
 *  @return    Return description
 *  
 *  @details   ParsedonExtra and remove newlines
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-12-06T16:25:21 / erba
 */
function getContextHelp( $docfile ) 
{
    $note   = file_get_contents( $docfile );
    $Extra  = new ParsedownExtra();
    return str_replace("\r", ""
    ,   str_replace("\n", "", $Extra->text( $note ) )
    );
}   // getContextHelp()

//---------------------------------------------------------------------

/**
 *  @fn        getLatestFileDateRecursive
 *  @brief     Get latest file + file date recurcive
 *  
 *  @param [in] $path 	Path to start
 *  @param [in] $exts 	File extentions
 *  @param [in] $types	File types
 *  @return    filename + date
 *  
 *  @details   Only for specified extensions and file types
 *  
 *  @example   
 *   
 *   list($file, $date)  = getLatestFileDate( './src/', ['php', 'js', 'css', 'json'] );
 *   
 *   printf( "Latest: %s %s\n"
 *   ,   $file
 *   ,   $date
 *   );
 *   
 *   list($file, $date)  = getLatestFileDate( './src/', FALSE, ['file'] );   // Any file of any ext
 *   list($file, $date)  = getLatestFileDate( './src/' );                    // Any type or ext
 *   
 *   printf( "Latest: %s %s\n"
 *   ,   $file
 *   ,   $date
 *   );
 *   
 *   
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-12-06T16:29:03 / erba
 */
function getLatestFileDateRecursive( $path, $exts = FALSE, $types = ['file', 'link', 'dir', 'block', 'fifo', 'char', 'socket', 'unknown'] )
{
    $newFile    = FALSE;
    $newTime    = FALSE;

    $di = new RecursiveDirectoryIterator( $path );
    foreach (new RecursiveIteratorIterator($di) as $filename => $file) 
    {
        // If not the matching type
        if ( $types &&  ! in_array( @$file->getType() ?? 'unknown', $types ) )
            continue;

        // Get current file ext
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        // Skip all but mentioned extensions
        if ( $exts &&  ! in_array( $ext, $exts ) )
            continue;
        
        if ( !$exts || in_array( $ext, $exts ))
        {
            $thisTime   = $file->getMTime();
            if ( $newTime < $thisTime )
            {
                $newFile    = $filename;
                $newTime    = $thisTime;
            }
        }
    }

    return( [ $newFile, gmdate( "c", $newTime ) ] );
}   // getLatestFileDateRecursive()


//---------------------------------------------------------------------

/**
 *  @fn        getUrlStub
 *  @brief     Get protocol, host, port and path to current file
 *  
 *  @return    Get current path excl. filename
 *  
 *  @details   
 *  
 *  @example   $url_stub   = getUrlStub();
*              echo "\n[$url_stub]\n";

 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       // https://stackoverflow.com/a/8891890
 *  @since     2024-02-26T07:03:37 / Bruger
 */
function getUrlStub()
{
    $ssl      = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' );
    $sp       = strtolower( $_SERVER['SERVER_PROTOCOL'] );
    $protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );

    $url_stub   = $protocol
    .   "://"
    .   "{$_SERVER['HTTP_HOST']}"
    .   dirname( "{$_SERVER['SCRIPT_NAME']}")
    .   "/";
    return($url_stub);
}   // getUrlStub()

//---------------------------------------------------------------------

?>