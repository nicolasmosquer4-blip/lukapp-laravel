@extends('layouts.panel', [
    'active' => 'statistics',
    'topbarTitle' => 'Estadísticas',
    'topbarLabel' => 'Panel financiero',
])

@section('title', 'LukApp - Estadísticas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estadisticas.css') }}">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
@endpush

@section('content')
    <main class="dashboard-container estadisticas-container">

        <!-- Encabezado -->
        <header class="page-header stats-page-header">
            <div class="header-titles">
                <h1>Estadísticas</h1>
                <span class="records-count" id="statsPeriodLabel">Análisis financiero · Cargando...</span>
            </div>

            <!-- Filtros segmentados -->
            <div class="filter-tabs" id="statsFilterGroup">
                <button type="button" class="tab-btn active" data-filter="6M">6M</button>
                <button type="button" class="tab-btn" data-filter="1A">1A</button>
                <button type="button" class="tab-btn" data-filter="TODO">Todo</button>
            </div>
        </header>

        <!-- KPIs -->
        <section class="summary-cards-grid stats-kpi-grid" aria-label="Métricas financieras">

            <article class="summary-card">
                <span class="summary-label">INGRESO PROMEDIO</span>
                <span class="summary-value positive mono" id="kpiIngresoPromedio">$0</span>
                <span class="text-muted stats-kpi-note">mensual</span>
            </article>

            <article class="summary-card">
                <span class="summary-label">GASTO PROMEDIO</span>
                <span class="summary-value negative mono" id="kpiGastoPromedio">$0</span>
                <span class="text-muted stats-kpi-note">mensual</span>
            </article>

            <article class="summary-card">
                <span class="summary-label">TASA DE AHORRO</span>
                <span class="summary-value mono" id="kpiTasaAhorro" style="color: var(--warning-color, #f59e0b)">0%</span>
                <span class="text-muted stats-kpi-note">del ingreso</span>
            </article>

            <article class="summary-card">
                <span class="summary-label">MEJOR MES</span>
                <span class="summary-value" id="kpiMejorMes" style="color: var(--info-color, #3b82f6)">--</span>
                <span class="text-muted stats-kpi-note" id="kpiMejorMesMonto">$0 ingresados</span>
            </article>

        </section>

        <!-- Gráficos superiores -->
        <section class="charts-layout-top" aria-label="Gráficos principales">

            <!-- Ingresos vs Gastos (Líneas) -->
            <article class="chart-card">
                <div class="chart-card-header" style="justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <h3 style="font-size: 15px;">Ingresos vs Gastos</h3>
                        <span class="text-muted" style="font-size: 12px;">Comparativo histórico</span>
                    </div>
                    <div style="display: flex; gap: 12px; font-size: 11px; color: var(--text-muted); font-weight: 600;">
                        <span style="display: flex; align-items: center; gap: 4px;">
                            <span style="width: 8px; height: 8px; background: var(--success-color, #22c55e); border-radius: 2px;"></span> Ingresos
                        </span>
                        <span style="display: flex; align-items: center; gap: 4px;">
                            <span style="width: 8px; height: 8px; background: var(--danger-color, #ef4444); border-radius: 2px;"></span> Gastos
                        </span>
                    </div>
                </div>
                <div class="chart-wrapper" style="height: 250px;">
                    <canvas id="chartComparativa" aria-label="Comparación entre ingresos y gastos"></canvas>
                </div>
            </article>

            <!-- Distribución (Dona) -->
            <article class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3 style="font-size: 15px;">Distribución de gastos</h3>
                        <span class="text-muted" style="font-size: 12px;" id="donutPeriodLabel">Por categoría</span>
                    </div>
                </div>
                <div class="donut-container" style="display: flex; flex-direction: column; align-items: center; gap: 24px;">
                    <div class="chart-wrapper" style="height: 160px; width: 160px; margin: 0 auto;">
                        <canvas id="chartCategorias" aria-label="Gastos por categoría"></canvas>
                    </div>
                    <ul class="custom-legend" id="donutLegend" style="width: 100%; display: flex; flex-direction: column; gap: 8px;">
                        <!-- Generado por JS -->
                    </ul>
                </div>
            </article>

        </section>

        <!-- Gráficos inferiores -->
        <section class="charts-layout-bottom" aria-label="Gráficos secundarios">

            <!-- Gastos por Día (Barras) -->
            <article class="chart-card">
                <div class="chart-card-header">
                    <div>
                        <h3 style="font-size: 15px;">Gastos por día</h3>
                        <span class="text-muted" style="font-size: 12px;">Últimos 7 días</span>
                    </div>
                </div>
                <div class="chart-wrapper" style="height: 220px; margin-top: 20px;">
                    <canvas id="chartGastosDia" aria-label="Gastos de los últimos 7 días"></canvas>
                </div>
            </article>

            <!-- Ranking Top Categorías -->
            <article class="chart-card">
                <div class="chart-card-header" style="margin-bottom: 24px;">
                    <div>
                        <h3 style="font-size: 15px;">Top categorías</h3>
                        <span class="text-muted" style="font-size: 12px;">Gastos del periodo</span>
                    </div>
                </div>
                <ul class="category-breakdown-list" id="categoryBreakdownList">
                    <!-- Generado por JS -->
                </ul>
            </article>

        </section>

    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/core/app.js') }}"></script>
    <script type="module" src="{{ asset('js/core/theme.js') }}"></script>
    <script type="module" src="{{ asset('js/estadisticas/estadisticaController.js') }}"></script>
@endpush
