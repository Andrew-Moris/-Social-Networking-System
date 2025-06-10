<?php


class ProfileController {
    private $db;
    private $userModel;
    private $postModel;

    /**
     * @param PDO 
     */
    public function __construct($db) {
        $this->db = $db;
        
        require_once 'models/User.php';
        require_once 'models/Post.php';
        
        $this->userModel = new User($db);
        $this->postModel = new Post($db);
    }

    /**
     * @param int 
     * @return array
     */
    public function getProfile($userId) {
        $data = [];
        
        $data['user'] = $this->userModel->findById($userId);
        
        if (!$data['user']) {
            return ['error' => 'User not found'];
        }
        
        $data['posts'] = $this->userModel->getUserPosts($userId, 1, POSTS_PER_PAGE);
        
        $data['total_posts_count'] = $this->userModel->getUserPostsCount($userId);
        $data['followers_count'] = $this->userModel->getFollowersCount($userId);
        $data['following_count'] = $this->userModel->getFollowingCount($userId);
        
        $data['friends'] = $this->userModel->getFriends($userId);
        
        $data['is_current_user_profile'] = ($_SESSION['user_id'] == $userId);
        
        return $data;
    }

    /**
     * @param int
     * @param array 
     * @param array 
     * @return array 
     */
    public function updateProfile($userId, $formData, $files = []) {
        if ($_SESSION['user_id'] != $userId) {
            return ['success' => false, 'message' => 'لا يمكنك تعديل ملف شخصي لا يخصك'];
        }
        
        $updateData = [];
        
        if (isset($formData['display_name']) && !empty($formData['display_name'])) {
            $updateData['display_name'] = htmlspecialchars(trim($formData['display_name']));
        }
        
        if (isset($formData['bio'])) {
            $updateData['bio'] = htmlspecialchars(trim($formData['bio']));
        }
        
        if (isset($files['avatar']) && $files['avatar']['error'] === UPLOAD_ERR_OK) {
            $avatarUrl = $this->processImageUpload($files['avatar'], 'avatars', $userId);
            if ($avatarUrl) {
                $updateData['avatar_url'] = $avatarUrl;
            }
        }
        
        if (isset($files['cover']) && $files['cover']['error'] === UPLOAD_ERR_OK) {
            $coverUrl = $this->processImageUpload($files['cover'], 'covers', $userId);
            if ($coverUrl) {
                $updateData['cover_url'] = $coverUrl;
            }
        }
        
        $success = $this->userModel->updateProfile($userId, $updateData);
        
        if ($success) {
            return ['success' => true, 'message' => 'تم تحديث الملف الشخصي بنجاح'];
        } else {
            return ['success' => false, 'message' => 'حدث خطأ أثناء تحديث الملف الشخصي'];
        }
    }

    /**
     * @param array 
     * @param string 
     * @param int 
     * @return string|false
     */
    private function processImageUpload($file, $folder, $userId) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; 
    
        if (!in_array($file['type'], $allowedTypes)) {
            error_log("Invalid file type: {$file['type']}");
            return false;
        }
        
        if ($file['size'] > $maxSize) {
            error_log("File too large: {$file['size']}");
            return false;
        }
        
        $uploadDir = "uploads/{$folder}/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = $userId . '_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            return $filePath;
        }
        
        error_log("Failed to move uploaded file");
        return false;
    }
}
