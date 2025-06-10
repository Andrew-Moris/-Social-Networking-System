
class EmojiPicker {
    constructor(options) {
        this.options = options || {};
        this.container = null;
        this.button = null;
        this.pickerElement = null;
        this.emojiList = null;
        this.isOpen = false;
        this.currentCategory = 'smileys';
        
        this.categories = {
            smileys: {
                name: 'الوجوه',
                icon: '😊',
                emojis: [
                    '😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', 
                    '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', 
                    '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩',
                    '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖',
                    '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯'
                ]
            },
            gestures: {
                name: 'إيماءات',
                icon: '👍',
                emojis: [
                    '👋', '🤚', '✋', '🖖', '👌', '🤌', '🤏', '✌️', '🤞', '🤟',
                    '🤘', '🤙', '👈', '👉', '👆', '🖕', '👇', '☝️', '👍', '👎',
                    '✊', '👊', '🤛', '🤜', '👏', '🙌', '👐', '🤲', '🙏', '✍️'
                ]
            },
            symbols: {
                name: 'رموز',
                icon: '❤️',
                emojis: [
                    '❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔',
                    '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '☮️',
                    '✝️', '☪️', '🕉️', '☸️', '✡️', '🔯', '🕎', '☯️', '☦️', '🛐'
                ]
            }
        };
        
        this.init();
    }
    
    init() {
        this.createButton();
        this.createPicker();
        this.bindEvents();
    }
    
    createButton() {
        this.button = document.createElement('button');
        this.button.className = 'emoji-button';
        this.button.innerHTML = '😊';
        this.button.title = 'اختر إيموجي';
        
        if (this.options.buttonContainer) {
            const container = document.querySelector(this.options.buttonContainer);
            if (container) {
                container.appendChild(this.button);
            }
        }
    }
    
    createPicker() {
        this.pickerElement = document.createElement('div');
        this.pickerElement.className = 'emoji-picker';
        
        const header = document.createElement('div');
        header.className = 'emoji-picker-header';
        
        const title = document.createElement('div');
        title.className = 'emoji-picker-title';
        title.textContent = 'اختر إيموجي';
        
        const closeButton = document.createElement('div');
        closeButton.className = 'emoji-picker-close';
        closeButton.innerHTML = '&times;';
        closeButton.addEventListener('click', () => this.close());
        
        header.appendChild(title);
        header.appendChild(closeButton);
        
        const categories = document.createElement('div');
        categories.className = 'emoji-categories';
        
        for (const [key, category] of Object.entries(this.categories)) {
            const categoryElement = document.createElement('div');
            categoryElement.className = 'emoji-category';
            categoryElement.dataset.category = key;
            categoryElement.innerHTML = category.icon;
            categoryElement.title = category.name;
            
            if (key === this.currentCategory) {
                categoryElement.classList.add('active');
            }
            
            categoryElement.addEventListener('click', () => {
                this.setCategory(key);
            });
            
            categories.appendChild(categoryElement);
        }
        
        this.emojiList = document.createElement('div');
        this.emojiList.className = 'emoji-list';
        
        this.pickerElement.appendChild(header);
        this.pickerElement.appendChild(categories);
        this.pickerElement.appendChild(this.emojiList);
        
        document.body.appendChild(this.pickerElement);
        
        this.renderEmojis();
    }
    
    renderEmojis() {
        this.emojiList.innerHTML = '';
        
        const category = this.categories[this.currentCategory];
        if (!category) return;
        
        for (const emoji of category.emojis) {
            const emojiElement = document.createElement('div');
            emojiElement.className = 'emoji-item';
            emojiElement.innerHTML = emoji;
            
            emojiElement.addEventListener('click', () => {
                this.selectEmoji(emoji);
            });
            
            this.emojiList.appendChild(emojiElement);
        }
    }
    
    setCategory(category) {
        this.currentCategory = category;
        
        const categoryElements = this.pickerElement.querySelectorAll('.emoji-category');
        categoryElements.forEach(el => {
            el.classList.remove('active');
            if (el.dataset.category === category) {
                el.classList.add('active');
            }
        });
        
        this.renderEmojis();
    }
    
    selectEmoji(emoji) {
        if (this.options.onSelect && typeof this.options.onSelect === 'function') {
            this.options.onSelect(emoji);
        }
        
        this.close();
    }
    
    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }
    
    open() {
        this.pickerElement.classList.add('active');
        this.isOpen = true;
    }
    
    close() {
        this.pickerElement.classList.remove('active');
        this.isOpen = false;
    }
    
    bindEvents() {
        this.button.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });
        
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.pickerElement.contains(e.target) && e.target !== this.button) {
                this.close();
            }
        });
    }
}
