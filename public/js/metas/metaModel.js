/* =========================================================
    LukApp · metaModel.js
    Metas de ahorro contra la API REST. Abonos y retiros se
    registran como historial (tabla goal_contributions) y la
    notificación de "meta alcanzada" la genera el servidor.
    ========================================================= */
import { api } from '../services/apiClient.js';

/** @returns {Promise<{goals:Array, summary:{saved:number,target:number,progress:number,count:number}}>} */
export async function getGoals() {
    const res = await api.get('/goals');
    return { goals: res.data, summary: res.summary };
}

export async function createGoal(data) {
    return api.post('/goals', {
        name: data.name,
        icon: data.icon || '🎯',
        target: Number(data.target),
        saved: Number(data.saved || 0),
        deadline: data.deadline || null
    });
}

// El ahorro ("saved") NO se edita aquí: solo cambia con abonos/retiros.
export async function editGoal(id, data) {
    return api.put(`/goals/${encodeURIComponent(id)}`, {
        name: data.name,
        icon: data.icon,
        target: Number(data.target),
        deadline: data.deadline || null
    });
}

// Abonos y retiros. type: 'abonar' | 'retirar'
export async function contributeToGoal(id, amount, type = 'abonar', comment = '') {
    return api.post(`/goals/${encodeURIComponent(id)}/contributions`, {
        type,
        amount: Number(amount),
        comment: comment || null
    });
}

export async function removeGoal(id) {
    return api.delete(`/goals/${encodeURIComponent(id)}`);
}
