/* =========================================================
   LukApp · configuracionModel.js
   Perfil, seguridad y preferencias del usuario vía API.
   ========================================================= */
import { api, errorMessage } from '../services/apiClient.js';
import { setCurrentUser } from '../core/session.js';

const DEFAULT_SETTINGS = {
    appearance: { theme: 'light', currency: 'COP', language: 'es' },
    notifications: { notif_email: true, notif_push: true, notif_weekly: false, notif_monthly: true, notif_alerts: true, notif_goals: true }
};

export async function getSettings() {
    const res = await api.get('/settings');
    return {
        appearance: { ...DEFAULT_SETTINGS.appearance, ...(res.data?.appearance || {}) },
        notifications: { ...DEFAULT_SETTINGS.notifications, ...(res.data?.notifications || {}) }
    };
}

/** Guarda una sola preferencia (PUT parcial). La categoría se conserva por compatibilidad. */
export async function updateSettingCategory(category, key, value) {
    const res = await api.put('/settings', { [key]: value });
    return res.data;
}

export async function updateProfile(data) {
    const res = await api.put('/profile', {
        name: data.name,
        phone: data.phone,
        occupation: data.occupation
    });
    return { user: setCurrentUser(res.data), message: res.message };
}

/** Sube la foto como multipart/form-data (validada en el servidor: imagen, máx. 5 MB). */
export async function updateAvatar(file) {
    const body = new FormData();
    body.append('avatar', file);
    const res = await api.post('/profile/avatar', body);
    return { user: setCurrentUser(res.data), message: res.message };
}

export async function changePassword(currentPass, newPass, confirmPass) {
    try {
        const res = await api.put('/profile/password', {
            current_password: currentPass,
            password: newPass,
            password_confirmation: confirmPass
        });
        return { success: true, message: res.message };
    } catch (error) {
        return { success: false, message: errorMessage(error) };
    }
}

export async function deleteAccount() {
    return api.delete('/profile');
}
