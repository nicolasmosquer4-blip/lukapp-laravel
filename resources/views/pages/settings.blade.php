@extends('layouts.panel', [
    'active' => 'settings',
    'topbarTitle' => 'Configuración',
    'topbarLabel' => null,
])

@section('title', 'Configuración · LukApp')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/configuracion.css') }}">
@endpush

@section('content')
    <main class="dashboard-container">

        <header class="page-header" style="margin-bottom: 24px;">
            <div class="header-titles">
                <h1>Configuración</h1>
                <span class="records-count">Gestiona tu cuenta y preferencias</span>
            </div>
        </header>

        <div class="settings-layout">

            <!-- MENÚ INTERNO (Figma reference) -->
            <aside class="settings-nav-panel">
                <nav class="settings-inner-nav">
                    <button type="button" class="settings-tab active" data-target="perfil">
                        <span class="tab-icon">👤</span> Perfil
                    </button>
                    <button type="button" class="settings-tab" data-target="seguridad">
                        <span class="tab-icon">🛡️</span> Seguridad
                    </button>
                    <button type="button" class="settings-tab" data-target="notificaciones">
                        <span class="tab-icon">🔔</span> Notificaciones
                    </button>
                    <button type="button" class="settings-tab" data-target="apariencia">
                        <span class="tab-icon">☀️</span> Apariencia
                    </button>
                </nav>
            </aside>

            <!-- CONTENIDO DE LAS SECCIONES -->
            <div class="settings-content-panel">

                <!-- ==================== PERFIL ==================== -->
                <section id="section-perfil" class="settings-section active">
                    <div class="settings-card">
                        <div class="card-header-flex">
                            <div>
                                <h3>Información personal</h3>
                                <p class="text-muted" style="font-size: 12px; margin-top: 4px;">Actualiza tu foto y datos personales</p>
                            </div>
                        </div>
                        <hr class="divider">

                        <form id="formProfile" class="settings-form">
                            <!-- Avatar Area -->
                            <div class="avatar-upload-area">
                                <div class="user-avatar-large" id="profileLargeAvatar">U</div>
                                <div class="avatar-actions">
                                    <button type="button" class="btn-primary btn-sm" id="btnChangeAvatar">Cambiar foto</button>
                                    <span class="text-muted" style="font-size: 10px; display: block; margin-top: 6px;">JPG, PNG o GIF. Máx. 5 MB.</span>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="input-group" style="grid-column: 1 / -1;">
                                    <label for="inputName">NOMBRE COMPLETO</label>
                                    <input type="text" id="inputName" name="name" placeholder="Tu nombre completo">
                                </div>
                                <div class="input-group">
                                    <label for="inputEmail">CORREO ELECTRÓNICO</label>
                                    <input type="email" id="inputEmail" name="email" readonly style="background: var(--bg-muted); color: var(--text-muted); cursor: not-allowed;">
                                </div>
                                <div class="input-group">
                                    <label for="inputPhone">TELÉFONO</label>
                                    <input type="tel" id="inputPhone" name="phone" placeholder="+57 300 000 0000">
                                </div>
                                <div class="input-group" style="grid-column: 1 / -1;">
                                    <label for="inputOccupation">OCUPACIÓN</label>
                                    <input type="text" id="inputOccupation" name="occupation" placeholder="Ej. Desarrollador de Software">
                                </div>
                            </div>

                            <div class="form-actions right">
                                <button type="submit" class="btn-primary">Guardar cambios</button>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- ==================== SEGURIDAD ==================== -->
                <section id="section-seguridad" class="settings-section">
                    <div class="settings-card">
                        <h3>Seguridad</h3>
                        <p class="text-muted" style="font-size: 12px; margin-top: 4px; margin-bottom: 20px;">Gestiona tu contraseña y accesos</p>

                        <form id="formSecurity">
                            <div id="securityMessage"></div>

                            <div class="input-group" style="margin-bottom: 16px;">
                                <label for="currentPassword">CONTRASEÑA ACTUAL</label>
                                <div class="password-field">
                                    <input type="password" name="currentPassword" id="currentPassword" placeholder="••••••••" required>
                                    <button type="button" class="password-toggle" data-target="currentPassword" aria-label="Mostrar contraseña">Mostrar</button>
                                </div>
                            </div>

                            <div class="input-group" style="margin-bottom: 16px;">
                                <label for="newPassword">NUEVA CONTRASEÑA</label>
                                <div class="password-field">
                                    <input type="password" name="newPassword" id="newPassword" placeholder="Mínimo 6 caracteres" required>
                                    <button type="button" class="password-toggle" data-target="newPassword" aria-label="Mostrar contraseña">Mostrar</button>
                                </div>
                            </div>

                            <div class="input-group" style="margin-bottom: 24px;">
                                <label for="confirmPassword">CONFIRMAR CONTRASEÑA</label>
                                <div class="password-field">
                                    <input type="password" name="confirmPassword" id="confirmPassword" placeholder="Repite tu nueva contraseña" required>
                                    <button type="button" class="password-toggle" data-target="confirmPassword" aria-label="Mostrar contraseña">Mostrar</button>
                                </div>
                            </div>

                            <div class="form-actions right">
                                <button type="submit" class="btn-primary" id="btnUpdatePassword">Actualizar contraseña</button>
                            </div>
                        </form>

                        <h4 style="margin-top: 32px; margin-bottom: 16px; font-size: 14px;">Autenticación de dos factores</h4>
                        <div class="settings-list-item">
                            <div>
                                <strong>2FA con aplicación</strong>
                                <p class="text-muted" style="font-size: 11px;">Usa Google Authenticator o Authy</p>
                            </div>
                            <button type="button" class="btn-secondary btn-sm" id="btnToggle2FA">Activar</button>
                        </div>

                        <div class="danger-zone" style="margin-top: 32px;">
                            <div>
                                <strong style="color: var(--danger-color);">Zona de peligro</strong>
                                <p class="text-muted" style="font-size: 11px;">Una vez eliminada, no podrás recuperar tu cuenta.</p>
                            </div>
                            <button type="button" class="btn-danger-outline" id="btnDeleteAccount">Eliminar cuenta</button>
                        </div>
                    </div>
                </section>

                <!-- ==================== NOTIFICACIONES ==================== -->
                <section id="section-notificaciones" class="settings-section">
                    <div class="settings-card">
                        <h3>Notificaciones</h3>
                        <p class="text-muted" style="font-size: 12px; margin-top: 4px; margin-bottom: 20px;">Controla qué alertas y resúmenes deseas recibir</p>

                        <form id="formNotifications">
                            <div class="settings-list">
                                <div class="settings-list-item">
                                    <div>
                                        <strong>Notificaciones por correo</strong>
                                        <p class="text-muted" style="font-size: 11px;">Recibe alertas en tu bandeja de entrada</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_email" checked><span class="slider"></span></label>
                                </div>
                                <div class="settings-list-item">
                                    <div>
                                        <strong>Notificaciones push</strong>
                                        <p class="text-muted" style="font-size: 11px;">Alertas en tiempo real en tu navegador</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_push" checked><span class="slider"></span></label>
                                </div>
                                <div class="settings-list-item">
                                    <div>
                                        <strong>Resumen semanal</strong>
                                        <p class="text-muted" style="font-size: 11px;">Un resumen cada lunes con tus gastos</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_weekly"><span class="slider"></span></label>
                                </div>
                                <div class="settings-list-item">
                                    <div>
                                        <strong>Informe mensual</strong>
                                        <p class="text-muted" style="font-size: 11px;">Análisis completo al fin de cada mes</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_monthly" checked><span class="slider"></span></label>
                                </div>
                                <div class="settings-list-item">
                                    <div>
                                        <strong>Alertas de gastos inusuales</strong>
                                        <p class="text-muted" style="font-size: 11px;">Notifica cuando detecte patrones atípicos</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_alerts" checked><span class="slider"></span></label>
                                </div>
                                <div class="settings-list-item borderless">
                                    <div>
                                        <strong>Progreso de metas</strong>
                                        <p class="text-muted" style="font-size: 11px;">Actualización al alcanzar hitos en tus metas</p>
                                    </div>
                                    <label class="switch-ui"><input type="checkbox" name="notif_goals" checked><span class="slider"></span></label>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

                <!-- ==================== APARIENCIA ==================== -->
                <section id="section-apariencia" class="settings-section">
                    <div class="settings-card">
                        <h3>Apariencia</h3>
                        <p class="text-muted" style="font-size: 12px; margin-top: 4px; margin-bottom: 20px;">Personaliza la interfaz de LukApp</p>

                        <form id="formAppearance">
                            <div class="input-group" style="margin-bottom: 24px;">
                                <label>TEMA</label>
                                <div class="theme-selector-group">
                                    <label class="theme-radio"><input type="radio" name="theme" value="light"><span>☀️ Claro</span></label>
                                    <label class="theme-radio"><input type="radio" name="theme" value="dark"><span>🌙 Oscuro</span></label>
                                    <label class="theme-radio"><input type="radio" name="theme" value="auto"><span>⚙️ Automático</span></label>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="input-group">
                                    <label>MONEDA</label>
                                    <select name="currency">
                                        <option value="COP">COP - Peso colombiano</option>
                                        <option value="USD">USD - Dólar americano</option>
                                        <option value="EUR">EUR - Euro</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <label>IDIOMA</label>
                                    <select name="language">
                                        <option value="es">Español</option>
                                        <option value="en">English</option>
                                    </select>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </main>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('js/core/app.js') }}"></script>
    <script type="module" src="{{ asset('js/core/theme.js') }}"></script>
    <script type="module" src="{{ asset('js/configuracion/configuracionController.js') }}"></script>
@endpush
