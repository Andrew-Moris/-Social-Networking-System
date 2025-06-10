<?php

?>
<footer class="main-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section">
                <h3>منصة التواصل الاجتماعي</h3>
                <p>تواصل مع الأصدقاء وشارك لحظاتك المميزة</p>
            </div>
            <div class="footer-section">
                <h3>روابط مهمة</h3>
                <ul>
                    <li><a href="discover.php">استكشف</a></li>
                    <li><a href="friends.php">الأصدقاء</a></li>
                    <li><a href="messages.php">الرسائل</a></li>
                    <li><a href="settings.php">الإعدادات</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>مساعدة</h3>
                <ul>
                    <li><a href="help.php">مركز المساعدة</a></li>
                    <li><a href="privacy.php">الخصوصية</a></li>
                    <li><a href="terms.php">الشروط والأحكام</a></li>
                    <li><a href="contact.php">اتصل بنا</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>جميع الحقوق محفوظة &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>
</footer>

<style>
.main-footer {
    background: linear-gradient(to bottom, #ffffff, #f8f9fa);
    padding: 3rem 0 1rem;
    margin-top: 3rem;
    border-top: 1px solid var(--border-color);
}

.footer-content {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
    margin-bottom: 2rem;
}

.footer-section h3 {
    color: var(--primary-color);
    margin-bottom: 1rem;
    font-size: 1.2rem;
    font-weight: bold;
}

.footer-section p {
    color: var(--secondary-color);
    line-height: 1.6;
}

.footer-section ul {
    list-style: none;
    padding: 0;
}

.footer-section ul li {
    margin-bottom: 0.8rem;
}

.footer-section ul li a {
    color: var(--dark-color);
    text-decoration: none;
    transition: all 0.3s ease;
    display: block;
    padding: 0.3rem 0;
}

.footer-section ul li a:hover {
    color: var(--primary-color);
    transform: translateX(-5px);
}

.footer-bottom {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid var(--border-color);
    color: var(--secondary-color);
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        text-align: center;
    }

    .footer-section ul li a:hover {
        transform: none;
    }
}
</style>
    </div>
    <script src="/WEP/assets/js/profile.js"></script>
    <?php if (isset($extra_js) && !empty($extra_js)): ?>
    <script src="<?php echo $extra_js; ?>"></script>
    <?php endif; ?>
</body>
</html>
