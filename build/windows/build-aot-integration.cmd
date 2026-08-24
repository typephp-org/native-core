@echo off
setlocal

set "REPO_ROOT=%~dp0..\.."
for %%I in ("%REPO_ROOT%") do set "REPO_ROOT=%%~fI"
if not defined TYPEPHP_HOME set "TYPEPHP_HOME=D:\DevTools\TypePHP\v0.2.3"
if not defined PHP_HOME set "PHP_HOME=%TYPEPHP_HOME%"
if not defined PHPX_HOME set "PHPX_HOME=%TYPEPHP_HOME%\phpx"
if not defined VS_BUILD_TOOLS set "VS_BUILD_TOOLS=D:\DevTools\VisualStudio\2022\BuildTools"

call "%VS_BUILD_TOOLS%\VC\Auxiliary\Build\vcvars64.bat" >nul
if errorlevel 1 exit /b %errorlevel%
if not exist "%REPO_ROOT%\build\artifacts" mkdir "%REPO_ROOT%\build\artifacts"
if exist "%REPO_ROOT%\build\artifacts\aot_integration.exe" del /q "%REPO_ROOT%\build\artifacts\aot_integration.exe"

pushd "%TYPEPHP_HOME%"
"%TYPEPHP_HOME%\tpc.exe" "%REPO_ROOT%\examples\aot-integration\project.yml" --no-color
set "BUILD_EXIT=%ERRORLEVEL%"
popd
if not "%BUILD_EXIT%"=="0" exit /b %BUILD_EXIT%
if not exist "%REPO_ROOT%\build\artifacts\aot_integration.exe" (
    echo ERROR: TypePHP did not produce aot_integration.exe
    exit /b 3
)

set "PATH=%TYPEPHP_HOME%;%PATH%"
"%REPO_ROOT%\build\artifacts\aot_integration.exe"
exit /b %ERRORLEVEL%
