/* =========================================================
   LukApp · authController.js
   Controlador para el inicio de sesión.
   ========================================================= */
import { login } from './authModel.js';
import { route } from '../core/session.js';
import { getLoginFormData, setLoading, showMessage, clearMessage, togglePasswordVisibility } from './authView.js';

function initializeAuth() {
    const form = document.getElementById('loginForm');
    if (!form) return;

    // Si ya hay sesión, Laravel redirige desde el servidor (middleware "guest").

    form.addEventListener('submit', handleLogin);
    document.getElementById('btnTogglePassword')?.addEventListener('click', togglePasswordVisibility);

    // Redirección directa al registro
    document.getElementById('btnRegister')?.addEventListener('click', () => {
        window.location.href = route('register');
    });

    // Recuperación real por correo electrónico
    document.getElementById('btnForgotPassword')?.addEventListener('click', () => {
        const email = document.getElementById('userEmail')?.value.trim();
        window.location.href = route('forgot') + (email ? `?email=${encodeURIComponent(email)}` : '');
    });

    document.getElementById('btnGoogleLogin')?.addEventListener('click', () => {
        showMessage('El inicio de sesión con Google aún no está configurado en este servidor. Usa tu correo y contraseña.', 'info');
    });
}

async function handleLogin(event) {
    event.preventDefault();
    clearMessage();

    const { email, password, remember } = getLoginFormData();

    if (!email) return showMessage('Ingresa tu correo electrónico.');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showMessage('Ingresa un correo electrónico válido.');
    if (!password) return showMessage('Ingresa tu contraseña.');

    setLoading(true);

    const result = await login(email, password, remember);

    if (!result.success) {
        setLoading(false);
        showMessage(result.message);
        return;
    }

    showMessage(result.message || 'Sesión iniciada. Redirigiendo...', 'success');

    // Redirección según el rol (la decide el servidor)
    setTimeout(() => {
        window.location.replace(result.redirect || route('dashboard'));
    }, 300);
}

document.addEventListener('DOMContentLoaded', initializeAuth);
