@extends('layouts.panel', [
    'active' => 'dashboard',
    'topbarTitle' => 'Dashboard',
    'topbarLabel' => 'Panel financiero',
])

@section('title', 'LukApp - Dashboard')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
@endpush

@section('content')
    <main class="dashboard-container">

        <!-- Encabezado -->
        <header class="dashboard-header">

            <div class="header-titles">

                <span
                    class="current-month"
                    id="currentMonth">
                    Mes actual
                </span>

                <h1
                    class="greeting"
                    id="dashboardGreeting">
                    Hola
                </h1>

            </div>

            <button
                type="button"
                class="btn-primary"
                id="btnAgregarMovimiento">

                <span aria-hidden="true">+</span>
                Agregar movimiento

            </button>

        </header>

        <!-- =================================================
             KPI
        ================================================== -->
        <section
            class="kpi-grid"
            aria-label="Resumen financiero">

            <!-- Saldo -->
            <article class="kpi-card active-card">

                <span class="kpi-label">
                    SALDO TOTAL
                </span>

                <h2
                    class="kpi-value mono"
                    id="kpiSaldoTotal">
                    $0
                </h2>

                <span
                    class="kpi-trend"
                    id="kpiSaldoTrend">
                </span>

            </article>

            <!-- Ingresos -->
            <article class="kpi-card">

                <span class="kpi-label">
                    INGRESOS
                </span>

                <h2
                    class="kpi-value mono positive"
                    id="kpiIngresos">
                    $0
                </h2>

                <span
                    class="kpi-trend positive"
                    id="kpiIngresosTrend">
                </span>

            </article>

            <!-- Gastos -->
            <article class="kpi-card">

                <span class="kpi-label">
                    GASTOS
                </span>

                <h2
                    class="kpi-value mono"
                    id="kpiGastos">
                    $0
                </h2>

                <span
                    class="kpi-trend negative"
                    id="kpiGastosTrend">
                </span>

            </article>

            <!-- Ahorros -->
            <article class="kpi-card">

                <span class="kpi-label">
                    AHORROS
                </span>

                <h2
                    class="kpi-value mono positive"
                    id="kpiAhorros">
                    $0
                </h2>

                <span
                    class="kpi-trend positive"
                    id="kpiAhorrosTrend">
                </span>

            </article>

        </section>

        <!-- =================================================
             CONTENIDO INFERIOR
        ================================================== -->
        <section class="dashboard-widgets">

            <!-- =================================================
                 ÚLTIMOS MOVIMIENTOS
            ================================================== -->
            <section class="widget-panel col-span-2">

                <div class="widget-header">

                    <div>
                        <span class="section-eyebrow">
                            Actividad reciente
                        </span>

                        <h3>
                            Últimos movimientos
                        </h3>
                    </div>

                    <a
                        href="{{ route('movements') }}"
                        class="link-view-all">
                        Ver todos →
                    </a>

                </div>

                <ul
                    class="transactions-list"
                    id="recentTransactionsList">

                    <!--
                        Las transacciones serán insertadas
                        dinámicamente mediante View/Controller.
                    -->

                    <li class="empty-state" id="transactionsEmptyState">
                        No hay movimientos registrados.
                    </li>

                </ul>

            </section>

            <!-- =================================================
                 COLUMNA DERECHA
            ================================================== -->
            <div class="widget-column">

                <!-- =================================================
                     TENDENCIA DE GASTOS
                ================================================== -->
                <section class="widget-panel">

                    <div class="widget-header">

                        <div>

                            <span class="section-eyebrow">
                                Análisis
                            </span>

                            <h3>
                                Tendencia de gastos
                            </h3>

                        </div>

                    </div>

                    <div class="trend-summary">

                        <h2
                            class="mono"
                            id="trendExpensesTotal">
                            $0
                        </h2>

                        <span
                            class="trend-subtitle"
                            id="trendExpensesComparison">
                            Sin comparación disponible
                        </span>

                    </div>

                    <div
                        class="chart-container-small"
                        id="miniChart">

                        <!--
                            Chart.js podrá renderizar aquí
                            cuando implementemos el View.
                        -->

                        <canvas
                            id="dashboardExpenseChart"
                            aria-label="Tendencia de gastos">
                        </canvas>

                        <div
                            class="placeholder-chart"
                            id="miniChartPlaceholder">
                            Cargando tendencia...
                        </div>

                    </div>

                </section>

                <!-- =================================================
                     METAS ACTIVAS
                ================================================== -->
                <section class="widget-panel">

                    <div class="widget-header">

                        <div>

                            <span class="section-eyebrow">
                                Planificación
                            </span>

                            <h3>
                                Metas activas
                            </h3>

                        </div>

                        <a
                            href="{{ route('goals') }}"
                            class="link-view-all">
                            Ver todas →
                        </a>

                    </div>

                    <ul
                        class="goals-mini-list"
                        id="activeGoalsList">

                        <!--
                            Las metas serán generadas
                            dinámicamente por el MVC.
                        -->

                        <li
                            class="empty-state"
                            id="goalsEmptyState">
                            No hay metas activas.
                        </li>

                    </ul>

                </section>

            </div>

        </section>

    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/core/app.js') }}"></script>
    <script type="module" src="{{ asset('js/core/theme.js') }}"></script>
    <script type="module" src="{{ asset('js/dashboard/dashboardController.js') }}"></script>
@endpush
