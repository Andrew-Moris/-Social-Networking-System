<?php


session_start();

error_log("Posts API called with REQUEST_METHOD: {$_SERVER['REQUEST_METHOD']}");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, DELETE, PUT');
header('Access-Control-Allow-Headers: Content-Type');

error_log("Session status: " . session_status());
error_log("Session data: " . print_r($_SESSION, true));

if (!isset($_SESSION['user_id'])) {
    error_log("User not logged in (no session). Checking for user_id in POST.");
    
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        error_log("Using user_id from POST: {$user_id}");
    } else {
        error_log("No user_id found in POST. Authentication failed.");
        echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً']);
        exit;
    }
} else {
    $user_id = $_SESSION['user_id'];
    error_log("User authenticated via session. User ID: {$user_id}");
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    $host = 'localhost';
    $dbname = 'wep_db';
    $user_db = 'root';
    $password = '';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $user_db, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    error_log("Posts API: Database connection successful");

    switch ($method) {
        case 'POST':
            if (isset($_POST['action'])) {
                switch ($_POST['action']) {
                    case 'create_post_on_profile':
                        handlePostCreation($pdo, $user_id);
                        break;
                    case 'delete_post':
                        handlePostDeletion($pdo, $user_id);
                        break;
                    case 'edit_post':
                        handlePostEdit($pdo);
                        break;
                    case 'toggle_like':
                        handleToggleLike($pdo, $user_id);
                        break;
                    case 'toggle_bookmark':
                        handleToggleBookmark($pdo, $user_id);
                        break;
                    case 'toggle_favorite':
                        handleToggleFavorite($pdo, $user_id);
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'إجراء غير مدعوم']);
                        break;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'إجراء غير محدد']);
            }
            break;
        case 'GET':
            if (isset($_GET['action'])) {
                switch ($_GET['action']) {
                    case 'get_user_posts':
                        handlePostRetrieval($pdo, $user_id);
                        break;
                    case 'get_comments_count':
                        handleGetCommentsCount($pdo);
                        break;
                    default:
                        echo json_encode(['success' => false, 'message' => 'إجراء غير مدعوم']);
                        break;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'إجراء غير محدد']);
            }
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة']);
            break;
    }
} catch (PDOException $e) {
    error_log("Database Error in posts.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'حدث خطأ في قاعدة البيانات']);
} catch (Exception $e) {
    error_log("General Error in posts.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function handlePostRetrieval($pdo, $current_user_id) {
    try {
        $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : $current_user_id;
        
        error_log("handlePostRetrieval called for user_id: {$target_user_id} by current_user: {$current_user_id}");
        
        $userColumnsQuery = "SHOW COLUMNS FROM users";
        $userColumnsStmt = $pdo->query($userColumnsQuery);
        $userColumns = $userColumnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
        
        error_log("User table columns: " . implode(", ", $userColumns));
        
        $avatarColumn = 'username'; 
        foreach(['avatar', 'avatar_url', 'profile_picture', 'image', 'picture'] as $possibleColumn) {
            if (in_array($possibleColumn, $userColumns)) {
                $avatarColumn = $possibleColumn;
                break;
            }
        }
        
        error_log("Using avatar column: {$avatarColumn}");
        
        $query = "SELECT p.*,
                    u.username,
                    ";
        
        $selectColumns = [];
        
        if (in_array('first_name', $userColumns)) {
            $selectColumns[] = "u.first_name";
        }
        
        if (in_array('last_name', $userColumns)) {
            $selectColumns[] = "u.last_name";
        }
        
        if (!empty($selectColumns)) {
            $query .= implode(", ", $selectColumns) . ",";
        }
        
        $query .= "u.{$avatarColumn} as avatar
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = ?
                ORDER BY p.created_at DESC";
        
        error_log("Dynamic query: {$query}");
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$target_user_id]);
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($posts) . " posts for user ID: {$target_user_id}");
        
        if (!empty($posts)) {
            $postIds = array_column($posts, 'id');
            $postIdsStr = implode(',', $postIds);
            
            $likesStmt = $pdo->prepare("SELECT post_id, COUNT(*) as count FROM likes WHERE post_id IN ({$postIdsStr}) GROUP BY post_id");
            $likesStmt->execute();
            $likes = $likesStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $commentsStmt = $pdo->prepare("SELECT post_id, COUNT(*) as count FROM comments WHERE post_id IN ({$postIdsStr}) GROUP BY post_id");
            $commentsStmt->execute();
            $comments = $commentsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
            
            $userLikesStmt = $pdo->prepare("SELECT post_id FROM likes WHERE post_id IN ({$postIdsStr}) AND user_id = ?");
            $userLikesStmt->execute([$current_user_id]);
            $userLikes = $userLikesStmt->fetchAll(PDO::FETCH_COLUMN);
            
            $userBookmarksStmt = $pdo->prepare("SELECT post_id FROM bookmarks WHERE post_id IN ({$postIdsStr}) AND user_id = ?");
            $userBookmarksStmt->execute([$current_user_id]);
            $userBookmarks = $userBookmarksStmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($posts as &$post) {
                $postId = $post['id'];
                $post['likes_count'] = isset($likes[$postId]) ? (int)$likes[$postId] : 0;
                $post['comments_count'] = isset($comments[$postId]) ? (int)$comments[$postId] : 0;
                $post['is_liked'] = in_array($postId, $userLikes);
                $post['is_bookmarked'] = in_array($postId, $userBookmarks);
                
                if (isset($post['media_url']) && !empty($post['media_url'])) {
                    error_log("Original media_url: {$post['media_url']}");
                    
                    if (strpos($post['media_url'], '/') !== 0 && strpos($post['media_url'], 'http') !== 0) {
                        $post['media_url'] = '/WEP/' . $post['media_url'];
                    }
                    
                    error_log("Final media_url: {$post['media_url']}");
                }
                
                if (isset($post['media_url']) && !empty($post['media_url'])) {
                    $post['image_url'] = $post['media_url'];
                }
                
                if (isset($post['avatar']) && !empty($post['avatar'])) {
                    if (strpos($post['avatar'], '/') !== 0 && strpos($post['avatar'], 'http') !== 0) {
                        $post['avatar'] = '/WEP/' . $post['avatar'];
                    }
                }
                $post['avatar_url'] = $post['avatar']; 
                
                $post['created_at'] = date('Y-m-d H:i:s', strtotime($post['created_at']));
            }
            unset($post); 
            
            if (!empty($posts)) {
                error_log("First post data: " . print_r($posts[0], true));
            }
        }
        
        echo json_encode([
            'success' => true, 
            'total_posts' => count($posts),
            'posts' => $posts
        ]);
    } catch (Exception $e) {
        error_log("Error in handlePostRetrieval: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب المنشورات: ' . $e->getMessage()]);
    }
}

