/* =========================================================
    LukApp · dashboardController.js
    ========================================================= */

import { getDashboardData } from './dashboardModel.js';
import { renderDashboard } from './dashboardView.js';
import { route } from '../core/session.js';
import { errorMessage } from '../services/apiClient.js';
import { toastError } from '../components/toast.js';

document.addEventListener('DOMContentLoaded', initializeDashboard);

// Sincronización global sin recargar
document.addEventListener('lukapp:user-updated', refreshDashboard);
document.addEventListener('lukapp:data-updated', refreshDashboard);

async function refreshDashboard() {
    try {
        renderDashboard(await getDashboardData());
    } catch (error) {
        toastError(errorMessage(error));
    }
}

function initializeDashboard() {
    refreshDashboard();

    // Redirección automática a movimientos con el modal abierto
    document.getElementById('btnAgregarMovimiento')?.addEventListener('click', () => {
        window.location.href = `${route('movements')}?action=new`;
    });
}
