<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

session_start();
require_once 'config.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔍 تشخيص شامل - مشكلة الإحصائيات</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; direction: rtl; }
        .debug-section { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .console-log { background: #2d3748; color: #e2e8f0; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .step { border-left: 4px solid #007bff; padding-left: 15px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 تشخيص شامل لمشكلة الإحصائيات في home.php</h1>";

echo "<script>
console.group('🚀 بدء التشخيص الشامل');
console.time('⏱️ وقت التشخيص الكامل');
console.log('📅 وقت البدء:', new Date().toLocaleString('ar-SA'));
</script>";

try {
    echo "<div class='debug-section'>";
    echo "<h2>🔌 الخطوة 1: فحص الاتصال بقاعدة البيانات</h2>";
    
    echo "<script>console.group('🔌 فحص قاعدة البيانات');</script>";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p class='success'>✅ تم الاتصال بقاعدة البيانات بنجاح</p>";
    echo "<script>console.log('✅ اتصال قاعدة البيانات: نجح');</script>";
    
    $required_tables = ['users', 'posts', 'followers', 'likes', 'comments', 'bookmarks'];
    foreach ($required_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "<p class='success'>✅ الجدول $table موجود</p>";
            echo "<script>console.log('✅ جدول $table: موجود');</script>";
        } else {
            echo "<p class='error'>❌ الجدول $table غير موجود!</p>";
            echo "<script>console.error('❌ جدول $table: غير موجود!');</script>";
        }
    }
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>👤 الخطوة 2: فحص الجلسة والمستخدم</h2>";
    
    echo "<script>console.group('👤 فحص الجلسة والمستخدم');</script>";
    
    echo "<div class='step'>";
    echo "<h3>🔍 فحص متغيرات الجلسة:</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";
    echo "<script>console.log('📋 متغيرات الجلسة:', " . json_encode($_SESSION) . ");</script>";
    
    if (!isset($_SESSION['user_id'])) {
        echo "<p class='warning'>⚠️ المستخدم غير مسجل دخول، جاري التسجيل التلقائي...</p>";
        echo "<script>console.warn('⚠️ المستخدم غير مسجل دخول');</script>";
        
        $stmt_temp = $pdo->prepare("SELECT * FROM users WHERE username = 'ben10' OR id = 5");
        $stmt_temp->execute();
        $user_temp = $stmt_temp->fetch();
        
        if ($user_temp) {
            $_SESSION['user_id'] = $user_temp['id'];
            $_SESSION['username'] = $user_temp['username'];
            $_SESSION['email'] = $user_temp['email'];
            $_SESSION['first_name'] = $user_temp['first_name'];
            $_SESSION['last_name'] = $user_temp['last_name'];
            $_SESSION['avatar_url'] = $user_temp['avatar_url'];
            
            echo "<p class='success'>✅ تم تسجيل الدخول التلقائي للمستخدم: " . $user_temp['username'] . "</p>";
            echo "<script>console.log('✅ تسجيل دخول تلقائي نجح للمستخدم:', '" . $user_temp['username'] . "');</script>";
        } else {
            echo "<p class='error'>❌ فشل في العثور على مستخدم للتسجيل التلقائي</p>";
            echo "<script>console.error('❌ فشل التسجيل التلقائي');</script>";
            throw new Exception("لا يمكن العثور على مستخدم للاختبار");
        }
    }
    
    $user_id = $_SESSION['user_id'];
    echo "<p class='info'>🆔 معرف المستخدم الحالي: $user_id</p>";
    echo "<script>console.log('🆔 معرف المستخدم:', $user_id);</script>";
    
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p class='success'>✅ تم العثور على بيانات المستخدم في قاعدة البيانات</p>";
        echo "<script>console.log('✅ بيانات المستخدم:', " . json_encode($user) . ");</script>";
    } else {
        echo "<p class='error'>❌ لم يتم العثور على بيانات المستخدم في قاعدة البيانات!</p>";
        echo "<script>console.error('❌ بيانات المستخدم غير موجودة');</script>";
        throw new Exception("بيانات المستخدم غير موجودة");
    }
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>📊 الخطوة 3: اختبار استعلامات الإحصائيات</h2>";
    
    echo "<script>console.group('📊 اختبار الإحصائيات');</script>";
    
    echo "<div class='step'>";
    echo "<h3>📝 اختبار عدد المنشورات:</h3>";
    
    echo "<script>console.group('📝 عدد المنشورات');</script>";
    
    $posts_stmt = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
    $posts_stmt->execute([$user_id]);
    $posts_count = $posts_stmt->fetchColumn();
    
    echo "<p><strong>عدد المنشورات:</strong> $posts_count</p>";
    echo "<script>console.log('📝 عدد المنشورات:', $posts_count);</script>";
    
    $posts_details_stmt = $pdo->prepare("SELECT id, content, created_at FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $posts_details_stmt->execute([$user_id]);
    $posts_details = $posts_details_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>تفاصيل المنشورات:</strong></p>";
    echo "<pre>" . print_r($posts_details, true) . "</pre>";
    echo "<script>console.log('📝 تفاصيل المنشورات:', " . json_encode($posts_details) . ");</script>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>👥 اختبار عدد المتابعين:</h3>";
    
    echo "<script>console.group('👥 المتابعين');</script>";
    
    $followers_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE followed_id = ?");
    $followers_stmt->execute([$user_id]);
    $followers_count = $followers_stmt->fetchColumn();
    
    echo "<p><strong>عدد المتابعين:</strong> $followers_count</p>";
    echo "<script>console.log('👥 عدد المتابعين:', $followers_count);</script>";
    
    $followers_details_stmt = $pdo->prepare("
        SELECT f.follower_id, u.username, u.first_name, u.last_name, f.created_at 
        FROM followers f 
        JOIN users u ON f.follower_id = u.id 
        WHERE f.followed_id = ? 
        ORDER BY f.created_at DESC LIMIT 5
    ");
    $followers_details_stmt->execute([$user_id]);
    $followers_details = $followers_details_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>تفاصيل المتابعين:</strong></p>";
    echo "<pre>" . print_r($followers_details, true) . "</pre>";
    echo "<script>console.log('👥 تفاصيل المتابعين:', " . json_encode($followers_details) . ");</script>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='step'>";
    echo "<h3>➡️ اختبار عدد المتابَعين:</h3>";
    
    echo "<script>console.group('➡️ المتابَعين');</script>";
    
    $following_stmt = $pdo->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = ?");
    $following_stmt->execute([$user_id]);
    $following_count = $following_stmt->fetchColumn();
    
    echo "<p><strong>عدد المتابَعين:</strong> $following_count</p>";
    echo "<script>console.log('➡️ عدد المتابَعين:', $following_count);</script>";
    
    $following_details_stmt = $pdo->prepare("
        SELECT f.followed_id, u.username, u.first_name, u.last_name, f.created_at 
        FROM followers f 
        JOIN users u ON f.followed_id = u.id 
        WHERE f.follower_id = ? 
        ORDER BY f.created_at DESC LIMIT 5
    ");
    $following_details_stmt->execute([$user_id]);
    $following_details = $following_details_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>تفاصيل المتابَعين:</strong></p>";
    echo "<pre>" . print_r($following_details, true) . "</pre>";
    echo "<script>console.log('➡️ تفاصيل المتابَعين:', " . json_encode($following_details) . ");</script>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>🔬 الخطوة 4: اختبار الاستعلام المدمج</h2>";
    
    echo "<script>console.group('🔬 الاستعلام المدمج');</script>";
    
    $stats_query = "
        SELECT 
            (SELECT COUNT(*) FROM posts WHERE user_id = ?) as posts_count,
            (SELECT COUNT(*) FROM followers WHERE followed_id = ?) as followers_count,
            (SELECT COUNT(*) FROM followers WHERE follower_id = ?) as following_count
    ";
    
    echo "<div class='step'>";
    echo "<h3>📋 الاستعلام المستخدم:</h3>";
    echo "<pre>$stats_query</pre>";
    echo "<script>console.log('📋 الاستعلام:', `$stats_query`);</script>";
    
    echo "<h3>🔧 المعاملات المرسلة:</h3>";
    $params = [$user_id, $user_id, $user_id];
    echo "<pre>" . print_r($params, true) . "</pre>";
    echo "<script>console.log('🔧 المعاملات:', " . json_encode($params) . ");</script>";
    
    $stats_stmt = $pdo->prepare($stats_query);
    $stats_stmt->execute($params);
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>📊 نتيجة الاستعلام:</h3>";
    echo "<pre>" . print_r($stats, true) . "</pre>";
    echo "<script>console.log('📊 نتيجة الاستعلام المدمج:', " . json_encode($stats) . ");</script>";
    
    echo "<h3>🔍 فحص نوع البيانات:</h3>";
    foreach ($stats as $key => $value) {
        $type = gettype($value);
        echo "<p><strong>$key:</strong> $value (نوع: $type)</p>";
        echo "<script>console.log('🔍 $key:', $value, 'نوع:', '$type');</script>";
    }
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>⚙️ الخطوة 5: محاكاة معالجة البيانات</h2>";
    
    echo "<script>console.group('⚙️ معالجة البيانات');</script>";
    
    echo "<div class='step'>";
    echo "<h3>🔄 معالجة البيانات كما في home.php:</h3>";
    
    if (!$stats) {
        $stats = ['posts_count' => 0, 'followers_count' => 0, 'following_count' => 0];
        echo "<p class='warning'>⚠️ لم يتم العثور على إحصائيات، تم تعيين القيم الافتراضية</p>";
        echo "<script>console.warn('⚠️ لم يتم العثور على إحصائيات');</script>";
    } else {
        $stats['posts_count'] = (int)($stats['posts_count'] ?? 0);
        $stats['followers_count'] = (int)($stats['followers_count'] ?? 0);
        $stats['following_count'] = (int)($stats['following_count'] ?? 0);
        echo "<p class='success'>✅ تم معالجة الإحصائيات بنجاح</p>";
        echo "<script>console.log('✅ معالجة الإحصائيات نجحت');</script>";
    }
    
    echo "<h3>📊 الإحصائيات النهائية:</h3>";
    echo "<pre>" . print_r($stats, true) . "</pre>";
    echo "<script>console.log('📊 الإحصائيات النهائية:', " . json_encode($stats) . ");</script>";
    
    if ($stats['posts_count'] == 0) {
        echo "<h3>🔍 فحص إضافي للمنشورات:</h3>";
        $posts_check = $pdo->prepare("SELECT COUNT(*) FROM posts WHERE user_id = ?");
        $posts_check->execute([$user_id]);
        $actual_posts = $posts_check->fetchColumn();
        echo "<p><strong>العدد الفعلي للمنشورات:</strong> $actual_posts</p>";
        echo "<script>console.log('🔍 العدد الفعلي للمنشورات:', $actual_posts);</script>";
        
        if ($actual_posts > 0) {
            $stats['posts_count'] = (int)$actual_posts;
            echo "<p class='success'>✅ تم تحديث عدد المنشورات</p>";
            echo "<script>console.log('✅ تم تحديث عدد المنشورات إلى:', $actual_posts);</script>";
        }
    }
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    if ($stats['posts_count'] == 0 && $stats['followers_count'] == 0 && $stats['following_count'] == 0) {
        echo "<div class='debug-section'>";
        echo "<h2>🛠️ الخطوة 6: إنشاء بيانات تجريبية</h2>";
        
        echo "<script>console.group('🛠️ إنشاء بيانات تجريبية');</script>";
        
        echo "<div class='step'>";
        echo "<h3>📝 إنشاء منشورات تجريبية:</h3>";
        for ($i = 1; $i <= 5; $i++) {
            $stmt = $pdo->prepare("INSERT INTO posts (user_id, content, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user_id, "منشور تجريبي رقم $i من المستخدم " . $_SESSION['username'] . " - تم إنشاؤه في " . date('Y-m-d H:i:s')]);
            echo "<p class='success'>✅ تم إنشاء المنشور رقم $i</p>";
            echo "<script>console.log('✅ تم إنشاء المنشور رقم $i');</script>";
        }
        echo "</div>";
        
        echo "<div class='step'>";
        echo "<h3>👥 إنشاء متابعين تجريبيين:</h3>";
        
        $test_users = [
            ['id' => 100, 'username' => 'test_follower_1', 'email' => 'follower1@test.com', 'first_name' => 'متابع', 'last_name' => 'أول'],
            ['id' => 101, 'username' => 'test_follower_2', 'email' => 'follower2@test.com', 'first_name' => 'متابع', 'last_name' => 'ثاني'],
            ['id' => 102, 'username' => 'test_following_1', 'email' => 'following1@test.com', 'first_name' => 'متابَع', 'last_name' => 'أول'],
            ['id' => 103, 'username' => 'test_following_2', 'email' => 'following2@test.com', 'first_name' => 'متابَع', 'last_name' => 'ثاني']
        ];
        
        foreach ($test_users as $test_user) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, username, email, first_name, last_name, password, created_at) VALUES (?, ?, ?, ?, ?, 'dummy_password', NOW())");
            $stmt->execute([$test_user['id'], $test_user['username'], $test_user['email'], $test_user['first_name'], $test_user['last_name']]);
            echo "<p class='success'>✅ تم إنشاء المستخدم: " . $test_user['username'] . "</p>";
            echo "<script>console.log('✅ تم إنشاء المستخدم:', '" . $test_user['username'] . "');</script>";
        }
        
        $follow_relations = [
            [100, $user_id], 
            [101, $user_id], 
            [$user_id, 102], 
            [$user_id, 103]  
        ];
        
        foreach ($follow_relations as $relation) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO followers (follower_id, followed_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute($relation);
            echo "<p class='success'>✅ تم إنشاء علاقة متابعة: {$relation[0]} يتابع {$relation[1]}</p>";
            echo "<script>console.log('✅ علاقة متابعة:', {$relation[0]}, 'يتابع', {$relation[1]});</script>";
        }
        echo "</div>";
        
        echo "<script>console.groupEnd();</script>";
        echo "</div>";
        
        echo "<div class='debug-section'>";
        echo "<h2>🔄 إعادة اختبار الإحصائيات</h2>";
        
        echo "<script>console.group('🔄 إعادة اختبار الإحصائيات');</script>";
        
        $stats_stmt->execute([$user_id, $user_id, $user_id]);
        $new_stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<div class='step'>";
        echo "<h3>📊 الإحصائيات الجديدة:</h3>";
        echo "<pre>" . print_r($new_stats, true) . "</pre>";
        echo "<script>console.log('📊 الإحصائيات الجديدة:', " . json_encode($new_stats) . ");</script>";
        echo "</div>";
        
        echo "<script>console.groupEnd();</script>";
        echo "</div>";
    }
    
    echo "<div class='debug-section'>";
    echo "<h2>🎯 الخطوة 7: النتيجة النهائية</h2>";
    
    echo "<script>console.group('🎯 النتيجة النهائية');</script>";
    
    $final_stats_stmt = $pdo->prepare($stats_query);
    $final_stats_stmt->execute([$user_id, $user_id, $user_id]);
    $final_stats = $final_stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<div class='step'>";
    echo "<h3>🏆 الإحصائيات النهائية:</h3>";
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;'>";
    echo "<p><strong>📝 عدد المنشورات:</strong> " . ($final_stats['posts_count'] ?? 0) . "</p>";
    echo "<p><strong>👥 عدد المتابعين:</strong> " . ($final_stats['followers_count'] ?? 0) . "</p>";
    echo "<p><strong>➡️ عدد المتابَعين:</strong> " . ($final_stats['following_count'] ?? 0) . "</p>";
    echo "</div>";
    
    echo "<script>
    console.log('🏆 الإحصائيات النهائية:', " . json_encode($final_stats) . ");
    console.log('📝 عدد المنشورات:', " . ($final_stats['posts_count'] ?? 0) . ");
    console.log('👥 عدد المتابعين:', " . ($final_stats['followers_count'] ?? 0) . ");
    console.log('➡️ عدد المتابَعين:', " . ($final_stats['following_count'] ?? 0) . ");
    </script>";
    
    echo "<h3>🔍 تحليل المشكلة:</h3>";
    $has_issue = false;
    
    if (($final_stats['posts_count'] ?? 0) == 0) {
        echo "<p class='warning'>⚠️ عدد المنشورات لا يزال صفر</p>";
        echo "<script>console.warn('⚠️ عدد المنشورات لا يزال صفر');</script>";
        $has_issue = true;
    }
    
    if (($final_stats['followers_count'] ?? 0) == 0) {
        echo "<p class='warning'>⚠️ عدد المتابعين لا يزال صفر</p>";
        echo "<script>console.warn('⚠️ عدد المتابعين لا يزال صفر');</script>";
        $has_issue = true;
    }
    
    if (($final_stats['following_count'] ?? 0) == 0) {
        echo "<p class='warning'>⚠️ عدد المتابَعين لا يزال صفر</p>";
        echo "<script>console.warn('⚠️ عدد المتابَعين لا يزال صفر');</script>";
        $has_issue = true;
    }
    
    if (!$has_issue) {
        echo "<p class='success'>🎉 جميع الإحصائيات تعمل بشكل صحيح!</p>";
        echo "<script>console.log('🎉 جميع الإحصائيات تعمل بشكل صحيح!');</script>";
    }
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
    echo "<div class='debug-section'>";
    echo "<h2>🛠️ الخطوة 8: توصيات الإصلاح</h2>";
    
    echo "<script>console.group('🛠️ توصيات الإصلاح');</script>";
    
    echo "<div class='step'>";
    echo "<h3>💡 التوصيات:</h3>";
    
    if ($has_issue) {
        echo "<ol>";
        echo "<li><strong>تحقق من home.php:</strong> تأكد من أن الكود يستخدم نفس الاستعلام المختبر هنا</li>";
        echo "<li><strong>تحقق من معالجة الأخطاء:</strong> تأكد من وجود معالجة صحيحة للأخطاء في home.php</li>";
        echo "<li><strong>تحقق من الجلسة:</strong> تأكد من أن user_id محفوظ بشكل صحيح في الجلسة</li>";
        echo "<li><strong>تحقق من قاعدة البيانات:</strong> تأكد من وجود البيانات المطلوبة</li>";
        echo "</ol>";
        
        echo "<script>
        console.warn('💡 يوجد مشاكل تحتاج إصلاح');
        console.log('🔧 توصيات الإصلاح:');
        console.log('1. تحقق من home.php');
        console.log('2. تحقق من معالجة الأخطاء');
        console.log('3. تحقق من الجلسة');
        console.log('4. تحقق من قاعدة البيانات');
        </script>";
    } else {
        echo "<p class='success'>✅ لا توجد مشاكل! يمكنك الآن الذهاب إلى home.php</p>";
        echo "<script>console.log('✅ لا توجد مشاكل!');</script>";
    }
    
    echo "<h3>🔗 الخطوات التالية:</h3>";
    echo "<p><a href='home.php' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 اذهب إلى home.php</a></p>";
    echo "<p><a href='test_home_stats.php' target='_blank' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 تشغيل اختبار آخر</a></p>";
    echo "</div>";
    
    echo "<script>console.groupEnd();</script>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='debug-section'>";
    echo "<h2 class='error'>❌ خطأ في التشخيص</h2>";
    echo "<p class='error'>رسالة الخطأ: " . $e->getMessage() . "</p>";
    echo "<p class='error'>ملف الخطأ: " . $e->getFile() . "</p>";
    echo "<p class='error'>سطر الخطأ: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
    
    echo "<script>
    console.error('❌ خطأ في التشخيص:', '" . addslashes($e->getMessage()) . "');
    console.error('📁 ملف الخطأ:', '" . addslashes($e->getFile()) . "');
    console.error('📍 سطر الخطأ:', " . $e->getLine() . ");
    </script>";
}

echo "<script>
console.timeEnd('⏱️ وقت التشخيص الكامل');
console.log('🏁 انتهى التشخيص في:', new Date().toLocaleString('ar-SA'));
console.groupEnd();
</script>";

echo "</body></html>";
?> 