function handlePostCreation($pdo, $user_id) {
    try {
        error_log("handlePostCreation called by user_id: {$user_id}");
        error_log("POST data in handlePostCreation: " . print_r($_POST, true));
        error_log("FILES data in handlePostCreation: " . print_r($_FILES, true));
        
        if (empty($user_id) || !is_numeric($user_id)) {
            error_log("Invalid user_id: {$user_id}");
            echo json_encode(['success' => false, 'message' => 'معرف المستخدم غير صحيح']);
            return;
        }
        
        if (empty($_POST['content']) && empty($_FILES['media'])) {
            error_log("Post creation error: No content or media provided");
            echo json_encode(['success' => false, 'message' => 'يجب إدخال محتوى أو إرفاق وسائط']);
            return;
        }

        $content = isset($_POST['content']) ? trim($_POST['content']) : '';
        $media_url = null;

        if (!empty($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4'];
            $file_type = $_FILES['media']['type'];
            
            if (!in_array($file_type, $allowed_types)) {
                error_log("Post creation error: Invalid file type: {$file_type}");
                echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم']);
                return;
            }

            $upload_dir = '../uploads/posts/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['media']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('post_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                $media_url = 'uploads/posts/' . $file_name;
                error_log("Media uploaded successfully: {$media_url}");
            } else {
                error_log("Failed to move uploaded file to {$file_path}");
            }
        }

        $pdo->beginTransaction();

        try {
            $tableStructure = $pdo->query("DESCRIBE posts");
            $columns = $tableStructure->fetchAll(PDO::FETCH_COLUMN);
            error_log("Table structure for posts: " . print_r($columns, true));
        } catch (Exception $e) {
            error_log("Error checking table structure: " . $e->getMessage());
        }
        
        try {
            $tableStructure = $pdo->query("DESCRIBE posts");
            $columns = [];
            while ($row = $tableStructure->fetch(PDO::FETCH_ASSOC)) {
                $columns[] = $row['Field'];
            }
            error_log("Table structure for posts: " . print_r($columns, true));
            
            $fields = ['user_id', 'content', 'created_at'];
            $values = [$user_id, $content, 'NOW()'];
            $placeholders = ['?', '?', 'NOW()'];
            
            if (!empty($media_url)) {
                $fields[] = 'media_url';
                $values[] = $media_url;
                $placeholders[] = '?';
            }
            
            array_pop($values);
            
            $fields_str = implode(', ', $fields);
            $placeholders_str = implode(', ', $placeholders);
            
            $query = "INSERT INTO posts ({$fields_str}) VALUES ({$placeholders_str})";
            error_log("Dynamic query: {$query}");
            
            $stmt = $pdo->prepare($query);
        } catch (Exception $e) {
            error_log("Error building query: " . $e->getMessage());
            $stmt = $pdo->prepare("
                INSERT INTO posts (user_id, content, media_url, created_at)
                VALUES (?, ?, ?, NOW())
            ");
        }
        
        error_log("Post insert query prepared with params: user_id={$user_id}, content=" . substr($_POST['content'] ?? '', 0, 50) . "..., media_url={$media_url}");

        if ($stmt->execute(isset($query) ? array_slice($values, 0, count($values)) : [$user_id, $content, $media_url])) {
            $post_id = $pdo->lastInsertId();
            error_log("New post created with ID: {$post_id}");

            $userStmt = $pdo->prepare("SELECT id, username, first_name, last_name, avatar_url FROM users WHERE id = ?");
            $userStmt->execute([$user_id]);
            $userData = $userStmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("User data columns: " . print_r(array_keys($userData ?: []), true));
            
            if (!$userData) {
                $pdo->rollBack();
                error_log("Failed to retrieve user data for user_id: {$user_id}");
                echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع بيانات المستخدم']);
                return;
            }

            $postStmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
            $postStmt->execute([$post_id]);
            $post = $postStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$post) {
                $pdo->rollBack();
                error_log("Failed to retrieve new post with ID: {$post_id}");
                echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء استرجاع بيانات المنشور']);
                return;
            }
            
            $pdo->commit();
            
            $post = array_merge($post, [
                'username' => $userData['username'],
                'first_name' => $userData['first_name'] ?? '',
                'last_name' => $userData['last_name'] ?? '',
                'avatar' => $userData['avatar_url'] ?? '', 
                'avatar_url' => $userData['avatar_url'] ?? '', 
                'likes_count' => 0,
                'comments_count' => 0,
                'is_liked' => false,
                'is_bookmarked' => false,
                'user_id' => $user_id  
            ]);
            
            error_log("User data being merged: " . print_r($userData, true));
            
            $post['created_at'] = date('Y-m-d H:i:s', strtotime($post['created_at']));
            
            error_log("Complete post data being returned: " . print_r($post, true));

            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء المنشور بنجاح',
                'post' => $post
            ]);
        } else {
            $pdo->rollBack();
            error_log("Failed to execute post creation query: " . print_r($stmt->errorInfo(), true));
            echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء إنشاء المنشور']);
        }
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error in handlePostCreation: " . $e->getMessage() . "\nStack trace: " . $e->getTraceAsString());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ في النظام: ' . $e->getMessage()]);
    }
}

