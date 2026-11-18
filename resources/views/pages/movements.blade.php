@extends('layouts.panel', [
    'active' => 'movements',
    'topbarTitle' => 'Movimientos',
    'topbarLabel' => 'Panel financiero',
])

@section('title', 'LukApp - Movimientos')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/movimientos.css') }}">
@endpush

@section('content')
    <main class="dashboard-container movimientos-container">

        <!-- Encabezado -->
        <header class="page-header">

            <div class="header-titles">

                <span class="page-eyebrow">
                    Registro financiero
                </span>

                <h1>Movimientos</h1>

                <span
                    class="records-count"
                    id="recordsCount">
                    0 registros encontrados
                </span>

            </div>

            <div class="header-actions">

                <button
                    type="button"
                    class="btn-secondary"
                    id="btnExportCSV">
                    Exportar CSV
                </button>

                <button
                    type="button"
                    class="btn-secondary"
                    id="btnExportPDF">
                    Exportar PDF
                </button>

                <button
                    type="button"
                    class="btn-primary"
                    id="btnNuevoMovimiento">
                    <span aria-hidden="true">+</span>
                    Nuevo movimiento
                </button>

            </div>

        </header>

        <!-- =================================================
             RESUMEN FINANCIERO
        ================================================== -->
        <section
            class="summary-cards-grid"
            aria-label="Resumen de movimientos">

            <article class="summary-card">

                <span class="summary-label">
                    Ingresos totales
                </span>

                <span
                    class="summary-value positive mono"
                    id="kpiIngresos">
                    +$0
                </span>

            </article>

            <article class="summary-card">

                <span class="summary-label">
                    Gastos totales
                </span>

                <span
                    class="summary-value negative mono"
                    id="kpiGastos">
                    -$0
                </span>

            </article>

            <article class="summary-card">

                <span class="summary-label">
                    Balance del periodo
                </span>

                <span
                    class="summary-value mono"
                    id="kpiBalance">
                    $0
                </span>

            </article>

        </section>

        <!-- =================================================
             FILTROS
        ================================================== -->
        <section
            class="table-controls-panel"
            aria-label="Filtros de movimientos">

            <div class="filter-tabs">

                <button
                    type="button"
                    class="tab-btn active"
                    data-filter="todos">
                    Todos
                </button>

                <button
                    type="button"
                    class="tab-btn"
                    data-filter="ingreso">
                    Ingresos
                </button>

                <button
                    type="button"
                    class="tab-btn"
                    data-filter="gasto">
                    Gastos
                </button>

            </div>

            <div class="filter-actions">

                <div class="search-box-small">

                    <span aria-hidden="true">⌕</span>

                    <input
                        type="search"
                        id="tableSearchInput"
                        placeholder="Buscar movimiento..."
                        autocomplete="off">

                </div>

                <div
                    class="selection-action hidden"
                    id="batchActionGroup">

                    <span
                        class="selected-count"
                        id="selectedCountText">
                        0 seleccionados
                    </span>

                    <button
                        type="button"
                        class="btn-danger-outline"
                        id="btnDeleteSelected">
                        Eliminar
                    </button>

                </div>

            </div>

        </section>

        <!-- =================================================
             TABLA
        ================================================== -->
        <section
            class="table-responsive-container"
            aria-label="Tabla de movimientos">

            <table class="data-table">

                <thead>

                    <tr>

                        <th class="th-checkbox">
                            <input
                                type="checkbox"
                                id="selectAllCheckbox"
                                aria-label="Seleccionar todos">
                        </th>

                        <th>DESCRIPCIÓN</th>

                        <th>CATEGORÍA</th>

                        <th>FECHA</th>

                        <th>MONTO</th>

                        <th>ESTADO</th>

                        <th class="th-actions">
                            <span class="sr-only">
                                Acciones
                            </span>
                        </th>

                    </tr>

                </thead>

                <tbody id="movimientosTableBody">

                    <!--
                        Las filas serán generadas por
                        movimientoController.js
                    -->

                </tbody>

            </table>

        </section>

        <!-- =================================================
             PAGINACIÓN
        ================================================== -->
        <footer class="pagination-footer">

            <span
                class="pagination-info"
                id="paginationInfo">
                Mostrando 0 de 0
            </span>

            <div
                class="pagination-controls"
                id="paginationControls">

                <!-- Botones generados por JS -->

            </div>

        </footer>

    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/core/theme.js') }}"></script>
    <script type="module" src="{{ asset('js/movimientos/movimientoController.js') }}"></script>
@endpush
