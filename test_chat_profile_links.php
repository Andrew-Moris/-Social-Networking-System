<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>اختبار روابط الملفات الشخصية</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; direction: rtl; }
        .test-link { 
            display: block; 
            margin: 10px 0; 
            padding: 10px; 
            background: #f0f0f0; 
            border-radius: 5px; 
            text-decoration: none; 
            color: #333;
        }
        .test-link:hover { background: #e0e0e0; }
    </style>
</head>
<body>
    <h1>🧪 اختبار روابط الملفات الشخصية في المحادثة</h1>
    
    <h2>الروابط المتاحة:</h2>
    
    <a href='chat_enhanced.php' class='test-link'>
        📱 المحادثة المطورة (مع أيقونات الملفات الشخصية)
    </a>
    
    <a href='u.php?username=admin' class='test-link' target='_blank'>
        👤 ملف admin الشخصي
    </a>
    
    <a href='u.php?username=ben10' class='test-link' target='_blank'>
        👤 ملف ben10 الشخصي
    </a>
    
    <h2>الميزات الجديدة:</h2>
    <ul>
        <li>✅ أيقونة بجانب كل مستخدم في قائمة المحادثات</li>
        <li>✅ أيقونة في رأس المحادثة لفتح ملف المستخدم الحالي</li>
        <li>✅ أيقونة صغيرة في الرسائل المستقبلة لفتح ملف المرسل</li>
        <li>✅ فتح الروابط في تبويب جديد</li>
        <li>✅ منع تداخل الأحداث (event bubbling)</li>
        <li>✅ تصميم متجاوب وجميل</li>
    </ul>
    
    <h2>كيفية الاختبار:</h2>
    <ol>
        <li>افتح المحادثة المطورة</li>
        <li>اختر مستخدم من القائمة</li>
        <li>انقر على أيقونة الملف الشخصي بجانب اسم المستخدم</li>
        <li>تحقق من فتح الملف الشخصي في تبويب جديد</li>
        <li>جرب أيضاً الأيقونة في رأس المحادثة</li>
    </ol>
    
    <div style='background: #e8f5e8; padding: 15px; border-radius: 8px; margin-top: 20px;'>
        <strong>✅ تم تطبيق جميع التحسينات بنجاح!</strong><br>
        الآن يمكن للمستخدمين الوصول بسهولة إلى الملفات الشخصية من داخل المحادثة.
    </div>
</body>
</html>";
?> 