function handlePostDeletion($pdo, $user_id) {
    try {
        $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
        
        if (!$postId) {
            throw new Exception('معرف المنشور غير صالح');
        }
        
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("SELECT media_url FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $user_id]);
        $post = $stmt->fetch();
        
        if (!$post) {
            throw new Exception('المنشور غير موجود أو لا يمكنك حذفه');
        }
        
        $stmt = $pdo->prepare("DELETE FROM likes WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM comments WHERE post_id = ?");
        $stmt->execute([$postId]);
        
        $stmt = $pdo->prepare("DELETE FROM posts WHERE id = ? AND user_id = ?");
        $stmt->execute([$postId, $user_id]);
        
        if ($post['media_url']) {
            $mediaPath = '../' . $post['media_url'];
            if (file_exists($mediaPath)) {
                unlink($mediaPath);
            }
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم حذف المنشور بنجاح'
        ]);
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Post deletion error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function handlePostEdit($pdo) {
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    $content = trim($_POST['content'] ?? '');
    $removeMedia = isset($_POST['remove_media']) && $_POST['remove_media'] === '1';
    
    if (!$postId) {
        throw new Exception('معرف المنشور غير صالح');
    }
    
    $stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ? AND user_id = ?");
    $stmt->execute([$postId, $_SESSION['user_id']]);
    $post = $stmt->fetch();
    
    if (!$post) {
        throw new Exception('المنشور غير موجود أو لا يمكنك تعديله');
    }
    
    try {
        $pdo->beginTransaction();
        
        $mediaUrl = $post['media_url'];
        $mediaType = $post['media_type'];
        
        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            
            $allowedImageTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $allowedVideoTypes = ['mp4', 'webm', 'ogg'];
            
            if (!in_array($fileType, array_merge($allowedImageTypes, $allowedVideoTypes))) {
                throw new Exception('نوع الملف غير مدعوم');
            }
            
            if ($post['media_url'] && file_exists('../' . $post['media_url'])) {
                unlink('../' . $post['media_url']);
            }
            
            $uploadDir = 'uploads/posts/';
            if (!file_exists('../' . $uploadDir)) {
                mkdir('../' . $uploadDir, 0777, true);
            }
            
            $fileName = uniqid() . '_' . time() . '.' . $fileType;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], '../' . $filePath)) {
                $mediaUrl = $filePath;
                $mediaType = in_array($fileType, $allowedImageTypes) ? 'image' : 'video';
            } else {
                throw new Exception('فشل في رفع الملف');
            }
        } elseif ($removeMedia) {
            if ($post['media_url'] && file_exists('../' . $post['media_url'])) {
                unlink('../' . $post['media_url']);
            }
            $mediaUrl = null;
            $mediaType = null;
        }
        
        $stmt = $pdo->prepare("
            UPDATE posts 
            SET content = ?, 
                media_url = ?,
                media_type = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$content, $mediaUrl, $mediaType, $postId]);
        
        $stmt = $pdo->prepare("
            SELECT p.*, 
                   u.username,
                   u.avatar,
                   (SELECT COUNT(*) FROM likes WHERE post_id = p.id) as likes_count,
                   (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                   EXISTS(SELECT 1 FROM likes WHERE post_id = p.id AND user_id = ?) as is_liked,
                   EXISTS(SELECT 1 FROM bookmarks WHERE post_id = p.id AND user_id = ?) as is_bookmarked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $postId]);
        $updatedPost = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($updatedPost) {
            $updatedPost['created_at'] = date('Y-m-d H:i', strtotime($updatedPost['created_at']));
        }
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'post' => $updatedPost]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}


