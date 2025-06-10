<?php

if (!isset($posts) || !is_array($posts)) {
    $posts = [];
}

$current_user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
?>

<div class="feed-container">
    <?php if ($is_logged_in): ?>
    <div class="create-post">
        <div class="create-post-header">
            <img src="<?php echo isset($_SESSION['avatar_url']) && !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                 class="user-avatar" alt="<?php echo htmlspecialchars($current_username); ?>">
            <div class="create-post-input" id="create-post-trigger">ماذا يدور في ذهنك؟</div>
        </div>
        <div class="create-post-actions">
            <button class="post-action-btn" id="photo-video-btn">
                <i class="fas fa-image text-success"></i>
                <span>صورة/فيديو</span>
            </button>
            <button class="post-action-btn" id="tag-friends-btn">
                <i class="fas fa-user-tag text-primary"></i>
                <span>الإشارة إلى صديق</span>
            </button>
            <button class="post-action-btn" id="feeling-btn">
                <i class="fas fa-smile text-warning"></i>
                <span>المشاعر</span>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (empty($posts)): ?>
        <div class="empty-feed">
            <div class="text-center py-5">
                <i class="fas fa-newspaper fa-4x mb-3 text-muted"></i>
                <h4>لا توجد منشورات لعرضها</h4>
                <p class="text-muted">ابدأ بمتابعة أشخاص لرؤية منشوراتهم هنا، أو قم بنشر أول منشور لك!</p>
                <?php if ($is_logged_in): ?>
                    <button class="btn btn-primary" id="first-post-btn">إنشاء أول منشور</button>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
            <div class="post" id="post-<?php echo $post['id']; ?>">
                <div class="post-header">
                    <img src="<?php echo !empty($post['avatar_url']) ? $post['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                         class="user-avatar" alt="<?php echo htmlspecialchars($post['username']); ?>">
                    <div class="post-user-info">
                        <h6 class="post-username">
                            <a href="/WEP/index.php?username=<?php echo urlencode($post['username']); ?>">
                                <?php echo htmlspecialchars($post['username']); ?>
                            </a>
                        </h6>
                        <div class="post-time"><?php echo date('d M Y H:i', strtotime($post['created_at'])); ?></div>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" id="post-options-<?php echo $post['id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-h"></i>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="post-options-<?php echo $post['id']; ?>">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-bookmark me-2"></i> حفظ المنشور</a></li>
                            <?php if ($current_user_id == $post['user_id']): ?>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-edit me-2"></i> تعديل المنشور</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-trash-alt me-2"></i> حذف المنشور</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-bell-slash me-2"></i> إيقاف الإشعارات</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-times me-2"></i> إلغاء متابعة <?php echo htmlspecialchars($post['username']); ?></a></li>
                            <?php endif; ?>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-flag me-2"></i> الإبلاغ عن المنشور</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="post-content">
                    <?php if (!empty($post['content'])): ?>
                        <div class="post-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($post['image_url'])): ?>
                        <img src="<?php echo htmlspecialchars($post['image_url']); ?>" class="post-image" alt="صورة المنشور">
                    <?php endif; ?>
                </div>
                
                <div class="post-stats">
                    <div class="post-reactions">
                        <?php 
                            $likes_count = isset($post['likes_count']) ? $post['likes_count'] : rand(0, 50);
                            $dislikes_count = isset($post['dislikes_count']) ? $post['dislikes_count'] : rand(0, 10);
                            $comments_count = isset($post['comments_count']) ? $post['comments_count'] : rand(0, 20);
                            
                            $total_reactions = $likes_count + $dislikes_count;
                        ?>
                        
                        <?php if ($total_reactions > 0): ?>
                            <i class="fas fa-thumbs-up text-primary"></i>
                            <i class="fas fa-thumbs-down text-danger"></i>
                            <span><?php echo $total_reactions; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="post-comments-count">
                        <?php if ($comments_count > 0): ?>
                            <span><?php echo $comments_count; ?> تعليقات</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="post-actions">
                    <button class="post-action like-btn <?php echo (isset($post['user_reaction']) && $post['user_reaction'] == 'like') ? 'liked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" data-action="like">
                        <i class="fas fa-thumbs-up"></i>
                        <span>إعجاب</span>
                    </button>
                    <button class="post-action dislike-btn <?php echo (isset($post['user_reaction']) && $post['user_reaction'] == 'dislike') ? 'disliked' : ''; ?>" data-post-id="<?php echo $post['id']; ?>" data-action="dislike">
                        <i class="fas fa-thumbs-down"></i>
                        <span>عدم إعجاب</span>
                    </button>
                    <button class="post-action comment-btn" data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-comment-alt"></i>
                        <span>تعليق</span>
                    </button>
                    <button class="post-action share-btn" data-post-id="<?php echo $post['id']; ?>">
                        <i class="fas fa-share"></i>
                        <span>مشاركة</span>
                    </button>
                </div>
                
                <div class="post-comments" id="comments-<?php echo $post['id']; ?>" style="display: none;">
                    <?php 
                        $fake_comments = [
                            [
                                'id' => 1,
                                'user_id' => 2,
                                'username' => 'أحمد محمد',
                                'avatar_url' => '/WEP/assets/images/default-avatar.png',
                                'content' => 'تعليق رائع على هذا المنشور!',
                                'created_at' => '2025-05-23 14:30:00',
                                'likes' => 3
                            ],
                            [
                                'id' => 2,
                                'user_id' => 3,
                                'username' => 'سارة أحمد',
                                'avatar_url' => '/WEP/assets/images/default-avatar.png',
                                'content' => 'أتفق معك تماماً، شكراً للمشاركة.',
                                'created_at' => '2025-05-23 15:45:00',
                                'likes' => 1
                            ]
                        ];
                    ?>
                    
                    <?php foreach ($fake_comments as $comment): ?>
                        <div class="comment" id="comment-<?php echo $comment['id']; ?>">
                            <img src="<?php echo $comment['avatar_url']; ?>" class="user-avatar" style="width: 32px; height: 32px;">
                            <div class="comment-content">
                                <div class="comment-bubble">
                                    <h6 class="comment-username">
                                        <a href="/WEP/index.php?username=<?php echo urlencode($comment['username']); ?>">
                                            <?php echo htmlspecialchars($comment['username']); ?>
                                        </a>
                                    </h6>
                                    <div class="comment-text"><?php echo htmlspecialchars($comment['content']); ?></div>
                                </div>
                                <div class="comment-actions">
                                    <button class="comment-action-btn" data-action="like" data-comment-id="<?php echo $comment['id']; ?>">إعجاب</button>
                                    <button class="comment-action-btn" data-action="reply" data-comment-id="<?php echo $comment['id']; ?>">رد</button>
                                    <span class="comment-time"><?php echo date('d M H:i', strtotime($comment['created_at'])); ?></span>
                                    <?php if ($comment['likes'] > 0): ?>
                                        <span class="comment-likes">
                                            <i class="fas fa-thumbs-up text-primary"></i>
                                            <?php echo $comment['likes']; ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ($is_logged_in): ?>
                        <div class="add-comment">
                            <img src="<?php echo isset($_SESSION['avatar_url']) && !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                                 class="user-avatar" style="width: 32px; height: 32px;">
                            <div class="comment-input-container">
                                <input type="text" class="comment-input" placeholder="اكتب تعليقاً..." data-post-id="<?php echo $post['id']; ?>">
                                <button class="comment-submit-btn" data-post-id="<?php echo $post['id']; ?>">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <div class="text-center my-4">
            <button class="btn btn-light load-more-btn" id="load-more-posts" data-page="<?php echo isset($page) ? $page + 1 : 2; ?>">
                تحميل المزيد من المنشورات
            </button>
        </div>
    <?php endif; ?>
</div>

<div class="modal" id="create-post-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">إنشاء منشور جديد</h5>
            <button class="close-modal" id="close-create-post">&times;</button>
        </div>
        <div class="modal-body">
            <div class="create-post-user mb-3">
                <img src="<?php echo isset($_SESSION['avatar_url']) && !empty($_SESSION['avatar_url']) ? $_SESSION['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                     class="user-avatar" alt="<?php echo htmlspecialchars($current_username); ?>">
                <div>
                    <h6 class="mb-0"><?php echo htmlspecialchars($current_username); ?></h6>
                    <select class="form-select form-select-sm mt-1">
                        <option value="public">عام</option>
                        <option value="friends">الأصدقاء</option>
                        <option value="private">خاص</option>
                    </select>
                </div>
            </div>
            <textarea class="form-control mb-3" id="post-content-textarea" rows="5" placeholder="ماذا يدور في ذهنك؟"></textarea>
            
            <div class="d-none" id="post-image-preview-container">
                <div class="position-relative mb-3">
                    <img src="" id="post-image-preview" class="img-fluid rounded" alt="معاينة الصورة">
                    <button class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2" id="remove-post-image">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <div class="post-attachments">
                <button class="btn btn-light" id="modal-photo-btn">
                    <i class="fas fa-image text-success"></i>
                    <span>إضافة صورة</span>
                </button>
                <button class="btn btn-light" id="modal-tag-btn">
                    <i class="fas fa-user-tag text-primary"></i>
                    <span>إشارة لصديق</span>
                </button>
                <button class="btn btn-light" id="modal-feeling-btn">
                    <i class="fas fa-smile text-warning"></i>
                    <span>المشاعر</span>
                </button>
                <button class="btn btn-light" id="modal-location-btn">
                    <i class="fas fa-map-marker-alt text-danger"></i>
                    <span>الموقع</span>
                </button>
            </div>
            
            <input type="file" id="post-image-input" accept="image/*" style="display: none;">
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" id="cancel-post">إلغاء</button>
            <button class="btn btn-primary" id="submit-post">نشر</button>
        </div>
    </div>
</div>
