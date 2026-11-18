/* =========================================================
   LukApp · authRegisterController.js
   Orquesta la validación de registro y conexión con el modelo.
   ========================================================= */

import { register } from './authModel.js';
import { route } from '../core/session.js';
import { getRegisterFormData, showMessage, setLoading } from './authRegisterView.js';

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('registerForm')?.addEventListener('submit', handleRegister);
});

async function handleRegister(event) {
    event.preventDefault();

    const { name, email, phone, occupation, password, confirmPassword } = getRegisterFormData();

    // Validaciones en cliente (el servidor vuelve a validar todo)
    if (!name || !email || !password) return showMessage('Completa los campos obligatorios (Nombre, Correo y Contraseña).');
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return showMessage('Ingresa un correo electrónico válido.');
    if (password.length < 6) return showMessage('La contraseña debe tener mínimo 6 caracteres.');
    if (password !== confirmPassword) return showMessage('Las contraseñas no coinciden.');

    setLoading(true);

    const result = await register({ name, email, phone, occupation, password, confirmPassword });

    if (!result.success) {
        setLoading(false);
        return showMessage(result.message);
    }

    showMessage(result.message || 'Registro exitoso. Preparando tu panel...', 'success');

    setTimeout(() => window.location.replace(result.redirect || route('dashboard')), 500);
}
