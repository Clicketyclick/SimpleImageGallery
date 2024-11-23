/**
 *   @file       dummy_dirs.sql
 *   @brief      Insert missing root directoris into `dirs`
 *   @details    
 *   
 *   @todo       Implement a recursive function processing 
 *   each directory as in `recursive_split_string.sql`
 *   
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-23T22:02:33 / ErBa
 *   @version    2024-11-23T22:02:33
 */

--./Gamle album
INSERT OR IGNORE INTO dirs (path) values( './Gamle album');
--./2022
INSERT OR IGNORE INTO dirs (path) values( './2022');
--./2009
INSERT OR IGNORE INTO dirs (path) values( './2009');
--./2008
INSERT OR IGNORE INTO dirs (path) values( './2008');
--./2001
INSERT OR IGNORE INTO dirs (path) values( './2001');
--./1995
INSERT OR IGNORE INTO dirs (path) values( './1995');
--./1982
INSERT OR IGNORE INTO dirs (path) values( './1982');

