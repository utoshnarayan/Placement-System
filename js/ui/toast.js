// Toast notification system
class ToastManager {
    constructor() {
        this.container = null;
        this.init();
    }
    
    init() {
        // Create toast container if it doesn't exist
        if (!document.getElementById('toast-container')) {
            this.container = document.createElement('div');
            this.container.id = 'toast-container';
            this.container.style.position = 'fixed';
            this.container.style.top = '20px';
            this.container.style.right = '20px';
            this.container.style.zIndex = '10000';
            document.body.appendChild(this.container);
        } else {
            this.container = document.getElementById('toast-container');
        }
    }
    
    show(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        toast.innerHTML = `
            <div class="toast__message">${message}</div>
            <button class="toast__close">&times;</button>
        `;
        
        this.container.appendChild(toast);
        
        // Trigger animation
        setTimeout(() => {
            toast.classList.add('toast--show');
        }, 10);
        
        // Auto remove after duration
        const removeToast = () => {
            toast.classList.remove('toast--show');
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        };
        
        // Close button
        toast.querySelector('.toast__close').addEventListener('click', removeToast);
        
        // Auto remove
        if (duration > 0) {
            setTimeout(removeToast, duration);
        }
        
        return toast;
    }
}

// Create singleton instance
const toastManager = new ToastManager();

// Export showToast function
export function showToast(message, type = 'info', duration = 5000) {
    return toastManager.show(message, type, duration);
}