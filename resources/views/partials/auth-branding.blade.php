<section class="login-branding" aria-label="Información de LukApp">
    <div class="branding-content">
        <a href="{{ route('home') }}" class="brand-link" aria-label="LukApp">
            <img src="{{ asset('img/logo_LukApp.png') }}" alt="LukApp" class="brand-logo">
        </a>
        <div class="brand-copy">
            <span class="brand-eyebrow">Gestión financiera</span>
            <h1 class="brand-title">Finanzas claras.<br>Decisiones inteligentes.</h1>
            <div class="brand-description">
                <h2>Controla tu dinero, no al revés.</h2>
                <p>{{ $copy ?? 'Recupera el acceso a tu cuenta de forma segura.' }}</p>
            </div>
        </div>
    </div>
</section>
