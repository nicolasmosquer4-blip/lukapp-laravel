/* =========================================================
   LukApp · adminView.js
   Maneja la UI del Backoffice.
   ========================================================= */
export function setupAdminNavigation(onChange) {
    const navButtons = document.querySelectorAll('.nav-link-btn');
    navButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            document.querySelectorAll('.admin-section, .nav-item').forEach(el => {
                el.classList.remove('active');
            });
            e.currentTarget.closest('.nav-item').classList.add('active');
            document.getElementById(`section-${e.currentTarget.dataset.target}`).classList.add('active');
            
            const title = e.currentTarget.querySelector('span:not(.nav-icon)').textContent;
            document.getElementById('topbarTitleDynamic').textContent = title;
            
            if (window.innerWidth <= 900) document.getElementById('sidebar')?.classList.remove('is-open');
            onChange(e.currentTarget.dataset.target);
        });
    });
}

export function renderKPIs(kpis) {
    document.getElementById('mockUsers').textContent = kpis.totalUsers;
    document.getElementById('mockMovements').textContent = kpis.totalMovements;
    document.getElementById('mockGoals').textContent = kpis.totalGoals;
    document.getElementById('mockNotifications').textContent = kpis.totalNotifs;
}

export function renderUsersTable(users, onAction) {
    const tbody = document.getElementById('adminUsersTableBody');
    if (!tbody) return;
    
    if(users.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios registrados.</td></tr>';
        return;
    }

    let html = '';
    users.forEach(u => {
        const safeName = u.name || 'Usuario Desconocido';
        const safeEmail = u.email || 'Sin correo';
        const initials = safeName.slice(0, 2).toUpperCase();
        
        const dateStr = u.created_at ? new Date(u.created_at).toLocaleDateString('es-CO') : 'Desconocida';
        const statusClass = u.blocked ? 'status-blocked' : 'status-active';
        const statusText = u.blocked ? 'Bloqueado' : 'Activo';

        html += `
            <tr>
                <td>
                    <div class="user-cell">
                        <div class="user-avatar-sm">${escapeHTML(initials)}</div>
                        <span class="user-cell-name">${escapeHTML(safeName)}</span>
                    </div>
                </td>
                <td class="text-muted">${escapeHTML(safeEmail)}</td>
                <td><span class="role-badge role-user">User</span></td>
                <td class="text-muted font-size-12">${dateStr}</td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
                <td class="text-right">
                    <button class="admin-action-btn" data-action="view" data-id="${escapeHTML(u.id)}" title="Ver Detalles">👁️</button>
                    <button class="admin-action-btn" data-action="block" data-id="${escapeHTML(u.id)}" title="${u.blocked ? 'Desbloquear' : 'Bloquear'}">${u.blocked ? '🔓' : '🔒'}</button>
                    <!-- ELIMINADO: Botón de suplantación -->
                    <button class="admin-action-btn btn-delete" data-action="delete" data-id="${escapeHTML(u.id)}" title="Eliminar cuenta">🗑️</button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    tbody.querySelectorAll('.admin-action-btn').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const btnEl = e.currentTarget;
            onAction(btnEl.dataset.action, btnEl.dataset.id);
        });
    });
}

export function renderUsersPagination(meta) {
    const info = document.getElementById('adminUsersPaginationInfo');
    const prev = document.getElementById('adminUsersPrev');
    const next = document.getElementById('adminUsersNext');
    if (info) info.textContent = meta.total ? `Mostrando ${meta.from}-${meta.to} de ${meta.total} usuarios` : 'Sin resultados';
    if (prev) prev.disabled = meta.current_page <= 1;
    if (next) next.disabled = meta.current_page >= meta.last_page;
}

// Modal Genérico Administrativo Perfeccionado
export function openAdminModal(title, contentHTML, onConfirm = null, confirmText = 'Confirmar', isDanger = false) {
    document.querySelector('.modal-overlay')?.remove();
    
    const modal = document.createElement('div');
    modal.className = 'modal-overlay';
    
    modal.innerHTML = `
        <div class="modal" role="dialog" aria-modal="true">
            <div class="modal__header">
                <h2 class="modal__title">${title}</h2>
                <button type="button" class="modal__close" id="btnCloseModal" title="Cerrar">×</button>
            </div>
            <div class="modal__body">
                ${contentHTML}
                ${onConfirm ? `
                    <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:32px;">
                        <button class="btn-secondary" id="btnCancelModal">Cancelar</button>
                        <button class="btn-primary" style="${isDanger ? 'background:var(--danger-color); color:#fff; border:none;' : ''}" id="btnConfirmModal">${confirmText}</button>
                    </div>
                ` : ''}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);

    modal.querySelector('#btnCloseModal').addEventListener('click', () => modal.remove());
    modal.querySelector('#btnCancelModal')?.addEventListener('click', () => modal.remove());
    modal.addEventListener('click', (e) => { if (e.target === modal) modal.remove(); });
    
    if (onConfirm) {
        modal.querySelector('#btnConfirmModal').addEventListener('click', () => {
            onConfirm();
            modal.remove();
        });
    }
}

export function renderSystemMetrics(metrics) {
    const db = metrics.database || {};
    const dbOk = db.status === 'ok';
    const dbStatus = document.getElementById('systemDbStatus');
    const dbIcon = document.getElementById('systemDbIcon');
    if (dbStatus) dbStatus.textContent = dbOk ? `${String(db.driver).toUpperCase()} · ${db.name} · ${db.tables} tablas` : 'Sin conexión con la base de datos';
    if (dbIcon) { dbIcon.className = `system-icon ${dbOk ? 'success' : 'warning'}`; dbIcon.textContent = dbOk ? '✓' : '!'; }

    const version = document.getElementById('systemVersion');
    if (version && metrics.version) version.textContent = `LukApp Core v${metrics.version.app} · Laravel ${metrics.version.laravel} · PHP ${metrics.version.php}`;

    const storage = document.getElementById('systemStorage');
    if (storage) storage.textContent = db.sizeKB !== null && db.sizeKB !== undefined ? `Aprox. ${db.sizeKB} KB en MySQL` : 'No disponible';
    const demoEl = document.querySelector('.system-card:nth-child(3)');
    const isDemo = metrics.demoStatus;
    demoEl.innerHTML = `
        <div class="system-icon ${isDemo ? 'warning' : 'info'}">${isDemo ? '!' : '✓'}</div>
        <div class="system-info">
            <strong>Modo Demo</strong>
            <span>${isDemo ? 'Habilitado: los nuevos registros reciben datos de ejemplo' : 'Desactivado: los usuarios inician desde cero'}</span>
            <button class="btn-secondary btn-sm" id="btnToggleDemo" style="margin-top:8px;">${isDemo ? 'Desactivar' : 'Activar'}</button>
        </div>
    `;
}

let userChart = null; 
let transChart = null;

export function renderAdminCharts(stats) {
    if (typeof Chart === 'undefined') {
        console.warn('LukApp: Chart.js no está cargado. Revisa el <head> de pages/admin.blade.php');
        return;
    }

    const ctxU = document.getElementById('chartAdminUsers');
    const ctxT = document.getElementById('chartAdminTransactions');

    // Si los contenedores no existen en el DOM, no hacer nada
    if (!ctxU || !ctxT) return;

    // Destruir instancias previas para evitar "Memory Leaks" y solapamientos
    if (userChart) userChart.destroy();
    if (transChart) transChart.destroy();

    // 1. GRÁFICA: Crecimiento de Usuarios (Líneas)
    userChart = new Chart(ctxU, {
        type: 'line',
        data: { 
            labels: stats.userLabels, 
            datasets: [{ 
                label: 'Nuevos Usuarios', 
                data: stats.usersByMonth, 
                borderColor: '#3B82F6', // Azul info-color
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#3B82F6',
                pointRadius: 4,
                tension: 0.3, 
                fill: true 
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { 
                legend: { display: false },
                tooltip: { backgroundColor: '#111', titleColor: '#fff', bodyColor: '#fff', padding: 10 }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. GRÁFICA: Volumen de Transacciones (Barras)
    transChart = new Chart(ctxT, {
        type: 'bar',
        data: { 
            labels: ['Ingresos Glob.', 'Gastos Glob.'], 
            datasets: [{ 
                data: [stats.totalIncome, stats.totalExpense], 
                backgroundColor: ['#1FA36A', '#E04848'], // success-color y danger-color
                borderRadius: 6,
                barThickness: 40
            }] 
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false, 
            plugins: { 
                legend: { display: false },
                tooltip: { 
                    backgroundColor: '#111', 
                    padding: 10,
                    callbacks: {
                        label: (ctx) => ' ' + Number(ctx.raw).toLocaleString('es-CO', {style: 'currency', currency: 'COP', maximumFractionDigits: 0})
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f1ef' } },
                x: { grid: { display: false } }
            }
        }
    });
}

export function escapeHTML(str) { return String(str ?? '').replace(/[&<>'"]/g, tag => ({'&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;'}[tag])); }