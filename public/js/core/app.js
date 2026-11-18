/* =========================================================
   LukApp · app.js
   Lógica transversal del panel (sidebar, sesión, búsqueda,
   notificaciones). El control de acceso (route guards) ahora
   lo hace Laravel con middleware en el servidor.
   ========================================================= */
import { getCurrentUser, route, isCurrentPage } from './session.js';
import { api, errorMessage } from '../services/apiClient.js';
import { showToast } from '../components/toast.js';
import {
    fetchNotifications,
    getCachedNotifications,
    getUnreadCount,
    markAllAsRead,
    markAsRead
} from '../services/notificationService.js';

function initializeApp() {
    initializeSidebar();
    initializeLogout();
    refreshUserUI();
    initializeGlobalSearch();
    initializeNotifications();

    // REVEAL APP: Revelar la aplicación de forma segura tras el pintado inicial
    revealApp();
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeApp);
} else {
    initializeApp();
}
document.addEventListener('lukapp:user-updated', refreshUserUI);


/* =========================================================
   REVEAL APP (Control de FOUC y Fade-in)
   ========================================================= */
function revealApp() {
    setTimeout(() => {
        const layout = document.querySelector('.app-layout');
        if (layout) {
            layout.classList.add('app-loaded');
        }
    }, 15);
}


/* =========================================================
   SIDEBAR
   ========================================================= */

export function initializeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const toggle = document.getElementById('sidebarToggle');
    if (!sidebar || !toggle || toggle.dataset.bound) return;

    toggle.dataset.bound = 'true';
    toggle.addEventListener('click', () => {
        const isOpen = sidebar.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', event => {
        if (window.matchMedia('(max-width: 900px)').matches && sidebar.classList.contains('is-open')) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) {
                sidebar.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            }
        }
    });

    sidebar.addEventListener('click', event => {
        const link = event.target.closest('a');
        if (!link) return;
        if (window.matchMedia('(max-width: 900px)').matches) {
            sidebar.classList.remove('is-open');
            toggle.setAttribute('aria-expanded', 'false');
        }
    });
}


/* =========================================================
   CIERRE DE SESIÓN (invalida la sesión en el servidor)
   ========================================================= */

export function initializeLogout() {
    const logout = document.getElementById('btnLogout');
    if (!logout || logout.dataset.bound) return;
    logout.dataset.bound = 'true';
    logout.addEventListener('click', async event => {
        event.preventDefault();
        try {
            await api.post('/auth/logout');
        } catch (error) {
            // Si la sesión ya había expirado igualmente se vuelve al login
        }
        try { localStorage.removeItem('lukapp_session'); } catch (e) { /* datos heredados */ }
        window.location.replace(route('login'));
    });
}


/* =========================================================
   PERFIL DEL USUARIO
   ========================================================= */

export function refreshUserUI() {
    const user = getCurrentUser();
    if (!user) return;

    const nameElements = document.querySelectorAll('.user-name, #userName, #userNameTopbar');
    const avatarElements = document.querySelectorAll('.user-avatar, #userAvatar, #userAvatarTopbar');
    const planElements = document.querySelectorAll('.user-plan, #userPlan, #userPlanTopbar');

    nameElements.forEach(el => el.textContent = user.name || 'Usuario');
    planElements.forEach(el => el.textContent = user.role === 'admin' ? 'Root Access' : (user.plan || 'Premium'));

    avatarElements.forEach(avatar => {
        if (user.avatar) {
            avatar.style.backgroundImage = `url('${encodeURI(user.avatar)}')`;
            avatar.style.backgroundSize = 'cover';
            avatar.style.backgroundPosition = 'center';
            avatar.textContent = '';
        } else {
            avatar.style.backgroundImage = 'none';
            avatar.textContent = (user.name || 'U').slice(0, 2).toUpperCase();
        }
    });
}


/* =========================================================
   BÚSQUEDA GLOBAL
   ========================================================= */

export function initializeGlobalSearch() {
    const input = document.getElementById('globalSearch');
    if (!input || input.dataset.bound) return;
    input.dataset.bound = 'true';

    input.addEventListener('keydown', event => {
        if (event.key !== 'Enter') return;
        const query = input.value.trim();
        if (!query) return;

        if (isCurrentPage('movements')) {
            document.dispatchEvent(new CustomEvent('lukapp:search', { detail: { query } }));
        } else {
            window.location.href = `${route('movements')}?search=${encodeURIComponent(query)}`;
        }
    });
}


/* =========================================================
   NOTIFICACIONES (API)
   ========================================================= */

