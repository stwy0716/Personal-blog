<!-- Toast Notification -->
<div id="toast-container" class="fixed top-4 right-4 z-[999] space-y-2"></div>
<script>
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    
    const toast = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-emerald-600' : type === 'error' ? 'bg-red-600' : 'bg-amber-600';
    const icon = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    toast.className = bgColor + ' text-white px-5 py-3 rounded-2xl shadow-lg flex items-center gap-x-2 text-sm font-medium transform translate-x-full transition-transform duration-300';
    
    // 使用 textContent 防止 DOM XSS
    const iconEl = document.createElement('i');
    iconEl.className = 'fa-solid ' + icon;
    
    const spanEl = document.createElement('span');
    spanEl.textContent = message;
    
    toast.appendChild(iconEl);
    toast.appendChild(spanEl);
    
    container.appendChild(toast);
    
    requestAnimationFrame(() => {
        toast.classList.remove('translate-x-full');
        toast.classList.add('translate-x-0');
    });
    
    setTimeout(() => {
        toast.classList.remove('translate-x-0');
        toast.classList.add('translate-x-full');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>