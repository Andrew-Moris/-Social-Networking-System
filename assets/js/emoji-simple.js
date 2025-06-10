
document.addEventListener('DOMContentLoaded', function() {
    console.log('تهيئة منتقي الإيموجي البسيط...');
    
    const emojiButton = document.getElementById('emojiBtn');
    const messageInput = document.getElementById('messageInput');
    
    if (!emojiButton || !messageInput) {
        console.error('لم يتم العثور على زر الإيموجي أو حقل الإدخال');
        return;
    }
    
    const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '👍', '👎', '👌', '✌️', '🤞', '👏', '🙌', '👐', '🤲', '🙏', '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔'];
    
    const emojiPicker = document.createElement('div');
    emojiPicker.className = 'emoji-picker';
    emojiPicker.style.display = 'none';
    
    emojis.forEach(emoji => {
        const span = document.createElement('span');
        span.className = 'emoji-item';
        span.textContent = emoji;
        span.addEventListener('click', function() {
            const cursorPos = messageInput.selectionStart;
            const textBeforeCursor = messageInput.value.substring(0, cursorPos);
            const textAfterCursor = messageInput.value.substring(cursorPos);
            
            messageInput.value = textBeforeCursor + emoji + textAfterCursor;
            
            const newCursorPos = cursorPos + emoji.length;
            messageInput.setSelectionRange(newCursorPos, newCursorPos);
            messageInput.focus();
            
            emojiPicker.style.display = 'none';
        });
        
        emojiPicker.appendChild(span);
    });
    
    document.body.appendChild(emojiPicker);
    
    emojiButton.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (emojiPicker.style.display === 'flex') {
            emojiPicker.style.display = 'none';
        } else {
            const buttonRect = emojiButton.getBoundingClientRect();
            
            emojiPicker.style.display = 'flex';
            emojiPicker.style.position = 'absolute';
            emojiPicker.style.bottom = (window.innerHeight - buttonRect.top + 10) + 'px';
            emojiPicker.style.right = (window.innerWidth - buttonRect.right + 30) + 'px';
        }
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== emojiButton && !emojiPicker.contains(e.target)) {
            emojiPicker.style.display = 'none';
        }
    });
    
    console.log('تم تهيئة منتقي الإيموجي البسيط بنجاح');
});
