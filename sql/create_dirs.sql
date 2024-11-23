/**
 *   @file       create_dirs.sql
 *   @brief      Create thumbs for directory indexes
 *   @details    To speed up the process of finding the newest 
 *   thumb for each directory folder in an index the paths and thumbs
 *   are prebuild into table `dirs`.
 *
 *   @code
 *   .read ../sql/create_dirs.sql
 *   @endcode
 *
 *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *   @since      2024-11-22T23:43:50 / ErBa
 *   @version    2024-11-23T11:57:10
 */

-- DROP TABLE dirs;
-- Create table and index
.read ../sql/create_dirs_table.sql
-- Insert all distinct paths from images
.read ../sql/insert_path_in_dirs.sql
-- Find the newest thumb in each path
.read ../sql/update_thumb.sql
-- Check for missing thumbs
.read ../sql/check_thumb.sql
-- Test specific paths
.read ../sql/testing_dirs.sql

--SELECT DISTINCT path FROM images;

