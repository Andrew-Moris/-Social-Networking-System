<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'طريقة الطلب غير صحيحة'
    ]);
    exit;
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$first_name = $_POST['first_name'] ?? '';
$last_name = $_POST['last_name'] ?? '';

if (empty($username) || empty($email) || empty($password)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يرجى ملء جميع الحقول المطلوبة'
    ]);
    exit;
}

if (strlen($username) < USERNAME_MIN_LENGTH || strlen($username) > USERNAME_MAX_LENGTH) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يجب أن يكون اسم المستخدم بين ' . USERNAME_MIN_LENGTH . ' و ' . USERNAME_MAX_LENGTH . ' حرف'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'البريد الإلكتروني غير صحيح'
    ]);
    exit;
}

if (strlen($password) < PASSWORD_MIN_LENGTH) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'يجب أن تكون كلمة المرور على الأقل ' . PASSWORD_MIN_LENGTH . ' أحرف'
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'اسم المستخدم موجود بالفعل'
        ]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'البريد الإلكتروني موجود بالفعل'
        ]);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $result = $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name]);
    
    if ($result) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الحساب بنجاح',
            'redirect' => 'login.html'
        ]);
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'حدث خطأ أثناء إنشاء الحساب'
        ]);
    }
} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ في النظام: ' . $e->getMessage()
    ]);
}
