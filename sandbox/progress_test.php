<?php
/**
 *   @file       progress.php
 *   @brief      Test progressbar and show_status
 *   @details    
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-16T11:24:15 / ErBa
 *   @version    2024-11-16T11:24:15
 */
//

include_once( "../lib/progress_bar.php");
/**/
for($x=1;$x<=100;$x++){
     echo show_status($x, 100);
     usleep(100000);
}
/**/
echo "\n\n";
for($x=1;$x<=100;$x++){
     echo progressbar($x, 100) . "count[$x]";
     usleep(100000);
}
