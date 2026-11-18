/* =========================================================
   LukApp · authModel.js
   Autenticación contra la API de Laravel (Sanctum + sesión).
   Las contraseñas se validan y se guardan con hash (bcrypt) en
   el servidor; ya no se calcula ningún hash en el navegador.
   ========================================================= */
import { api, errorMessage } from '../services/apiClient.js';
import { getCurrentUser as sessionUser, isAuthenticated as sessionActive } from '../core/session.js';

export async function login(email, password, remember = true) {
    try {
        const res = await api.post('/auth/login', { email, password, remember });
        return { success: true, user: res.data, redirect: res.redirect, message: res.message };
    } catch (error) {
        return { success: false, message: errorMessage(error) };
    }
}

export async function register({ name, email, phone, occupation, password, confirmPassword }) {
    try {
        const res = await api.post('/auth/register', {
            name, email, phone, occupation, password, password_confirmation: confirmPassword
        });
        return { success: true, user: res.data, redirect: res.redirect, message: res.message };
    } catch (error) {
        return { success: false, message: errorMessage(error) };
    }
}

export async function requestPasswordReset(email) {
    try {
        const res = await api.post('/auth/forgot-password', { email });
        return { success: true, message: res.message };
    } catch (error) {
        return { success: false, message: errorMessage(error) };
    }
}

export async function resetPassword({ token, email, password, confirmPassword }) {
    try {
        const res = await api.post('/auth/reset-password', {
            token, email, password, password_confirmation: confirmPassword
        });
        return { success: true, message: res.message, redirect: res.redirect };
    } catch (error) {
        return { success: false, message: errorMessage(error) };
    }
}

export function getCurrentUser() {
    return sessionUser();
}

export function isAuthenticated() {
    return sessionActive();
}

export async function logout() {
    await api.post('/auth/logout');
}
