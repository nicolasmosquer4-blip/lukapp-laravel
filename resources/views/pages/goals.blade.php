@extends('layouts.panel', [
    'active' => 'goals',
    'topbarTitle' => 'Metas',
    'topbarLabel' => 'Panel financiero',
])

@section('title', 'LukApp - Metas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/metas.css') }}">
@endpush

@section('content')
    <main class="dashboard-container metas-container">

        <!-- Encabezado -->
        <header class="page-header">

            <div class="header-titles">
                <span class="page-eyebrow">Planificación financiera</span>

                <h1>Metas financieras</h1>

                <span
                    class="records-count"
                    id="metasSummaryText">
                    Cargando resumen de ahorro...
                </span>
            </div>

            <div class="header-actions">

                <button
                    type="button"
                    class="btn-primary"
                    id="btnNuevaMeta">
                    <span aria-hidden="true">+</span>
                    Nueva meta
                </button>

            </div>

        </header>

        <!-- =================================================
             RESUMEN DE METAS
        ================================================== -->
        <section
            class="summary-cards-grid"
            aria-label="Resumen financiero de metas">

            <!-- Ahorrado -->
            <article class="summary-card">

                <span class="summary-label">
                    Ahorrado total
                </span>

                <span
                    class="summary-value positive mono"
                    id="kpiAhorradoTotal">
                    $0
                </span>

            </article>

            <!-- Objetivo -->
            <article class="summary-card">

                <span class="summary-label">
                    Objetivo total
                </span>

                <span
                    class="summary-value mono"
                    id="kpiObjetivoTotal">
                    $0
                </span>

            </article>

            <!-- Progreso -->
            <article class="summary-card">

                <span class="summary-label">
                    Progreso global
                </span>

                <span
                    class="summary-value mono"
                    id="kpiProgresoGlobal">
                    0%
                </span>

            </article>

        </section>

        <!-- =================================================
             LISTADO DE METAS
        ================================================== -->
        <section
            class="metas-section"
            aria-labelledby="metasSectionTitle">

            <div class="section-header">

                <div>
                    <h2 id="metasSectionTitle">Mis metas</h2>
                    <p>Administra tus objetivos de ahorro.</p>
                </div>

            </div>

            <!--
                El contenido será generado dinámicamente
                desde metasController.js
            -->
            <div
                class="metas-grid"
                id="metasGridContainer">

                <!-- Inyección dinámica con JavaScript -->

            </div>

        </section>

    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/core/theme.js') }}"></script>
    <script type="module" src="{{ asset('js/metas/metaController.js') }}"></script>
@endpush
