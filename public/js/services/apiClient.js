/* =========================================================
   LukApp · apiClient.js
   Cliente HTTP único para la API REST de Laravel.
   - Autenticación Sanctum por cookie de sesión (mismo dominio).
   - Envía el token CSRF en cada petición que modifica datos.
   - Normaliza errores: { message, status, errors }.
   ========================================================= */

const BOOT = window.LUKAPP || {};
const API_BASE = String(BOOT.apiBase || '/api').replace(/\/$/, '');

export class ApiError extends Error {
    constructor(message, status = 0, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors || {};
    }

    /** Primer mensaje de validación (o el mensaje general). */
    get firstError() {
        const first = Object.values(this.errors)[0];
        return Array.isArray(first) && first.length ? first[0] : this.message;
    }
}

function readCookie(name) {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));
    return match ? decodeURIComponent(match[1]) : null;
}

function buildUrl(path, query) {
    const url = new URL(API_BASE + path, window.location.href);
    if (query) {
        Object.entries(query).forEach(([key, value]) => {
            if (value !== undefined && value !== null && value !== '') {
                url.searchParams.set(key, value);
            }
        });
    }
    return url;
}

export function apiUrl(path, query) {
    return buildUrl(path, query).toString();
}

export async function request(method, path, { body, query } = {}) {
    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    // Token CSRF: cookie XSRF-TOKEN (siempre actualizada) o meta tag como respaldo
    const xsrf = readCookie('XSRF-TOKEN');
    if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
    else {
        const meta = document.querySelector('meta[name="csrf-token"]')?.content;
        if (meta) headers['X-CSRF-TOKEN'] = meta;
    }

    let payload;
    if (body instanceof FormData) {
        payload = body; // el navegador define el boundary multipart
    } else if (body !== undefined) {
        headers['Content-Type'] = 'application/json';
        payload = JSON.stringify(body);
    }

    let response;
    try {
        response = await fetch(buildUrl(path, query), {
            method,
            headers,
            body: payload,
            credentials: 'same-origin'
        });
    } catch (networkError) {
        throw new ApiError('No se pudo conectar con el servidor. Verifica tu conexión.', 0);
    }

    const isJson = (response.headers.get('content-type') || '').includes('application/json');
    const data = isJson ? await response.json().catch(() => ({})) : {};

    if (response.ok) return data;

    // Sesión expirada o cuenta suspendida en una página protegida → volver al login
    if ((response.status === 401 || (response.status === 403 && /suspendida/i.test(data.message || ''))) && BOOT.user) {
        window.location.replace(BOOT.routes?.login || '/login');
    }

    const fallback = {
        400: 'Solicitud incorrecta.',
        401: 'Debes iniciar sesión.',
        403: 'No tienes permisos para realizar esta acción.',
        404: 'El recurso no existe.',
        419: 'La sesión expiró. Recarga la página.',
        422: 'Los datos enviados no son válidos.',
        429: 'Demasiados intentos. Espera un momento.',
        500: 'Error interno del servidor.'
    };

    throw new ApiError(data.message || fallback[response.status] || 'Ocurrió un error inesperado.', response.status, data.errors);
}

export const api = {
    get: (path, query) => request('GET', path, { query }),
    post: (path, body) => request('POST', path, { body }),
    put: (path, body) => request('PUT', path, { body }),
    delete: (path, body) => request('DELETE', path, { body })
};

/** Mensaje legible de cualquier error (ApiError o genérico). */
export function errorMessage(error) {
    if (error instanceof ApiError) return error.firstError;
    return error?.message || 'Ocurrió un error inesperado.';
}
