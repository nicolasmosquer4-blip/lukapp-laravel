@extends('layouts.base')

@section('title', 'LukApp - Iniciar sesión')
@section('description', 'Inicia sesión en LukApp para gestionar tus finanzas')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
@endpush

@section('body')
<main class="login-layout">


        <!-- =================================================
             PANEL DE BRANDING
        ================================================== -->
        <section
            class="login-branding"
            aria-label="Información de LukApp">

            <div class="branding-content">


                <!-- Logo -->
                <a
                    href="{{ route('home') }}"
                    class="brand-link"
                    aria-label="LukApp">

                    <img
                        src="{{ asset('img/logo_LukApp.png') }}"
                        alt="LukApp"
                        class="brand-logo">

                </a>


                <!-- Presentación -->
                <div class="brand-copy">

                    <span class="brand-eyebrow">
                        Gestión financiera
                    </span>

                    <h1 class="brand-title">
                        Finanzas claras.
                        <br>
                        Decisiones inteligentes.
                    </h1>


                    <div class="brand-description">

                        <h2>
                            Controla tu dinero, no al revés.
                        </h2>

                        <p>
                            Registra tus movimientos, analiza tus
                            hábitos financieros y establece metas
                            para llevar un mejor control de tu dinero.
                        </p>

                    </div>

                </div>


                <!-- Beneficios -->
                <div class="brand-features">

                    <div class="feature-item">

                        <span
                            class="feature-icon"
                            aria-hidden="true">
                            ✓
                        </span>

                        <span>
                            Organiza tus ingresos y gastos
                        </span>

                    </div>


                    <div class="feature-item">

                        <span
                            class="feature-icon"
                            aria-hidden="true">
                            ✓
                        </span>

                        <span>
                            Visualiza tu comportamiento financiero
                        </span>

                    </div>


                    <div class="feature-item">

                        <span
                            class="feature-icon"
                            aria-hidden="true">
                            ✓
                        </span>

                        <span>
                            Define y controla tus metas
                        </span>

                    </div>

                </div>

            </div>

        </section>


        <!-- =================================================
             PANEL DE AUTENTICACIÓN
        ================================================== -->
        <section
            class="login-form-container"
            aria-labelledby="loginTitle">

            <div class="form-wrapper">


                <!-- Encabezado -->
                <header class="form-header">

                    <span class="form-eyebrow">
                        Bienvenido
                    </span>

                    <h2 id="loginTitle">
                        Iniciar sesión
                    </h2>

                    <p>
                        Accede a tu panel financiero de LukApp.
                    </p>

                </header>


                <!-- =================================================
                     MENSAJE DE AUTENTICACIÓN
                ================================================== -->
                <div
                    class="form-message {{ session('status_error') ? 'error' : '' }}"
                    id="loginMessage"
                    role="alert"
                    aria-live="polite"
                    @unless (session('status_error')) hidden @endunless>{{ session('status_error') }}</div>


                <!-- =================================================
                     FORMULARIO
                ================================================== -->
                <form
                    id="loginForm"
                    novalidate>


                    <!-- Correo -->
                    <div class="input-group">

                        <label for="userEmail">
                            Correo electrónico
                        </label>

                        <input
                            type="email"
                            id="userEmail"
                            name="email"
                            placeholder="correo@ejemplo.com"
                            autocomplete="email"
                            inputmode="email"
                            required
                            aria-required="true">

                    </div>


                    <!-- Contraseña -->
                    <div class="input-group">

                        <div class="label-row">

                            <label for="userPassword">
                                Contraseña
                            </label>

                            <button
                                type="button"
                                class="forgot-password"
                                id="btnForgotPassword">

                                ¿Olvidaste tu contraseña?

                            </button>

                        </div>


                        <div class="password-field">

                            <input
                                type="password"
                                id="userPassword"
                                name="password"
                                placeholder="Ingresa tu contraseña"
                                autocomplete="current-password"
                                required
                                aria-required="true">

                            <button
                                type="button"
                                class="password-toggle"
                                id="btnTogglePassword"
                                aria-label="Mostrar contraseña"
                                aria-pressed="false">

                                Mostrar

                            </button>

                        </div>

                    </div>


                    <!-- Recordar sesión -->
                    <div class="checkbox-group">

                        <input
                            type="checkbox"
                            id="rememberSession"
                            name="remember"
                            checked>

                        <label for="rememberSession">
                            Recordar sesión
                        </label>

                    </div>


                    <!-- Ingresar -->
                    <button
                        type="submit"
                        class="btn-primary login-submit"
                        id="btnLogin">

                        Ingresar a LukApp

                    </button>


                    <!-- Separador -->
                    <div class="form-divider">

                        <span>o</span>

                    </div>


                    <!-- Google -->
                    <button
                        type="button"
                        class="btn-google"
                        id="btnGoogleLogin">

                        <img
                            src="https://static.vecteezy.com/system/resources/previews/029/284/964/non_2x/google-logo-on-transparent-background-popular-search-engine-google-logotype-symbol-icon-google-sign-stock-free-vector.jpg"
                            alt=""
                            aria-hidden="true">

                        <span>
                            Continuar con Google
                        </span>

                    </button>

                </form>


                <!-- =================================================
                     REGISTRO
                ================================================== -->
                <footer class="form-footer">

                    <p>
                        ¿No tienes una cuenta?

                        <button
                            type="button"
                            class="signup-link"
                            id="btnRegister">

                            Regístrate gratis

                        </button>
                    </p>

                </footer>

            </div>

        </section>

    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/auth/authController.js') }}"></script>
@endpush
