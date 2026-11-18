/* =========================================================
   LukApp · passwordResetController.js
   Recuperación de contraseña: solicitud del enlace y
   restablecimiento con el token recibido por correo.
   ========================================================= */
import { requestPasswordReset, resetPassword } from './authModel.js';
import { route } from '../core/session.js';

function showMessage(boxId, message, type = 'error') {
    const box = document.getElementById(boxId);
    if (!box) return;
    box.hidden = false;
    box.className = `form-message ${type}`;
    box.textContent = message;
}

function setLoading(button, isLoading, idleText, busyText) {
    if (!button) return;
    button.disabled = isLoading;
    button.textContent = isLoading ? busyText : idleText;
}

function initForgot() {
    const form = document.getElementById('forgotForm');
    if (!form) return;

    const params = new URLSearchParams(location.search);
    if (params.get('email')) form.email.value = params.get('email');

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const email = form.email.value.trim();
        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            return showMessage('forgotMessage', 'Ingresa un correo electrónico válido.');
        }

        const button = document.getElementById('btnForgotSubmit');
        setLoading(button, true, 'Enviar enlace de recuperación', 'Enviando...');
        const result = await requestPasswordReset(email);
        setLoading(button, false, 'Enviar enlace de recuperación', 'Enviando...');

        showMessage('forgotMessage', result.message, result.success ? 'success' : 'error');
    });
}

function initReset() {
    const form = document.getElementById('resetForm');
    if (!form) return;

    form.addEventListener('submit', async event => {
        event.preventDefault();
        const data = {
            token: form.token.value,
            email: form.email.value.trim(),
            password: form.password.value,
            confirmPassword: form.password_confirmation.value
        };

        if (!data.email || !data.password) return showMessage('resetMessage', 'Completa todos los campos.');
        if (data.password.length < 6) return showMessage('resetMessage', 'La contraseña debe tener mínimo 6 caracteres.');
        if (data.password !== data.confirmPassword) return showMessage('resetMessage', 'Las contraseñas no coinciden.');

        const button = document.getElementById('btnResetSubmit');
        setLoading(button, true, 'Guardar nueva contraseña', 'Guardando...');
        const result = await resetPassword(data);

        if (!result.success) {
            setLoading(button, false, 'Guardar nueva contraseña', 'Guardando...');
            return showMessage('resetMessage', result.message);
        }

        showMessage('resetMessage', result.message, 'success');
        setTimeout(() => window.location.replace(result.redirect || route('login')), 1200);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initForgot();
    initReset();
});
