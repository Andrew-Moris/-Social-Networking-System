<?php

?>

<div id="genericModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">عنوان النافذة</h3>
        </div>
        <div class="modal-body" id="modalBody">
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" id="modalCancelBtn">إلغاء</button>
            <button type="button" class="btn-primary" id="modalConfirmBtn">موافق</button>
        </div>
    </div>
</div>

<div id="editProfileModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">تعديل الملف الشخصي</h3>
        </div>
        <div class="modal-body">
            <form id="editProfileForm">
                <div class="mb-4">
                    <label for="editName" class="block text-sm font-medium text-slate-300 mb-2">الاسم الكامل</label>
                    <input type="text" id="editName" name="name" class="form-control" 
                           value="<?php echo htmlspecialchars($db_user['first_name'] . ' ' . $db_user['last_name']); ?>"
                           placeholder="أدخل اسمك الكامل">
                </div>
                
                <div class="mb-4">
                    <label for="editUsername" class="block text-sm font-medium text-slate-300 mb-2">اسم المستخدم</label>
                    <input type="text" id="editUsername" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($db_user['username']); ?>"
                           placeholder="أدخل اسم المستخدم">
                </div>
                
                <div class="mb-4">
                    <label for="editBio" class="block text-sm font-medium text-slate-300 mb-2">نبذة تعريفية</label>
                    <textarea id="editBio" name="bio" class="form-control" rows="4" 
                              placeholder="اكتب نبذة تعريفية عن نفسك"><?php echo htmlspecialchars($db_user['bio']); ?></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="editEmail" class="block text-sm font-medium text-slate-300 mb-2">البريد الإلكتروني</label>
                    <input type="email" id="editEmail" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($db_user['email']); ?>"
                           placeholder="أدخل بريدك الإلكتروني">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-slate-300 mb-2">الصورة الشخصية</label>
                    <div class="flex items-center gap-4">
                        <img src="<?php echo !empty($db_user['avatar_url']) ? htmlspecialchars($db_user['avatar_url']) : 'https://placehold.co/100x100/1a1f2e/ffffff?text=' . strtoupper(substr($db_user['username'], 0, 1)); ?>" 
                             alt="الصورة الشخصية الحالية" 
                             class="w-20 h-20 rounded-full object-cover">
                        <div class="flex-1">
                            <input type="file" id="editAvatar" name="avatar" class="form-control" 
                                   accept="image/*">
                            <p class="text-xs text-slate-400 mt-1">
                                الصيغ المدعومة: JPG, PNG, GIF. الحد الأقصى للحجم: 5MB
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="editPassword" class="block text-sm font-medium text-slate-300 mb-2">كلمة المرور الجديدة (اختياري)</label>
                    <input type="password" id="editPassword" name="password" class="form-control" 
                           placeholder="اتركه فارغاً إذا لم ترد تغيير كلمة المرور">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('editProfileModal')">إلغاء</button>
            <button type="button" class="btn-primary" onclick="saveProfileChanges()">حفظ التغييرات</button>
        </div>
    </div>
</div>

<div id="deletePostModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">تأكيد حذف المنشور</h3>
        </div>
        <div class="modal-body">
            <p class="text-slate-300">
                هل أنت متأكد أنك تريد حذف هذا المنشور؟
                <br>
                <span class="text-red-400">لا يمكن التراجع عن هذا الإجراء.</span>
            </p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('deletePostModal')">إلغاء</button>
            <button type="button" class="btn-danger" id="confirmDeletePostBtn">نعم، احذف المنشور</button>
        </div>
    </div>
</div>

<div id="addLocationModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">إضافة موقع</h3>
        </div>
        <div class="modal-body">
            <div class="mb-4">
                <label for="locationInput" class="block text-sm font-medium text-slate-300 mb-2">اسم الموقع</label>
                <input type="text" id="locationInput" class="form-control" 
                       placeholder="مثال: القاهرة, مصر">
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('addLocationModal')">إلغاء</button>
            <button type="button" class="btn-primary" onclick="addLocationToPost()">إضافة</button>
        </div>
    </div>
</div>

<div id="sharePostModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title">مشاركة المنشور</h3>
        </div>
        <div class="modal-body">
            <div class="mb-4">
                <label class="block text-sm font-medium text-slate-300 mb-2">رابط المنشور</label>
                <div class="flex gap-2">
                    <input type="text" id="postShareLink" class="form-control" readonly>
                    <button type="button" class="btn-primary" onclick="copyShareLink()">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
            </div>
            <div class="flex justify-center gap-4 mt-6">
                <button type="button" class="action-btn" onclick="shareToSocial('facebook')">
                    <i class="bi bi-facebook"></i>
                    Facebook
                </button>
                <button type="button" class="action-btn" onclick="shareToSocial('twitter')">
                    <i class="bi bi-twitter"></i>
                    Twitter
                </button>
                <button type="button" class="action-btn" onclick="shareToSocial('whatsapp')">
                    <i class="bi bi-whatsapp"></i>
                    WhatsApp
                </button>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('sharePostModal')">إغلاق</button>
        </div>
    </div>
</div> 