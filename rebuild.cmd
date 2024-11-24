@ECHO OFF
cls

IF "!"=="!%*" GOTO :usage

php util\rebuild.php %*

GOTO :EOF

:usage
ECHO: %~n0 - Rebuild database for Simple Image Gallery
ECHO Usage:
ECHO    %~n0 -action=full   Build a new database and load data
ECHO        - clear Tables
ECHO        - Read all files
ECHO        - Rebuild metadata
ECHO        - Build indexes
ECHO:
ECHO    %~n0 -action=update   Update current database
ECHO        - Rebuild metadata
ECHO        - Build indexes
ECHO:
ECHO    %~n0 -action=resume   Process files without metadata
ECHO        - Read all files
ECHO            - add_new_files
ECHO            - Rebuild metadata
ECHO            - Build indexes
ECHO See: config\config.json:
ECHO - data/data_root: Source dir for image files
ECHO - database/file_name: Database file to rebuild


