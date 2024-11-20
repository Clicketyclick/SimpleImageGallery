@ECHO OFF
::## 
:: #  @file      run.cmd
:: #  @brief     Starting PHP webserver
:: #  
:: #  @details   Starting a local PHP webserver with root in current directory
:: #  
:: #  @copyright http://www.gnu.org/licenses/lgpl.txt LGPL version 3
:: #  @author    Erik Bachmann <ErikBachmann@ClicketyClick.dk>
:: #  @since     2023-03-13T12:29:14 / erba
:: #  @version   2024-11-01T12:17:20
:: ##  
:init
    ECHO Running PHP webserver for test
    SET _PORT=8083
    ::CALL :04
    ::CALL :05
    CALL :run

:main
    :: Get current subdir
    FOR %%a IN ("%__CD__%\.") DO SET "currentDir=%%~nxa"
    ECHO [%currentDir%]

    :: Set window title
    TITLE localhost:%_PORT% - [%currentDir%]

    :: Star server
    php -S localhost:%_PORT%

GOTO :EOF

:run
    TITLE %_PORT%
    ECHO: Plain 
    START /B /MIN http://localhost:%_PORT%/index.php
GOTO :EOF

:05
    ECHO: v. 05.xx
    START /B /MIN http://localhost:%_PORT%/src/.releases/05.00/index.php
    ::START /B http://localhost:%_PORT%/
GOTO :EOF

:04
    :: Start browser
    ECHO: v. 04.xx
    START /B /MIN http://localhost:%_PORT%/src/
GOTO :EOF
