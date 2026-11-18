/* =========================================================
   LukApp · adminModel.js
   Consultas del Backoffice contra /api/admin/* (rol admin).
   ========================================================= */
import { api } from '../services/apiClient.js';
import { getCurrentUser } from '../core/session.js';

export function verifyAdminAccess() {
    const user = getCurrentUser();
    return (user && user.role === 'admin') ? user : false;
}

export async function getGlobalKPIs() {
    return (await api.get('/admin/kpis')).data;
}

/** @returns {Promise<{users:Array, meta:Object}>} */
export async function getUsers({ query = '', page = 1, perPage = 15 } = {}) {
    const res = await api.get('/admin/users', { q: query, page, per_page: perPage });
    return { users: res.data, meta: res.meta };
}

// Datos integrales del usuario (conteos y preferencias)
export async function getUserDetails(userId) {
    return (await api.get(`/admin/users/${encodeURIComponent(userId)}`)).data;
}

// Bloquear / desbloquear
export async function setUserBlocked(userId, blocked) {
    return api.put(`/admin/users/${encodeURIComponent(userId)}/block`, { blocked });
}

// Eliminar movimientos, metas y notificaciones (conserva la cuenta)
export async function resetUserData(userId) {
    return api.delete(`/admin/users/${encodeURIComponent(userId)}/data`);
}

export async function deleteUser(userId) {
    return api.delete(`/admin/users/${encodeURIComponent(userId)}`);
}

export async function getGlobalStats() {
    return (await api.get('/admin/stats')).data;
}

export async function getSystemMetrics() {
    return (await api.get('/admin/system')).data;
}

export async function setDemoMode(enabled) {
    return api.put('/admin/system/demo-mode', { enabled });
}
