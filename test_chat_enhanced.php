<?php


session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $users_stmt = $pdo->prepare("
        SELECT id, username, first_name, last_name, avatar_url
        FROM users 
        WHERE id != ? 
        ORDER BY username ASC
        LIMIT 10
    ");
    $users_stmt->execute([$_SESSION['user_id']]);
    $all_users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $all_users = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار المحادثة المحسنة 🚀</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .test-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .feature-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            border-left: 5px solid #2196f3;
        }
        .user-card {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin: 10px 0;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
        }
        .user-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid #e9ecef;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
        }
        .btn-primary {
            background: #2196f3;
            color: white;
        }
        .btn-primary:hover {
            background: #1976d2;
            transform: translateY(-2px);
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        .btn-info:hover {
            background: #138496;
            transform: translateY(-2px);
        }
        .feature-icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-left: 8px;
        }
        .status-online { background: #28a745; }
        .status-offline { background: #6c757d; }
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .comparison-table th,
        .comparison-table td {
            padding: 12px;
            text-align: center;
            border: 1px solid #e9ecef;
        }
        .comparison-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .check-icon {
            color: #28a745;
            font-size: 1.2rem;
        }
        .x-icon {
            color: #dc3545;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="test-card">
        <div style="text-align: center; margin-bottom: 30px;">
            <h1 style="color: #2c3e50; font-size: 2.5rem; margin-bottom: 10px;">
                🚀 اختبار المحادثة المحسنة
            </h1>
            <p style="color: #6c757d; font-size: 1.1rem;">
                جرب جميع المميزات الجديدة: الصور، الإيموجي، ملفات المستخدمين، وعلامات الصح
            </p>
        </div>

        <div class="feature-card">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">
                <i class="bi bi-list-check"></i>
                مقارنة المميزات
            </h2>
            <table class="comparison-table">
                <thead>
                    <tr>
                        <th>الميزة</th>
                        <th>المحادثة العادية</th>
                        <th>المحادثة المحسنة</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>إرسال النصوص</td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>إرسال الصور</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>الإيموجي</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>علامات الصح</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>ملف المستخدم</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>معاينة الصور</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                    <tr>
                        <td>حالة الاتصال</td>
                        <td><i class="bi bi-x-lg x-icon"></i></td>
                        <td><i class="bi bi-check-lg check-icon"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="feature-card">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">
                <i class="bi bi-stars"></i>
                المميزات الجديدة
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div style="text-align: center;">
                    <div class="feature-icon">🖼️</div>
                    <h4>إرسال الصور</h4>
                    <p style="font-size: 0.9rem; color: #6c757d;">
                        ارفع وأرسل الصور مباشرة في المحادثة
                    </p>
                </div>
                <div style="text-align: center;">
                    <div class="feature-icon">😊</div>
                    <h4>الإيموجي</h4>
                    <p style="font-size: 0.9rem; color: #6c757d;">
                        أضف الإيموجي لجعل المحادثة أكثر تعبيراً
                    </p>
                </div>
                <div style="text-align: center;">
                    <div class="feature-icon">👤</div>
                    <h4>ملف المستخدم</h4>
                    <p style="font-size: 0.9rem; color: #6c757d;">
                        اضغط على الصورة لعرض ملف المستخدم
                    </p>
                </div>
                <div style="text-align: center;">
                    <div class="feature-icon">✅</div>
                    <h4>علامات الصح</h4>
                    <p style="font-size: 0.9rem; color: #6c757d;">
                        تأكيد الإرسال والتسليم والقراءة
                    </p>
                </div>
            </div>
        </div>

        <div class="feature-card">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">
                <i class="bi bi-chat-dots"></i>
                اختبار المحادثة
            </h2>
            <div style="text-align: center; margin-bottom: 20px;">
                <a href="chat_enhanced.php" class="btn btn-primary">
                    <i class="bi bi-chat-heart"></i>
                    افتح المحادثة المحسنة
                </a>
                <a href="chat.php" class="btn btn-info" style="margin-right: 10px;">
                    <i class="bi bi-chat"></i>
                    المحادثة العادية
                </a>
            </div>
            
            <?php if (!empty($all_users)): ?>
                <h3 style="margin-bottom: 15px;">اختر مستخدم للمحادثة:</h3>
                <?php foreach ($all_users as $user): ?>
                    <div class="user-card">
                        <img src="<?php echo !empty($user['avatar_url']) ? htmlspecialchars($user['avatar_url']) : 'https://ui-avatars.com/api/?name=' . urlencode($user['username']) . '&background=2196f3&color=fff&size=100'; ?>" 
                             alt="<?php echo htmlspecialchars($user['username']); ?>" 
                             class="user-avatar">
                        <div style="flex: 1;">
                            <h4 style="margin: 0; color: #2c3e50;">
                                <?php echo !empty($user['first_name']) ? htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) : htmlspecialchars($user['username']); ?>
                                <span class="status-indicator status-online" title="متصل"></span>
                            </h4>
                            <p style="margin: 5px 0 0 0; color: #6c757d; font-size: 0.9rem;">
                                @<?php echo htmlspecialchars($user['username']); ?>
                            </p>
                        </div>
                        <div>
                            <a href="chat_enhanced.php?user=<?php echo $user['id']; ?>" class="btn btn-success">
                                <i class="bi bi-chat-dots"></i>
                                محادثة محسنة
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p style="text-align: center; color: #6c757d;">
                    لا يوجد مستخدمين متاحين للمحادثة
                </p>
            <?php endif; ?>
        </div>

        <div class="feature-card">
            <h2 style="color: #2c3e50; margin-bottom: 15px;">
                <i class="bi bi-info-circle"></i>
                كيفية الاستخدام
            </h2>
            <ol style="line-height: 1.8;">
                <li><strong>إرسال الصور:</strong> اضغط على أيقونة الصورة 🖼️ واختر الصور من جهازك</li>
                <li><strong>إضافة الإيموجي:</strong> اضغط على أيقونة الإيموجي 😊 واختر من القائمة</li>
                <li><strong>عرض ملف المستخدم:</strong> اضغط على صورة المستخدم في القائمة أو في رأس المحادثة</li>
                <li><strong>علامات الصح:</strong> ستظهر تلقائياً بجانب الرسائل المرسلة</li>
                <li><strong>معاينة الصور:</strong> اضغط على أي صورة في المحادثة لعرضها بحجم كامل</li>
            </ol>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="home.php" class="btn btn-info">
                <i class="bi bi-house-door"></i>
                العودة للصفحة الرئيسية
            </a>
            <a href="debug_chat_detailed.php" class="btn" style="background: #6c757d; color: white; margin-right: 10px;">
                <i class="bi bi-bug"></i>
                أدوات التشخيص
            </a>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.feature-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });
            
            cards.forEach(card => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'all 0.6s ease';
                observer.observe(card);
            });
            
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                });
            });
        });
    </script>
</body>
</html> 