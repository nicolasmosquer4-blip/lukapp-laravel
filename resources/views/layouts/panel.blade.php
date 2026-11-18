{{--
    Layout del panel de usuario: sidebar + topbar compartidos por
    dashboard, movimientos, estadísticas, metas y configuración.
    Variables: $active, $topbarTitle, $topbarLabel (opcional)
--}}
@extends('layouts.base')

@push('head')
    {{-- SCRIPT BLOQUEANTE PRE-PAINT: aplica el tema guardado en la BD antes de pintar --}}
    <script>
        (function () {
            try {
                var pref = (window.LUKAPP && window.LUKAPP.theme) || localStorage.getItem('lukapp_theme_setting') || 'light';
                var resolved = pref;
                if (pref === 'auto') {
                    var hour = new Date().getHours();
                    resolved = (hour >= 18 || hour < 6) ? 'dark' : 'light';
                }
                document.documentElement.dataset.theme = resolved === 'dark' ? 'dark' : 'light';
                document.documentElement.dataset.themePreference = pref;
            } catch (e) {}
        })();
    </script>
@endpush

@section('body')
    <div class="app-layout">

        @include('partials.sidebar', ['active' => $active ?? ''])

        <!-- =================================================
             CONTENIDO PRINCIPAL
        ================================================== -->
        <div class="main-content">

            @include('partials.topbar', [
                'topbarTitle' => $topbarTitle ?? '',
                'topbarLabel' => $topbarLabel ?? 'Panel financiero',
            ])

            @yield('content')

        </div>

    </div>
@endsection
