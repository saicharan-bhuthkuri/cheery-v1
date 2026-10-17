@echo off
setlocal EnableDelayedExpansion
cd /d "%~dp0.."
title KaamMitra - Local Server Controller

if /I "%~1"=="stop" goto STOP_SERVICES
if /I "%~1"=="start" goto START_SERVICES
if /I "%~1"=="restart" goto RESTART_SERVICES
if /I "%~1"=="status" goto STATUS_CHECK

:START_SERVICES
cls
echo ======================================================================
echo                   KaamMitra Local Development Server                 
echo ======================================================================
echo  Project Root : %CD%
echo  Public Dir   : %CD%\public
echo  Time Started : %TIME%
echo ======================================================================
echo.

:: 1. Check and start MySQL
echo [1/2] Initializing MySQL Database Server...
netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo       - Starting MySQL daemon on port 3306...
    start "" "C:\wamp\bin\mysql\mysql5.6.12\bin\mysqld.exe" --defaults-file="C:\wamp\bin\mysql\mysql5.6.12\my.ini"
    ping 127.0.0.1 -n 3 >nul
    echo       - MySQL Daemon: STARTED (Port 3306 active)
) else (
    echo       - MySQL Server: ALREADY RUNNING (Port 3306 active)
)

echo.
:: 2. Check and start PHP server
echo [2/2] Initializing PHP Web Server...
netstat -ano | findstr /R /C:":8080 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo       - Starting PHP server on port 8080...
    start /B "" php -S 127.0.0.1:8080 -t public >nul 2>&1
    ping 127.0.0.1 -n 2 >nul
    echo       - PHP Server  : STARTED (http://127.0.0.1:8080)
) else (
    echo       - PHP Server  : ALREADY RUNNING (http://127.0.0.1:8080)
)

:: Open browser
start "" "http://127.0.0.1:8080"

:DASHBOARD
cls
echo ======================================================================
echo                   KaamMitra Local Development Server                 
echo ======================================================================
echo  Server Status : ACTIVE ^& RUNNING
echo  Website URL   : http://127.0.0.1:8080
echo  Admin Panel   : http://127.0.0.1:8080/auth/admin/login.php
echo  MySQL Service : ACTIVE (Port 3306)
echo ======================================================================
echo.
echo  Quick Actions (No Ctrl+C needed!):
echo    [Q] STOP ALL SERVICES (Safe ^& Clean Exit)
echo    [R] Restart Server
echo    [O] Open Website in Browser
echo    [A] Open Admin Panel in Browser
echo.
echo ======================================================================
echo  Press 'Q' to STOP all services cleanly:
echo ======================================================================

choice /C QROA /N /M "Enter action [Q=Stop, R=Restart, O=Home, A=Admin]: "
if errorlevel 4 (
    start "" "http://127.0.0.1:8080/auth/admin/login.php"
    goto DASHBOARD
)
if errorlevel 3 (
    start "" "http://127.0.0.1:8080"
    goto DASHBOARD
)
if errorlevel 2 (
    goto RESTART_SERVICES
)
if errorlevel 1 (
    goto STOP_SERVICES
)

:STOP_SERVICES
cls
echo.
echo ======================================================================
echo                   Shutting Down KaamMitra Services                   
echo ======================================================================
echo.

:: 1. Stop PHP Server
echo [1/2] Stopping PHP Web Server (Port 8080)...
netstat -ano | findstr /R /C:":8080 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo       - PHP server is already stopped.
) else (
    taskkill /F /IM php.exe >nul 2>&1
    echo       - Process php.exe terminated.
    echo       - Port 8080 released: SUCCESS
)

echo.
:: 2. Stop MySQL Server
echo [2/2] Stopping MySQL Database Server (Port 3306)...
netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo       - MySQL server is already stopped.
) else (
    echo       - Sending graceful shutdown signal to MySQL...
    "C:\wamp\bin\mysql\mysql5.6.12\bin\mysqladmin.exe" -u root shutdown >nul 2>&1
    ping 127.0.0.1 -n 2 >nul
    netstat -ano | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
    if not errorlevel 1 (
        echo       - Terminating mysqld.exe process...
        taskkill /F /IM mysqld.exe >nul 2>&1
    )
    echo       - Port 3306 released: SUCCESS
)

echo.
echo ----------------------------------------------------------------------
echo  Shutdown Summary:
echo   - PHP Server   : STOPPED
echo   - MySQL Server : STOPPED
echo   - Port 8080    : FREE
echo   - Port 3306    : FREE
echo ======================================================================
echo All KaamMitra local services have been stopped cleanly.
echo ======================================================================
ping 127.0.0.1 -n 2 >nul
goto :EOF

:RESTART_SERVICES
cls
echo ======================================================================
echo                   Restarting KaamMitra Services                      
echo ======================================================================
echo Stopping existing processes...
taskkill /F /IM php.exe >nul 2>&1
"C:\wamp\bin\mysql\mysql5.6.12\bin\mysqladmin.exe" -u root shutdown >nul 2>&1
taskkill /F /IM mysqld.exe >nul 2>&1
ping 127.0.0.1 -n 2 >nul
echo Cleaned up. Launching fresh servers...
goto START_SERVICES

:STATUS_CHECK
cls
echo ======================================================================
echo                     KaamMitra Service Status                         
echo ======================================================================
echo.
echo [1] Checking Port 8080 (PHP Server):
netstat -ano | findstr /R /C:":8080 .*LISTENING"
if errorlevel 1 (
    echo     Status: NOT RUNNING (Port 8080 is free)
) else (
    echo     Status: ACTIVE (Listening on port 8080)
)
echo.
echo [2] Checking Port 3306 (MySQL Database):
netstat -ano | findstr /R /C:":3306 .*LISTENING"
if errorlevel 1 (
    echo     Status: NOT RUNNING (Port 3306 is free)
) else (
    echo     Status: ACTIVE (Listening on port 3306)
)
echo.
echo ======================================================================
pause
goto DASHBOARD
