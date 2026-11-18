@extends('layouts.base')

@section('title', 'LukApp - Registro')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
@endpush

@section('body')
<main class="login-layout">
        <section class="login-branding">
            <!-- Branding heredado -->
            <div class="branding-content">
                <a href="{{ route('home') }}" class="brand-link" aria-label="LukApp">
                    <img src="{{ asset('img/logo_LukApp.png') }}" alt="LukApp" class="brand-logo">
                </a>
                <div class="brand-copy">
                    <span class="brand-eyebrow">Gestión financiera</span>
                    <h1 class="brand-title">Finanzas claras.<br>Decisiones inteligentes.</h1>
                    <div class="brand-description">
                        <h2>Controla tu dinero, no al revés.</h2>
                        <p>Únete a LukApp y toma el control absoluto de tu futuro financiero hoy mismo.</p>
                    </div>
                </div>
            </div>
        </section>
        
        <section class="login-form-container">
            <div class="form-wrapper">
                <header class="form-header">
                    <span class="form-eyebrow">Comienza ahora</span>
                    <h2>Crear cuenta</h2>
                </header>
                
                <div class="form-message" id="registerMessage" role="alert" aria-live="polite" hidden></div>
                
                <form id="registerForm" novalidate>
                    <!-- Campo Nombre -->
                    <div class="input-group">
                        <label for="userName">Nombre completo</label>
                        <input type="text" id="userName" name="name" required maxlength="100" autocomplete="name" placeholder="Tu nombre completo">
                    </div>
                    
                    <!-- Campo Correo -->
                    <div class="input-group">
                        <label for="userEmail">Correo electrónico</label>
                        <input type="email" id="userEmail" name="email" required autocomplete="email" placeholder="correo@ejemplo.com">
                    </div>

                    <!-- NUEVO: Campo Teléfono -->
                    <div class="input-group">
                        <label for="userPhone">Teléfono <span class="text-muted" style="font-weight: normal;">(Opcional)</span></label>
                        <input type="tel" id="userPhone" name="phone" maxlength="30" autocomplete="tel" placeholder="+57 300 000 0000">
                    </div>

                    <!-- NUEVO: Campo Ocupación -->
                    <div class="input-group">
                        <label for="userOccupation">Ocupación <span class="text-muted" style="font-weight: normal;">(Opcional)</span></label>
                        <input type="text" id="userOccupation" name="occupation" maxlength="100" placeholder="Ej. Desarrollador de Software">
                    </div>

                    <!-- Campos de Contraseña -->
                    <div class="input-group">
                        <label for="userPassword">Contraseña</label>
                        <input type="password" id="userPassword" name="password" required autocomplete="new-password" placeholder="Mínimo 6 caracteres">
                    </div>
                    <div class="input-group">
                        <label for="userConfirmPassword">Confirmar contraseña</label>
                        <input type="password" id="userConfirmPassword" name="password_confirmation" required autocomplete="new-password" placeholder="Repite tu contraseña">
                    </div>

                    <button type="submit" class="btn-primary login-submit" id="btnRegisterSubmit" style="margin-top: 12px;">Crear cuenta en LukApp</button>
                </form>
                
                <footer class="form-footer">
                    <p>¿Ya tienes cuenta? <a href="{{ route('login') }}" class="signup-link">Inicia sesión</a></p>
                </footer>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/auth/authRegisterController.js') }}"></script>
@endpush
