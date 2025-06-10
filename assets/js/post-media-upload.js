

document.addEventListener('DOMContentLoaded', function() {
    const postForm = document.getElementById('post-form');
    const mediaInput = document.getElementById('post-media');
    const mediaPreview = document.getElementById('media-preview');
    const removeMediaBtn = document.getElementById('remove-media');
    const postContent = document.getElementById('post-content');
    const submitBtn = document.getElementById('submit-post');
    const progressBar = document.getElementById('upload-progress');
    const progressContainer = document.getElementById('progress-container');
    
    let uploadedMediaUrl = '';
    let isUploading = false;
    
    if (mediaInput) {
        mediaInput.addEventListener('change', handleMediaSelect);
    }
    
    if (removeMediaBtn) {
        removeMediaBtn.addEventListener('click', removeMedia);
    }
    
    if (postForm) {
        postForm.addEventListener('submit', handlePostSubmit);
    }

    function handleMediaSelect(event) {
        const file = event.target.files[0];
        if (!file) return;
        
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm'];
        if (!allowedTypes.includes(file.type)) {
            alert('نوع الملف غير مدعوم. الرجاء اختيار صورة أو فيديو.');
            mediaInput.value = '';
            return;
        }
        
        const maxSize = 10 * 1024 * 1024;
        if (file.size > maxSize) {
            alert('حجم الملف كبير جدًا. الحد الأقصى هو 10 ميجابايت.');
            mediaInput.value = '';
            return;
        }
        
        showMediaPreview(file);
        
        uploadMedia(file);
    }

    function showMediaPreview(file) {
        if (!mediaPreview) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            mediaPreview.innerHTML = '';
            
            if (file.type.startsWith('image/')) {
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'max-w-full max-h-64 rounded-lg';
                mediaPreview.appendChild(img);
            } else if (file.type.startsWith('video/')) {
                const video = document.createElement('video');
                video.src = e.target.result;
                video.className = 'max-w-full max-h-64 rounded-lg';
                video.controls = true;
                mediaPreview.appendChild(video);
            }
            
            if (removeMediaBtn) {
                removeMediaBtn.style.display = 'block';
            }
            
            mediaPreview.style.display = 'block';
        };
        reader.readAsDataURL(file);
    }

    function uploadMedia(file) {
        if (isUploading) return;
        isUploading = true;
        
        if (progressContainer) {
            progressContainer.style.display = 'block';
        }
        if (progressBar) {
            progressBar.style.width = '0%';
            progressBar.textContent = '0%';
        }
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'جاري الرفع...';
        }
        
        const formData = new FormData();
        formData.append('media', file);
        
        fetch('api/post_media_upload.php', {
            method: 'POST',
            body: formData,
            onUploadProgress: function(progressEvent) {
                if (progressBar) {
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                    progressBar.style.width = percentCompleted + '%';
                    progressBar.textContent = percentCompleted + '%';
                }
            }
        })
        .then(response => response.json())
        .then(data => {
            isUploading = false;
            
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'نشر';
            }
            
            if (data.success) {
                console.log('تم رفع الوسائط بنجاح:', data);
                uploadedMediaUrl = data.media_url;
                
                let mediaUrlInput = document.getElementById('media-url-input');
                if (!mediaUrlInput) {
                    mediaUrlInput = document.createElement('input');
                    mediaUrlInput.type = 'hidden';
                    mediaUrlInput.id = 'media-url-input';
                    mediaUrlInput.name = 'media_url';
                    postForm.appendChild(mediaUrlInput);
                }
                mediaUrlInput.value = uploadedMediaUrl;
            } else {
                console.error('فشل رفع الوسائط:', data.message);
                alert('فشل رفع الوسائط: ' + data.message);
                removeMedia();
            }
        })
        .catch(error => {
            isUploading = false;
            console.error('خطأ في رفع الوسائط:', error);
            alert('حدث خطأ أثناء رفع الوسائط. الرجاء المحاولة مرة أخرى.');
            
            if (progressContainer) {
                progressContainer.style.display = 'none';
            }
            
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'نشر';
            }
            
            removeMedia();
        });
    }
    
 
    function removeMedia() {
        if (mediaInput) {
            mediaInput.value = '';
        }
        
        if (mediaPreview) {
            mediaPreview.innerHTML = '';
            mediaPreview.style.display = 'none';
        }
        
        if (removeMediaBtn) {
            removeMediaBtn.style.display = 'none';
        }
        
        uploadedMediaUrl = '';
        const mediaUrlInput = document.getElementById('media-url-input');
        if (mediaUrlInput) {
            mediaUrlInput.value = '';
        }
    }
    
 
    function handlePostSubmit(event) {
        if (!postContent.value.trim() && !uploadedMediaUrl) {
            alert('الرجاء إدخال نص أو إرفاق صورة/فيديو للمنشور.');
            event.preventDefault();
            return;
        }
        
        if (isUploading) {
            alert('الرجاء الانتظار حتى اكتمال رفع الوسائط.');
            event.preventDefault();
            return;
        }
        
        if (uploadedMediaUrl) {
            let mediaUrlInput = document.getElementById('media-url-input');
            if (!mediaUrlInput) {
                mediaUrlInput = document.createElement('input');
                mediaUrlInput.type = 'hidden';
                mediaUrlInput.id = 'media-url-input';
                mediaUrlInput.name = 'media_url';
                postForm.appendChild(mediaUrlInput);
            }
            mediaUrlInput.value = uploadedMediaUrl;
        }
        
        return true;
    }
});
