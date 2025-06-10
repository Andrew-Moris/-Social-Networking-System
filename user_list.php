<?php

function getUniqueUsers($pdo, $current_user_id) {
    try {
        $query = "SELECT DISTINCT id, username, first_name, last_name, email, avatar_url, last_active 
                 FROM users 
                 WHERE id != ? 
                 ORDER BY username ASC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$current_user_id]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Found " . count($users) . " unique users");
        
        return $users;
    } catch (Exception $e) {
        error_log("Error retrieving unique users: " . $e->getMessage());
        return [];
    }
}

function renderUserList($users) {
    if (empty($users)) {
        echo '<div class="no-users">لا يوجد مستخدمين</div>';
        return;
    }
    
    $rendered_user_ids = [];
    
    foreach ($users as $user) {
        if (in_array($user['id'], $rendered_user_ids)) {
            continue;
        }
        
        $rendered_user_ids[] = $user['id'];
        
        ?>
        <div class="user-item" data-user-id="<?php echo $user['id']; ?>">
            <div class="user-item-content">
                <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?php echo $user['avatar_url']; ?>" alt="<?php echo $user['username']; ?>" class="user-avatar">
                <?php else: ?>
                    <div class="default-avatar"><?php echo strtoupper(substr($user['username'], 0, 1)); ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="user-name-container">
                        <span class="user-name">
                            <?php 
                            if (!empty($user['first_name']) && !empty($user['last_name'])) {
                                echo $user['first_name'] . ' ' . $user['last_name'];
                            } else {
                                echo $user['username'];
                            }
                            ?>
                        </span>
                        <div class="chat-time" data-user-id="<?php echo $user['id']; ?>"></div>
                    </div>
                    <div class="chat-preview">
                        <div class="last-message" data-user-id="<?php echo $user['id']; ?>"></div>
                    </div>
                </div>
            </div>
            <a href="profile.php?username=<?php echo urlencode($user['username']); ?>" 
               class="view-profile-link" 
               title="عرض الملف الشخصي">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </a>
        </div>
        <?php
    }
}
?>
