# LukApp · Gestión financiera personal (Laravel + MySQL)

LukApp es una aplicación web para registrar ingresos y gastos, analizar el comportamiento financiero con
gráficos y gestionar metas de ahorro. Incluye un panel de administración para gestionar usuarios.

Este proyecto es la **migración a Laravel** del frontend original `LukApp DC_Frontend` (HTML + CSS + JavaScript
con `localStorage`). Se conservaron el diseño, las hojas de estilo, los componentes y los controladores
JavaScript (patrón MVC por módulo); lo que cambió es la capa de datos: ahora todo se guarda en **MySQL** y se
consume mediante una **API REST** protegida con **Laravel Sanctum**.

---

## 1. Tecnologías

| Capa | Tecnología |
|------|-----------|
| Backend | PHP 8.2, Laravel 12, Eloquent ORM, Form Requests, Policies, Middleware |
| Autenticación | Laravel Sanctum (cookie de sesión + CSRF para el navegador, token Bearer para Postman) |
| Base de datos | MySQL / MariaDB (XAMPP) con migraciones, seeders y factories |
| Vistas | Blade (layouts y parciales extraídos del HTML original) |
| Frontend | JavaScript ES Modules (MVC por módulo), CSS original, Chart.js 4.4.1 (CDN) |
| Pruebas | PHPUnit (40 pruebas de integración) + colección de Postman |
| Herramientas | Composer, Artisan, XAMPP, Git |

No se instaló ningún paquete adicional: solo `laravel/sanctum`, que viene con `php artisan install:api`.

---

## 2. Análisis del frontend original y cómo se migró

| Página original | Ruta Laravel | Vista Blade | Funcionalidad conectada a la BD |
|---|---|---|---|
| `index.html` | `/` | — (redirección) | Redirige según la sesión y el rol |
| `login.html` | `/login` | `auth/login` | Inicio de sesión, "recordar sesión", bloqueo de cuentas |
| `registro.html` | `/registro` | `auth/register` | Registro con validación en servidor |
| *(no existía)* | `/olvide-contrasena`, `/restablecer-contrasena/{token}` | `auth/forgot-password`, `auth/reset-password` | Recuperación de contraseña por correo (antes solo mostraba un aviso) |
| `dashboard.html` | `/dashboard` | `pages/dashboard` | KPIs, últimos movimientos, metas activas, tendencia de gastos |
| `movimientos.html` | `/movimientos` | `pages/movements` | CRUD, filtros, búsqueda, paginación, borrado múltiple, exportar CSV/PDF |
| `estadisticas.html` | `/estadisticas` | `pages/statistics` | Promedios, tasa de ahorro, mejor mes, gráficos por mes/categoría/día |
| `metas.html` | `/metas` | `pages/goals` | CRUD de metas, abonos y retiros con historial |
| `configuracion.html` | `/configuracion` | `pages/settings` | Perfil, foto, contraseña, notificaciones, tema, moneda, idioma, eliminar cuenta |
| `admin.html` | `/admin` | `pages/admin` | KPIs globales, usuarios (buscar, ver, bloquear, resetear, eliminar), estadísticas, estado del sistema, modo demo |

Los enlaces antiguos `*.html` redirigen (301) a las nuevas rutas.

### Cambios en el JavaScript
- **Eliminados** (eran exclusivos de `localStorage`): `js/repositories/*`, `js/services/storageService.js`,
  `js/services/cryptoService.js` (hash SHA-256 en el navegador), `data/demoData.js`, `js/core/router.js` (no se usaba).
- **Nuevos**: `js/services/apiClient.js` (cliente HTTP con CSRF y manejo de errores), `js/core/session.js`
  (usuario inyectado por Laravel), `js/auth/passwordResetController.js`, `js/components/toast.js` (existía vacío).
- **Adaptados**: los `*Model.js` ahora llaman a la API (`async`); los controladores esperan las respuestas y
  muestran mensajes de éxito/error. Las vistas (`*View.js`) se conservan casi intactas.
