
document.addEventListener('DOMContentLoaded', function() {
    console.log('تهيئة منتقي الإيموجي...');
    setTimeout(initEmojiPicker, 500);
});

function initEmojiPicker() {
    console.log('بدء تهيئة منتقي الإيموجي...');
    
    const commonEmojis = [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', 
        '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', 
        '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
        '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖',
        '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯',
        '👍', '👎', '👌', '✌️', '🤞', '👏', '🙌', '👐', '🤲', '🙏',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔'
    ];
    
    const emojiButton = document.getElementById('emojiBtn');
    const messageInput = document.getElementById('messageInput');
    
    if (!emojiButton || !messageInput) {
        console.error('لم يتم العثور على زر الإيموجي أو حقل الإدخال');
        console.log('emojiBtn موجود:', !!document.getElementById('emojiBtn'));
        console.log('messageInput موجود:', !!document.getElementById('messageInput'));
        return;
    }
    
    console.log('تم العثور على زر الإيموجي:', emojiButton);
    console.log('تم العثور على حقل الإدخال:', messageInput);
    
    console.log('تم العثور على زر الإيموجي وحقل الإدخال');
    
    let emojiPickerContainer = document.getElementById('emojiPickerContainer');
    
    if (!emojiPickerContainer) {
        emojiPickerContainer = document.createElement('div');
        emojiPickerContainer.id = 'emojiPickerContainer';
        emojiPickerContainer.className = 'emoji-picker-container';
        document.body.appendChild(emojiPickerContainer);
    }
    
    emojiPickerContainer.innerHTML = `
        <div class="emoji-picker">
            <div class="emoji-picker-header">
                <span>الرموز التعبيرية</span>
                <button class="emoji-picker-close">&times;</button>
            </div>
            <div class="emoji-picker-content"></div>
        </div>
    `;
    
    const emojiPickerContent = emojiPickerContainer.querySelector('.emoji-picker-content');
    const closeButton = emojiPickerContainer.querySelector('.emoji-picker-close');
    
    commonEmojis.forEach(emoji => {
        const emojiSpan = document.createElement('span');
        emojiSpan.className = 'emoji-item';
        emojiSpan.textContent = emoji;
        emojiSpan.addEventListener('click', function() {
            const cursorPos = messageInput.selectionStart;
            const textBeforeCursor = messageInput.value.substring(0, cursorPos);
            const textAfterCursor = messageInput.value.substring(cursorPos);
            
            messageInput.value = textBeforeCursor + emoji + textAfterCursor;
            
            const newCursorPos = cursorPos + emoji.length;
            messageInput.setSelectionRange(newCursorPos, newCursorPos);
            messageInput.focus();
            
            emojiPickerContainer.style.display = 'none';
        });
        
        emojiPickerContent.appendChild(emojiSpan);
    });
    
    emojiButton.addEventListener('click', function(e) {
        console.log('تم النقر على زر الإيموجي');
        e.preventDefault();
        e.stopPropagation();
        
        if (emojiPickerContainer.style.display === 'block') {
            emojiPickerContainer.style.display = 'none';
        } else {
            const buttonRect = emojiButton.getBoundingClientRect();
            const emojiPicker = emojiPickerContainer.querySelector('.emoji-picker');
            
            emojiPickerContainer.style.display = 'block';
            emojiPicker.style.bottom = (window.innerHeight - buttonRect.top + 10) + 'px';
            emojiPicker.style.right = (window.innerWidth - buttonRect.right + 30) + 'px';
        }
    });
    
    closeButton.addEventListener('click', function() {
        emojiPickerContainer.style.display = 'none';
    });
    
    document.addEventListener('click', function(e) {
        if (e.target !== emojiButton && !emojiPickerContainer.contains(e.target)) {
            emojiPickerContainer.style.display = 'none';
        }
    });
    
    emojiPickerContainer.style.display = 'none';
    
    console.log('تم تهيئة منتقي الإيموجي بنجاح');
}
