@ECHO OFF&SetLocal EnableDelayedExpansion
::**
:: *   @file       sqlite.cmd
:: *   @brief      Start SQLite in a given database w window title
:: *   @details    
:: *   
:: *   @copyright  http://www.gnu.org/licenses/lgpl.txt LGPL version 3
:: *   @author     Erik Bachmann <ErikBachmann@ClicketyClick.dk>
:: *   @since      2024-11-19T12:16:08 / ErBa
:: *   @version    2024-11-19T12:16:08
::**

:: Set Window title: database.ext - drive path
TITLE %~nx1 - %~dp1
"C:\Program Files\SQLite3\sqlite3.exe" %*
