@echo off
setlocal
set "PHP84=C:\Users\jbran\AppData\Local\Microsoft\WinGet\Packages\PHP.PHP.8.4_Microsoft.Winget.Source_8wekyb3d8bbwe"
set "PATH=%PHP84%;%USERPROFILE%\AppData\Local\ComposerSetup\bin;%PATH%"
cd /d "%~dp0"
call composer check
exit /b %errorlevel%
