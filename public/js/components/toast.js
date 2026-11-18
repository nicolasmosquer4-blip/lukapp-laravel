/* =========================================================
   LukApp · toast.js
   Mensajes breves de éxito / error / información.
   ========================================================= */

const ICONS = { success: '✓', error: '!', warning: '!', info: 'i' };

function getStack() {
    let stack = document.getElementById('toastStack');
    if (!stack) {
        stack = document.createElement('div');
        stack.id = 'toastStack';
        stack.className = 'toast-stack';
        stack.setAttribute('role', 'status');
        stack.setAttribute('aria-live', 'polite');
        document.body.appendChild(stack);
    }
    return stack;
}

export function showToast(message, type = 'info', duration = 3500) {
    if (!message) return;
    const toast = document.createElement('div');
    toast.className = `toast ${ICONS[type] ? type : 'info'}`;

    const icon = document.createElement('span');
    icon.className = 'toast__icon';
    icon.textContent = ICONS[type] || ICONS.info;

    const text = document.createElement('span');
    text.textContent = message;

    toast.append(icon, text);
    getStack().appendChild(toast);

    setTimeout(() => {
        toast.classList.add('is-leaving');
        setTimeout(() => toast.remove(), 200);
    }, duration);
}

export const toastSuccess = message => showToast(message, 'success');
export const toastError = message => showToast(message, 'error', 5000);
export const toastInfo = message => showToast(message, 'info');
