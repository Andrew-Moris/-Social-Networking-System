@echo off
echo Fixing MySQL configuration issues...
echo.

REM Stop any running MySQL processes
taskkill /f /im mysqld.exe 2>nul

REM Navigate to XAMPP MySQL directory
cd /d C:\xampp\mysql\bin

REM Create a backup of current config
if exist my.ini.backup del my.ini.backup
copy my.ini my.ini.backup

REM Create new optimized configuration
echo Creating optimized MySQL configuration...
(
echo # MySQL Configuration for XAMPP
echo [client]
echo port=3306
echo socket="C:/xampp/mysql/mysql.sock"
echo.
echo [mysqld]
echo port=3306
echo socket="C:/xampp/mysql/mysql.sock"
echo basedir="C:/xampp/mysql"
echo tmpdir="C:/xampp/tmp"
echo datadir="C:/xampp/mysql/data"
echo pid_file="mysql.pid"
echo # Enable Logging
echo log_error="C:/xampp/mysql/data/mysql_error.log"
echo # Memory Settings
echo key_buffer_size=16M
echo max_allowed_packet=1M
echo table_open_cache=64
echo sort_buffer_size=512K
echo net_buffer_length=8K
echo read_buffer_size=256K
echo read_rnd_buffer_size=512K
echo myisam_sort_buffer_size=8M
echo # InnoDB Settings
echo innodb_data_home_dir="C:/xampp/mysql/data"
echo innodb_data_file_path=ibdata1:10M:autoextend
echo innodb_log_group_home_dir="C:/xampp/mysql/data"
echo innodb_buffer_pool_size=16M
echo innodb_log_file_size=5M
echo innodb_log_buffer_size=8M
echo innodb_flush_log_at_trx_commit=1
echo innodb_lock_wait_timeout=50
echo # Character Set
echo character-set-server=utf8mb4
echo collation-server=utf8mb4_unicode_ci
echo.
echo [mysqldump]
echo quick
echo max_allowed_packet=16M
echo.
echo [mysql]
echo no-auto-rehash
echo default-character-set=utf8mb4
echo.
echo [myisamchk]
echo key_buffer_size=20M
echo sort_buffer_size=20M
echo read_buffer=2M
echo write_buffer=2M
echo.
echo [mysqlhotcopy]
echo interactive-timeout
) > my.ini

echo Configuration updated!
echo.
echo Now trying to initialize MySQL data directory...
cd /d C:\xampp\mysql\bin
mysqld --initialize-insecure --user=mysql --console

echo.
echo MySQL configuration fix completed!
echo Please try starting MySQL from XAMPP Control Panel now.
echo.
pause 