- Las reglas de negocio que estaban en el navegador ahora se ejecutan en el servidor: estado del movimiento
  según la fecha, totales, notificación única de "meta alcanzada", aislamiento de datos por usuario.

---

## 3. Estructura de carpetas (lo relevante)

```
lukapp-laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                 # Controladores de la API REST
│   │   │   │   ├── Admin/           # UserController, SystemController (rol admin)
│   │   │   │   ├── AuthController.php, PasswordResetController.php
│   │   │   │   ├── MovementController.php, GoalController.php, CategoryController.php
│   │   │   │   ├── DashboardController.php, StatisticsController.php
│   │   │   │   └── ProfileController.php, SettingsController.php, NotificationController.php
│   │   │   └── Web/PageController.php   # Renderiza las vistas Blade
│   │   ├── Middleware/              # EnsureRole (role:admin|user), EnsureUserIsActive (cuentas bloqueadas)
│   │   ├── Requests/                # Validación (Form Requests)
│   │   └── Resources/               # Formato JSON de las respuestas
│   ├── Models/                      # User, UserSetting, Category, Movement, Goal, GoalContribution, SystemSetting
│   ├── Notifications/AppNotification.php
│   ├── Policies/                    # MovementPolicy, GoalPolicy, UserPolicy
│   └── Services/                    # Dashboard, Statistics, Goal, Admin, MovementAlert, DemoData
├── bootstrap/app.php                # Middleware, Sanctum y manejo de errores JSON
├── database/
│   ├── migrations/  seeders/  factories/
├── docs/LukApp.postman_collection.json
├── lang/es/                         # Mensajes de validación en español
├── public/
│   ├── css/  img/                   # Estilos e imágenes originales
│   └── js/                          # Módulos JS (MVC) del frontend
├── resources/views/
│   ├── layouts/ (base, panel)  partials/ (sidebar, topbar, ...)
│   ├── auth/  pages/
├── routes/api.php  routes/web.php
└── tests/Feature/                   # Pruebas PHPUnit
```

---

## 4. Instalación y ejecución local (XAMPP + Composer)

### Requisitos
- XAMPP con **PHP 8.2+** y **MySQL/MariaDB** (probado con PHP 8.2.12 y MariaDB 10.4.32).
- Composer 2.
- Extensiones PHP: `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` (vienen activas en XAMPP).

### Pasos

1. Inicia **MySQL** desde el panel de control de XAMPP.
2. Crea la base de datos (phpMyAdmin o consola):
   ```sql
   CREATE DATABASE lukapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Instala las dependencias y configura el entorno:
   ```bash
   cd lukapp-laravel
   composer install
   cp .env.example .env        # en Windows: copy .env.example .env
   php artisan key:generate
   ```
4. Revisa en `.env` la conexión (valores por defecto de XAMPP):
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=lukapp
   DB_USERNAME=root
   DB_PASSWORD=
   ```
5. Crea las tablas, los datos de prueba y el enlace para las fotos de perfil:
   ```bash
   php artisan migrate --seed
   php artisan storage:link
   ```
6. Inicia el servidor:
   ```bash
   php artisan serve
   ```
   Abre <http://127.0.0.1:8000>.

> **Con Apache de XAMPP** (opcional): crea un VirtualHost cuyo `DocumentRoot` apunte a
> `lukapp-laravel/public` y ajusta `APP_URL` en `.env`. Las rutas y llamadas a la API usan `url()`/`asset()`,
> así que también funcionan si la app vive en una subcarpeta.

### Cuentas de prueba (creadas por los seeders)

| Rol | Correo | Contraseña |
|-----|--------|-----------|
| Administrador | `administrador@lukapp.com` | `123456` |
| Usuario con datos de ejemplo | `usuario@lukapp.com` | `123456` |
| 5 usuarios aleatorios | (factory) | `password` |

Cambia estas contraseñas fuera del entorno local.

### Comandos útiles

