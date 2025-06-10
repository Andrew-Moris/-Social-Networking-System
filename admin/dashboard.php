<?php
session_start();
require_once '../config.php';
require_once '../functions.php';

if (!isLoggedIn() || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header('Location: login.php');
    exit;
}

$pdo = new PDO($dsn, DB_USER, DB_PASS, $pdo_options);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'delete' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                $stmt->execute([$user_id, $_SESSION['user_id']]);
                
                if ($stmt->rowCount() > 0) {
                    $message = 'تم حذف المستخدم بنجاح';
                } else {
                    $error = 'لا يمكن حذف هذا المستخدم';
                }
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء حذف المستخدم';
            }
        } elseif ($_POST['action'] === 'add') {
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            if (empty($username) || empty($email) || empty($password)) {
                $error = 'يرجى ملء جميع الحقول المطلوبة';
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, is_admin) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $is_admin]);
                    
                    $message = 'تم إضافة المستخدم بنجاح';
                } catch (PDOException $e) {
                    $error = 'حدث خطأ أثناء إضافة المستخدم: ' . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'edit' && isset($_POST['user_id'])) {
            $user_id = $_POST['user_id'];
            $username = $_POST['username'] ?? '';
            $email = $_POST['email'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $is_admin = isset($_POST['is_admin']) ? 1 : 0;
            
            if (empty($username) || empty($email)) {
                $error = 'يرجى ملء جميع الحقول المطلوبة';
            } else {
                try {
                    $sql = "UPDATE users SET username = ?, email = ?, first_name = ?, last_name = ?, is_admin = ? WHERE id = ?";
                    $params = [$username, $email, $first_name, $last_name, $is_admin, $user_id];
                    
                    if (!empty($_POST['password'])) {
                        $sql = "UPDATE users SET username = ?, email = ?, password = ?, first_name = ?, last_name = ?, is_admin = ? WHERE id = ?";
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $params = [$username, $email, $hashed_password, $first_name, $last_name, $is_admin, $user_id];
                    }
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    
                    $message = 'تم تحديث المستخدم بنجاح';
                } catch (PDOException $e) {
                    $error = 'حدث خطأ أثناء تحديث المستخدم: ' . $e->getMessage();
                }
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY id");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المسؤول | SUT Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background: #0a0f1c; color: #ffffff; }
    </style>
</head>
<body class="min-h-screen">
    <div class="flex">
        <div class="w-64 bg-[#1a1f2e] min-h-screen p-4">
            <div class="mb-8">
                <h1 class="text-xl font-bold">لوحة تحكم المسؤول</h1>
                <p class="text-gray-400 text-sm">SUT Premium</p>
            </div>
            
            <nav class="space-y-2">
                <a href="dashboard.php" class="block py-2 px-4 bg-blue-600 rounded-lg">
                    <i class="bi bi-people-fill ml-2"></i>
                    إدارة المستخدمين
                </a>
                <a href="../index.php" class="block py-2 px-4 hover:bg-[#2a2f3e] rounded-lg">
                    <i class="bi bi-house-door-fill ml-2"></i>
                    الرئيسية
                </a>
                <a href="../logout.php" class="block py-2 px-4 hover:bg-[#2a2f3e] rounded-lg text-red-400">
                    <i class="bi bi-box-arrow-right ml-2"></i>
                    تسجيل الخروج
                </a>
            </nav>
        </div>
        
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">إدارة المستخدمين</h2>
                <button id="add-user-btn" class="bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg flex items-center">
                    <i class="bi bi-plus-lg ml-2"></i>
                    إضافة مستخدم جديد
                </button>
            </div>
            
            <?php if ($message): ?>
            <div class="bg-green-500/10 border border-green-500/20 text-green-500 rounded-lg p-4 mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="bg-red-500/10 border border-red-500/20 text-red-500 rounded-lg p-4 mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <div class="bg-[#1a1f2e] rounded-xl overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-700">
                            <th class="py-3 px-4 text-right">#</th>
                            <th class="py-3 px-4 text-right">اسم المستخدم</th>
                            <th class="py-3 px-4 text-right">البريد الإلكتروني</th>
                            <th class="py-3 px-4 text-right">الاسم</th>
                            <th class="py-3 px-4 text-right">مسؤول</th>
                            <th class="py-3 px-4 text-right">تاريخ الإنشاء</th>
                            <th class="py-3 px-4 text-right">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b border-gray-700 hover:bg-[#2a2f3e]">
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="py-3 px-4">
                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                            </td>
                            <td class="py-3 px-4">
                                <?php echo $user['is_admin'] ? '<span class="text-green-500">نعم</span>' : '<span class="text-gray-400">لا</span>'; ?>
                            </td>
                            <td class="py-3 px-4"><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="py-3 px-4">
                                <div class="flex space-x-2 space-x-reverse">
                                    <button class="edit-user-btn text-blue-500 hover:text-blue-400" data-id="<?php echo $user['id']; ?>" 
                                            data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                            data-email="<?php echo htmlspecialchars($user['email']); ?>"
                                            data-firstname="<?php echo htmlspecialchars($user['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($user['last_name']); ?>"
                                            data-admin="<?php echo $user['is_admin'] ? '1' : '0'; ?>">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                    <button class="delete-user-btn text-red-500 hover:text-red-400" data-id="<?php echo $user['id']; ?>">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div id="add-user-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-[#1a1f2e] rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">إضافة مستخدم جديد</h3>
                <button class="close-modal text-gray-400 hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="block text-sm font-medium mb-2">اسم المستخدم*</label>
                    <input type="text" name="username" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">البريد الإلكتروني*</label>
                    <input type="email" name="email" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">كلمة المرور*</label>
                    <input type="password" name="password" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white" required>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">الاسم الأول</label>
                        <input type="text" name="first_name" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">الاسم الأخير</label>
                        <input type="text" name="last_name" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white">
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_admin" id="add-is-admin" class="ml-2">
                    <label for="add-is-admin">مسؤول</label>
                </div>
                
                <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg">
                    إضافة المستخدم
                </button>
            </form>
        </div>
    </div>
    
    <div id="edit-user-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-[#1a1f2e] rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">تعديل المستخدم</h3>
                <button class="close-modal text-gray-400 hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="user_id" id="edit-user-id">
                
                <div>
                    <label class="block text-sm font-medium mb-2">اسم المستخدم*</label>
                    <input type="text" name="username" id="edit-username" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">البريد الإلكتروني*</label>
                    <input type="email" name="email" id="edit-email" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white" required>
                </div>
                
                <div>
                    <label class="block text-sm font-medium mb-2">كلمة المرور (اتركها فارغة إذا لم ترغب في تغييرها)</label>
                    <input type="password" name="password" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white">
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">الاسم الأول</label>
                        <input type="text" name="first_name" id="edit-first-name" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">الاسم الأخير</label>
                        <input type="text" name="last_name" id="edit-last-name" class="w-full bg-[#0a0f1c] border border-gray-700 rounded-lg p-2 text-white">
                    </div>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_admin" id="edit-is-admin" class="ml-2">
                    <label for="edit-is-admin">مسؤول</label>
                </div>
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg">
                    حفظ التغييرات
                </button>
            </form>
        </div>
    </div>
    
    <div id="delete-user-modal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-[#1a1f2e] rounded-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">حذف المستخدم</h3>
                <button class="close-modal text-gray-400 hover:text-white">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            
            <p class="mb-6">هل أنت متأكد من أنك تريد حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه.</p>
            
            <form method="post" class="space-y-4">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="delete-user-id">
                
                <div class="flex space-x-4 space-x-reverse">
                    <button type="button" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white py-2 rounded-lg close-modal">
                        إلغاء
                    </button>
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg">
                        حذف
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addUserBtn = document.getElementById('add-user-btn');
            const addUserModal = document.getElementById('add-user-modal');
            const editUserModal = document.getElementById('edit-user-modal');
            const deleteUserModal = document.getElementById('delete-user-modal');
            const closeModalButtons = document.querySelectorAll('.close-modal');
            const editUserButtons = document.querySelectorAll('.edit-user-btn');
            const deleteUserButtons = document.querySelectorAll('.delete-user-btn');
            
            addUserBtn.addEventListener('click', function() {
                addUserModal.classList.remove('hidden');
            });
            
            editUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    const username = this.getAttribute('data-username');
                    const email = this.getAttribute('data-email');
                    const firstName = this.getAttribute('data-firstname');
                    const lastName = this.getAttribute('data-lastname');
                    const isAdmin = this.getAttribute('data-admin') === '1';
                    
                    document.getElementById('edit-user-id').value = userId;
                    document.getElementById('edit-username').value = username;
                    document.getElementById('edit-email').value = email;
                    document.getElementById('edit-first-name').value = firstName;
                    document.getElementById('edit-last-name').value = lastName;
                    document.getElementById('edit-is-admin').checked = isAdmin;
                    
                    editUserModal.classList.remove('hidden');
                });
            });
            
            deleteUserButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const userId = this.getAttribute('data-id');
                    document.getElementById('delete-user-id').value = userId;
                    deleteUserModal.classList.remove('hidden');
                });
            });
            
            closeModalButtons.forEach(button => {
                button.addEventListener('click', function() {
                    addUserModal.classList.add('hidden');
                    editUserModal.classList.add('hidden');
                    deleteUserModal.classList.add('hidden');
                });
            });
            
            window.addEventListener('click', function(event) {
                if (event.target === addUserModal) {
                    addUserModal.classList.add('hidden');
                }
                if (event.target === editUserModal) {
                    editUserModal.classList.add('hidden');
                }
                if (event.target === deleteUserModal) {
                    deleteUserModal.classList.add('hidden');
                }
            });
        });
    </script>
</body>
</html>
