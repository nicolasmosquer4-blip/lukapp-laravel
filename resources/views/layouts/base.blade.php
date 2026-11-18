<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @hasSection('description')
        <meta name="description" content="@yield('description')">
    @endif

    <title>@yield('title', 'LukApp')</title>

    {{-- Datos de arranque para los módulos JS (sin localStorage) --}}
    <script>
        window.LUKAPP = @json($lukappBoot);
    </script>

    @stack('head')

    <link rel="stylesheet" href="{{ asset('css/estilos.css') }}">
    @stack('styles')
</head>
<body>
    @yield('body')

    @stack('scripts')
</body>
</html>
