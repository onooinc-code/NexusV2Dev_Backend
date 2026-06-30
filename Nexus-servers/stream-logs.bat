@echo off
setlocal enabledelayedexpansion

:: ============================================================
:: Nexus Laravel Log Streamer
:: Streams live application logs and HTTP requests in real-time
:: ============================================================

:: Set title for the window
title Nexus Laravel Logs - Live Stream

:: Define colors (using ANSI escape codes for Windows 10+)
set "COLOR_RESET=[0m"
set "COLOR_RED=[91m"
set "COLOR_GREEN=[92m"
set "COLOR_YELLOW=[93m"
set "COLOR_BLUE=[94m"
set "COLOR_MAGENTA=[95m"
set "COLOR_CYAN=[96m"
set "COLOR_WHITE=[97m"
set "COLOR_BG_RED=[41m"
set "COLOR_BG_YELLOW=[43m"

:: Enable ANSI colors
for /F "tokens=3" %%A in ('ver') do set "WINVER=%%A"
if %WINVER% GEQ 10.0 (
    reg add "HKEY_CURRENT_USER\Console" /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
)

:: Parse command line arguments
set "LOG_LEVEL="
set "FILTER_TEXT="

:PARSE_ARGS
if "%~1"=="" goto :END_PARSE
if /i "%~1"=="--level" (
    set "LOG_LEVEL=%~2"
    shift
    shift
    goto :PARSE_ARGS
)
if /i "%~1"=="--filter" (
    set "FILTER_TEXT=%~2"
    shift
    shift
    goto :PARSE_ARGS
)
set "CUSTOM_CMD=%~1"
shift
goto :PARSE_ARGS

:END_PARSE

:: Get script directory and change to project root
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%.."

echo.
echo %COLOR_BLUE%====================================%COLOR_RESET%
echo %COLOR_CYAN%  Nexus Laravel Log Streamer%COLOR_RESET%
echo %COLOR_BLUE%====================================%COLOR_RESET%
echo.

:: Check if artisan exists
if not exist "artisan" (
    echo %COLOR_RED%[ERROR]%COLOR_RESET% artisan file not found. Are you in a Laravel project?
    exit /b 1
)

:: Display configuration
if defined LOG_LEVEL (
    echo %COLOR_YELLOW%Log Level Filter:%COLOR_RESET% %LOG_LEVEL%
)
if defined FILTER_TEXT (
    echo %COLOR_YELLOW%Text Filter:%COLOR_RESET% %FILTER_TEXT%
)
echo %COLOR_YELLOW%Press Ctrl+C to stop logging%COLOR_RESET%
echo.

:: Set up log file path for reference
set "LOG_PATH=storage/logs/laravel.log"

:: Display current active connections
echo %COLOR_MAGENTA%--- Active HTTP Connections ---%COLOR_RESET%
for /F "skip=4 tokens=2" %%A in ('netstat -ano ^| findstr "ESTABLISHED"') do (
    echo %COLOR_WHITE%%%A%COLOR_RESET%
)
echo.

:: Start Pail log streaming
echo %COLOR_GREEN%[%date% %time%] Starting log stream...%COLOR_RESET%
echo Type 'exit' or press Ctrl+C to stop.
echo.

:: Build the artisan pail command
set "PAIL_CMD=php artisan pail --timeout=0"

if defined LOG_LEVEL (
    set "PAIL_CMD=%PAIL_CMD% --level=%LOG_LEVEL%"
)

:: Stream logs with formatted output
:PAIL_STREAM_LOOP
for /F "delims=" %%L in ('%PAIL_CMD% 2^>nul') do (
    set "LINE=%%L"
    
    :: Apply text filter if specified - skip this line if filter doesn't match
    if defined FILTER_TEXT (
        echo !LINE! | findstr /I "%FILTER_TEXT%" >nul
        if !errorlevel! neq 0 goto :PAIL_STREAM_LOOP
    )
    
    :: Add timestamp prefix
    set "TIMESTAMP=%COLOR_WHITE%[%date% %time:~0,8%]%%COLOR_RESET%"
    
    :: Color-code based on log level (highest priority first)
    echo !LINE! | findstr /I "CRITICAL" >nul
    if !errorlevel! equ 0 (
        echo %COLOR_BG_RED%%COLOR_WHITE%%TIMESTAMP% !LINE!%COLOR_RESET%
        goto :PAIL_CONTINUE
    )
    
    echo !LINE! | findstr /I "ERROR" >nul
    if !errorlevel! equ 0 (
        echo %COLOR_RED%%TIMESTAMP% !LINE!%COLOR_RESET%
        goto :PAIL_CONTINUE
    )
    
    echo !LINE! | findstr /I "WARNING" >nul
    if !errorlevel! equ 0 (
        echo %COLOR_YELLOW%%TIMESTAMP% !LINE!%COLOR_RESET%
        goto :PAIL_CONTINUE
    )
    
    echo !LINE! | findstr /I "INFO" >nul
    if !errorlevel! equ 0 (
        echo %COLOR_GREEN%%TIMESTAMP% !LINE!%COLOR_RESET%
        goto :PAIL_CONTINUE
    )
    
    :: Default formatting for other log levels
    echo %COLOR_WHITE%%TIMESTAMP% !LINE!%COLOR_RESET%
    
    :PAIL_CONTINUE
)

:: If pail exits, restart it
echo %COLOR_YELLOW%Log stream ended, restarting...%COLOR_RESET%
timeout /t 3 /nobreak >nul
goto :PAIL_STREAM_LOOP

:GRACEFUL_EXIT
echo.
echo %COLOR_GREEN%Log streaming stopped.%COLOR_RESET%
exit /b 0