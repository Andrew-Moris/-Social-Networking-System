
document.addEventListener('DOMContentLoaded', function() {
    const commonEmojis = [
        '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', 
        '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', 
        '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
        '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖',
        '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯',
        '👍', '👎', '👌', '✌️', '🤞', '👏', '🙌', '👐', '🤲', '🙏',
        '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔'
    ];
    
    function createEmojiPicker() {
        const emojiButton = document.getElementById('emojiBtn');
        const messageInput = document.getElementById('messageInput');
        
        if (!emojiButton || !messageInput) {
            console.error('Emoji button or message input not found');
            return;
        }
        
        const emojiPicker = document.createElement('div');
        emojiPicker.id = 'emojiPicker';
        emojiPicker.className = 'emoji-picker';
        
        commonEmojis.forEach(emoji => {
            const emojiElement = document.createElement('span');
            emojiElement.className = 'emoji-item';
            emojiElement.textContent = emoji;
            emojiElement.addEventListener('click', function() {
                const cursorPos = messageInput.selectionStart;
                const textBeforeCursor = messageInput.value.substring(0, cursorPos);
                const textAfterCursor = messageInput.value.substring(cursorPos);
                
                messageInput.value = textBeforeCursor + emoji + textAfterCursor;
                
                const newCursorPos = cursorPos + emoji.length;
                messageInput.setSelectionRange(newCursorPos, newCursorPos);
                messageInput.focus();
                
                emojiPicker.style.display = 'none';
            });
            
            emojiPicker.appendChild(emojiElement);
        });
        
        document.body.appendChild(emojiPicker);
        
        emojiButton.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (emojiPicker.style.display === 'flex') {
                emojiPicker.style.display = 'none';
            } else {
                document.querySelectorAll('.emoji-picker').forEach(picker => {
                    if (picker !== emojiPicker) {
                        picker.style.display = 'none';
                    }
                });
                
                emojiPicker.style.display = 'flex';
                
                const buttonRect = emojiButton.getBoundingClientRect();
                const chatInputArea = document.getElementById('chatInputArea');
                const chatInputRect = chatInputArea.getBoundingClientRect();
                
                emojiPicker.style.bottom = (window.innerHeight - buttonRect.top + 10) + 'px';
                emojiPicker.style.right = (window.innerWidth - buttonRect.right + 30) + 'px';
            }
        });
        
        document.addEventListener('click', function(e) {
            if (e.target !== emojiButton && !emojiPicker.contains(e.target)) {
                emojiPicker.style.display = 'none';
            }
        });
    }
    
    createEmojiPicker();
});
