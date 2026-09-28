@echo off
setlocal

set "REPO_ROOT=%~dp0..\.."
for %%I in ("%REPO_ROOT%") do set "REPO_ROOT=%%~fI"
if not defined TYPEPHP_HOME set "TYPEPHP_HOME=D:\DevTools\TypePHP"
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
if not exist "%TEMP%\typephp-native-core-opcache" mkdir "%TEMP%\typephp-native-core-opcache"
set "PHP_INI_SCAN_DIR=%REPO_ROOT%\build\windows\php-ini"
set "RUN_LOG=%REPO_ROOT%\build\aot-integration-runtime.log"
"%REPO_ROOT%\build\artifacts\aot_integration.exe" > "%RUN_LOG%" 2>&1
set "RUN_EXIT=%ERRORLEVEL%"
type "%RUN_LOG%"
if not "%RUN_EXIT%"=="0" exit /b %RUN_EXIT%
findstr /c:"PASS aot-integration" "%RUN_LOG%" >nul
if errorlevel 1 (
    echo ERROR: AOT integration artifact did not emit its success marker
    exit /b 4
)
exit /b 0
