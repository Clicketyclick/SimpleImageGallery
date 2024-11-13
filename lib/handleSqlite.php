<?php
/**
  *  @file       handleSqlite.php
  *  @brief      Abstraction layer for SQLite database operations
  *  
  *  @details    Detailed description
  *  
  *  
  * openSqlDb               Open database if exists
  * createSqlDb             Create database if not exists
  * createSqlTable          Create table 
  * buildSqlInsert          Build INSERT statement by template
  * buildSqlUpdate          Build UPDATE statement by template
  * getSqlTableLength       Get no of elements in table
  * getSqlTables            List tables in database
  * getSqlMaxRowId          Get highest rowid in table
  * querySql                Executes an SQL query
  * querySqlSingleValue     Executes a query and returns a single result (value)
  * querySqlSingleRow       Executes a query and returns a single result (Row)
  * executeSql              Prepares an SQL statement for execution, execute and return result as array
  *----------------------------------
  * fetchObject()           Fetch object from SQLite
  * truncateTable()         Truncating a table
  * resetRowid()            Reset rowids after truncate
  * dbNoOfRecords           Get no of records
  * dbLastEntry             Get last entry from table
  * dbSchema                Get database schema
  * vacuumInto              Vacuum current database to a new database file
  * stdClass2array          Converting an array/stdClass -> array
  * dbDump                  Dump entire database as SQL
  * closeSqlDb              Close / Close +  delete database file
  * getDbFile               Get full path to database
  * getDbName               Get database name
  * flatten                 Alias for array_flatten()
  * array_flatten           Change single string in array to just single string??
  * expandBoolean           Expand boolean keys in SQL expressions
  * matchSqlDefInDb         Match SQL definition in database
  * matchColumnDefInDb      Match columen definition in database
  * ifExistsColumnInDb      Check if column exists in database
  * ifExistsTriggerInDb     Check if trigger exists in database
  * ifExistsIdxInDb         Check if index exists
  * ifExistsInDb            Count occurences in sqlite_master
  *
  * Obsolete functions
  * 
  * deleteRow               Use: executeSql()
  * insertData              Use: buildSqlUpdate() + executeSql()
  * updateData              Use: buildSqlInsert() + executeSql()
  *
  * Deprecated functions:
  * 
  * array2stdClass
  * stdClass2array          Converting an array/stdClass -> array (use JSON handling)
  * fetchObject             Fetch object from SQLite (use JSON handling)
  * 
  * 
  *  php -r "print SQLite3::escapeString( \"O'donald\" ); "
  *  
  *  @todo       
  *  @bug        
  *  @warning    
  *  
  *  @deprecated no
  *  @link       
  *  
  *  @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
  *  @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
  *  @since      2019-11-23T20:25:02
  *  @version    2024-04-11 13:23:27
  */

require_once( $GLOBALS['releaseroot'] . "config/sql.php");

// Boolean expressions
$booleans   = [
    " AND "   => "'\nINTERSECT /* AND */\n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
,   " OR "    => "'\nUNION     /* OR  */\n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
,   " NOT "   => "'\nEXCEPT    /* NOT */\n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
];

$booleans_cut   = [
    " AND "   => ";"
,   " OR "    => ";"
,   " NOT "   => ";"
];


//---------------------------------------------------------------------

/**
 *  @fn        openSqlDb()
 *  @brief     Open or create database
 *  
 *  @details   Wrapper for SQLite3::open()
 *  
 *  @param [in] $dbfile Path and name of database file to open
 *  @return     File handle to database OR FALSE
 *  
 *  @example   $db = openSqlDb( "./my.db" );
 *  
 *  @todo     
 *  @bug     
 *  @warning    An empty database IS valid, but issues a warning
 *  
 *  @see
 *  @since      2019-12-11T07:43:08
 */
