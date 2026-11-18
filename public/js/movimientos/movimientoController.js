import '../core/app.js';
/* =========================================================
    LukApp · movimientoController.js
    Tabla de movimientos con paginación, filtros y búsqueda
    resueltos en el servidor (GET /api/movements).
    ========================================================= */

import { getMovements, getCategories, createMovement, editMovement, removeMovement, removeMovements, getExportUrl } from './movimientoModel.js';
import { renderSummary, renderTable, renderPagination, openMovementModal, closeMovementModal, getMovementFormData, setCategories, setModalSaving } from './movimientoView.js';
import { printTableAsPDF } from '../services/exportService.js';
import { errorMessage } from '../services/apiClient.js';
import { route } from '../core/session.js';
import { toastSuccess, toastError } from '../components/toast.js';

const PAGE_SIZE = 8;

const state = { items: [], meta: null, page: 1, filter: 'todos', query: '' };
let searchTimer = null;
let requestId = 0;

document.addEventListener('DOMContentLoaded', initializeMovements);

// Sincronización global sin recargar
document.addEventListener('lukapp:data-updated', () => load());

async function initializeMovements() {
    document.getElementById('btnNuevoMovimiento')?.addEventListener('click', () => showForm());
    document.getElementById('btnExportCSV')?.addEventListener('click', exportCSV);
    document.getElementById('btnExportPDF')?.addEventListener('click', exportPDF);
    document.getElementById('tableSearchInput')?.addEventListener('input', event => {
        // Búsqueda en servidor con "debounce" para no saturar la API
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            state.query = event.target.value.trim();
            state.page = 1;
            load();
        }, 300);
    });

    document.querySelectorAll('.filter-tabs .tab-btn').forEach(button => {
        button.addEventListener('click', () => {
            document.querySelectorAll('.filter-tabs .tab-btn').forEach(btn => btn.classList.remove('active'));
            button.classList.add('active');
            state.filter = button.dataset.filter;
            state.page = 1;
            load();
        });
    });

    document.getElementById('selectAllCheckbox')?.addEventListener('change', event => {
        document.querySelectorAll('.row-checkbox').forEach(box => { box.checked = event.target.checked; });
        updateSelection();
    });

    document.getElementById('movimientosTableBody')?.addEventListener('change', event => {
        if (event.target.classList.contains('row-checkbox')) updateSelection();
    });

    document.getElementById('movimientosTableBody')?.addEventListener('click', async event => {
        const btnEdit = event.target.closest('[data-action="edit"]');
        const btnDel = event.target.closest('[data-action="delete"]');

        if (btnEdit) {
            const movement = state.items.find(item => String(item.id) === btnEdit.dataset.id);
            if (movement) showForm(movement);
        }
        if (btnDel && confirm('¿Eliminar este movimiento?')) {
            try {
                const res = await removeMovement(btnDel.dataset.id);
                toastSuccess(res.message || 'Movimiento eliminado.');
                emitDataUpdated();
            } catch (error) {
                toastError(errorMessage(error));
            }
        }
    });

    document.getElementById('paginationControls')?.addEventListener('click', event => {
        const pageButton = event.target.closest('[data-page]');
        if (!pageButton) return;
        state.page = Number(pageButton.dataset.page);
        load();
    });

    document.getElementById('btnDeleteSelected')?.addEventListener('click', deleteSelected);

    document.addEventListener('lukapp:search', event => {
        const input = document.getElementById('tableSearchInput');
        if (input) input.value = event.detail.query;
        state.query = event.detail.query;
        state.page = 1;
        load();
    });

    const params = new URLSearchParams(location.search);
    const initialSearch = params.get('search');
    if (initialSearch) {
        const input = document.getElementById('tableSearchInput');
        if (input) input.value = initialSearch;
        state.query = initialSearch;
    }

    // Categorías desde la BD (necesarias para el formulario)
    try {
        setCategories(await getCategories());
    } catch (error) {
        toastError(errorMessage(error));
    }

    await load();

    // Redirección desde el Dashboard: abre el modal y limpia la URL
    if (params.get('action') === 'new') {
        showForm();
        window.history.replaceState(null, '', route('movements'));
    }
}

async function load() {
    const current = ++requestId;
    try {
        const { items, meta, totals } = await getMovements({
            page: state.page, perPage: PAGE_SIZE, type: state.filter, query: state.query
        });
        if (current !== requestId) return; // respuesta obsoleta (el usuario siguió escribiendo)

        // Si se borró el último registro de la página, retrocede una página
        if (!items.length && meta.current_page > 1) {
            state.page = meta.last_page;
            return load();
        }

        state.items = items;
        state.meta = meta;
        state.page = meta.current_page;

        renderSummary(totals, meta.total);
        renderTable(items);
        renderPagination(meta.current_page, meta.last_page, meta.total, meta.from, meta.to);
        const selectAll = document.getElementById('selectAllCheckbox');
        if (selectAll) selectAll.checked = false;
        updateSelection();
    } catch (error) {
        toastError(errorMessage(error));
    }
}

function showForm(movement = null) {
    const form = openMovementModal(movement);
    if (!form) return;

    // Sin { once: true }: si la validación falla, el formulario sigue funcionando
    form.addEventListener('submit', async event => {
        event.preventDefault();
        const data = getMovementFormData(form);

        if (Number(data.amount) <= 0) {
            toastError('El monto debe ser mayor a 0.');
            return;
        }
        if (!data.description || !data.category || !data.date) {
            toastError('Completa correctamente los datos del movimiento.');
            return;
        }

        setModalSaving(true);
        try {
            const res = movement ? await editMovement(movement.id, data) : await createMovement(data);
            closeMovementModal();
            toastSuccess(res.message || 'Movimiento guardado.');
            emitDataUpdated(); // el listener global recarga la tabla y las notificaciones
        } catch (error) {
            setModalSaving(false);
            toastError(errorMessage(error));
        }
    });
}

function emitDataUpdated() {
    document.dispatchEvent(new CustomEvent('lukapp:data-updated'));
}

function updateSelection() {
    const selected = document.querySelectorAll('.row-checkbox:checked');
    const group = document.getElementById('batchActionGroup');
    const count = document.getElementById('selectedCountText');

    if (group) group.classList.toggle('hidden', selected.length === 0);
    if (count) count.textContent = `${selected.length} seleccionado${selected.length === 1 ? '' : 's'}`;
}

async function deleteSelected() {
    const ids = [...document.querySelectorAll('.row-checkbox:checked')].map(box => box.dataset.id);
    if (!ids.length) return;
    if (!confirm(`¿Eliminar ${ids.length} movimiento${ids.length === 1 ? '' : 's'}?`)) return;

    try {
        const res = await removeMovements(ids);
        toastSuccess(res.message);
        emitDataUpdated();
    } catch (error) {
        toastError(errorMessage(error));
    }
}

function exportCSV() {
    if (!state.meta?.total) {
        toastError('No hay datos para exportar.');
        return;
    }
    // Descarga generada por el servidor con los filtros activos (todas las páginas)
    const link = document.createElement('a');
    link.href = getExportUrl({ type: state.filter, query: state.query });
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
}

function exportPDF() {
    try {
        printTableAsPDF({
            title: 'Movimientos financieros · LukApp',
            subtitle: `${state.meta?.total ?? 0} registros encontrados · página ${state.page} de ${state.meta?.last_page ?? 1}`,
            filename: 'lukapp-movimientos'
        });
    } catch (error) {
        toastError(error.message);
    }
}
