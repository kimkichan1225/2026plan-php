@echo off
chcp 65001 > nul
cls

echo ========================================
echo Server Starting...
echo ========================================
echo.
echo Open browser: http://localhost:8000
echo.
echo Press Ctrl+C to stop
echo ========================================
echo.

cd /d "%~dp0"

set PHP_PATH=C:\xampp\php\php.exe

if not exist "%PHP_PATH%" (
    echo Error: PHP not found at %PHP_PATH%
    echo.
    echo Please check your XAMPP installation path
    pause
    exit /b 1
)

"%PHP_PATH%" -S localhost:8000

pause
