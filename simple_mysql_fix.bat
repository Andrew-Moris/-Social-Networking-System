@echo off
title MySQL Manual Start
echo ===============================
echo Starting MySQL Manually
echo ===============================
echo.

REM Stop any existing MySQL processes
taskkill /f /im mysqld.exe 2>nul
timeout /t 2 >nul

REM Navigate to MySQL bin directory
cd /d C:\xampp\mysql\bin

echo Starting MySQL server...
echo Keep this window open while using MySQL!
echo.
echo To stop MySQL: Close this window or press Ctrl+C
echo.

REM Start MySQL in console mode (keeps running)
mysqld --console --skip-grant-tables --skip-networking

echo.
echo MySQL has stopped.
pause 