@extends('layouts.base')

@section('title', 'LukApp - Nueva contraseña')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/registro.css') }}">
@endpush

@section('body')
    <main class="login-layout">
        @include('partials.auth-branding', ['copy' => 'Crea una nueva contraseña para volver a tu panel financiero.'])

        <section class="login-form-container" aria-labelledby="resetTitle">
            <div class="form-wrapper">
                <header class="form-header">
                    <span class="form-eyebrow">Recuperación</span>
                    <h2 id="resetTitle">Nueva contraseña</h2>
                </header>

                <div class="form-message" id="resetMessage" role="alert" aria-live="polite" hidden></div>

                <form id="resetForm" novalidate>
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="input-group">
                        <label for="userEmail">Correo electrónico</label>
                        <input type="email" id="userEmail" name="email" value="{{ $email }}" required autocomplete="email">
                    </div>
                    <div class="input-group">
                        <label for="userPassword">Nueva contraseña</label>
                        <input type="password" id="userPassword" name="password" required placeholder="Mínimo 6 caracteres" autocomplete="new-password">
                    </div>
                    <div class="input-group">
                        <label for="userConfirmPassword">Confirmar contraseña</label>
                        <input type="password" id="userConfirmPassword" name="password_confirmation" required placeholder="Repite tu contraseña" autocomplete="new-password">
                    </div>

                    <button type="submit" class="btn-primary login-submit" id="btnResetSubmit" style="margin-top: 12px;">Guardar nueva contraseña</button>
                </form>

                <footer class="form-footer">
                    <p><a href="{{ route('login') }}" class="signup-link">Volver a iniciar sesión</a></p>
                </footer>
            </div>
        </section>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/auth/passwordResetController.js') }}"></script>
@endpush
