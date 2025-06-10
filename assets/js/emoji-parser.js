
 
const EmojiParser = {
    emojiMap: {
        ':)': '😊',
        ':D': '😄',
        ':P': '😛',
        ':p': '😛',
        ':(': '😔',
        ':"(': '😢',
        ':o': '😮',
        ':O': '😮',
        ';)': '😉',
        '<3': '❤️',
        ':*': '😘',
        '^^': '😁',
        ':|': '😐',
        ':/': '😕',
        ':S': '😖',
        ':s': '😖',
        ':$': '😳',
        ':@': '😠',
        '8)': '😎',
        '8D': '😁',
        'XD': '😂',
        'xD': '😂',
        '(y)': '👍',
        '(n)': '👎'
    },

    parse: function(text) {
        if (!text) return '';
        
        let result = text;
        for (const [textEmoji, emoji] of Object.entries(this.emojiMap)) {
            result = result.split(textEmoji).join(emoji);
        }
        
        return result;
    }
};
