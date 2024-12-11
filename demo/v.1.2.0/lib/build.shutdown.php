<?php
/**
 *   @file       index-dev.shutdown.php
 *   @brief      Shutdown for `index.php`
 *   @details    Display details w. runtime etc.
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-23T21:01:30 / ErBa
 *   @version    @include version.txt
 */

function shutdown()
{
    // Footer
    printf( "<br clear=both><hr><small>{$GLOBALS['config']['system']['copyright']} 
    - <a href='{$GLOBALS['config']['display']['home_url']}'>{$GLOBALS['config']['system']['app_name']}</a></small> %s"
    ,   date('Y')
    ,   getGitVersion()
    );

    if ( ! empty( $_REQUEST['slide'] ) )
        echo "<script>slideshow(true, {$_REQUEST['slide']});</script>";

}   // shutdown()

?>