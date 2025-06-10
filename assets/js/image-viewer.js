
if (!document.getElementById('imageViewerModal')) {
    const imageViewerModal = document.createElement('div');
    imageViewerModal.id = 'imageViewerModal';
    imageViewerModal.className = 'fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center';
    imageViewerModal.innerHTML = `
        <div class="relative w-full h-full flex items-center justify-center p-4">
            <button id="closeImageViewer" class="absolute top-4 right-4 text-white text-2xl hover:text-gray-300">
                <i class="bi bi-x-lg"></i>
            </button>
            <img id="imageViewerContent" src="" alt="صورة كبيرة" class="max-w-full max-h-full object-contain">
        </div>
    `;
    document.body.appendChild(imageViewerModal);
    
    document.getElementById('closeImageViewer').addEventListener('click', function() {
        document.getElementById('imageViewerModal').classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    });
    
    imageViewerModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    });
}

/**
 * @param {string} imageUrl 
 */
function showImageModal(imageUrl) {
    const imageViewerModal = document.getElementById('imageViewerModal');
    const imageViewerContent = document.getElementById('imageViewerContent');
    
    if (imageViewerModal && imageViewerContent) {
        imageViewerContent.src = '';
        imageViewerContent.style.display = 'none';
        
        let loadingSpinner = document.getElementById('imageLoadingSpinner');
        if (!loadingSpinner) {
            loadingSpinner = document.createElement('div');
            loadingSpinner.id = 'imageLoadingSpinner';
            loadingSpinner.className = 'loading-spinner';
            imageViewerModal.querySelector('.relative').appendChild(loadingSpinner);
        } else {
            loadingSpinner.style.display = 'block';
        }
        
        imageViewerModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        
        const img = new Image();
        img.onload = function() {
            imageViewerContent.src = imageUrl;
            imageViewerContent.style.display = 'block';
            if (loadingSpinner) loadingSpinner.style.display = 'none';
        };
        img.onerror = function() {
            imageViewerContent.src = 'assets/images/image-placeholder.svg';
            imageViewerContent.classList.add('error-image');
            if (loadingSpinner) loadingSpinner.style.display = 'none';
        };
        img.src = imageUrl;
    }
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const imageViewerModal = document.getElementById('imageViewerModal');
        if (imageViewerModal && !imageViewerModal.classList.contains('hidden')) {
            imageViewerModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
});