function openSqlDb( $dbfile ) {
    if ( ! file_exists($dbfile) )
    {
        trigger_error( ___('database_not_found'). " [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    if ( ! filesize($dbfile) )
    {
        trigger_error( ___('database_is_empty')." [$dbfile] " . var_export( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS,2), TRUE ), E_USER_WARNING );
    }
    
    $db = new SQLite3( $dbfile );
    if ( ! $db ) {
        trigger_error( ___('database_not_open')." [$dbfile]", E_USER_ERROR );
        return( FALSE );
    }
    return( $db );
}   // openSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        createSqlDb()
 *  @brief     Create new database if not exists
 *  
 *  @details   Wrapper for SQLite3::open()
 *  
 *  @param [in] $dbfile Path and name of database file to create
 *  @return     File handle to database OR FALSE
 *  
 *  @example   $db = createSqlDb( "./my.db" );
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2024-04-11 13:19:44
 */
function createSqlDb( $dbfile ) {
    if ( file_exists($dbfile) )
    {
        trigger_error( ___('database_already_exists')." [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    
    $db = new SQLite3( $dbfile );
    if ( ! $db ) {
        trigger_error( ___('database_not_open')." [$dbfile]", E_USER_WARNING );
        return( FALSE );
    }
    return( $db );
}   // createSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        createTable()
 *  @brief     Create new table in database
 *  
 *  @details   Build new tables from tabledef. Tabledef is an assosiative array with
 *      table name as key and field name / type pairs as sub array
 *  
 *  @param [in] $db       Handle to open database
 *  @param [in] $tabledef Table definitions
 *  @return    Return description
 *  
 *  
 *  @example    $tabledef    = [ "mytable" => ["id" => "INTEGER", "str" => "TEXT"] ];
 *              createSqlTable( $db, $tabledef );
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-10T08:25:50
 */
function createSqlTable( &$db, $tabledef ) {
    $result     = array();
    foreach( $tabledef as $tablename => $fields ) {
        $sql = "CREATE TABLE IF NOT EXISTS {$tablename} (\n";
        $count  = 0;
        foreach ( $fields as $fieldname => $config ) {
            if ( 0 < $count )
                $sql .= ",\n";
            $sql .= "\t{$fieldname} {$config}";
            $count++;
        }
        $sql .= ");";

        //$r  = executeSql( $db, $sql );
        $r  = $db->exec( $sql );
        array_push( $result, $r );
    }
    return( $result );
}   // createSqlTable()

//---------------------------------------------------------------------
/**
 *  @fn        buildSqlInsert()
 *  @brief     Insert new row in table
 *  
 *  @details   More details
 *  
 *  @return    Return description
 *  
 *  @example   $fields = [ "name" = 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015' ];
 *      echo buildSqlInsert("devices", $fields );
 *  
 *   INSERT INTO devices (name, model, serial)
 *   VALUES('HP ZBook 17','ZBook','SN-2015');
 *  
 *  @param [in] $tablename Name of table
 *  @param [in] $fields    Hash of field tags and values
 *  @param [in] $rowid     RecNo to update - or FALSE to Insert
 *  @return    SQL expression
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-11-23T20:09:46
 */
function buildSqlInsert( $tablename, $fields, $rowid = FALSE ) {
    $sql = "INSERT " 
    .   ($rowid ? "OR REPLACE" : "") 
    .   " INTO $tablename (";
    $count  = 0;
    $values = "";
    foreach ( $fields as $fieldname => $value ) {

        if ( 0 < $count ) {
            $sql .= ", ";
            $values .= ", ";
        }
        $sql .= " '{$fieldname}'";
        $values .= " '{$value}'";
        $count++;
    }
    $sql .= ") VALUES({$values} )";
    if ( $rowid )
        $sql .= "WHERE rowid = $rowid";
    $sql .= ";\n" ;

    return( $sql );
}   // buildSqlInsert()

//---------------------------------------------------------------------
/**
 *  @fn        buildSqlUpdate()
 *  @brief     Build an UPDATE statement in SQL
 *  
 *  @details   More details
 *  
 *  @param [in] $tablename Name of table
 *  @param [in] $fields    Hash of field tags and values
 *  @param [in] $shere     WHERE clause
 *  @return    SQL expression
 *  
 *  @example    $fields = [ "name" => 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015' ];
 *      $where = [ "rowid" => 1];
 *      echo buildSqlUpdate("devices", $fields, $where );
 *
 *      UPDATE devices SET "name" = 'HP ZBook 17', "model" => 'ZBook', "serial" => 'SN-2015'
 *      WHERE     rowid = 1;
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-11T07:51:32
 */
function buildSqlUpdate( $tablename, $fields, $where = FALSE ) {
    $sql = "UPDATE $tablename SET ";
    $count  = 0;
    $values = "";
    foreach ( $fields as $fieldname => $value ) {

        if ( 0 < $count ) {
            $sql .= ", ";
        }
        $sql .= "'{$fieldname}' = '{$value}'";
        $count++;
    }
    $count = 0;
    if ( $where )
        $sql .= " WHERE ";
        foreach ( $where as $fieldname => $value ) {
            if ( 0 < $count ) {
                $sql .= " AND ";
            }
            $sql .= "$fieldname = $value";
        }
    $sql .= ";" . PHP_EOL;

    return( $sql );
}   // buildSqlUpdate()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlTableLength()
 *  @brief     Return no of elements in table
 *  
 *  @details   Count no of elements
 *  
 *  @param [in] $db    Handle to database
 *  @param [in] $table Name of table
 *  @param [in] $where WHERE clause
 *  @return    No of rows
 *  
 *  @example   getSqlTableLength( $db, "meta" )
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2019-12-11T08:23:06
 */
function getSqlTableLength( &$db, $table, $where = FALSE ) {
    $query = "SELECT max( rowid ) AS max FROM $table";
    if ( $where ) {
        $query = "SELECT count(*) AS max FROM $table";
        $query  .= " WHERE $where";
    }
    
    $result = $db->query( "$query;");

    $row = $result->fetchArray(SQLITE3_ASSOC);
    return( $row['max'] ); 
}   // getSqlTableLength()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlTables()
 *  @brief     List tables/indices in database
 *  
 *  @details   Getting list of tables from sqlite_master
 *  
 *  CREATE TABLE sqlite_master (
  type text,
  name text,
  tbl_name text,
  rootpage integer,
  sql text
);

 *  @param [in] $db Database handle
 *  @param [in] $fields Field name (name)
 *  @param [in] $type   Element type (table/trigger/index)
 *  @return    Array of tables
 *  
 *  @example   $list = getSqlTables( $db );
 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:58:34
 */
 function getSqlTables( &$db, $fields = "name", $type = "table") {
    //return( getData( $db, "SELECT name FROM sqlite_master WHERE type='table';" ) );
    $sql    = "SELECT $fields FROM sqlite_master WHERE type='$type';";
    $got    = $db->query( $sql );

    $rows   = [];
    while ($row = $got->fetchArray(SQLITE3_ASSOC)) {
        array_push( $rows, $row );
    }
    return( $rows );
}   // getSqlTables()

//---------------------------------------------------------------------

/**
 *  @fn        getSqlMaxRowId()
 *  @brief     Get highest rowid in table
 *  
 *  @details   More details
 *  
 *  @param [in] $db    Database handle
 *  @param [in] $table Name of table
 *  @return    Highest rowid
 *  
 *  @example   getSqlMaxRowId
 *  
 *  @todo       
 *  @bug        
 *  @warning    Highest rowid is NOT the no of rows! Use getSqlTableLength()
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:12:18
 */
function getSqlMaxRowId( &$db, $table ) {
    $query = "SELECT max(ROWID) FROM $table;";

    $result = $db->query($query);

    if ( ! $result )
        return( FALSE );

    $row = $result->fetchArray(SQLITE3_ASSOC);

    return( $row['max(ROWID)'] ); 
}   // getSqlMaxRowId()

//---------------------------------------------------------------------

/**
 *  @fn        querySql()
 *  @brief     Executes an SQL query
 *  
 *  @details   More details
 *  
 *  @param [in] $db  Database handle
 *  @param [in] $sql SQL statement
 *  @return    Return result
 *  
 *  @example   querySql
 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:13:49
 */
function querySql( &$db, $sql ) {
    $result = $db->query( $sql );

    $names=false;
    if ( $result->numColumns() ) {
        $names=array();

        while($arr=$result->fetchArray(SQLITE3_ASSOC))
        {
            array_push( $names, $arr );
        }
    }
    return( $names ); 
}   //querySql()

//---------------------------------------------------------------------

/**
 *  @fn        querySqlSingleValue()
 *  @brief     Executes a query and returns a single result (value)
 *  
 *  @details   Alias for SQLite3::querySingle (default)
 *  
 *  @param [in] $db  Description for $db
 *  @param [in] $sql Description for $sql
 *  @return    Return description
 *  
 *  @example   $sql    = "SELECT str FROM {$tablename};";
 *  @example   $got    = querySqlSingleValue( $db, $sql );
 *  @example   $expected   = "'Hello world'";
 *  
 *  @todo       
 *  @bug        
 *  @warning    Returned value is quoted: "'Hello'"
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:09:19
 */
function querySqlSingleValue( &$db, $sql ) {
    return( $db->querySingle( $sql ) );
}   //querySqlSingleValue()

//---------------------------------------------------------------------


/**
 *  @fn        querySqlSingleRow()
 *  @brief     Executes a query and returns a single result (Row)
 *  
 *  @details   Alias for SQLite3::querySingle (entire_row = true)
 *  
 *  @param [in] $db  Description for $db
 *  @param [in] $sql Description for $sql
 *  @return    Return description
 *  
 *  @example   $sql    = "SELECT * FROM {$tablename};";
 *  @example   $got    = querySqlSingleRow( $db, $sql );
 *  @example   $expected   = "array (
 *  @example     'id' => 1,
 *  @example     'str' => 'Hello world',
 *  @example   )";

 *  
 *  @todo       
 *  @bug        
 *  @warning    
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T13:09:19
 */
function querySqlSingleRow( &$db, $sql ) {
    return( $db->querySingle( $sql, TRUE ) );
}   //querySqlSingleRow()


//---------------------------------------------------------------------

/**
 *  @fn        executeSql()
 *  @brief     Prepares an SQL statement for execution, execute and return result as array
 *  
 *  @details   More details
 *  
 *  @param [in] $db  Database handle
 *  @param [in] $sql SQL statement
 *  @return    Return description
 *  
 *  @example   executeSql()
 *  
 *  @todo       
 *  @bug        
 *  @warning    May only process ONE statement at a time
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:18:28
 */
function executeSql( &$db, $sql ) {
    global $debug;
    $stmt   = FALSE;

    $names=false;
    //$sql    = SQLite3::escapeString( $sql );
/*
    if ( $debug ) {
        trigger_error( "SQL: [$sql]", E_USER_NOTICE );
    }
*/
    //if($stmt = $db->prepare( SQLite3::escapeString( $sql ) ))
    //if( ( $stmt = $db->prepare( $sql  ) ) === TRUE )
    if( $stmt = $db->prepare( $sql  ) )
    {
        //trigger_error( print_r($stmt, TRUE), E_USER_NOTICE);
        
        if ( ! isset( $stmt ) )
            trigger_error( "Cannot prepare SQL [$sql]", E_USER_ERROR );
        try {
            $result = $stmt->execute();
        }
        catch (Exception $exception) {
            if ($sqliteDebug) {
                trigger_error( $exception->getMessage(), E_USER_WARNING) ;
            }
            trigger_error( "Error executing SQL [$sql]", E_USER_ERROR );
        }
        
        $names  = TRUE;
        if ( $result->numColumns() ) {
            $names=array();

            while($arr=$result->fetchArray(SQLITE3_ASSOC))
            {
                array_push( $names, $arr );
            }
        }
    } else {
        $err = error_get_last();
        trigger_error( "Error executing SQL [$sql]" . var_export( $err, TRUE), E_USER_ERROR );
    }

    return( $names ); 
}   // executeSql()


//---------------------------------------------------------------------

/**
 *  @fn        closeSqlDb()
 *  @brief     Close / Close +  delete database file
 *  
 *  @details   More details
 *  
 *  @param [in] $db     Database handle
 *  @param [in] $dbfile Name of database file to remove
 *  @return    Return description
 *  
 *  @example   closeSqlDb
 *  
 *  @todo       
 *  @bug        
 *  @warning    Will rename database file without warning
 *  @deprecated 
 *   
 *  @see
 *  @since      2019-12-11T14:19:02
 */
function closeSqlDb( &$db, $dbfile = FALSE ) {
    $result = $db->close();
    if (! $result ) {
        trigger_error("Failed to close database $dbfile", E_USER_WARNING );
    } 
    if ( $dbfile ) {
        rename( "$dbfile", "$dbfile.old" );
        if ( file_exists( $dbfile ) )
            trigger_error( "Database file still exists [$dbfile] [$result]", E_USER_WARNING );
    }
    return( $result );
}   // closeSqlDb()

//---------------------------------------------------------------------

/**
 *  @fn        stdClass2array()
 *  @brief     Converting an array/stdClass -> array
 *  
 *  @details   Converting an array/stdClass -> array
 *   The manual specifies the second argument of json_decode as: assoc
 *   When TRUE, returned objects will be converted into associative arrays.
 *  
 *  @param [in] $stdClass Description for $stdClass
 *  @return    Return description
 *  
 *  @example   stdClass2array
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  @deprecated No longer in used
 *  
 *  @see        https://stackoverflow.com/a/18576902
 *  @since      2019-11-23T16:33:15
 */
function stdClass2array( &$stdClass ) {
    trigger_error( __FUNCTION__ . " is deprecated", E_USER_WARNING );
    $array = json_decode(json_encode($stdClass), TRUE);
    return( $array );
}   // stdClass2array()

//---------------------------------------------------------------------

/**
 *  @fn        fetchObject()
 *  @brief     Fetch object from SQLite
 *  
 *  @details   More details
 *  
 *  @param [in] $sqlite3result Description for $sqlite3result
 *  @param [in] $objectType    Description for $objectType
 *  @return    Return description
 *  
 *  @example   fetchObject
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  @deprecated No longer in used
 *  
 *  @see        https://www.php.net/manual/en/class.sqlite3result.php#101589
 *  @since      2019-12-11T09:07:07
 */
function fetchObject( &$sqlite3result, $objectType = NULL) {
    trigger_error( __FUNCTION__ . " is deprecated", E_USER_WARNING );
    
    $array = $sqlite3result->fetchArray();

    if(is_null($objectType)) {
        $object = new stdClass();
    } else {
        // does not call this class' constructor
        $object = unserialize(sprintf('O:%d:"%s":0:{}', strlen($objectType), $objectType));
    }
   
    $reflector = new ReflectionObject($object);
    for($i = 0; $i < $sqlite3result->numColumns(); $i++) {
        $name = $sqlite3result->columnName($i);
        $value = $array[$name];
       
        try {
            $attribute = $reflector->getProperty($name);
           
            $attribute->setAccessible(TRUE);
            $attribute->setValue($object, $value);
        } catch (ReflectionException $e) {
            $object->$name = $value;
        }
    }
   
    return $object;
}   // fetchObject()

//---------------------------------------------------------------------

/** 
 *  @fn        truncateTable()
 *  @brief     Truncating a table
 *  
 *  @details   Delete all entries - execpt LIMIT last entries
 *  
 *  @param [in] $db     File pointer to database
 *  @param [in] $table  Table name
 *  @param [in] $limit  Rest to leave in table
 *  @return    Return description
 *  
 *  @example   truncateTable
 *  
 *  @todo     
 *  @bug     
 *  @warning    This function requires that table has rowid's (default)
 *  
 *  @see        https://stackoverflow.com/a/6990013/7485823
 *  @since      2020-01-28T10:43:43
 */
function truncateTable( &$db, $table, $limit = 10 ) {
    $sql    = "
DELETE FROM $table WHERE rowid NOT IN ( 
   SELECT rowid FROM $table
   ORDER BY rowid DESC
   LIMIT $limit
);
";
    $db->exec( $sqlQueue );
}   // deleteFromTableExcept()

//---------------------------------------------------------------------

/** 
 *  @fn        resetRowid()
 *  @brief     Reset rowids after truncate
 *  
 *  @details   Unload and reload entries to reset rowid. 
 *  Usefull for trucating log files
 *  
 *  @param [in] $db    	Description for $db
 *  @param [in] $table 	Description for $table
 *  @return    Return description
 *  
 *  @example   resetRowid
 *  
 *  @todo     
 *  @bug     
 *  @warning 
 *  
 *  @see
 *  @since      2020-01-28T10:47:05
 */
function resetRowid( &$db, $table ) {
    $sqlQueue = "
-- Reset rowid
-- Create temporary table
CREATE TABLE IF NOT EXISTS {$table}_destination 
    AS SELECT * FROM $table;
-- SELECT * FROM {$table}_destination;
-- Delete from $table
DELETE FROM $table;
-- Reload source from temporary
INSERT INTO $table
    SELECT * FROM {$table}_destination;
-- SELECT rowid, * FROM $table;
";
    $db->exec( $sqlQueue );
}   // resetRowid()


//----------------------------------------------------------------------

function dbNoOfRecords( &$db, $table, $where = ""  ) {
    $no = querySqlSingleValue( $db, "SELECT count(*) FROM $table $where;" );
    return( $no );
}

//----------------------------------------------------------------------


function dbLastEntry(  &$db, $table, $orderfield = '*', $where = "" ) {
//function dbLastEntry(  &$db, $table, $where = "" ) {
    //$sql    = "SELECT $orderfield FROM $table $where ORDER BY $orderfield LIMIT 1;";
    //$sql    = "SELECT * FROM $table $where ORDER BY $orderfield LIMIT 1;";
	//https://stackoverflow.com/a/53947463
    //$sql    = "SELECT * FROM $table $where ORDER BY rowid DESC LIMIT 1;";
    $sql    = "SELECT $orderfield FROM $table $where ORDER BY rowid DESC LIMIT 1;";
	//error_log( $sql );
    //$no = querySqlSingleValue( $db, $sql );
    $no = querySqlSingleRow( $db, $sql );
	//error_log( var_export( $no, TRUE) );
    return( $no );
}	//*** dbLastEntry() ***

//----------------------------------------------------------------------

function dbSchema( &$db, $table, $where = ""  ) {
    $sql    = "SELECT type, name, tbl_name, REPLACE( sql , char(10), '<BR>' ) as sql
    FROM    sqlite_master
	$where";
    return( querySql( $db, $sql ) );
}

//----------------------------------------------------------------------

/**
 *  @fn        vacuumInto
 *  @brief     Vacuum current database to a new database file
 *  
 *  @param [in] $db        Handle to current database
 *  @param [in] $newdbfile File name for new database
 *  @return    VOID
 *  
 *  @details   As of SQLite 3.27.0 (2019-02-07), it is also possible 
 *  to use the statement VACUUM INTO 'file.db'; to backup the database 
 *  to a new file.
 *  
 *  @example    $db = openSqlDb( "source.db" );
 *              var_export( vacuumInto($db, "target.db" ) );
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://www.php.net/manual/en/sqlite3.backup.php
 *  @since     2022-01-16T22:32:40 / erba
 */
function vacuumInto( &$db, $newdbfile ) {
    if ( ! file_exists( $newdbfile ) ) {
        $sql = "VACUUM INTO '$newdbfile';";
        return( executeSql( $db, $sql ) );
    } else
        trigger_error( "Database already exists: [$newdbfile]", E_USER_WARNING );
    return( FALSE );
}   //*** vacuumInto() ***

//----------------------------------------------------------------------

/** 
	https://github.com/ephestione/php-sqlite-dump/blob/master/sqlite_dump.php
	PHP SQLite Dump

Tired of searching for "dump sqlite php" on the interwebs and 
finding only people suggesting to use the sqlite3 tool from 
CLI, or using PHP just as a wrapper for said sqlite3 tool? Look 
no further!

*/
function dbDump( $filename, $dumpfile) {
	//$db = new SQLite3(dirname(__FILE__)."/your/db.sqlite");
	$db = new SQLite3( $filename );
	$db->busyTimeout(5000);
	$length	= 0;

	$sql="-- Dumping '$filename' to '$dumpfile'\n";
	file_put_contents($dumpfile,$sql);
	
	$tables     =   $db->query("SELECT name FROM sqlite_master WHERE type ='table' AND name NOT LIKE 'sqlite_%';");
    trigger_error( "List of tables: " . print_r($tables), E_USER_NOTICE );
	//$tables     =   $db->query( $sql_config['get_table_names' ]);

	while ($table=$tables->fetchArray(SQLITE3_NUM)) {
        trigger_error( "Table name: '{$table[0]}'", E_USER_NOTICE );
		$sql.=$db->querySingle("SELECT sql FROM sqlite_master WHERE name = '{$table[0]}'").";\n\n";
		$rows=$db->query("SELECT * FROM {$table[0]}");
		$sql.="INSERT INTO {$table[0]} (";
		$columns=$db->query("PRAGMA table_info({$table[0]})");
		$fieldnames=array();
		while ($column=$columns->fetchArray(SQLITE3_ASSOC)) {
			$fieldnames[]=$column["name"];
		}
		$sql.=implode(",",$fieldnames).") VALUES";
		while ($row=$rows->fetchArray(SQLITE3_ASSOC)) {
			foreach ($row as $k=>$v) {
				//if ( empty( $v ) ) 	trigger_error( "Empty value [$v] in key [$k]", E_USER_WARNING );
				$row[$k]="'".SQLite3::escapeString("$v")."'";
			}
			//$sql.="\n(".implode(",",$row)."),";
            file_put_contents( $dumpfile, $sql . "\n(".implode(",",$row).");" , FILE_APPEND );
		}
		/*
        $sql=rtrim($sql,",").";\n\n";
        trigger_error( "SQL: " . print_r($sql), E_USER_NOTICE );
		file_put_contents( $dumpfile, $sql, FILE_APPEND );
		*/
        $length	+= strlen($sql);
		$sql 	= "";
	}
	file_put_contents($dumpfile,"-- Done", FILE_APPEND );
	//file_put_contents( $dumpfile,$sql, FILE_APPEND );
	//file_put_contents("sqlitedump.sql",$sql);
	return( $length );
}	//*** dbDump() ***

//----------------------------------------------------------------------

/**
 *  @fn        getDbFile
 *  @brief     Get full path to database
 *  
 *  @param [in] $db        Handle to current database
 *  @return    Path as string
 *  
 *  @details   More details
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://stackoverflow.com/a/44279467
 *  @since     2022-04-26T14:15:52 / erba
 */
function getDbFile( &$db ) {
    $name = querySqlSingleValue( $db, "SELECT file FROM pragma_database_list WHERE name='main';" );
    return( $name );
}   //*** getDbFile() ***

//----------------------------------------------------------------------

/**
 *  @fn        getDbName
 *  @brief     Get database name
 *  
 *  @param [in] $db     	Handle to current database
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
 *  @see       https://stackoverflow.com/a/44279467
 *  @since     2022-04-26T14:16:08 / erba
 */
function getDbName( &$db ) {
    $name = querySqlSingleValue( $db, "SELECT file FROM pragma_database_list WHERE name='main';" );
    return( basename( $name ) );
}   //*** getDbName() ***

//----------------------------------------------------------------------

/**
 *  @fn        array_flatten
 *  @brief     Flatten array
 *  
 *  @param [in] $arrayDescription for $array
 *  @return    Remove line no from returned set
 *  
 *  @details   Change single string in array to just single string??
 *  
 *  @example   
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-03-12T17:53:02 / Bruger
 */
function flatten(array $array) {
    return( array_flatten($array) );
}

function array_flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}   // array_flatten()


//----------------------------------------------------------------------



/**
 *  @fn        array_keys_OR
 *  @brief     Boolean OR operation on keys
 *  
 *  @param [in] $array1 Primary array
 *  @param [in] $array2 Secondary array
 *  @return    Combined array
 *  
 *  @details   More details
 *  
 *  @example   
 *      $array1 = [ 550 => 'xxx',  645 => 'xxx',  1097 => 'xxx',  1125 => 'xxx',  1126 => 'xxx' ];
 *      $array2 = [ 1126 => 'xxx', 645 => 'xxx' ];
 *      $result = array_keys_OR( $array1, $array2 );
 *      print implode( ",", array_keys($result) );
 *  
 *  should give:
 *          550,645,1097,1125,1126
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-03-19T18:00:50 / Erik Bachmann
 */
function array_keys_OR ( &$array1, &$array2 ) {
    return( array_replace_recursive($array1, $array2 ) );
}   // array_keys_OR()


/**
 *  @fn        array_keys_AND
 *  @brief     Boolean AND operation on keys
 *  
 *  @param [in] $array1 Primary array
 *  @param [in] $array2 Secondary array
 *  @return    Combined array
 *  
 *  @details   More details
 *  
 *  @example   
 *      $array1 = [ 550 => 'xxx',  645 => 'xxx',  1097 => 'xxx',  1125 => 'xxx',  1126 => 'xxx' ];
 *      $array2 = [ 1126 => 'xxx', 645 => 'xxx' ];
 *      $result = array_keys_AND( $array1, $array2 );
 *      print implode( ",", array_keys($result) );
 *  
 *  should give:
 *          645,1126
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-03-19T18:05:14 / Erik Bachmann
 */
function array_keys_AND ( &$array1, &$array2 ) {
    return( array_intersect_key($array1, $array2 ) );
}   // array_keys_AND()


/**
 *  @fn        array_keys_NOT
 *  @brief     Boolean NOT operation on keys
 *  
 *  @param [in] $array1 Primary array
 *  @param [in] $array2 Secondary array
 *  @return    Combined array
 *  
 *  @details   More details
 *  
 *  @example   
 *      $array1 = [ 550 => 'xxx',  645 => 'xxx',  1097 => 'xxx',  1125 => 'xxx',  1126 => 'xxx' ];
 *      $array2 = [ 1126 => 'xxx', 645 => 'xxx' ];
 *      $result = array_keys_NOT( $array1, $array2 );
 *      print implode( ",", array_keys($result) );
 *  
 *  should give:
 *          550,1097,1125
 *  
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-03-19T18:05:14 / Erik Bachmann
 */
function array_keys_NOT ( &$array1, &$array2 ) {
    return( array_diff_key( $array1, $array2 ) );
}   // array_keys_NOT()


//----------------------------------------------------------------------

/**
 *  @fn        expandBoolean
 *  @brief     Expand boolean keys in SQL expressions
 *  
 *  @param [in] $booleans   Expansion pairs FROM => TO
 *  @param [in] $phrase     SQL phrase to expand
 *  @return    Expanded SQL expression
 *  
 *  @details   Combining boolean relations between expressions
 *  
 *  Like: "This AND THAT"
 *  to: "This' INTERSECT SELECT recno FROM search WHERE entry LIKE 'THAT"
 *  
 *  @example   
 *      $booleans   = [
 *          "AND"   => "'\nINTERSECT \n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
 *      ,   "OR"    => "'\nUNION \n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
 *      ,   "NOT"   => "'\nEXCEPT \n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '"
 *      ];
 *      $sql        = "\n    SELECT recno \n\tFROM search \n\tWHERE entry \n\tLIKE '%s';\n";
 *  
 *      $newphrase  = expandBoolean( $booleans, "em:Biler OR em:Opel NOT em:Brugermanualer");
 *      var_dump( sprintf( $sql, $newphrase ) );
 *  
 *      string(214) "
 *          SELECT recno
 *              FROM search
 *              WHERE entry
 *              LIKE 'em:Biler'
 *      UNION
 *          SELECT recno
 *              FROM search
 *              WHERE entry
 *              LIKE 'em:Opel'
 *      EXCEPT
 *          SELECT recno
 *              FROM search
 *              WHERE entry
 *              LIKE 'em:Brugermanualer';
 *      "
 *
 *  @todo      
 *  @bug       
 *  @warning   
 *  
 *  @see       https://
 *  @since     2023-03-12T17:27:06 / Bruger
 */
function expandBoolean( &$booleans, $phrase )
{
    $newphrase = str_replace( array_keys( $booleans ), array_values( $booleans ), $phrase);
    return( $newphrase );
}   // expandBoolean()

//----------------------------------------------------------------------


/**
 *  @fn        matchSqlDefInDb
 *  @brief     Match SQL definition in database
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $type Description for $type
 *  @param [in] $tbl_name Description for $tbl_name
 *  @param [in] $name Description for $name
 *  @param [in] $coldef Description for $coldef
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
 *  @since     2023-03-26T19:20:56 / Bruger
 *///---------------------------------------------------------------------
function matchSqlDefInDb( &$db, $type, $tbl_name, $name, $coldef)
{
    if ( ! isset($coldef) )
        return;
    if ( isset($coldef['_comment'] ) ) 
        unset( $coldef['_comment'] );
    if ( isset($coldef['name'] ) ) 
        unset( $coldef['name'] );

    debug("Coldef: ");debug( $coldef );

    //SELECT * FROM pragma_table_info('meta') WHERE name='meta_id';
    $sql    = "SELECT sql FROM sqlite_master WHERE type='$type' and tbl_name ='$tbl_name' and name='$name' ;";
    $result = querySqlSingleRow( $db, $sql );
    
    debug("result: ");debug( $result );
   	is_deeply( $result, $coldef, "... Def.: $name.$name" );
	
	if ( array_diff_assoc_recursive( $result, $coldef ) ) {
		$a1	= var_export( $result, TRUE );
		$a2	= var_export( $coldef, TRUE );
		$a1	= preg_replace( "/[\n\t]/", " ", $a1 );
		$a2	= preg_replace( "/[\n\t]/", " ", $a2 );
		$a1	= preg_replace( "/[\s]+/", " ", $a1 );
		$a2	= preg_replace( "/[\s]+/", " ", $a2 );

		// output the result of comparing two files as plain text
		require_once( $GLOBALS['releaseroot'] . 'lib/class.Diff.php' );
		echo Diff::toTable( 
			Diff::compare( 
				$a1
			,	$a2
			)	
		); 

		
		
		
	}

    return( ! count( array_diff_assoc( $coldef, $result ) ) );
}   // matchSqlDefInDb()

//---------------------------------------------------------------------

/**
 *  @fn        matchColumnDefInDb
 *  @brief     Match columen definition in database
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $name Description for $name
 *  @param [in] $column Description for $column
 *  @param [in] $coldef Description for $coldef
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
 *  @since     2023-03-26T19:25:14 / Bruger
 */
function matchColumnDefInDb( &$db, $name, $column, $coldef)
{
    if ( ! isset($coldef) )
        return;
    if ( isset($coldef['_comment'] ) ) 
        unset( $coldef['_comment'] );

    $coldef = array_merge( ["name" => "$column" ], $coldef );
    debug("Coldef: ");debug( $coldef );

    //SELECT * FROM pragma_table_info('meta') WHERE name='meta_id';
    $sql    = "SELECT * FROM pragma_table_info('$name') WHERE name='$column';";
    $result = querySqlSingleRow( $db, $sql );
    
    if ( isset($result['cid'] ) ) unset( $result['cid'] );
    if ( isset($result['pk'] ) ) unset( $result['pk'] );
    
    debug("result: ");debug( $result );
    debug( array_diff_assoc( $coldef, $result ) );
   	is_deeply( $result, $coldef, "... Def.: $name.$column" );

    return( ! count( array_diff_assoc( $coldef, $result ) ) );
}   // matchColumnDefInDb()

//---------------------------------------------------------------------

/**
 *  @fn        ifExistsColumnInDb
 *  @brief     Check if column exists in database
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $name Description for $name
 *  @param [in] $column Description for $column
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
 *  @since     2023-03-26T19:25:26 / Bruger
 */
function ifExistsColumnInDb( &$db, $name, $column)
{
    //SELECT count(*) FROM sqlite_master WHERE type='table' and name='meta';"
    $sql    = "SELECT COUNT(*) AS CNTREC FROM pragma_table_info('$name') WHERE name='$column';";
    return( querySqlSingleValue( $db, $sql ) );
}   // ifExistsColumnInDb()

//---------------------------------------------------------------------

/**
 *  @fn        ifExistsTriggerInDb
 *  @brief     Check if trigger exists in database
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $tbl_name Description for $tbl_name
 *  @param [in] $idx Description for $idx
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
 *  @since     2023-03-26T19:25:35 / Bruger
 */
function ifExistsTriggerInDb( &$db, $tbl_name, $idx)
{
    //SELECT count(*) FROM sqlite_master WHERE type='table' and name='meta';"
    $sql    = "SELECT COUNT(*) AS CNTREC FROM sqlite_master WHERE type='trigger' and tbl_name ='$tbl_name' and name='$idx' ;";
    return( querySqlSingleValue( $db, $sql ) );
}   // ifExistsTriggerInDb()

//---------------------------------------------------------------------

/**
 *  @fn        ifExistsIdxInDb
 *  @brief     Check if index exists
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $tbl_name Description for $tbl_name
 *  @param [in] $idx Description for $idx
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
 *  @since     2023-03-26T19:25:56 / Bruger
 */
function ifExistsIdxInDb( &$db, $tbl_name, $idx)
{
	global $sql_config;
    //SELECT count(*) FROM sqlite_master WHERE type='table' and name='meta';"
    //$sql    = "SELECT COUNT(*) AS CNTREC FROM pragma_index_info('$idx');";
	$sql    = sprintf( $sql_config['index_exists'], $name );
    return( querySqlSingleValue( $db, $sql ) );
}   // ifExistsIdxInDb()

//---------------------------------------------------------------------

/**
 *  @fn        ifExistsInDb
 *  @brief     Count occurences in sqlite_master
 *  
 *  @param [in] $db Description for $db
 *  @param [in] $name Description for $name
 *  @param [in] $type Description for $type
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
 *  @since     2023-03-26T19:26:23 / Bruger
 */
function ifExistsInDb( &$db, $name, $type = "table")
{
	global $sql_config;
    //$sql    = "SELECT count(*) AS x FROM sqlite_master WHERE type='$type' and name='$name';";
    $sql    = sprintf( $sql_config['table_exists'], $name );

    return( querySqlSingleValue( $db, $sql ) );
}   // ifExistsInDb()


//---------------------------------------------------------------------


?>