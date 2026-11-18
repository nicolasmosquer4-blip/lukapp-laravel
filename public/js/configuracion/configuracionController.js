/* =========================================================
   LukApp · configuracionController.js
   Lógica real, persistencia en la API y eventos globales.
   ========================================================= */
import { initTabs, populateProfileData, syncAppearanceUI, syncNotificationsUI, showInlineMessage, renderAvatar, openDeleteModal } from './configuracionView.js';
import { getSettings, updateProfile, updateAvatar, changePassword, updateSettingCategory, deleteAccount } from './configuracionModel.js';
import { getCurrentUser, route } from '../core/session.js';
import { applyTheme } from '../core/theme.js';
import { errorMessage } from '../services/apiClient.js';
import { toastSuccess, toastError, toastInfo } from '../components/toast.js';

document.addEventListener('DOMContentLoaded', initializeSettings);

async function initializeSettings() {
    initTabs();

    // 1. Datos del usuario autenticado (inyectados por Laravel)
    const user = getCurrentUser();
    if (!user) return;
    populateProfileData(user);

    // 2. Bind Events
    setupProfileEvents();
    setupSecurityEvents();
    setupSettingsEvents();

    // 3. Preferencias (Apariencia y Notificaciones) desde la BD
    try {
        const settings = await getSettings();
        syncAppearanceUI(settings.appearance);
        syncNotificationsUI(settings.notifications);
    } catch (error) {
        toastError(errorMessage(error));
    }
}

function setupProfileEvents() {
    const form = document.getElementById('formProfile');
    const avatarBtn = document.getElementById('btnChangeAvatar');

    // Guardado de Perfil
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const name = document.getElementById('inputName').value.trim();
        const phone = document.getElementById('inputPhone').value.trim();
        const occupation = document.getElementById('inputOccupation').value.trim();

        if (!name) {
            toastError('El nombre completo es obligatorio.');
            return;
        }

        const btn = form.querySelector('button[type="submit"]');
        const originalText = btn.textContent;
        btn.disabled = true;

        try {
            const { user, message } = await updateProfile({ name, phone, occupation });
            btn.textContent = '¡Guardado!';
            setTimeout(() => btn.textContent = originalText, 2000);
            toastSuccess(message);
            document.dispatchEvent(new CustomEvent('lukapp:user-updated', { detail: user }));
        } catch (error) {
            toastError(errorMessage(error));
        } finally {
            btn.disabled = false;
        }
    });

    // Subida de Avatar al servidor (multipart)
    avatarBtn.addEventListener('click', () => {
        const input = document.createElement('input');
        input.type = 'file';
        input.accept = 'image/png, image/jpeg, image/jpg, image/webp, image/gif';

        input.addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;

            // Validación previa en cliente (el servidor vuelve a validar)
            if (file.size > 5 * 1024 * 1024) {
                toastError('La imagen no debe superar los 5 MB.');
                return;
            }

            avatarBtn.disabled = true;
            try {
                const { user, message } = await updateAvatar(file);
                renderAvatar(user);
                toastSuccess(message);
                document.dispatchEvent(new CustomEvent('lukapp:user-updated', { detail: user }));
            } catch (error) {
                toastError(errorMessage(error));
            } finally {
                avatarBtn.disabled = false;
            }
        });

        input.click();
    });
}