export function initializeNotifications() {
    const button = document.getElementById('btnNotifications');
    if (!button || button.dataset.bound) return;
    button.dataset.bound = 'true';

    const reload = () => fetchNotifications().catch(() => { /* el badge simplemente no se actualiza */ });

    reload();

    button.addEventListener('click', () => {
        toggleNotificationPanel(button);
    });

    document.addEventListener('lukapp:notification', () => {
        renderNotificationBadge(button);
        renderNotificationPanelIfOpen();
    });

    // Al crear movimientos o abonar a metas el servidor genera notificaciones nuevas
    document.addEventListener('lukapp:data-updated', reload);
}

function renderNotificationBadge(button) {
    if (!button) return;
    const count = getUnreadCount();
    let badge = button.querySelector('.notification-badge');

    if (count <= 0) {
        badge?.remove();
        return;
    }

    if (!badge) {
        badge = document.createElement('span');
        badge.className = 'notification-badge';
        badge.style.cssText = `
            position: absolute;
            top: 3px;
            right: 3px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: #E04848;
            color: #fff;
            font-size: 9px;
            font-weight: 800;
            display: grid;
            place-items: center;
            line-height: 1;
        `;
        button.style.position = 'relative';
        button.appendChild(badge);
    }
    badge.textContent = count > 99 ? '99+' : String(count);
}

function toggleNotificationPanel(button) {
    let panel = document.getElementById('notificationPanel');
    if (panel) {
        panel.remove();
        return;
    }
    panel = createNotificationPanel();
    document.body.appendChild(panel);
    const rect = button.getBoundingClientRect();
    panel.style.top = `${rect.bottom + 10}px`;
    panel.style.right = `${Math.max(12, window.innerWidth - rect.right)}px`;
}

function renderNotificationPanelIfOpen() {
    const old = document.getElementById('notificationPanel');
    if (!old) return;
    const replacement = createNotificationPanel();
    replacement.style.top = old.style.top;
    replacement.style.right = old.style.right;
    old.replaceWith(replacement);
}

function createNotificationPanel() {
    const panel = document.createElement('div');
    panel.id = 'notificationPanel';
    panel.style.cssText = `
        position: fixed;
        z-index: 500;
        width: min(360px, calc(100vw - 24px));
        max-height: 480px;
        overflow: auto;
        background: var(--bg-surface);
        color: var(--text-color);
        border: 1px solid var(--border-color);
        border-radius: 14px;
        box-shadow: var(--shadow-pop);
        padding: 14px;
    `;

    const notifications = getCachedNotifications();

    const header = document.createElement('div');
    header.style.cssText = `
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 12px;
    `;
    header.innerHTML = `
        <strong>Notificaciones</strong>
        <button type="button" id="markNotificationsRead" style="font-size:11px;color:var(--text-muted)">Marcar leídas</button>
    `;
    panel.appendChild(header);

    const list = document.createElement('div');
    list.style.cssText = `display: flex; flex-direction: column; gap: 8px;`;

    if (!notifications.length) {
        const empty = document.createElement('div');
        empty.textContent = 'No tienes notificaciones.';
        empty.style.cssText = `padding: 24px 8px; text-align: center; color: var(--text-subtle); font-size: 12px;`;
        list.appendChild(empty);
    }

    notifications.forEach(notification => {
        const item = document.createElement('button');
        item.type = 'button';
        item.style.cssText = `
            display: block;
            width: 100%;
            padding: 11px;
            text-align: left;
            border: 1px solid ${notification.read ? 'var(--border-color)' : 'var(--primary-color)'};
            border-radius: 10px;
            background: ${notification.read ? 'var(--bg-surface)' : 'var(--primary-soft)'};
            color: var(--text-color);
        `;
        item.innerHTML = `
            <div style="font-weight:700;font-size:12px;margin-bottom:3px">${escapeHTML(notification.title)}</div>
            <div style="font-size:11px;color:var(--text-muted)">${escapeHTML(notification.message)}</div>
            <div style="font-size:10px;color:var(--text-subtle);margin-top:4px">${formatDateTime(notification.createdAt)}</div>
        `;
        item.addEventListener('click', async () => {
            if (notification.read) return;
            try { await markAsRead(notification.id); } catch (error) { showToast(errorMessage(error), 'error'); }
        });
        list.appendChild(item);
    });

    panel.appendChild(list);

    panel.querySelector('#markNotificationsRead')?.addEventListener('click', async () => {
        try { await markAllAsRead(); } catch (error) { showToast(errorMessage(error), 'error'); }
    });

    return panel;
}

function formatDateTime(value) {
    if (!value) return '';
    return new Date(value).toLocaleString('es-CO', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
}

function escapeHTML(value) {
    return String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}