| Comando | Para qué sirve |
|---|---|
| `php artisan serve` | Servidor de desarrollo |
| `php artisan migrate` | Crea/actualiza las tablas |
| `php artisan migrate:fresh --seed` | Borra todo y vuelve a crear la BD con datos de prueba |
| `php artisan db:seed` | Solo inserta los datos iniciales |
| `php artisan route:list --path=api` | Lista los endpoints |
| `php artisan test` | Ejecuta las pruebas automatizadas |
| `php artisan optimize:clear` | Limpia cachés de configuración, rutas y vistas |

### Correo (recuperación de contraseña)
En local `MAIL_MAILER=log`: el correo con el enlace se escribe en `storage/logs/laravel.log`.
Para enviar correos reales configura SMTP en `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`,
`MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`).

---

## 5. Base de datos

### Diagrama de relaciones

```
users 1───1 user_settings
  │
  ├──1───N movements N───1 categories
  │
  ├──1───N goals 1───N goal_contributions
  │
  ├──1───N notifications (polimórfica, sistema de Laravel)
  ├──1───N personal_access_tokens (Sanctum)
  └──1───N sessions

system_settings (clave/valor global)    password_reset_tokens
```

### Tablas

| Tabla | Descripción | Campos principales | Integridad |
|---|---|---|---|
| `users` | Cuentas | `name`, `email` (único), `phone`, `occupation`, `avatar_path`, `role` (`user`/`admin`), `plan`, `is_blocked`, `last_access_at`, `password` (bcrypt) | `email` UNIQUE |
| `user_settings` | Preferencias (1:1) | `theme` (`light`/`dark`/`auto`), `currency` (`COP`/`USD`/`EUR`), `language` (`es`/`en`), `notif_email`, `notif_push`, `notif_weekly`, `notif_monthly`, `notif_alerts`, `notif_goals` | FK `user_id` UNIQUE, `ON DELETE CASCADE` |
| `categories` | Catálogo de categorías | `name`, `type` (`ingreso`/`gasto`), `color`, `sort_order` | UNIQUE (`name`, `type`) |
| `movements` | Ingresos y gastos | `type`, `description` (80), `amount` DECIMAL(15,2), `date`, `status` (`completado`/`pendiente`/`cancelado`) | FK `user_id` CASCADE, FK `category_id` RESTRICT; índices (`user_id`,`date`), (`user_id`,`type`), (`user_id`,`status`) |
| `goals` | Metas de ahorro | `name` (70), `icon`, `target`, `saved`, `deadline`, `completed_notified` | FK `user_id` CASCADE |
| `goal_contributions` | Historial de abonos/retiros | `type` (`abonar`/`retirar`), `amount`, `comment` (50) | FK `goal_id` CASCADE |
| `notifications` | Notificaciones internas | `data` JSON (`title`, `message`, `type`, `link`), `read_at` | Polimórfica (`notifiable_type`, `notifiable_id`) |
| `system_settings` | Parámetros globales | `key` (PK), `value` (p. ej. `demo_mode`) | — |
| `personal_access_tokens` | Tokens de Sanctum | — | — |
| `sessions`, `password_reset_tokens`, `cache`, `jobs` | Tablas estándar de Laravel | — | — |

### Reglas de negocio implementadas en el servidor
- **Estado del movimiento**: fecha futura → `pendiente`; hoy o pasada → `completado` (se recalcula al consultar).
  `cancelado` solo si se envía explícitamente.
- **Totales de la tabla de movimientos**: solo movimientos `completado`. **Dashboard y estadísticas**: excluyen `cancelado`.
- **Metas**: el ahorro solo cambia con abonos/retiros (historial en `goal_contributions`); no se permite retirar
  más de lo ahorrado (400); la notificación "Meta alcanzada" se envía una sola vez (se reinicia si se retira por debajo del 100 %).
- **Notificaciones automáticas** (respetan las preferencias del usuario):
  - `notif_push`: aviso al registrar un ingreso o gasto.
  - `notif_goals`: aviso al alcanzar una meta.
  - `notif_alerts`: alerta cuando el gasto del mes en una categoría supera en 50 % su promedio de los 3 meses anteriores (máximo una por categoría y mes).