function handleToggleLike($pdo, $user_id) {
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $user_id]);
    $existingLike = $stmt->fetch();
    
    if ($existingLike) {
        $stmt = $pdo->prepare("DELETE FROM likes WHERE id = ?");
        $stmt->execute([$existingLike['id']]);
        $action = 'unlike';
    } else {
        $stmt = $pdo->prepare("INSERT INTO likes (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $user_id]);
        $action = 'like';
    }
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
    $stmt->execute([$postId]);
    $likesCount = $stmt->fetch()['count'];
    
    echo json_encode([
        'success' => true,
        'action' => $action,
        'likes_count' => number_format($likesCount)
    ]);
}

function handleToggleBookmark($pdo, $user_id) {
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$postId) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM bookmarks WHERE post_id = ? AND user_id = ?");
    $stmt->execute([$postId, $user_id]);
    $existingBookmark = $stmt->fetch();
    
    if ($existingBookmark) {
        $stmt = $pdo->prepare("DELETE FROM bookmarks WHERE id = ?");
        $stmt->execute([$existingBookmark['id']]);
        $action = 'unbookmark';
    } else {
        $stmt = $pdo->prepare("INSERT INTO bookmarks (post_id, user_id) VALUES (?, ?)");
        $stmt->execute([$postId, $user_id]);
        $action = 'bookmark';
    }
    
    echo json_encode([
        'success' => true,
        'action' => $action
    ]);
}