function setupSecurityEvents() {
    const form = document.getElementById('formSecurity');

    // ---------------------------------------------------------
    // MOSTRAR / OCULTAR CONTRASEÑA
    // ---------------------------------------------------------
    document.querySelectorAll('.password-toggle').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const targetId = e.currentTarget.dataset.target;
            const input = document.getElementById(targetId);

            if (!input) return;

            if (input.type === 'password') {
                input.type = 'text';
                e.currentTarget.textContent = 'Ocultar';
                e.currentTarget.classList.add('is-visible');
            } else {
                input.type = 'password';
                e.currentTarget.textContent = 'Mostrar';
                e.currentTarget.classList.remove('is-visible');
            }
        });
    });

    // ---------------------------------------------------------
    // ACTUALIZACIÓN DE CONTRASEÑA (verificada en el servidor)
    // ---------------------------------------------------------
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const current = document.getElementById('currentPassword').value;
        const newPass = document.getElementById('newPassword').value;
        const confirm = document.getElementById('confirmPassword').value;

        if (!current || !newPass || !confirm) {
            return showInlineMessage('securityMessage', 'Completa todos los campos de contraseña.', 'error');
        }
        if (newPass.length < 6) {
            return showInlineMessage('securityMessage', 'La nueva contraseña debe tener mínimo 6 caracteres.', 'error');
        }
        if (newPass !== confirm) {
            return showInlineMessage('securityMessage', 'Las nuevas contraseñas no coinciden.', 'error');
        }

        const btn = document.getElementById('btnUpdatePassword');
        btn.disabled = true;
        btn.textContent = 'Actualizando...';

        const result = await changePassword(current, newPass, confirm);

        btn.disabled = false;
        btn.textContent = 'Actualizar contraseña';

        if (result.success) {
            showInlineMessage('securityMessage', result.message, 'success');
            form.reset();

            document.querySelectorAll('.password-toggle').forEach(toggleBtn => {
                const input = document.getElementById(toggleBtn.dataset.target);
                if (input) input.type = 'password';
                toggleBtn.textContent = 'Mostrar';
                toggleBtn.classList.remove('is-visible');
            });
        } else {
            showInlineMessage('securityMessage', result.message, 'error');
        }
    });

    // 2FA: requiere un proveedor TOTP que aún no está instalado en el servidor
    document.getElementById('btnToggle2FA')?.addEventListener('click', () => {
        toastInfo('La autenticación de dos factores todavía no está disponible.');
    });

    // Eliminar cuenta (borrado en cascada en la BD)
    document.getElementById('btnDeleteAccount')?.addEventListener('click', () => {
        openDeleteModal(async () => {
            try {
                const res = await deleteAccount();
                window.location.replace(res.redirect || route('login'));
            } catch (error) {
                toastError(errorMessage(error));
            }
        });
    });
}

async function saveSetting(category, key, value) {
    try {
        await updateSettingCategory(category, key, value);
        toastSuccess('Preferencias guardadas.');
        return true;
    } catch (error) {
        toastError(errorMessage(error));
        return false;
    }
}

function setupSettingsEvents() {
    // Notificaciones
    document.querySelectorAll('#formNotifications input[type="checkbox"]').forEach(checkbox => {
        checkbox.addEventListener('change', async (e) => {
            const ok = await saveSetting('notifications', e.target.name, e.target.checked);
            if (!ok) e.target.checked = !e.target.checked; // revierte si el servidor falló
            else document.dispatchEvent(new CustomEvent('lukapp:settings-updated'));
        });
    });

    // Apariencia (Tema)
    document.querySelectorAll('input[name="theme"]').forEach(radio => {
        radio.addEventListener('change', async (e) => {
            if (!e.target.checked) return;
            applyTheme(e.target.value, true, false); // se aplica al instante
            if (await saveSetting('appearance', 'theme', e.target.value)) {
                document.dispatchEvent(new CustomEvent('lukapp:appearance-updated'));
            }
        });
    });

    // Apariencia (Moneda, Idioma)
    document.querySelectorAll('#formAppearance select').forEach(select => {
        select.addEventListener('change', async (e) => {
            if (await saveSetting('appearance', e.target.name, e.target.value)) {
                document.dispatchEvent(new CustomEvent('lukapp:appearance-updated'));
            }
        });
    });
}

// El tema también puede cambiarse desde el botón del topbar: refrescar los radios
document.addEventListener('lukapp:theme-changed', (e) => {
    syncAppearanceUI({ theme: e.detail.preference });
});
