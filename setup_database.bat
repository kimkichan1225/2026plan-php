@echo off
chcp 65001 > nul
cls
echo ========================================
echo Database Setup Script
echo ========================================
echo.

set MYSQL_PATH=C:\xampp\mysql\bin
set DB_NAME=new_year_goals
set SQL_FILE=%~dp0database\schema.sql

echo [1/3] Checking MySQL path...
if not exist "%MYSQL_PATH%\mysql.exe" (
    echo.
    echo Error: MySQL not found at %MYSQL_PATH%
    echo.
    echo Please check XAMPP installation: C:\xampp
    echo.
    pause
    exit /b 1
)

echo OK: MySQL path verified
echo.

echo [2/3] Creating database...
echo Enter MySQL password (or just press Enter if none):
"%MYSQL_PATH%\mysql.exe" -u root -p -e "CREATE DATABASE IF NOT EXISTS %DB_NAME% DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Error: Database creation failed
    pause
    exit /b 1
)

echo OK: Database created
echo.

echo [3/3] Creating tables...
"%MYSQL_PATH%\mysql.exe" -u root -p %DB_NAME% < "%SQL_FILE%"

if %ERRORLEVEL% NEQ 0 (
    echo.
    echo Error: Table creation failed
    pause
    exit /b 1
)

echo OK: Tables created
echo.
echo ========================================
echo SUCCESS! Database setup complete
echo ========================================
echo.
echo Now run start.bat to start the server.
echo.
pause