function handleGetCommentsCount($pdo) {
    $post_id = filter_input(INPUT_GET, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$post_id) {
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = ?");
        $stmt->execute([$post_id]);
        $count = (int)$stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo json_encode([
            'success' => true,
            'data' => [
                'count' => $count
            ]
        ]);
    } catch (Exception $e) {
        error_log("Error getting comments count: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'حدث خطأ أثناء جلب عدد التعليقات']);
    }
}

function handleToggleFavorite($pdo, $user_id) {
    error_log("handleToggleFavorite called with user_id: {$user_id}");
    $postId = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
    
    if (!$postId) {
        error_log("Invalid post_id in handleToggleFavorite");
        echo json_encode(['success' => false, 'message' => 'معرف المنشور غير صالح']);
        return;
    }
    
    error_log("Processing favorite toggle for post_id: {$postId}");
    
    try {
        // Check if favorites table exists, if not create it
        $tables_stmt = $pdo->query("SHOW TABLES LIKE 'favorites'");
        if ($tables_stmt->rowCount() == 0) {
            error_log("Creating favorites table");
            $pdo->exec("
                CREATE TABLE favorites (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    user_id INT UNSIGNED NOT NULL,
                    post_id INT UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_post_id (post_id),
                    UNIQUE KEY unique_favorite (user_id, post_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            error_log("Favorites table created successfully");
        }
        
        // Check if post exists
        $post_stmt = $pdo->prepare("SELECT id, media_url, media_type FROM posts WHERE id = ?");
        $post_stmt->execute([$postId]);
        $post = $post_stmt->fetch();
        
        if (!$post) {
            error_log("Post not found: {$postId}");
            echo json_encode(['success' => false, 'message' => 'المنشور غير موجود']);
            return;
        }
        
        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE post_id = ? AND user_id = ?");
        $stmt->execute([$postId, $user_id]);
        $existingFavorite = $stmt->fetch();
        
        if ($existingFavorite) {
            error_log("Removing existing favorite id: {$existingFavorite['id']}");
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
            $stmt->execute([$existingFavorite['id']]);
            $action = 'unfavorite';
            $is_favorited = false;
        } else {
            error_log("Adding new favorite for post_id: {$postId}, user_id: {$user_id}");
            $stmt = $pdo->prepare("INSERT INTO favorites (post_id, user_id) VALUES (?, ?)");
            $stmt->execute([$postId, $user_id]);
            $action = 'favorite';
            $is_favorited = true;
        }
        
        // Return detailed response with post info
        echo json_encode([
            'success' => true,
            'action' => $action,
            'data' => [
                'post_id' => $postId,
                'is_favorited' => $is_favorited,
                'media_url' => $post['media_url'],
                'media_type' => $post['media_type']
            ]
        ]);
        
        error_log("Favorite toggle successful. Action: {$action}");
    } catch (Exception $e) {
        error_log("Error in handleToggleFavorite: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?> 