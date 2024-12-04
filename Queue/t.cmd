@ECHO OFF

:: Check for arg: load
echo %* | FIND /C /I "init" >nul
IF ERRORLEVEL 1 GOTO :fail
:load   :: Initiate
    ECHO Initiating
    SET _INIT=-init=1
    DEL Queue.json
GOTO :init
:fail
    ECHO No init
GOTO :init

:init
:: Start + pause
    IF "!start"=="!%~1" GOTO :process
    TITLE Initiating queue
    ::php addQueue.php -element=""
    php addQueue.php %_INIT%

    ::IF "!load"=="!%~1" GOTO :eof

:run
    start t "start"
    timeout /T:3
    php addQueue.php -type=unshift -element="{\"title\":\"first_of_queue_INSERTED\",\"start\":1,\"end\":10,\"action\":\"something 1\"}"
    ::-debug=true
    pause
GOTO :EOF

:process
    TITLE Processing queue
    php processQueue.php
    timeout /T:10
    exit
