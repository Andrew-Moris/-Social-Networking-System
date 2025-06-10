# Social Networking Platform (منصة التواصل الاجتماعي)

A modern social networking platform with comprehensive features and Arabic language support.

منصة تواصل اجتماعي حديثة مع ميزات شاملة ودعم للغة العربية.

## Features (المميزات)

### User Management (إدارة المستخدمين)
- User registration and authentication (تسجيل المستخدمين والمصادقة)
- Profile management with avatars (إدارة الملف الشخصي مع الصور الرمزية)
- Follow/Unfollow system (نظام المتابعة وإلغاء المتابعة)

### Posts and Content (المنشورات والمحتوى)
- Create text posts with images (إنشاء منشورات نصية مع صور)
- Delete own posts (حذف المنشورات الخاصة)
- Privacy settings for posts (إعدادات الخصوصية للمنشورات)

### Social Interactions (التفاعلات الاجتماعية)
- Like/Unlike posts (الإعجاب/إلغاء الإعجاب بالمنشورات)
- Comment on posts (التعليق على المنشورات)
- Share posts (مشاركة المنشورات)
- Bookmark favorite posts (حفظ المنشورات المفضلة)

### Notifications (الإشعارات)
- Real-time notifications (إشعارات فورية)
- Activity tracking (تتبع النشاطات)
- Interactive alerts (تنبيهات تفاعلية)

## Technical Requirements (المتطلبات التقنية)

### Server Requirements (متطلبات الخادم)
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled
- GD Library for image processing

### Client Requirements (متطلبات المستخدم)
- Modern web browser (Chrome, Firefox, Safari, Edge)
- JavaScript enabled
- Minimum screen resolution: 320px (mobile responsive)

## Installation (التثبيت)

1. Clone the repository:
```bash
git clone [repository-url]
cd WEP
```

2. Set up the database:
```bash
# Import the database schema
php setup_database.php

# Set up social tables
php setup_social_tables.php
```

3. Configure the application:
- Copy `config.example.php` to `config.php`
- Update database credentials in `config.php`
- Set up your web server configuration

4. Set up file permissions:
```bash
chmod 755 -R ./
chmod 777 -R ./uploads
chmod 777 -R ./logs
```

## Configuration (الإعداد)

### Database Configuration (إعداد قاعدة البيانات)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'your_database');
```

### File Upload Configuration (إعداد رفع الملفات)
```php
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);
```

## Directory Structure (هيكل المجلدات)

```
WEP/
├── api/            # API endpoints
├── assets/         # Static assets (CSS, JS, images)
├── config/         # Configuration files
├── controllers/    # Application controllers
├── includes/       # PHP includes
├── js/            # JavaScript files
├── models/        # Data models
├── uploads/       # User uploads
└── vendor/        # Dependencies
```

## Security Features (ميزات الأمان)

- CSRF protection (حماية من هجمات CSRF)
- SQL injection prevention (الحماية من حقن SQL)
- XSS protection (الحماية من هجمات XSS)
- Secure password hashing (تشفير كلمات المرور)
- Input validation (التحقق من المدخلات)
- File upload security (أمان رفع الملفات)

## API Documentation (توثيق واجهة البرمجة)

### Authentication (المصادقة)
```
POST /api/auth/login
POST /api/auth/register
POST /api/auth/logout
```

### Posts (المنشورات)
```
GET /api/posts
POST /api/posts
DELETE /api/posts/{id}
```

### Social Interactions (التفاعلات الاجتماعية)
```
POST /api/social/like
POST /api/social/comment
POST /api/social/share
```

## Testing (الاختبار)

Run the test suite:
```bash
php test_features.php
php test_social_api.php
php test_chat_functionality.php
```

## Troubleshooting (استكشاف الأخطاء وإصلاحها)

Common issues and solutions:

1. Database Connection Issues:
   - Check database credentials
   - Verify MySQL service is running
   - Check database permissions

2. Upload Problems:
   - Verify directory permissions
   - Check file size limits
   - Validate file types

3. Social Features:
   - Clear browser cache
   - Check JavaScript console
   - Verify API endpoints
