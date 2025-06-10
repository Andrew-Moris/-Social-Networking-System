<?php


class User {
    private $db;

    /**
     * @param PDO 
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param int 
     * @return array|false 
     */
    public function findById($userId) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by ID: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param string
     * @return array|false 
     */
    public function findByUsername($username) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error finding user by username: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int 
     * @param int 
     * @param int 
     * @return array 
     */
    public function getUserPosts($userId, $page = 1, $postsPerPage = 10) {
        try {
            $offset = ($page - 1) * $postsPerPage;
            
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.display_name, u.avatar_url,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 1) as likes_count,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 0) as dislikes_count,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $postsPerPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting user posts: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param int 
     * @return int 
     */
    public function getUserPostsCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM posts WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting user posts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param int 
     * @return int 
     */
    public function getFollowersCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM followers WHERE following_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting followers: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param int
     * @return int
     */
    public function getFollowingCount($userId) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM followers WHERE follower_id = :user_id");
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Error counting following: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * @param int
     * @return array 
     */
    public function getFriends($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*
                FROM users u
                JOIN followers f1 ON u.id = f1.following_id
                JOIN followers f2 ON u.id = f2.follower_id
                WHERE f1.follower_id = :user_id AND f2.following_id = :user_id
                ORDER BY u.display_name, u.username
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting friends: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param int
     * @param array 
     * @return bool 
     */
    public function updateProfile($userId, $data) {
        try {
            $sql = "UPDATE users SET ";
            $params = [];
            
            if (isset($data['display_name'])) {
                $sql .= "display_name = :display_name, ";
                $params[':display_name'] = $data['display_name'];
            }
            
            if (isset($data['bio'])) {
                $sql .= "bio = :bio, ";
                $params[':bio'] = $data['bio'];
            }
            
            if (isset($data['avatar_url'])) {
                $sql .= "avatar_url = :avatar_url, ";
                $params[':avatar_url'] = $data['avatar_url'];
            }
            
            if (isset($data['cover_url'])) {
                $sql .= "cover_url = :cover_url, ";
                $params[':cover_url'] = $data['cover_url'];
            }
            
            $sql = rtrim($sql, ', ') . " WHERE id = :user_id";
            $params[':user_id'] = $userId;
                        if (count($params) <= 1) {
                return true;
            }
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error updating profile: " . $e->getMessage());
            return false;
        }
    }
}
