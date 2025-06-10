@echo off
echo Installing MySQL as Windows Service
echo ===================================
echo.

REM Check for admin rights
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Please run this script as Administrator!
    pause
    exit /b 1
)

echo Removing any existing MySQL service...
sc delete MySQL 2>nul
sc delete mysql 2>nul
sc delete MariaDB 2>nul
timeout /t 2 >nul

echo Installing MySQL as a service...
cd /d C:\xampp\mysql\bin
mysqld --install MySQL --defaults-file="C:\xampp\mysql\bin\my.ini"

echo Starting MySQL service...
net start MySQL

echo.
echo ===================================
echo MySQL Service Installation Complete
echo ===================================
echo.
echo MySQL is now running as a Windows service.
echo You can now:
echo - Access phpMyAdmin at http://localhost/phpmyadmin
echo - Use your chat system at http://localhost/WEP/chat_simple.php
echo.
echo To stop MySQL: net stop MySQL
echo To start MySQL: net start MySQL
echo.
pause 