- **Modo demo (admin)**: si está activo, las cuentas nuevas reciben datos de ejemplo (equivale al `DEMO_MODE` del frontend original).

---

## 6. API REST

- **Base URL**: `http://127.0.0.1:8000/api`
- **Formato**: JSON. Envía siempre `Accept: application/json`.
- **Autenticación**:
  - Navegador (el propio frontend): cookie de sesión + token CSRF (`X-XSRF-TOKEN`), gestionado por `apiClient.js`.
  - Clientes externos (Postman): `POST /api/auth/login` devuelve `token`; envíalo como `Authorization: Bearer <token>`.
- **Límites**: login/registro 5 intentos por minuto por correo+IP; API general 120 peticiones/minuto por usuario.

### Códigos HTTP

| Código | Significado en LukApp |
|---|---|
| 200 | Consulta, actualización o eliminación correcta |
| 201 | Recurso creado (registro, movimiento, meta, abono) |
| 400 | Operación no válida para el estado actual (p. ej. retirar más de lo ahorrado, token de recuperación inválido) |
| 401 | No autenticado o credenciales incorrectas |
| 403 | Sin permisos (rol incorrecto, recurso de otro usuario, cuenta bloqueada o protegida) |
| 404 | Recurso o endpoint inexistente |
| 405 | Método HTTP no permitido |
| 419 | Token CSRF expirado (solo navegador) |
| 422 | Error de validación (incluye `errors` por campo) |
| 429 | Demasiados intentos |
| 500 | Error interno (sin detalles si `APP_DEBUG=false`) |

Formato de error homogéneo:
```json
{ "message": "Los datos enviados no son válidos.", "errors": { "amount": ["El campo monto debe ser mayor que 0."] } }
```

### Endpoints

**Autenticación** (públicos)

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/auth/register` | Registro (`name`, `email`, `phone?`, `occupation?`, `password`, `password_confirmation`) |
| POST | `/auth/login` | Inicio de sesión (`email`, `password`, `remember?`, `device_name?`) |
| POST | `/auth/forgot-password` | Envía enlace de recuperación (`email`) |
| POST | `/auth/reset-password` | Restablece (`token`, `email`, `password`, `password_confirmation`) |

**Usuario autenticado** (`auth:sanctum`)

| Método | Endpoint | Descripción |
|---|---|---|
| POST | `/auth/logout` | Cierra la sesión / revoca el token |
| GET | `/auth/me` | Usuario actual + preferencias |
| GET · PUT · DELETE | `/profile` | Ver, actualizar (`name`, `phone`, `occupation`) o eliminar la cuenta |
| POST | `/profile/avatar` | Subir foto (multipart, campo `avatar`, imagen ≤ 5 MB) |
| PUT | `/profile/password` | Cambiar contraseña (`current_password`, `password`, `password_confirmation`) |
| GET · PUT | `/settings` | Ver / actualizar preferencias (parcial) |
| GET | `/notifications?unread=&limit=` | Listar (incluye `unread_count`) |
| PUT | `/notifications/{id}/read` · `/notifications/read-all` | Marcar como leídas |
| DELETE | `/notifications/{id}` · `/notifications` | Eliminar una / todas |

**Módulos financieros** (`role:user`)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/dashboard` | KPIs, recientes, metas, gastos de 6 meses |
| GET | `/statistics?period=6M\|1A\|TODO` | Datos para los gráficos |
| GET | `/categories?type=` | Catálogo de categorías |
| GET | `/movements?type=&q=&status=&category_id=&from=&to=&page=&per_page=` | Lista paginada + `totals` |
| POST | `/movements` | Crear (`type`, `category` o `category_id`, `description`, `amount`, `date`, `status?`) |
| GET · PUT · DELETE | `/movements/{id}` | Ver / actualizar / eliminar |
| DELETE | `/movements` | Borrado múltiple (`ids: []`) |
| GET | `/movements/export` | CSV con los mismos filtros |
| GET · POST | `/goals` | Listar (+ `summary`) / crear |
| GET · PUT · DELETE | `/goals/{id}` | Ver (con historial) / actualizar / eliminar |
| GET · POST | `/goals/{id}/contributions` | Historial / abonar o retirar (`type`, `amount`, `comment?`) |

