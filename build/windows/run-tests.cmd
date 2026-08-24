@echo off
setlocal

set "REPO_ROOT=%~dp0..\.."
for %%I in ("%REPO_ROOT%") do set "REPO_ROOT=%%~fI"
if not defined PHP_HOME set "PHP_HOME=D:\DevTools\TypePHP\v0.2.3"

"%PHP_HOME%\php.exe" "%REPO_ROOT%\tests\lint.php"
if errorlevel 1 exit /b %errorlevel%

"%PHP_HOME%\php.exe" "%REPO_ROOT%\tests\run.php"
exit /b %ERRORLEVEL%
