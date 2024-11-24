@ECHO OFF
ECHO :: Get updated version from Git
"C:\Program Files\Git\bin\git.exe" describe --tags > "%~dp0\version.txt"
TYPE "%~dp0\version.txt"