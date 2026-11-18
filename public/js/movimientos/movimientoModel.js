/* =========================================================
   LukApp · movimientoModel.js
   CRUD de movimientos contra la API REST.
   Reglas de negocio (estado por fecha, totales, pertenencia
   al usuario) se aplican en el servidor.
   ========================================================= */
import { api, apiUrl } from '../services/apiClient.js';

let categoriesCache = null;

/**
 * Lista paginada y filtrada.
 * @returns {Promise<{items:Array, meta:Object, totals:Object}>}
 */
export async function getMovements({ page = 1, perPage = 8, type = 'todos', query = '' } = {}) {
    const res = await api.get('/movements', {
        page,
        per_page: perPage,
        type: type === 'todos' ? '' : type,
        q: query
    });
    return { items: res.data, meta: res.meta, totals: res.totals };
}

export async function getMovementById(id) {
    const res = await api.get(`/movements/${encodeURIComponent(id)}`);
    return res.data;
}

export async function createMovement(data) {
    return api.post('/movements', toPayload(data));
}

export async function editMovement(id, data) {
    return api.put(`/movements/${encodeURIComponent(id)}`, toPayload(data));
}

export async function removeMovement(id) {
    return api.delete(`/movements/${encodeURIComponent(id)}`);
}

export async function removeMovements(ids) {
    return api.delete('/movements', { ids: ids.map(Number) });
}

/** Categorías desde la BD (tabla categories). */
export async function getCategories() {
    if (!categoriesCache) {
        const res = await api.get('/categories');
        categoriesCache = res.data;
    }
    return categoriesCache;
}

/** URL de descarga CSV con los mismos filtros de la tabla. */
export function getExportUrl({ type = 'todos', query = '' } = {}) {
    return apiUrl('/movements/export', { type: type === 'todos' ? '' : type, q: query });
}

function toPayload(data) {
    return {
        description: data.description,
        type: data.type,
        category: data.category,
        date: data.date,
        amount: Number(data.amount),
        status: data.status || undefined
    };
}
