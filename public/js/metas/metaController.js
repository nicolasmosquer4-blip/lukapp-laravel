import '../core/app.js';
/* =========================================================
    LukApp · metaController.js
    ========================================================= */

import { getGoals, createGoal, editGoal, contributeToGoal, removeGoal } from './metaModel.js';
import { renderSummary, renderGoals, openGoalModal, openContributeModal, closeModals, getFormData } from './metaView.js';
import { errorMessage } from '../services/apiClient.js';
import { toastSuccess, toastError } from '../components/toast.js';

let goals = [];

document.addEventListener('DOMContentLoaded', initializeGoals);

// Sincronización global (evento viejo + evento nuevo)
document.addEventListener('lukapp:data-changed', render);

function initializeGoals() {
    document.getElementById('btnNuevaMeta')?.addEventListener('click', () => showForm());
    document.getElementById('metasGridContainer')?.addEventListener('click', handleGoalAction);
    render();
}

async function render() {
    try {
        const data = await getGoals();
        goals = data.goals;
        renderSummary(data.summary, data.summary.count);
        renderGoals(goals);
    } catch (error) {
        toastError(errorMessage(error));
    }
}

/** Ejecuta una petición bloqueando el botón de envío para evitar duplicados. */
async function submitWith(form, action) {
    const button = form.querySelector('[type="submit"]');
    if (button) button.disabled = true;
    try {
        const res = await action();
        closeModals();
        toastSuccess(res.message || 'Cambios guardados.');
        emitDataChanged();
    } catch (error) {
        toastError(errorMessage(error));
    } finally {
        if (button) button.disabled = false;
    }
}

function showForm(goal = null) {
    const { form, deleteBtn } = openGoalModal(goal);
    if (!form) return;

    // Sin { once: true }: si la validación falla, el formulario sigue funcionando
    form.addEventListener('submit', event => {
        event.preventDefault();
        const data = getFormData(form);

        if (!data.name || Number(data.target) <= 0) {
            toastError('Completa el nombre y un objetivo válido.');
            return;
        }

        submitWith(form, () => goal ? editGoal(goal.id, data) : createGoal(data));
    });

    if (deleteBtn && goal) {
        deleteBtn.addEventListener('click', () => {
            if (confirm('¿Eliminar esta meta? Se perderá el registro de ahorro.')) {
                submitWith(form, () => removeGoal(goal.id));
            }
        });
    }
}

function handleGoalAction(event) {
    const button = event.target.closest('[data-goal-action]');
    if (!button) return;

    const goal = goals.find(item => String(item.id) === button.dataset.id);
    if (!goal) return;

    const action = button.dataset.goalAction;

    if (action === 'edit' || action === 'menu') showForm(goal);

    if (action === 'contribute') {
        const form = openContributeModal(goal);
        if (!form) return;

        form.addEventListener('submit', e => {
            e.preventDefault();
            const data = getFormData(form);

            if (Number(data.amount) <= 0) {
                toastError('Monto inválido.');
                return;
            }

            // La notificación de "meta alcanzada" la genera el servidor (solo 1 vez)
            submitWith(form, () => contributeToGoal(goal.id, data.amount, data.type, data.comment));
        });
    }
}

// Emite ambos eventos: el viejo (por si otros módulos lo escuchan) y el nuevo
function emitDataChanged() {
    document.dispatchEvent(new CustomEvent('lukapp:data-changed'));
    document.dispatchEvent(new CustomEvent('lukapp:data-updated'));
}
