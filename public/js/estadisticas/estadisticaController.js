/* =========================================================
   LukApp · estadisticaController.js
   ========================================================= */
import { getStatisticsData } from './estadisticaModel.js';
import { renderAll } from './estadisticaView.js';
import { errorMessage } from '../services/apiClient.js';
import { toastError } from '../components/toast.js';

const state = { period: '6M' };
let requestId = 0;

async function refreshData() {
    const current = ++requestId;
    try {
        const data = await getStatisticsData(state.period);
        if (current !== requestId) return; // se cambió de filtro mientras cargaba
        renderAll(data, state.period);
    } catch (error) {
        console.error('[Estadísticas] Error al cargar:', error);
        toastError(errorMessage(error));
    }
}

function initialize() {
    const group = document.getElementById('statsFilterGroup');

    if (group) {
        group.addEventListener('click', (e) => {
            const btn = e.target.closest('.tab-btn');
            if (!btn || !group.contains(btn)) return;

            group.querySelectorAll('.tab-btn').forEach(b => {
                b.classList.toggle('active', b === btn);
            });

            state.period = btn.dataset.filter || '6M';
            refreshData();
        });
    }

    refreshData();
}

// Evento global de actualización de datos
document.addEventListener('lukapp:data-updated', refreshData);

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
} else {
    initialize();
}