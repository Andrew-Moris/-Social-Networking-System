<?php

class Post {
    private $db;

    /**
     * @param PDO 
     */
    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @param int 
     * @param string
     * @param string|null 
     * @return int|false 
     */
    public function create($userId, $content, $imageUrl = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO posts (user_id, content, image_url, created_at)
                VALUES (:user_id, :content, :image_url, NOW())
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            $stmt->bindParam(':image_url', $imageUrl, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error creating post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int 
     * @return array|false 
     */
    public function getById($postId) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.display_name, u.avatar_url,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 1) as likes_count,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 0) as dislikes_count,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count
                FROM posts p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = :post_id
            ");
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int 
     * @param int 
     * @return bool 
     */
    public function delete($postId, $userId) {
        try {
            $stmt = $this->db->prepare("SELECT user_id FROM posts WHERE id = :post_id");
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$post || $post['user_id'] != $userId) {
                return false;
            }
            
            $stmt = $this->db->prepare("DELETE FROM posts WHERE id = :post_id");
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error deleting post: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int 
     * @param int 
     * @param bool 
     * @return bool 
     */
    public function likeOrDislike($postId, $userId, $isLike = true) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM post_likes 
                WHERE post_id = :post_id AND user_id = :user_id
            ");
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $existingReaction = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingReaction) {
                if ($existingReaction['is_like'] == $isLike) {
                    $stmt = $this->db->prepare("
                        DELETE FROM post_likes 
                        WHERE post_id = :post_id AND user_id = :user_id
                    ");
                } else {
                    $stmt = $this->db->prepare("
                        UPDATE post_likes 
                        SET is_like = :is_like 
                        WHERE post_id = :post_id AND user_id = :user_id
                    ");
                    $isLikeValue = $isLike ? 1 : 0;
                    $stmt->bindParam(':is_like', $isLikeValue, PDO::PARAM_INT);
                }
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO post_likes (post_id, user_id, is_like)
                    VALUES (:post_id, :user_id, :is_like)
                ");
                $isLikeValue = $isLike ? 1 : 0;
                $stmt->bindParam(':is_like', $isLikeValue, PDO::PARAM_INT);
            }
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Error processing like/dislike: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int
     * @param int 
     * @param int
     * @return array 
     */
    public function getFeed($userId, $page = 1, $postsPerPage = 10) {
        try {
            $offset = ($page - 1) * $postsPerPage;
            
            $stmt = $this->db->prepare("
                SELECT p.*, u.username, u.display_name, u.avatar_url,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 1) as likes_count,
                       (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND is_like = 0) as dislikes_count,
                       (SELECT COUNT(*) FROM comments WHERE post_id = p.id) as comments_count,
                       (SELECT is_like FROM post_likes WHERE post_id = p.id AND user_id = :viewing_user) as user_reaction
                FROM posts p
                JOIN users u ON p.user_id = u.id
                LEFT JOIN followers f ON p.user_id = f.following_id AND f.follower_id = :user_id
                WHERE f.following_id IS NOT NULL OR p.user_id = :user_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset
            ");
            
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':viewing_user', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $postsPerPage, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting feed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param int 
     * @param int
     * @param string 
     * @return int|false
     */
    public function addComment($postId, $userId, $content) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO comments (post_id, user_id, content, created_at)
                VALUES (:post_id, :user_id, :content, NOW())
            ");
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':content', $content, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error adding comment: " . $e->getMessage());
            return false;
        }
    }

    /**
     * @param int 
     * @return array 
     */
    public function getComments($postId) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, u.username, u.display_name, u.avatar_url
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.post_id = :post_id
                ORDER BY c.created_at ASC
            ");
            
            $stmt->bindParam(':post_id', $postId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error getting comments: " . $e->getMessage());
            return [];
        }
    }
}
