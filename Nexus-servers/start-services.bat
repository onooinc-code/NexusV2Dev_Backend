@echo off
setlocal enabledelayedexpansion

:: ============================================================
:: Nexus Laravel Service Orchestrator
:: Terminates existing processes, starts all Laravel dependencies,
:: displays service URLs, and monitors status in a 10-second loop
:: ============================================================

:: Set title for the window
title Nexus Laravel Services - Monitor

:: Define colors (using ANSI escape codes for Windows 10+)
set "COLOR_RESET=[0m"
set "COLOR_RED=[91m"
set "COLOR_GREEN=[92m"
set "COLOR_YELLOW=[93m"
set "COLOR_BLUE=[94m"
set "COLOR_MAGENTA=[95m"
set "COLOR_CYAN=[96m"
set "COLOR_WHITE=[97m"

:: Enable ANSI colors
for /F "tokens=3" %%A in ('ver') do set "WINVER=%%A"
if %WINVER% GEQ 10.0 (
    reg add "HKEY_CURRENT_USER\Console" /v VirtualTerminalLevel /t REG_DWORD /d 1 /f >nul 2>&1
)

:: Define ports to check (Only kill app ports, don't kill databases)
set "PORTS=8000 8080"

:: Define service URLs
set "SERVICE_LARAVEL=http://127.0.0.1:8000"
set "SERVICE_HORIZON=http://127.0.0.1:8000/horizon"
set "SERVICE_REVERB=ws://127.0.0.1:8080"
set "SERVICE_WAHA=http://127.0.0.1:3333"

:: Log file for this session
set "t=%time: =0%"
set "LOG_FILE=logs\services-%date:~-4,4%%date:~-10,2%%date:~-7,2%_%t:~0,2%%t:~3,2%%t:~6,2%.log"

:: Get script directory and change to project root
set "SCRIPT_DIR=%~dp0"
cd /d "%SCRIPT_DIR%.."

:: Create logs directory at project root
if not exist "logs" mkdir "logs" >nul

echo.
echo %COLOR_BLUE%====================================%COLOR_RESET%
echo %COLOR_CYAN%  Nexus Laravel Service Orchestrator%COLOR_RESET%
echo %COLOR_BLUE%====================================%COLOR_RESET%
echo.

:: Step 1: Terminate existing processes on required ports
echo %COLOR_YELLOW%[1/4] Terminating existing processes on required ports...%COLOR_RESET%
for %%P in (%PORTS%) do (
    for /F "skip=4 tokens=5" %%A in ('netstat -ano ^| findstr ":%%P "') do (
        if not "%%A"=="" (
            echo %COLOR_RED%  Killing process PID %%A on port %%P%COLOR_RESET%
            taskkill /F /PID %%A >nul 2>&1
            if !errorlevel! equ 0 (
                echo %COLOR_GREEN%    [OK] Port %%P freed%COLOR_RESET%
            ) else (
                echo %COLOR_YELLOW%    [SKIP] No process found or already terminated%COLOR_RESET%
            )
        )
    )
)
echo.

:: Step 2: Wait for ports to be fully released
echo %COLOR_YELLOW%[2/4] Waiting for ports to be released...%COLOR_RESET%
timeout /t 2 /nobreak >nul
echo.

:: Step 3: Check external services + Start Laravel services
echo %COLOR_YELLOW%[3/4] Checking external services (Docker-managed)...%COLOR_RESET%

:: Check Redis status (Docker - manual start required)
netstat -ano | findstr ":6379 " >nul
if %errorlevel% equ 0 (
    echo %COLOR_GREEN%  [OK]   Redis is running on port 6379%COLOR_RESET%
) else (
    echo %COLOR_RED%  [DOWN] Redis is NOT running on port 6379 ^(start Docker container manually^)%COLOR_RESET%
)

:: Check WAHA status (Docker - manual start required)
netstat -ano | findstr ":3333 " >nul
if %errorlevel% equ 0 (
    echo %COLOR_GREEN%  [OK]   WAHA is running on port 3333%COLOR_RESET%
) else (
    echo %COLOR_RED%  [DOWN] WAHA is NOT running on port 3333 ^(start Docker container manually^)%COLOR_RESET%
)
echo.

:: Start Laravel-owned services
echo %COLOR_YELLOW%       Starting Laravel services...%COLOR_RESET%

:: Start Laravel development server
echo %COLOR_CYAN%  Starting Laravel HTTP server...%COLOR_RESET%
start "Laravel Server" /B cmd /c "php artisan serve --host=127.0.0.1 --port=8000 --no-reload 2>nul"
timeout /t 2 /nobreak >nul

:: Start Reverb WebSocket server
echo %COLOR_CYAN%  Starting Reverb WebSocket server...%COLOR_RESET%
start "Reverb Server" /B cmd /c "php artisan reverb:start --host=127.0.0.1 --port=8080 2>nul"
timeout /t 2 /nobreak >nul

:: Start Queue worker
echo %COLOR_CYAN%  Starting Queue worker...%COLOR_RESET%
start "Queue Worker" /B cmd /c "php artisan queue:work 2>nul"
timeout /t 2 /nobreak >nul

echo.

:: Step 4: Display service URLs
echo %COLOR_YELLOW%[4/4] Service URLs:%COLOR_RESET%
echo.
echo %COLOR_GREEN%  Laravel HTTP:%COLOR_RESET%     %SERVICE_LARAVEL%
echo %COLOR_GREEN%  Horizon Panel:%COLOR_RESET%    %SERVICE_HORIZON%
echo %COLOR_GREEN%  Reverb WebSocket:%COLOR_RESET% %SERVICE_REVERB%
echo %COLOR_GREEN%  WAHA API:%COLOR_RESET%       %SERVICE_WAHA%
echo.

:: Write initial status to log
echo [%date% %time%] Services started >> "%LOG_FILE%"

:: Monitoring loop
echo %COLOR_MAGENTA%====================================%COLOR_RESET%
echo %COLOR_MAGENTA%  Monitoring Status (10s refresh)%COLOR_RESET%
echo %COLOR_MAGENTA%====================================%COLOR_RESET%
echo.

:MONITOR_LOOP
set "TIMESTAMP=%date% %time:~0,8%"

:: Check Laravel server status
set "LARAVEL_STATUS=%COLOR_RED%STOPPED%COLOR_RESET%"
netstat -ano | findstr ":8000 " >nul
if %errorlevel% equ 0 set "LARAVEL_STATUS=%COLOR_GREEN%RUNNING%COLOR_RESET%"

:: Check Reverb status  
set "REVERB_STATUS=%COLOR_RED%STOPPED%COLOR_RESET%"
netstat -ano | findstr ":8080 " >nul
if %errorlevel% equ 0 set "REVERB_STATUS=%COLOR_GREEN%RUNNING%COLOR_RESET%"

:: Check Redis status
set "REDIS_STATUS=%COLOR_RED%STOPPED%COLOR_RESET%"
netstat -ano | findstr ":6379 " >nul
if %errorlevel% equ 0 set "REDIS_STATUS=%COLOR_GREEN%RUNNING%COLOR_RESET%"

:: Check MySQL status
set "MYSQL_STATUS=%COLOR_RED%STOPPED%COLOR_RESET%"
netstat -ano | findstr ":3306 " >nul
if %errorlevel% equ 0 set "MYSQL_STATUS=%COLOR_GREEN%RUNNING%COLOR_RESET%"

:: Display status line
echo %COLOR_WHITE%[%TIMESTAMP%]%COLOR_RESET% Laravel: %LARAVEL_STATUS% ^| Reverb: %REVERB_STATUS% ^| Redis: %REDIS_STATUS% ^| MySQL: %MYSQL_STATUS%

:: Check for failed services and attempt restart
if "%LARAVEL_STATUS%"=="%COLOR_RED%STOPPED%COLOR_RESET%" (
    echo %COLOR_YELLOW%  [RESTART] Laravel server not running, restarting...%COLOR_RESET%
    start "Laravel Server" /B php artisan serve --host=127.0.0.1 --port=8000 --no-reload
)

if "%REVERB_STATUS%"=="%COLOR_RED%STOPPED%COLOR_RESET%" (
    echo %COLOR_YELLOW%  [RESTART] Reverb not running, restarting...%COLOR_RESET%
    start "Reverb Server" /B php artisan reverb:start --host=127.0.0.1 --port=8080
)

:: Write to log file
echo [%TIMESTAMP%] Laravel: %LARAVEL_STATUS% ^| Reverb: %REVERB_STATUS% ^| Redis: %REDIS_STATUS% ^| MySQL: %MYSQL_STATUS% >> "%LOG_FILE%"

:: Wait 10 seconds before next refresh
timeout /t 10 /nobreak >nul

:: Check if user pressed Ctrl+C
if %errorlevel% equ 1 goto :GRACEFUL_SHUTDOWN

goto :MONITOR_LOOP

:GRACEFUL_SHUTDOWN
echo.
echo %COLOR_YELLOW%====================================%COLOR_RESET%
echo %COLOR_YELLOW%  Initiating graceful shutdown...%COLOR_RESET%
echo %COLOR_YELLOW%====================================%COLOR_RESET%

:: Stop Queue worker
echo %COLOR_CYAN%  Stopping Queue worker...%COLOR_RESET%
php artisan queue:restart >nul 2>&1

:: Stop Reverb
echo %COLOR_CYAN%  Stopping Reverb...%COLOR_RESET%
for /F "skip=4 tokens=5" %%A in ('netstat -ano ^| findstr ":8080 "') do taskkill /F /PID %%A >nul 2>&1

:: Stop Laravel server
echo %COLOR_CYAN%  Stopping Laravel server...%COLOR_RESET%
for /F "skip=4 tokens=5" %%A in ('netstat -ano ^| findstr ":8000 "') do taskkill /F /PID %%A >nul 2>&1

echo %COLOR_GREEN%  All services stopped.%COLOR_RESET%
echo [%date% %time%] Services stopped >> "%LOG_FILE%"

exit /b 0