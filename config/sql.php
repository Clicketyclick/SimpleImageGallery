<?php
/**
 *  @file      sql.php
 *  @brief     Brief description
 *  For ByteMARC only
 *  @details   SQL strings for direct database interaction
 *             For ByteMARC
 *  @example   SQL templates
 *      require_once( $GLOBALS['releaseroot'] . "config/sql.php" );
 *  
 *  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 *  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
 *  @since     2022-05-19T08:29:49 / ErBa
 *  @version   2024-04-23 22:05:10 / ErBa
 */

$sql_config = [
    // https://stackoverflow.com/a/48875603 Reset rowid field after deleting rows
    "clear_index"               => "REINDEX %s;",
    "clear_table"               => "DELETE FROM %s;",
    "count_rows"                => "SELECT max(rowid) AS count FROM %s;",    //  --SELECT count(1) AS count FROM %s;

    "database_readme_edit1" 	=> "SELECT value, note, language FROM config WHERE key = 'readme' and language = '%s';",
    "database_readme_edit2" 	=> "UPDATE config set value=\"%s\" WHERE key=\"readme\" AND language=\"%s\"",
    "delete_record" 			=> "UPDATE meta SET active = 0 WHERE rowid = %s;",
    "delete_record_from_search" => "DELETE FROM search WHERE search_id = '%s';",

    "find"                      => "SELECT recno, count(search_id) AS count,  entry FROM search WHERE entry like '%s' GROUP BY entry;",        //  LIMIT 200
    "find_entry"                => "SELECT DISTINCT recno FROM search WHERE entry like '%s';",//  LIMIT 200
    "find_key"                  => "SELECT DISTINCT recno FROM search WHERE key like '%s' ESCAPE '\';",//  LIMIT 200
    "find_entry_"               => "SELECT recno, count(search_id) AS count,  entry FROM search WHERE entry like '%s' GROUP BY entry;",//  LIMIT 200
    
	"get_image"              	=> "SELECT * FROM images WHERE id = '%s' LIMIT 1;",
    "get_last_row"              => "SELECT * FROM %s %s ORDER BY rowid DESC LIMIT 1;",
    "get_latest_audits"		    => "SELECT rowid, ENTRY_DATE, META_ID, ACTION, OLD, NEW FROM audit WHERE META_ID = '%s' AND ACTION = 'Update' ORDER BY ENTRY_DATE DESC LIMIT 10 ;",
    "get_template"              => "SELECT value FROM %s WHERE key = '%s';",
    "get_readme"                => "SELECT * FROM config WHERE key LIKE 'readme' AND language = '%s' ;",
    "get_config"                => "SELECT * FROM config WHERE key LIKE '%s' %s ;",
    //"put_config"                => "UPDATE config set value=\"%s\" WHERE key=\"%s\" AND language=\"%s\"",
    //"put_config"                => "REPLACE into config( \"%s\", language ) VALUES ( \"%s\", \"%s\" )",
    "put_config"                => "REPLACE into config( key, value, section, language, note ) VALUES ( '%s', '%s', 'database', '%s', 'note');",
    "get_old_audit"             => "SELECT OLD FROM audit WHERE rowid = %s;",
    "get_database_size"         => "SELECT page_count * page_size AS size FROM pragma_page_count(),  pragma_page_size();",    //https://stackoverflow.com/a/52191503
    //"getcloud"                  => "SELECT * FROM wordclouds WHERE cloudname = \"%s\" ORDER BY upper(key) LIMIT 100000;",
    //"getcloud"                  => "SELECT * FROM wordclouds WHERE cloudname = \"%s\" AND key like \"%s%%\" ORDER BY upper(key) LIMIT 10000;",
    "getcloud_count"            => "SELECT count(*) FROM wordclouds WHERE cloudname = '%s' AND key like '%s%%' ORDER BY upper(key);",
    "getcloud"                  => "SELECT * FROM wordclouds WHERE cloudname = '%s' AND key like '%s%%' ORDER BY upper(key) %s LIMIT %s;",
    // Note that high value in BETWEEN is NOT included. Must be specified separately
    "getcloud_else"             => "SELECT * FROM wordclouds WHERE cloudname = \"%s\" 
        AND ( key NOT BETWEEN \"A%%\" AND \"Z%%\" ) 
        AND ( key NOT BETWEEN \"a%%\" AND \"z%%\" ) 
        AND ( key NOT BETWEEN \"0%%\" AND \"9%%\" ) 
        AND key NOT LIKE \"Z%%\" 
        AND key NOT LIKE \"z%%\" 
        AND key NOT LIKE \"9%%\" 
        ORDER BY upper(key) %s LIMIT %s;",
    "getcloud_num"             => "SELECT * FROM wordclouds WHERE cloudname = \"%s\" 
        AND ( ( key BETWEEN \"0%%\" AND \"9%%\" ) 
        OR key LIKE \"9%%\" ) 
        ORDER BY upper(key) %s LIMIT %s;",

	"index_exists"    			=> "SELECT EXISTS(SELECT 1 FROM pragma_index_info(%s));", // https://stackoverflow.com/a/8827554
    "insert_audit"              => "INSERT INTO audit (ENTRY_DATE,  META_ID,  ACTION,  OLD,  NEW, START, END, DURATION ) VALUES( '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s' );",
    //"insert_new_record"         => "INSERT INTO meta ( meta_id,  type,  created,  modified,  active,  shorttitle,  shortmain,  shelfmark,  size,  md5,  record,  normrec ) VALUES( '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s' );",
    "insert_new_record"         => "INSERT INTO meta ( type,  created,  modified,  active,  shorttitle,  shortmain,  shelfmark,  size,  md5,  record,  normrec ) VALUES(  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s',  '%s' );",
    //"insert_search_entry"       => "INSERT INTO search ( search_id,  recno,  entry ) VALUES( '%s',  %s,  '%s' );",
    //2024-04-19 06:03:11 Adding key
    "insert_search_entry"       => "INSERT INTO search ( search_id,  recno,  entry, key ) VALUES( '%s', %s, '%s', '%s' );",

    "get_meta_id"               => "SELECT meta_id FROM	meta WHERE rowid = %s;",
    "get_records_deleted"       => "SELECT rowid, shorttitle, shortmain, active FROM meta WHERE active = 0;",
    "get_table_names"           => "SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';",
    "get_short"           		=> "SELECT shorttitle, shortmain FROM meta WHERE rowid = %s;",
    "get_typemap"          		=> "SELECT rowid, type, active, shorttitle, shortmain, cover FROM %s",

    "kill_rebuild_history"      => "DELETE SELECT rowid,action FROM audit_copied WHERE rowid NOT IN ( SELECT MAX(rowid) FROM audit_copied GROUP BY action ) AND ACTION IN ( \"Rebuild\", \"Reindex\", \"RebuildCloud\");",

	//"table_exists2"    			=> "SELECT COALESCE(MAX(_ROWID_), 0) AS exist FROM sqlite_master WHERE type='table' AND name='%s' limit 1;", // https://stackoverflow.com/a/8827554/7485823
	"table_exists"    			=> "SELECT EXISTS(SELECT 1 FROM sqlite_master WHERE type='table' AND name='%s');", // https://stackoverflow.com/a/8827554
	//"table_exists1"    			=> "SELECT count(*) FROM sqlite_master WHERE type='table' AND name='%s';", // https://stackoverflow.com/a/8827554/7485823
    // Remove all duplicate entries from audit

    // No type
    //"update_record"           => "UPDATE meta SET shorttitle    = '%s',  shortmain = '%s',  shelfmark    = '%s',  size = '%s',  md5 = '%s',  modified = '%s',  record = '%s',  normrec = '%s' WHERE rowid = %s;",
    // no cover
    //"update_record"             => "UPDATE meta SET shorttitle    = '%s',  shortmain = '%s',  shelfmark    = '%s',  size = '%s',  md5 = '%s',  modified = '%s',  record = '%s',  normrec = '%s',  type = '%s'   WHERE rowid = %s;",

    //2023-11-07T06:31:23 Added cover
    "update_record"             => "UPDATE meta SET shorttitle    = '%s',  shortmain = '%s',  shelfmark    = '%s',  cover    = '%s',  size = '%s',  md5 = '%s',  modified = '%s',  record = '%s',  normrec = '%s',  type = '%s'   WHERE rowid = %s;",
    
    "wordclouds_delete"         => "DELETE FROM wordclouds;",
    "wordclouds_insert"         => "INSERT INTO wordclouds SELECT '%s',  substr(entry,4),  entry,  count(*),  substr(entry,1,3),  100 FROM search WHERE entry LIKE '%s' GROUP BY entry;",
    "wordclouds_update_norm"    => "UPDATE wordclouds SET norm = '%s' WHERE cloudname = '%s' AND entry = '%s';",
];


//var_export( $sql_config );