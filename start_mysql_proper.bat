@echo off
title MySQL Server - Keep This Window Open
echo ===============================
echo Starting MySQL Server
echo ===============================
echo.

REM Stop any existing MySQL processes
echo Stopping any existing MySQL processes...
taskkill /f /im mysqld.exe 2>nul
timeout /t 3 >nul

REM Navigate to MySQL bin directory
cd /d C:\xampp\mysql\bin

echo.
echo Starting MySQL server with full functionality...
echo.
echo IMPORTANT: Keep this window open while using MySQL!
echo Your chat system will be available at: http://localhost/WEP/chat_simple.php
echo phpMyAdmin will be available at: http://localhost/phpmyadmin
echo.
echo To stop MySQL: Close this window or press Ctrl+C
echo.
echo ===============================

REM Start MySQL with proper configuration
mysqld --defaults-file="C:\xampp\mysql\bin\my.ini" --console

echo.
echo MySQL server has stopped.
echo.
pause 