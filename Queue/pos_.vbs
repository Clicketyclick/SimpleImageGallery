Set objWMIService = GetObject("winmgmts:\\.\root\cimv2")
Set objConfig = objWMIService.Get("Win32_ProcessStartup")
objConfig.SpawnInstance_
objConfig.X = WScript.Arguments(1)
objConfig.Y = WScript.Arguments(2)
Set objNewProcess = objWMIService.Get("Win32_Process")
intReturn = objNewProcess.Create( chr(34) & WScript.Arguments(0) &chr(34)& " RestartedByVBS", Null, objConfig, intProcessID)
