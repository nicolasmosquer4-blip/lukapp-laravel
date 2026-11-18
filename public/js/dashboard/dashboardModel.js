/* =========================================================
   LukApp · dashboardModel.js
   Obtiene el resumen consolidado desde GET /api/dashboard.
   Los cálculos (totales, recientes, tendencia mensual) se
   hacen en el servidor con consultas agregadas a MySQL.
   ========================================================= */
import { api } from '../services/apiClient.js';

export async function getDashboardData() {
    const res = await api.get('/dashboard');
    return res.data;
}