**Administración** (`role:admin`)

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/admin/kpis` | Totales globales |
| GET | `/admin/users?q=&status=activo\|bloqueado&page=&per_page=` | Usuarios paginados |
| GET | `/admin/users/{id}` | Detalle con conteos y preferencias |
| PUT | `/admin/users/{id}/block` | `{ "blocked": true\|false }` (revoca tokens al bloquear) |
| DELETE | `/admin/users/{id}/data` | Borra movimientos, metas y notificaciones (conserva la cuenta) |
| DELETE | `/admin/users/{id}` | Elimina la cuenta en cascada |
| GET | `/admin/stats` | Ingresos/gastos globales y nuevos usuarios por mes |
| GET | `/admin/system` | Estado de la BD, versiones, tamaño, modo demo |
| PUT | `/admin/system/demo-mode` | `{ "enabled": true\|false }` |

Las cuentas con rol `admin` están protegidas: no se pueden bloquear, resetear ni eliminar (403).

### Ejemplos

**Login (token para Postman)**
```http
POST /api/auth/login
Content-Type: application/json
Accept: application/json

{ "email": "usuario@lukapp.com", "password": "123456", "device_name": "postman" }
```
```json
{
  "message": "Sesión iniciada. Redirigiendo...",
  "data": { "id": 2, "name": "Daniel Gutiérrez", "email": "usuario@lukapp.com", "role": "user", "plan": "Premium", "avatar": null, "blocked": false },
  "redirect": "http://127.0.0.1:8000/dashboard",
  "token_type": "Bearer",
  "token": "3|xY9...k2"
}
```

**Crear movimiento** → `201 Created`
```http
POST /api/movements
Authorization: Bearer 3|xY9...k2

