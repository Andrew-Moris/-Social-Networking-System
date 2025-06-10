@echo off
echo Complete MySQL Fix for XAMPP
echo ============================
echo.

REM Run as Administrator check
net session >nul 2>&1
if %errorLevel% neq 0 (
    echo ERROR: Please run this script as Administrator!
    echo Right-click and select "Run as administrator"
    pause
    exit /b 1
)

echo Step 1: Stopping all MySQL processes...
taskkill /f /im mysqld.exe 2>nul
taskkill /f /im mysql.exe 2>nul
timeout /t 2 >nul

echo Step 2: Cleaning up MySQL PID and socket files...
cd /d C:\xampp\mysql\data
if exist mysql.pid del mysql.pid
if exist *.err del *.err
cd /d C:\xampp\mysql
if exist mysql.sock del mysql.sock

echo Step 3: Setting proper permissions...
cd /d C:\xampp
icacls mysql /grant Everyone:F /t >nul 2>&1
icacls tmp /grant Everyone:F /t >nul 2>&1

echo Step 4: Creating required directories...
if not exist C:\xampp\tmp mkdir C:\xampp\tmp

echo Step 5: Clearing Windows temp files...
cd /d %TEMP%
del /q /f mysql* 2>nul
del /q /f ib* 2>nul

echo Step 6: Resetting MySQL error log...
cd /d C:\xampp\mysql\data
echo MySQL Error Log Reset > mysql_error.log

echo Step 7: Testing MySQL startup...
cd /d C:\xampp\mysql\bin
echo Starting MySQL in test mode (will run for 5 seconds)...
start /b mysqld --console
timeout /t 5 >nul
taskkill /f /im mysqld.exe 2>nul

echo.
echo ========================================
echo MySQL fix completed successfully!
echo ========================================
echo.
echo IMPORTANT: Now do the following:
echo 1. Close XAMPP Control Panel completely
echo 2. Right-click XAMPP Control Panel
echo 3. Select "Run as administrator"
echo 4. Start MySQL from the control panel
echo.
echo If MySQL still doesn't start:
echo - Check Windows Firewall settings
echo - Temporarily disable antivirus
echo - Make sure port 3306 is free
echo.
pause 