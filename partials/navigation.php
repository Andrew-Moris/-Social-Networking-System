<?php

?>
<nav class="vertical-nav">
    <a href="#" data-target="profile-content" class="nav-link <?php echo $active_section == 'profile' ? 'active' : ''; ?>">
        <i class="bi bi-person-workspace mr-2 text-lg"></i>
        <span>الملف الشخصي</span>
    </a>
    <a href="#" data-target="feed-content" class="nav-link <?php echo $active_section == 'feed' ? 'active' : ''; ?>">
        <i class="bi bi-journals mr-2 text-lg"></i>
        <span>المنشورات</span>
    </a>
    <a href="#" data-target="following-content" class="nav-link <?php echo $active_section == 'following' ? 'active' : ''; ?>">
        <i class="bi bi-person-check mr-2 text-lg"></i>
        <span>المتابَعون</span>
    </a>
    <a href="#" data-target="chat-content" class="nav-link <?php echo $active_section == 'chat' ? 'active' : ''; ?>">
        <i class="bi bi-chat-square-heart-fill mr-2 text-lg"></i>
        <span>المحادثات</span>
    </a>
    <a href="#" data-target="friends-content" class="nav-link <?php echo $active_section == 'friends' ? 'active' : ''; ?>">
        <i class="bi bi-people mr-2 text-lg"></i>
        <span>الأصدقاء</span>
    </a>
    <a href="/WEP/logout.php" id="logout-button" class="nav-link !text-red-400 hover:!bg-red-500/20 hover:!text-red-300 mt-auto" title="تسجيل الخروج">
        <i class="bi bi-box-arrow-right mr-2 text-lg"></i>
        <span>تسجيل الخروج</span>
    </a>
</nav>
