/* =========================================================
   LukApp · adminController.js
   Maneja la lógica del Backoffice y sincronización de datos.
   ========================================================= */
import { verifyAdminAccess, getGlobalKPIs, getUsers, getUserDetails, setUserBlocked, resetUserData, deleteUser, getGlobalStats, getSystemMetrics, setDemoMode } from './adminModel.js';
import { setupAdminNavigation, renderKPIs, renderUsersTable, renderUsersPagination, openAdminModal, renderSystemMetrics, renderAdminCharts, escapeHTML } from './adminView.js';
import { errorMessage } from '../services/apiClient.js';
import { route } from '../core/session.js';
import { toastSuccess, toastError } from '../components/toast.js';

const state = { query: '', page: 1, users: [], meta: null, demoStatus: false };
let searchTimer = null;

document.addEventListener('DOMContentLoaded', initializeAdmin);

// Sincronización Global
['lukapp:user-blocked', 'lukapp:user-unblocked', 'lukapp:user-deleted', 'lukapp:user-reset', 'lukapp:admin-updated']
    .forEach(evt => document.addEventListener(evt, refreshAllData));

function initializeAdmin() {
    if (!verifyAdminAccess()) {
        window.location.replace(route('dashboard'));
        return;
    }

    setupAdminNavigation((section) => {
        // Al mostrar la pestaña de estadísticas el canvas ya tiene dimensiones reales
        if (section === 'estadisticas') {
            setTimeout(() => loadCharts(), 50);
        }
    });

    refreshAllData();

    // Modo Demo
    document.addEventListener('click', async (e) => {
        if (e.target.id !== 'btnToggleDemo') return;
        e.target.disabled = true;
        try {
            const res = await setDemoMode(!state.demoStatus);
            toastSuccess(res.message);
            document.dispatchEvent(new CustomEvent('lukapp:admin-updated'));
        } catch (error) {
            toastError(errorMessage(error));
            e.target.disabled = false;
        }
    });

    // Buscador (en servidor)
    document.querySelector('.admin-search-input')?.addEventListener('input', (e) => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.query = e.target.value.trim();
            state.page = 1;
            loadUsers();
        }, 300);
    });

    // Paginación de usuarios
    document.getElementById('adminUsersPrev')?.addEventListener('click', () => { state.page--; loadUsers(); });
    document.getElementById('adminUsersNext')?.addEventListener('click', () => { state.page++; loadUsers(); });
}

async function refreshAllData() {
    await Promise.all([loadKPIs(), loadUsers(), loadSystem(), loadCharts()]);
}

async function loadKPIs() {
    try { renderKPIs(await getGlobalKPIs()); } catch (error) { toastError(errorMessage(error)); }
}

async function loadUsers() {
    try {
        const { users, meta } = await getUsers({ query: state.query, page: state.page });
        if (!users.length && meta.current_page > 1) {
            state.page = meta.last_page;
            return loadUsers();
        }
        state.users = users;
        state.meta = meta;
        renderUsersTable(users, handleUserAction);
        renderUsersPagination(meta);
    } catch (error) {
        toastError(errorMessage(error));
    }
}

async function loadSystem() {
    try {
        const metrics = await getSystemMetrics();
        state.demoStatus = metrics.demoStatus;
        renderSystemMetrics(metrics);
    } catch (error) {
        toastError(errorMessage(error));
    }
}

async function loadCharts() {
    try { renderAdminCharts(await getGlobalStats()); } catch (error) { toastError(errorMessage(error)); }
}

async function runAction(action, eventName) {
    try {
        const res = await action();
        toastSuccess(res.message);
        document.dispatchEvent(new CustomEvent(eventName));
    } catch (error) {
        toastError(errorMessage(error));
    }
}

