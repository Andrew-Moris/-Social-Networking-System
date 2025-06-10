
document.addEventListener('DOMContentLoaded', function() {
    console.log('تشغيل سكريبت إزالة المستخدمين المكررين');
    
    const userItems = document.querySelectorAll('.user-item');
    console.log('عدد عناصر المستخدمين:', userItems.length);
    
    const processedUserIds = new Set();
    const duplicateItems = [];
    
    userItems.forEach(item => {
        const userId = item.getAttribute('data-user-id');
        
        if (userId) {
            if (processedUserIds.has(userId)) {
                duplicateItems.push(item);
                console.log('تم العثور على مستخدم مكرر:', userId);
            } else {
                processedUserIds.add(userId);
            }
        }
    });
    
    duplicateItems.forEach(item => {
        console.log('إزالة عنصر مكرر:', item.getAttribute('data-user-id'));
        item.remove();
    });
    
    console.log('تم إزالة', duplicateItems.length, 'عنصر مكرر');
});
