@echo off
echo Fixing MySQL startup issues...
echo.

REM Stop any running MySQL processes
taskkill /f /im mysqld.exe 2>nul

REM Navigate to XAMPP directory
cd /d C:\xampp

REM Create backup of current data (if it exists)
if exist mysql\data_backup (
    echo Removing old backup...
    rmdir /s /q mysql\data_backup
)

echo Creating backup of current data...
if exist mysql\data (
    xcopy mysql\data mysql\data_backup /e /i /h /y
)

REM Copy fresh data from backup
echo Restoring fresh MySQL data...
if exist mysql\backup (
    rmdir /s /q mysql\data
    xcopy mysql\backup mysql\data /e /i /h /y
) else (
    echo No backup folder found. Creating minimal data structure...
    if not exist mysql\data mkdir mysql\data
    if not exist mysql\data\mysql mkdir mysql\data\mysql
    if not exist mysql\data\performance_schema mkdir mysql\data\performance_schema
    if not exist mysql\data\phpmyadmin mkdir mysql\data\phpmyadmin
    if not exist mysql\data\test mkdir mysql\data\test
)

REM Set proper permissions
echo Setting permissions...
icacls mysql\data /grant Everyone:F /t

echo.
echo MySQL fix completed!
echo Please try starting MySQL from XAMPP Control Panel now.
echo.
pause 