async function handleUserAction(action, userId) {
    const user = state.users.find(u => String(u.id) === String(userId));
    if (!user) return;

    if (user.role === 'admin' && ['block', 'delete', 'reset'].includes(action)) {
        openAdminModal('Acción Denegada', '<p>Esta cuenta es una cuenta protegida del sistema. No puede ser alterada.</p>');
        return;
    }

    if (action === 'view') {
        let details;
        try {
            details = await getUserDetails(userId);
        } catch (error) {
            toastError(errorMessage(error));
            return;
        }

        const themeStr = details.settings?.appearance?.theme || 'light';
        const dateAccess = details.last_access_at ? new Date(details.last_access_at).toLocaleString('es-CO') : 'Nunca';
        const dateReg = details.created_at ? new Date(details.created_at).toLocaleDateString('es-CO') : 'Desconocida';

        const content = `
            <div style="display:flex; flex-direction:column; gap:16px; text-align:left;">
                <div style="background:var(--bg-muted); padding:16px; border-radius:8px;">
                    <h3 style="margin-bottom:4px; font-size:16px;">${escapeHTML(details.name || 'Usuario')}</h3>
                    <p class="text-muted" style="font-size:13px;">${escapeHTML(details.email)} · Rol: ${escapeHTML(details.role)}</p>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; font-size:12px;">
                    <div style="border:1px solid var(--border-color); padding:12px; border-radius:8px;">
                        <span class="text-muted" style="display:block;">Registro:</span> <strong>${dateReg}</strong>
                    </div>
                    <div style="border:1px solid var(--border-color); padding:12px; border-radius:8px;">
                        <span class="text-muted" style="display:block;">Último acceso:</span> <strong>${dateAccess}</strong>
                    </div>
                </div>
                <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; font-size:12px; text-align:center;">
                    <div style="background:var(--bg-surface); border:1px solid var(--border-color); padding:12px; border-radius:8px;">
                        <strong style="font-size:18px;">${Number(details.movementsCount)}</strong><br><span class="text-muted">Movimientos</span>
                    </div>
                    <div style="background:var(--bg-surface); border:1px solid var(--border-color); padding:12px; border-radius:8px;">
                        <strong style="font-size:18px;">${Number(details.goalsCount)}</strong><br><span class="text-muted">Metas</span>
                    </div>
                    <div style="background:var(--bg-surface); border:1px solid var(--border-color); padding:12px; border-radius:8px;">
                        <strong style="font-size:18px;">${Number(details.notifsCount)}</strong><br><span class="text-muted">Notifs</span>
                    </div>
                </div>
                <div style="border:1px solid var(--border-color); padding:12px; border-radius:8px; font-size:12px;">
                    <span class="text-muted" style="display:block;">Tema UI:</span> <strong>${escapeHTML(themeStr.toUpperCase())}</strong>
                </div>
                <button class="btn-danger-outline" id="btnResetData" style="margin-top:8px;">Resetear datos (Conserva cuenta)</button>
            </div>
        `;
        openAdminModal('Detalles de Usuario', content);

        setTimeout(() => {
            document.getElementById('btnResetData')?.addEventListener('click', () => {
                if (confirm('¿Eliminar todos los movimientos, metas y notificaciones de este usuario?')) {
                    document.querySelector('.modal-overlay')?.remove();
                    runAction(() => resetUserData(userId), 'lukapp:user-reset');
                }
            });
        }, 50);
    }

    if (action === 'block') {
        const isBlocked = user.blocked;
        const msg = isBlocked ? '¿Restaurar el acceso de este usuario?' : '¿Suspender cuenta? El usuario no podrá iniciar sesión.';
        openAdminModal('Seguridad de Cuenta', `<p>${msg}</p>`, () => {
            runAction(() => setUserBlocked(userId, !isBlocked), isBlocked ? 'lukapp:user-unblocked' : 'lukapp:user-blocked');
        }, isBlocked ? 'Desbloquear' : 'Bloquear', !isBlocked);
    }

    if (action === 'delete') {
        openAdminModal('Eliminar Usuario', `<p>¿Borrar absolutamente todo el rastro de <strong>${escapeHTML(user.name || 'este usuario')}</strong>? Esta acción no se puede deshacer.</p>`, () => {
            runAction(() => deleteUser(userId), 'lukapp:user-deleted');
        }, 'Eliminar Permanentemente', true);
    }
}
