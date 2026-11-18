/* =========================================================
   LukApp · notificationService.js
   Notificaciones del usuario desde la API (tabla "notifications").
   Las notificaciones se generan en el servidor (movimientos, metas,
   alertas de gasto); aquí solo se consultan y se marcan como leídas.
   ========================================================= */
import { api } from './apiClient.js';

let cache = { items: [], unreadCount: 0 };

export function getCachedNotifications() {
    return cache.items;
}

export function getUnreadCount() {
    return cache.unreadCount;
}

export async function fetchNotifications() {
    const res = await api.get('/notifications', { limit: 30 });
    cache = { items: res.data || [], unreadCount: res.unread_count ?? 0 };
    emit('loaded');
    return cache;
}

export async function markAsRead(id) {
    const res = await api.put(`/notifications/${encodeURIComponent(id)}/read`);
    const item = cache.items.find(n => n.id === id);
    if (item) item.read = true;
    cache.unreadCount = res.unread_count ?? Math.max(0, cache.unreadCount - 1);
    emit('updated', { id, read: true });
    return true;
}

export async function markAllAsRead() {
    await api.put('/notifications/read-all');
    cache.items.forEach(n => { n.read = true; });
    cache.unreadCount = 0;
    emit('all-read');
    return true;
}

export async function deleteNotification(id) {
    await api.delete(`/notifications/${encodeURIComponent(id)}`);
    cache.items = cache.items.filter(n => n.id !== id);
    emit('deleted', id);
    return true;
}

export async function clearNotifications() {
    await api.delete('/notifications');
    cache = { items: [], unreadCount: 0 };
    emit('cleared');
    return true;
}

function emit(action, data = null) {
    document.dispatchEvent(new CustomEvent('lukapp:notification', {
        detail: { action, data, unreadCount: cache.unreadCount }
    }));
}
