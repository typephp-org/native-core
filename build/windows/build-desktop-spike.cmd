@echo off
setlocal

set "REPO_ROOT=%~dp0..\.."
for %%I in ("%REPO_ROOT%") do set "REPO_ROOT=%%~fI"
if not defined TYPEPHP_HOME set "TYPEPHP_HOME=D:\DevTools\TypePHP\v0.2.3"
call "%~dp0build-typephp-gui.cmd" "%REPO_ROOT%\examples\desktop-window-spike\project.yml" "%REPO_ROOT%\build\artifacts\desktop_window_spike.exe"
if errorlevel 1 exit /b %errorlevel%

set "PATH=%TYPEPHP_HOME%;%PATH%"
start "" /wait "%REPO_ROOT%\build\artifacts\desktop_window_spike.exe" --smoke
if errorlevel 1 exit /b %errorlevel%
exit /b 0
