@ECHO OFF


REM - Position the CMD Window Using .VBS -----------------------------------------
REM == MUST BE AT The Begining of The Batch =========
   IF "%~1" == "RestartedByVBS" Goto :Code

   REM Create the VBScript, if not exist
   IF NOT EXIST "%~DP0pos.vbs" (
      (FOR /F "tokens=1*" %%a in ('findstr "^VBS:" ^< "%~F0"') do (
         echo(%%b
      )) > "%~DP0pos.vbs"
   )
   dir "%~DP0pos.vbs"
   pause
   REM Start "" "%~DP0pos.vbs" "%~F0" 100 50
   Cscript //nologo "%~DP0pos.vbs" "%~F0" 200 50
   echo "%~DP0pos.vbs"
   pause
   EXIT /B
:code
::DEL /Q "%~DP0pos.vbs"
REM ------------------------------------------------------------------------------



REM - Position the CMD Window Using .VBS -----------------------------------------
:Pos <BatchFileName> <X_Coordinate> <Y_Coordinate>

REM This Function will take three inputs: the name of the Batch file to execute
REM and the X and Y Coordinates to Position its CMD window

VBS: Set objWMIService = GetObject("winmgmts:\\.\root\cimv2")
VBS: Set objConfig = objWMIService.Get("Win32_ProcessStartup")
VBS: objConfig.SpawnInstance_
VBS: objConfig.X = WScript.Arguments(1)
VBS: objConfig.Y = WScript.Arguments(2)
VBS: Set objNewProcess = objWMIService.Get("Win32_Process")
VBS: intReturn = objNewProcess.Create( chr(34) & WScript.Arguments(0) &chr(34)& " RestartedByVBS", Null, objConfig, intProcessID)
REM ------------------------------------------------------------------------------

