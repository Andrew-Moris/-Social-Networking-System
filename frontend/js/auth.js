
document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const googleLoginBtn = document.getElementById('googleLogin');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLogin);
    }
    
    if (googleLoginBtn) {
        googleLoginBtn.addEventListener('click', handleGoogleLogin);
    }
    
    checkAuthStatus();
});

/**
 * @param {Event} event 
 */
async function handleLogin(event) {
    event.preventDefault();
    
    const email = document.getElementById('email').value;
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        showLoginError('يرجى إدخال البريد الإلكتروني وكلمة المرور');
        return;
    }
    
    try {
        const response = await fetch('/WEP/api/login.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                username: email, 
                password: password
            })
        });
        
        const data = await response.json();
        
        if (response.ok && data.token) {
            localStorage.setItem('token', data.token);
            localStorage.setItem('user', JSON.stringify(data.user));
            
            window.location.href = '/WEP/frontend/index.html';
        } else {
            showLoginError(data.error || 'فشل تسجيل الدخول. يرجى التحقق من بيانات الاعتماد الخاصة بك.');
        }
    } catch (error) {
        console.error('Login error:', error);
        showLoginError('حدث خطأ أثناء محاولة تسجيل الدخول. يرجى المحاولة مرة أخرى لاحقًا.');
    }
}

function handleGoogleLogin() {
    alert('وظيفة تسجيل الدخول باستخدام Google غير منفذة بعد.');
}

/**
 * @param {string} message
 */
function showLoginError(message) {
    let errorElement = document.getElementById('loginError');
    
    if (!errorElement) {
        errorElement = document.createElement('div');
        errorElement.id = 'loginError';
        errorElement.className = 'error-message';
        
        const loginBtn = document.querySelector('.login-btn');
        loginBtn.parentNode.insertBefore(errorElement, loginBtn);
    }
    
    errorElement.textContent = message;
    errorElement.style.display = 'block';
}


function checkAuthStatus() {
    const token = localStorage.getItem('token');
    const currentPage = window.location.pathname;
    
    if ((currentPage.includes('login.html') || currentPage.includes('register.html')) && token) {
        window.location.href = '/WEP/frontend/index.html';
    }
    
    if (!currentPage.includes('login.html') && !currentPage.includes('register.html') && !token) {
        window.location.href = '/WEP/frontend/login.html';
    }
}
