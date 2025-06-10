@echo off
chcp 65001 >nul
title تشغيل خادم MySQL
echo ===============================
echo تشغيل خادم MySQL
echo ===============================
echo.

echo إيقاف أي عمليات MySQL موجودة...
taskkill /f /im mysqld.exe 2>nul
timeout /t 3 >nul

echo الانتقال إلى مجلد MySQL...
cd /d C:\xampp\mysql\bin

echo.
echo بدء تشغيل خادم MySQL...
echo.
echo مهم: اتركي هذه النافذة مفتوحة أثناء استخدام MySQL!
echo نظام الدردشة متاح على: http://localhost/WEP/chat_simple.php
echo phpMyAdmin متاح على: http://localhost/phpmyadmin
echo.
echo لإيقاف MySQL: أغلقي هذه النافذة أو اضغطي Ctrl+C
echo.
echo ===============================

mysqld --defaults-file="C:\xampp\mysql\bin\my.ini" --console

echo.
echo تم إيقاف خادم MySQL.
echo.
pause 