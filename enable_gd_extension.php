<?php
echo "<!DOCTYPE html>
<html>
<head>
    <title>Enable GD Extension</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; }
        .step { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🔧 تفعيل امتداد GD في XAMPP</h1>";

if (extension_loaded('gd')) {
    echo "<div class='success'>
        <h3>✅ امتداد GD مفعل بالفعل!</h3>
        <p>يمكنك الآن رفع وتحسين صور الملف الشخصي.</p>
    </div>";
    
    $gd_info = gd_info();
    echo "<h3>معلومات امتداد GD:</h3><ul>";
    foreach ($gd_info as $key => $value) {
        echo "<li><strong>$key:</strong> " . (is_bool($value) ? ($value ? 'Yes' : 'No') : $value) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<div class='error'>
        <h3>❌ امتداد GD غير مفعل</h3>
        <p>تحتاج إلى تفعيل امتداد GD لمعالجة الصور.</p>
    </div>";
    
    echo "<div class='step'>
        <h3>📋 خطوات تفعيل امتداد GD في XAMPP:</h3>
        
        <h4>الطريقة 1: من خلال XAMPP Control Panel</h4>
        <ol>
            <li>افتح XAMPP Control Panel</li>
            <li>اضغط على زر 'Config' بجانب Apache</li>
            <li>اختر 'PHP (php.ini)'</li>
            <li>ابحث عن السطر: <code class='code'>;extension=gd</code></li>
            <li>احذف علامة ';' من بداية السطر ليصبح: <code class='code'>extension=gd</code></li>
            <li>احفظ الملف</li>
            <li>أعد تشغيل Apache من XAMPP Control Panel</li>
        </ol>
        
        <h4>الطريقة 2: تحرير ملف php.ini مباشرة</h4>
        <ol>
            <li>اذهب إلى مجلد XAMPP (عادة: <code class='code'>C:\\xampp\\</code>)</li>
            <li>افتح مجلد <code class='code'>php</code></li>
            <li>افتح ملف <code class='code'>php.ini</code> بمحرر النصوص</li>
            <li>ابحث عن: <code class='code'>;extension=gd</code></li>
            <li>احذف ';' ليصبح: <code class='code'>extension=gd</code></li>
            <li>احفظ الملف</li>
            <li>أعد تشغيل Apache</li>
        </ol>
        
        <h4>الطريقة 3: للإصدارات الأحدث من PHP</h4>
        <p>إذا لم تجد <code class='code'>extension=gd</code>، ابحث عن:</p>
        <ul>
            <li><code class='code'>;extension=php_gd2.dll</code> (Windows)</li>
            <li><code class='code'>;extension=gd2</code></li>
        </ul>
        <p>وقم بإزالة ';' من بداية السطر.</p>
    </div>";
}

echo "<div class='step'>
    <h3>🧪 اختبار بعد التفعيل</h3>
    <p>بعد تفعيل امتداد GD وإعادة تشغيل Apache:</p>
    <ol>
        <li><a href='test_avatar_debug.php'>اختبر رفع الأفاتار</a></li>
        <li><a href='u.php?username=admin'>اذهب إلى الملف الشخصي</a></li>
        <li>جرب تغيير صورة الملف الشخصي</li>
    </ol>
</div>";

echo "<div class='info'>
    <h3>📝 ملاحظات مهمة:</h3>
    <ul>
        <li>تأكد من إعادة تشغيل Apache بعد تعديل php.ini</li>
        <li>يمكنك التحقق من phpinfo() لمعرفة الامتدادات المفعلة</li>
        <li>إذا لم يعمل، تأكد من أنك تحرر الملف الصحيح (قد يكون هناك أكثر من ملف php.ini)</li>
        <li>في حالة عدم وجود امتداد GD، ستعمل وظيفة رفع الصور بدون تحسين الحجم</li>
    </ul>
</div>";

echo "<div class='step'>
    <h3>🔗 روابط مفيدة</h3>
    <ul>
        <li><a href='test_avatar_debug.php'>صفحة تشخيص رفع الأفاتار</a></li>
        <li><a href='u.php?username=admin'>الملف الشخصي</a></li>
        <li><a href='phpinfo.php' target='_blank'>معلومات PHP (إذا كان متوفراً)</a></li>
    </ul>
</div>";

echo "</body></html>";
?> 