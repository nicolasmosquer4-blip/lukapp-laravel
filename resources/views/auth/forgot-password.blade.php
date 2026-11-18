@extends('layouts.base')

@section('title', 'LukApp - Recuperar contraseña')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
@endpush

@section('body')
    <main class="login-layout">
        @include('partials.auth-branding', ['copy' => 'Te enviaremos un enlace a tu correo para crear una nueva contraseña.'])

        <section class="login-form-container" aria-labelledby="forgotTitle">
            <div class="form-wrapper">
                <header class="form-header">
                    <span class="form-eyebrow">Recuperación</span>
                    <h2 id="forgotTitle">¿Olvidaste tu contraseña?</h2>
                    <p>Ingresa el correo con el que te registraste.</p>
                </header>

                <div class="form-message" id="forgotMessage" role="alert" aria-live="polite" hidden></div>

                <form id="forgotForm" novalidate>
                    <div class="input-group">
                        <label for="userEmail">Correo electrónico</label>
                        <input type="email" id="userEmail" name="email" placeholder="correo@ejemplo.com"
                               autocomplete="email" inputmode="email" required aria-required="true">
                    </div>

                    <button type="submit" class="btn-primary login-submit" id="btnForgotSubmit" style="margin-top: 12px;">Enviar enlace de recuperación</button>
                </form>

                <footer class="form-footer">
                    <p>¿La recordaste? <a href="{{ route('login') }}" class="signup-link">Inicia sesión</a></p>
                </footer>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/auth/passwordResetController.js') }}"></script>
@endpush
