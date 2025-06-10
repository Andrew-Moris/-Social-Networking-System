@echo off
echo Testing MySQL startup...
echo.

cd /d C:\xampp\mysql\bin

echo Starting MySQL manually to check for errors...
echo Press Ctrl+C to stop when you see it's working or if there are errors.
echo.

mysqld --console --skip-grant-tables --skip-networking

echo.
echo MySQL test completed.
pause 