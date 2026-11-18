/* =========================================================
   LukApp · estadisticaModel.js
   KPIs y series para los gráficos desde GET /api/statistics.
   El servidor agrupa por mes, categoría y día (ver StatisticsService).
   ========================================================= */
import { api } from '../services/apiClient.js';

/** @param {'6M'|'1A'|'TODO'} periodFilter */
export async function getStatisticsData(periodFilter = '6M') {
    const res = await api.get('/statistics', { period: periodFilter });
    return res.data;
}
