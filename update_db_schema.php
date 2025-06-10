<?php

$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'wep_db';

echo "<!DOCTYPE html>";
echo "<html dir='rtl'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<title>تحديث هيكل قاعدة البيانات</title>";
echo "<style>";
echo "body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; line-height: 1.6; margin: 0; padding: 20px; color: #333; background-color: #f5f7fa; }";
echo "h1, h2 { color: #2563eb; }";
echo "h1 { border-bottom: 2px solid #e5e7eb; padding-bottom: 10px; }";
echo ".container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }";
echo ".success { color: #047857; background: #ecfdf5; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".error { color: #b91c1c; background: #fee2e2; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".warning { color: #92400e; background: #fff7ed; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".btn { display: inline-block; background: #3b82f6; color: white; padding: 8px 16px; text-decoration: none; border-radius: 4px; margin-right: 10px; }";
echo ".btn:hover { background: #2563eb; }";
echo "</style>";
echo "</head>";
echo "<body>";
echo "<div class='container'>";
echo "<h1>تحديث هيكل قاعدة البيانات</h1>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<div class='success'>تم الاتصال بقاعدة البيانات بنجاح</div>";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
    $tableExists = $stmt->rowCount() > 0;
    
    if (!$tableExists) {
        echo "<div class='error'>جدول messages غير موجود في قاعدة البيانات</div>";
    } else {
        echo "<div class='success'>جدول messages موجود في قاعدة البيانات</div>";
        
        $sql = "ALTER TABLE messages MODIFY COLUMN media_url LONGTEXT";
        
        try {
            $pdo->exec($sql);
            echo "<div class='success'>تم تعديل عمود media_url بنجاح ليستوعب الصور الكبيرة</div>";
            
            $stmt = $pdo->query("DESCRIBE messages");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>هيكل جدول messages بعد التعديل:</h2>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #f1f5f9;'><th>الاسم</th><th>النوع</th><th>Null</th><th>المفتاح</th><th>الافتراضي</th><th>إضافي</th></tr>";
            
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>{$column['Field']}</td>";
                echo "<td>{$column['Type']}</td>";
                echo "<td>{$column['Null']}</td>";
                echo "<td>{$column['Key']}</td>";
                echo "<td>{$column['Default']}</td>";
                echo "<td>{$column['Extra']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } catch (PDOException $e) {
            echo "<div class='error'>فشل تعديل عمود media_url: " . $e->getMessage() . "</div>";
        }
    }
    
    echo "<div style='margin-top: 20px;'>";
    echo "<a href='/WEP/chat.php' class='btn'>العودة إلى صفحة الدردشة</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage() . "</div>";
}

echo "</div>";
echo "</body>";
echo "</html>";
?>
