<?php

if (!isset($suggested_users) || !is_array($suggested_users)) {
    $suggested_users = [];
}
?>

<div class="right-sidebar">
    <div class="sidebar-card">
        <h5 class="sidebar-title">اقتراحات للمتابعة</h5>
        
        <?php if (!empty($suggested_users)): ?>
            <ul class="user-suggestions">
                <?php foreach ($suggested_users as $user): ?>
                    <li class="suggestion-item">
                        <img src="<?php echo !empty($user['avatar_url']) ? $user['avatar_url'] : '/WEP/assets/images/default-avatar.png'; ?>" 
                             class="user-avatar" alt="<?php echo htmlspecialchars($user['username']); ?>">
                        <div class="suggestion-info">
                            <h6 class="suggestion-name">
                                <a href="/WEP/index.php?username=<?php echo urlencode($user['username']); ?>">
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </a>
                            </h6>
                            <?php if (!empty($user['mutual_friends'])): ?>
                                <small class="text-muted"><?php echo $user['mutual_friends']; ?> أصدقاء مشتركين</small>
                            <?php endif; ?>
                        </div>
                        <div class="suggestion-actions">
                            <button class="follow-btn" data-user-id="<?php echo $user['id']; ?>">متابعة</button>
                            <button class="dismiss-btn" data-user-id="<?php echo $user['id']; ?>">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-center py-3">لا توجد اقتراحات حالياً</p>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-card">
        <h5 class="sidebar-title">طلبات المتابعة <span class="badge bg-primary">3</span></h5>
        <ul class="user-suggestions">
            <li class="suggestion-item">
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar">
                <div class="suggestion-info">
                    <h6 class="suggestion-name">سارة أحمد</h6>
                    <small class="text-muted">منذ 2 ساعة</small>
                </div>
                <div class="suggestion-actions">
                    <button class="follow-btn">قبول</button>
                    <button class="dismiss-btn"><i class="fas fa-times"></i></button>
                </div>
            </li>
            <li class="suggestion-item">
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar">
                <div class="suggestion-info">
                    <h6 class="suggestion-name">محمد علي</h6>
                    <small class="text-muted">منذ 5 ساعات</small>
                </div>
                <div class="suggestion-actions">
                    <button class="follow-btn">قبول</button>
                    <button class="dismiss-btn"><i class="fas fa-times"></i></button>
                </div>
            </li>
            <li class="suggestion-item">
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar">
                <div class="suggestion-info">
                    <h6 class="suggestion-name">فاطمة محمود</h6>
                    <small class="text-muted">منذ يوم</small>
                </div>
                <div class="suggestion-actions">
                    <button class="follow-btn">قبول</button>
                    <button class="dismiss-btn"><i class="fas fa-times"></i></button>
                </div>
            </li>
        </ul>
        <div class="text-center mt-2">
            <a href="/WEP/friend-requests.php" class="text-primary">عرض كل الطلبات</a>
        </div>
    </div>
    
    <div class="sidebar-card">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="sidebar-title mb-0">جهات الاتصال</h5>
            <div>
                <button class="btn btn-sm btn-light"><i class="fas fa-search"></i></button>
                <button class="btn btn-sm btn-light"><i class="fas fa-ellipsis-h"></i></button>
            </div>
        </div>
        
        <ul class="contacts-list">
            <li class="contact-item">
                <div class="contact-status"></div>
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar" style="width: 30px; height: 30px;">
                <div class="contact-info">
                    <h6 class="contact-name">أحمد محمد</h6>
                </div>
            </li>
            <li class="contact-item">
                <div class="contact-status"></div>
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar" style="width: 30px; height: 30px;">
                <div class="contact-info">
                    <h6 class="contact-name">منى السيد</h6>
                </div>
            </li>
            <li class="contact-item">
                <div class="contact-status"></div>
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar" style="width: 30px; height: 30px;">
                <div class="contact-info">
                    <h6 class="contact-name">خالد محمود</h6>
                </div>
            </li>
            <li class="contact-item">
                <div class="contact-status"></div>
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar" style="width: 30px; height: 30px;">
                <div class="contact-info">
                    <h6 class="contact-name">سارة أحمد</h6>
                </div>
            </li>
            <li class="contact-item">
                <div class="contact-status"></div>
                <img src="/WEP/assets/images/default-avatar.png" class="user-avatar" style="width: 30px; height: 30px;">
                <div class="contact-info">
                    <h6 class="contact-name">محمد علي</h6>
                </div>
            </li>
        </ul>
    </div>
    
    <div class="sidebar-card">
        <div class="footer-links">
            <a href="#" class="footer-link">من نحن</a>
            <a href="#" class="footer-link">المساعدة</a>
            <a href="#" class="footer-link">سياسة الخصوصية</a>
            <a href="#" class="footer-link">الشروط والأحكام</a>
            <a href="#" class="footer-link">الإعلان</a>
            <a href="#" class="footer-link">الوظائف</a>
            <a href="#" class="footer-link">المطورين</a>
            <a href="#" class="footer-link">تواصل معنا</a>
        </div>
        <div class="copyright">
            <?php echo APP_NAME; ?> &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</div>
