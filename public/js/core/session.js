/* =========================================================
   LukApp · session.js
   Estado del usuario autenticado. Laravel lo inyecta en
   window.LUKAPP desde el layout Blade (ya no hay localStorage).
   ========================================================= */

const BOOT = window.LUKAPP || {};
let currentUser = BOOT.user ? { ...BOOT.user } : null;

export function getCurrentUser() {
    return currentUser;
}

/** Actualiza el usuario en memoria (tras editar perfil o avatar). */
export function setCurrentUser(changes) {
    currentUser = currentUser ? { ...currentUser, ...changes } : { ...changes };
    return currentUser;
}

export function isAuthenticated() {
    return Boolean(currentUser);
}

/** URL de una página web por nombre (definidas en routes/web.php). */
export function route(name) {
    return BOOT.routes?.[name] || '/';
}

export function isCurrentPage(name) {
    try {
        return new URL(route(name), window.location.href).pathname === window.location.pathname;
    } catch {
        return false;
    }
}
