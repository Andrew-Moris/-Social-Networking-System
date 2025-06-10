<?php

require_once 'config.php';

echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إصلاح مشكلة تحديث الصورة الشخصية</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url("https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap");
        body {
            font-family: "Cairo", sans-serif;
            background-color: #0A0F1E;
            color: #E5E7EB;
            padding: 2rem;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: rgba(23, 31, 48, 0.7);
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3);
        }
        .success {
            background-color: rgba(16, 185, 129, 0.2);
            color: #10B981;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .error {
            background-color: rgba(239, 68, 68, 0.2);
            color: #EF4444;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        .info {
            background-color: rgba(59, 130, 246, 0.2);
            color: #3B82F6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        pre {
            background-color: rgba(0, 0, 0, 0.3);
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-2xl font-bold mb-6 text-center">إصلاح مشكلة تحديث الصورة الشخصية</h1>';

try {
    echo '<div class="info">محاولة الاتصال بقاعدة البيانات...</div>';
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
        echo '<div class="success">تم الاتصال بقاعدة بيانات MySQL بنجاح!</div>';
        $db_type = 'mysql';
    } catch (PDOException $e) {
        echo '<div class="error">فشل الاتصال بقاعدة بيانات MySQL: ' . $e->getMessage() . '</div>';
        
        try {
            $pg_dsn = "pgsql:host=localhost;port=5432;dbname=socialmedia";
            $pg_user = "postgres";
            $pg_pass = "20043110";
            $pdo = new PDO($pg_dsn, $pg_user, $pg_pass, $pdo_options);
            echo '<div class="success">تم الاتصال بقاعدة بيانات PostgreSQL بنجاح!</div>';
            $db_type = 'postgresql';
        } catch (PDOException $e2) {
            echo '<div class="error">فشل الاتصال بقاعدة بيانات PostgreSQL أيضًا: ' . $e2->getMessage() . '</div>';
            throw new Exception("تعذر الاتصال بأي من قواعد البيانات");
        }
    }
    
    echo '<div class="info">التحقق من وجود جدول المستخدمين...</div>';
    
    if ($db_type == 'mysql') {
        $table_query = "SHOW TABLES LIKE 'users'";
    } else {
        $table_query = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'users'";
    }
    
    $table_result = $pdo->query($table_query);
    
    if ($table_result->rowCount() == 0) {
        echo '<div class="error">جدول المستخدمين غير موجود!</div>';
        
        echo '<div class="info">محاولة إنشاء جدول المستخدمين...</div>';
        
        if ($db_type == 'mysql') {
            $create_table_sql = "CREATE TABLE users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                bio TEXT,
                avatar_url VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL
            )";
        } else {
            $create_table_sql = "CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                first_name VARCHAR(50),
                last_name VARCHAR(50),
                bio TEXT,
                avatar_url VARCHAR(255) DEFAULT 'assets/img/default-avatar.png',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_login TIMESTAMP NULL
            )";
        }
        
        $pdo->exec($create_table_sql);
        echo '<div class="success">تم إنشاء جدول المستخدمين بنجاح!</div>';
    } else {
        echo '<div class="success">جدول المستخدمين موجود بالفعل.</div>';
        
        echo '<div class="info">التحقق من وجود عمود avatar_url...</div>';
        
        if ($db_type == 'mysql') {
            $column_query = "SHOW COLUMNS FROM users LIKE 'avatar_url'";
        } else {
            $column_query = "SELECT column_name FROM information_schema.columns 
                             WHERE table_schema = 'public' AND table_name = 'users' 
                             AND column_name = 'avatar_url'";
        }
        
        $column_result = $pdo->query($column_query);
        
        if ($column_result->rowCount() == 0) {
            echo '<div class="error">عمود avatar_url غير موجود!</div>';
            
            echo '<div class="info">محاولة إضافة عمود avatar_url...</div>';
            
            if ($db_type == 'mysql') {
                $add_column_sql = "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT 'assets/img/default-avatar.png'";
            } else {
                $add_column_sql = "ALTER TABLE users ADD COLUMN avatar_url VARCHAR(255) DEFAULT 'assets/img/default-avatar.png'";
            }
            
            $pdo->exec($add_column_sql);
            echo '<div class="success">تم إضافة عمود avatar_url بنجاح!</div>';
        } else {
            echo '<div class="success">عمود avatar_url موجود بالفعل.</div>';
        }
    }
    
    echo '<div class="info">التحقق من وجود مجلدات الرفع...</div>';
    
    if (!is_dir(UPLOAD_DIR)) {
        echo '<div class="error">مجلد التحميلات الرئيسي غير موجود!</div>';
        
        if (mkdir(UPLOAD_DIR, 0777, true)) {
            echo '<div class="success">تم إنشاء مجلد التحميلات الرئيسي بنجاح!</div>';
        } else {
            echo '<div class="error">فشل في إنشاء مجلد التحميلات الرئيسي.</div>';
        }
    } else {
        echo '<div class="success">مجلد التحميلات الرئيسي موجود بالفعل.</div>';
    }
    
    if (!is_dir(AVATAR_DIR)) {
        echo '<div class="error">مجلد الصور الشخصية غير موجود!</div>';
        
        if (mkdir(AVATAR_DIR, 0777, true)) {
            echo '<div class="success">تم إنشاء مجلد الصور الشخصية بنجاح!</div>';
        } else {
            echo '<div class="error">فشل في إنشاء مجلد الصور الشخصية.</div>';
        }
    } else {
        echo '<div class="success">مجلد الصور الشخصية موجود بالفعل.</div>';
    }
    
    if (is_dir(UPLOAD_DIR)) {
        chmod(UPLOAD_DIR, 0777);
        echo '<div class="info">تم تحديث صلاحيات مجلد التحميلات الرئيسي.</div>';
    }
    
    if (is_dir(AVATAR_DIR)) {
        chmod(AVATAR_DIR, 0777);
        echo '<div class="info">تم تحديث صلاحيات مجلد الصور الشخصية.</div>';
    }
    
    if ($db_type == 'postgresql' && strpos($dsn, 'mysql') !== false) {
        echo '<div class="error">تناقض في إعدادات قاعدة البيانات! يبدو أن التطبيق مكوّن لاستخدام MySQL لكن قاعدة البيانات المتصلة هي PostgreSQL.</div>';
        
        echo '<div class="info">يُنصح بتحديث ملف config.php لاستخدام إعدادات PostgreSQL الصحيحة:</div>';
        echo '<pre>
// إعدادات قاعدة البيانات PostgreSQL
$db_host = \'localhost\';
$db_name = \'socialmedia\';
$db_user = \'postgres\';
$db_pass = \'20043110\';

// إعدادات PDO لـ PostgreSQL
$dsn = "pgsql:host=$db_host;port=5432;dbname=$db_name";
</pre>';
    }
    
    echo '<div class="success text-xl text-center mt-6">تم الانتهاء من فحص وإصلاح المشكلات المحتملة!</div>';
    echo '<div class="info text-center">يمكنك الآن العودة إلى الصفحة الرئيسية ومحاولة تحديث الصورة الشخصية مرة أخرى.</div>';
    
    echo '<div class="text-center mt-6">
        <a href="home.php" class="inline-block px-5 py-2 bg-gradient-to-r from-blue-500 to-purple-500 text-white rounded-xl transition-all hover:shadow-lg">
            العودة إلى الصفحة الرئيسية
        </a>
    </div>';
    
} catch (Exception $e) {
    echo '<div class="error">حدث خطأ أثناء عملية الإصلاح: ' . $e->getMessage() . '</div>';
}

echo '
    </div>
</body>
</html>';
?>
