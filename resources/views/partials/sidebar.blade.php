<!-- =================================================
     SIDEBAR
================================================== -->
<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <a href="{{ route('dashboard') }}" class="logo-container" aria-label="Ir al Dashboard">
            <span class="logo-icon">L</span>
            <span class="logo-text">
                LukApp
                <span class="badge-pro">PRO</span>
            </span>
        </a>
    </div>

    <nav class="sidebar-nav" aria-label="Navegación principal">

        <span class="nav-section-title">PRINCIPAL</span>

        @php
            $mainLinks = [
                ['key' => 'dashboard', 'route' => 'dashboard', 'icon' => '⌂', 'label' => 'Dashboard'],
                ['key' => 'movements', 'route' => 'movements', 'icon' => '↕', 'label' => 'Movimientos'],
                ['key' => 'statistics', 'route' => 'statistics', 'icon' => '◔', 'label' => 'Estadísticas'],
                ['key' => 'goals', 'route' => 'goals', 'icon' => '◎', 'label' => 'Metas'],
            ];
        @endphp

        <ul class="nav-list">
            @foreach ($mainLinks as $link)
                <li class="nav-item {{ $active === $link['key'] ? 'active' : '' }}">
                    <a href="{{ route($link['route']) }}" @if ($active === $link['key']) aria-current="page" @endif>
                        <span class="nav-icon" aria-hidden="true">{{ $link['icon'] }}</span>
                        <span>{{ $link['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>

        <ul class="nav-list nav-bottom">
            <li class="nav-item {{ $active === 'settings' ? 'active' : '' }}">
                <a href="{{ route('settings') }}" @if ($active === 'settings') aria-current="page" @endif>
                    <span class="nav-icon" aria-hidden="true">⚙</span>
                    <span>Configuración</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('login') }}" id="btnLogout">
                    <span class="nav-icon" aria-hidden="true">↪</span>
                    <span>Cerrar sesión</span>
                </a>
            </li>
        </ul>

    </nav>

</aside>