{ "type": "gasto", "category": "Alimentación", "description": "Supermercado", "amount": 125000, "date": "2026-11-18" }
```
```json
{
  "data": {
    "id": 71, "description": "Supermercado", "type": "gasto", "category_id": 6, "category": "Alimentación",
    "category_color": "#EF4444", "date": "2026-11-18", "amount": 125000, "status": "completado",
    "created_at": "2026-11-18T13:23:10-05:00", "updated_at": "2026-11-18T13:23:10-05:00"
  },
  "message": "Gasto registrado."
}
```

**Listar movimientos** → `200 OK`
```json
{
  "data": [ { "id": 71, "description": "Supermercado", "...": "..." } ],
  "links": { "first": "...?page=1", "last": "...?page=9", "prev": null, "next": "...?page=2" },
  "meta": { "current_page": 1, "from": 1, "last_page": 9, "per_page": 8, "to": 8, "total": 70 },
  "totals": { "income": 27350000, "expense": 12041240, "balance": 15308760 }
}
```

**Abonar a una meta** → `201 Created`
```json
// POST /api/goals/2/contributions   { "type": "abonar", "amount": 1170000, "comment": "Prima diciembre" }
{
  "data": { "id": 2, "name": "Computador", "icon": "💻", "target": 4500000, "saved": 4500000, "remaining": 0, "progress": 100, "completed": true, "deadline": "2027-02-18" },
  "message": "Abono registrado."
}
```

**Error de validación** → `422`
```json
{
  "message": "Los datos enviados no son válidos.",
  "errors": {
    "category": ["Selecciona una categoría."],
    "description": ["El campo descripción es obligatorio."],
    "amount": ["El campo monto debe ser mayor que 0."],
    "date": ["El campo fecha es obligatorio."]
  }
}
```

### Postman
Importa `docs/LukApp.postman_collection.json` (46 peticiones organizadas por módulo). Ejecuta primero
**Auth → Login (usuario)** o **Login (admin)**: el token se guarda solo en la variable `token` y lo usan
las demás peticiones. Las peticiones de creación guardan el `id` creado (`movementId`, `goalId`).

---

## 7. Seguridad

| Riesgo | Medida |
|---|---|
| SQL Injection | Eloquent / Query Builder con parámetros enlazados; los comodines `%` y `_` de las búsquedas se escapan |
| CSRF | Token CSRF en sesión (meta `csrf-token` y cookie `XSRF-TOKEN`), verificado por Sanctum en cada petición del navegador |
| XSS | Blade escapa por defecto; `strip_tags` en textos de entrada; el JS escapa todo lo que inserta con `innerHTML` (incluido el modal de detalle del admin, que antes no escapaba) |
| Contraseñas | Hash bcrypt (`password` cast `hashed`); el navegador ya no calcula ni guarda hashes |
| Autenticación | Middleware `auth` / `auth:sanctum`; regeneración de sesión al iniciar; invalidación al cerrar |
| Autorización | Middleware `role:user` / `role:admin`; Policies para que cada usuario solo acceda a sus movimientos y metas; cuentas admin protegidas |
| Cuentas bloqueadas | Middleware `active`: cierra la sesión y revoca tokens inmediatamente |
| Fuerza bruta | Rate limiting en login, registro y recuperación; mensajes de login genéricos; la recuperación no revela si el correo existe |
| Asignación masiva | `role` e `is_blocked` fuera de `$fillable` (no se puede registrar un admin desde la API) |
| Archivos subidos | Validación de imagen real (`image`, `mimes`, tamaño ≤ 5 MB, dimensiones máx.), nombre aleatorio, disco `public` aislado; se elimina la foto anterior |
| CSV | Protección contra inyección de fórmulas al exportar |
| Configuración | Credenciales y claves en `.env` (no versionado); `APP_DEBUG=false` en producción oculta los detalles de los errores 500 |

---

## 8. Pruebas

```bash
php artisan test
```

40 pruebas de integración (`tests/Feature`) sobre SQLite en memoria (no tocan tu base MySQL):

| Archivo | Qué verifica |
|---|---|
| `AuthTest` | Registro, validaciones, escalada de rol, login correcto/incorrecto/bloqueado, 401, logout con token, rate limiting |
| `PasswordResetTest` | Envío del enlace sin revelar cuentas, restablecimiento con token válido/inválido |
| `MovementTest` | CRUD completo, estado por fecha, validaciones 422, aislamiento entre usuarios (403), filtros, búsqueda, paginación, totales, borrado múltiple, CSV, alerta de gasto inusual |
| `GoalTest` | CRUD, historial de abonos, notificación única, retiro excesivo (400), preferencias, aislamiento |
| `DashboardStatisticsTest` | Agregados del dashboard, periodos de estadísticas, validación del periodo |
| `ProfileSettingsTest` | Perfil sanitizado, avatar (válido, no imagen, > 5 MB), contraseña, preferencias parciales, eliminación en cascada |
| `NotificationTest` | Listado, leídas, aislamiento entre usuarios |
| `AdminTest` | Permisos por rol, KPIs, búsqueda, bloqueo con revocación inmediata, cuentas protegidas, reset/eliminación, modo demo |
| `WebRoutesTest` | Redirecciones de invitados, guardas por rol, sesión de cuenta bloqueada, rutas `.html` antiguas |

Además se verificó manualmente en el navegador (MySQL real): login, dashboard, CRUD de movimientos,
búsqueda, metas y abonos, notificaciones, configuración (perfil, avatar, tema, contraseña), panel admin,
recuperación de contraseña de punta a punta.

---

## 9. Pendientes conocidos (no implementados)

- **2FA**: el botón existe en el diseño, pero requiere un proveedor TOTP (p. ej. `laravel/fortify`); muestra un aviso.
- **Inicio de sesión con Google**: requiere Laravel Socialite y credenciales OAuth; muestra un aviso.
- **Resúmenes por correo** (`notif_weekly`, `notif_monthly`, `notif_email`): las preferencias se guardan, pero no hay
  tareas programadas que envíen esos correos.
- **Moneda e idioma**: se guardan como preferencia; la interfaz sigue mostrando COP y español, igual que el original.
