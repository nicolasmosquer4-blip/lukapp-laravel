<!-- =================================================
     TOPBAR
================================================== -->
<header class="topbar" id="topbar">

    <div class="topbar-left">

        <button type="button" class="sidebar-toggle" id="sidebarToggle"
                aria-label="Abrir menú" aria-controls="sidebar" aria-expanded="false">☰</button>

        <div class="topbar-title">
            @if ($topbarLabel)
                <span class="topbar-section-label">{{ $topbarLabel }}</span>
            @endif
            <h2>{{ $topbarTitle }}</h2>
        </div>

    </div>

    <div class="topbar-actions">

        <!-- Buscador global -->
        <div class="topbar-search">
            <span class="search-icon" aria-hidden="true">⌕</span>
            <input type="search" id="globalSearch" placeholder="Buscar en LukApp..." autocomplete="off">
        </div>

        <!-- Notificaciones -->
        <button type="button" class="topbar-action" id="btnNotifications" aria-label="Ver notificaciones">◔</button>

        <!-- Tema -->
        <button type="button" class="topbar-action" id="btnTheme" aria-label="Cambiar tema" title="Cambiar tema">◐</button>

        <!-- Perfil -->
        <a href="{{ route('settings') }}" class="user-profile-widget" aria-label="Abrir configuración">
            @include('partials.user-avatar', ['id' => 'userAvatar'])
            <div class="user-info">
                <span class="user-name" id="userName">{{ $authUser?->name ?? 'Usuario LukApp' }}</span>
                <span class="user-plan" id="userPlan">{{ $authUser?->plan ?? 'Premium' }}</span>
            </div>
        </a>

    </div>

</header>
