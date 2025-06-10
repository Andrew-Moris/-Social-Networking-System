<?php

session_start();

$_SESSION['user_id'] = 1; 

echo "<h2>🧪 اختبار API discover_filter.php</h2>";

$filters = ['all', 'recent', 'popular', 'media'];

foreach ($filters as $filter) {
    echo "<h3>🔍 اختبار فلتر: $filter</h3>";
    
    $url = "http://localhost/WEP/api/discover_filter.php?filter=$filter";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            if ($data['success']) {
                $postCount = count($data['data']['posts']);
                echo "<p style='color: green;'>✅ نجح الفلتر: $postCount منشور</p>";
                
                if ($postCount > 0) {
                    $firstPost = $data['data']['posts'][0];
                    echo "<div style='background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
                    echo "<strong>مثال منشور:</strong><br>";
                    echo "المؤلف: " . htmlspecialchars($firstPost['username']) . "<br>";
                    echo "المحتوى: " . htmlspecialchars(substr($firstPost['content'], 0, 100)) . "...<br>";
                    echo "الإعجابات: " . $firstPost['likes_count'] . "<br>";
                    echo "التعليقات: " . $firstPost['comments_count'] . "<br>";
                    echo "</div>";
                }
            } else {
                echo "<p style='color: red;'>❌ فشل الفلتر: " . htmlspecialchars($data['message']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ استجابة JSON غير صحيحة</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ لا توجد استجابة من الخادم</p>";
    }
    
    echo "<hr>";
}

echo "<h3>🔗 روابط الاختبار:</h3>";
echo "<ul>";
echo "<li><a href='discover.php' target='_blank'>صفحة Discover</a></li>";
echo "<li><a href='api/discover_filter.php?filter=all' target='_blank'>API - جميع المنشورات</a></li>";
echo "<li><a href='api/discover_filter.php?filter=recent' target='_blank'>API - المنشورات الحديثة</a></li>";
echo "<li><a href='api/discover_filter.php?filter=popular' target='_blank'>API - المنشورات الشائعة</a></li>";
echo "<li><a href='api/discover_filter.php?filter=media' target='_blank'>API - منشورات الوسائط</a></li>";
echo "</ul>";
?> 