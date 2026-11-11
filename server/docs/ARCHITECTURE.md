# Vibey Leads — Backend Architecture

> **⚠️ STALE — Phase 1 planning doc, not current-state documentation.** Written
> when only `AuthController` existed. All business modules (Leads, Clients,
> Projects, Documents, Meetings, AI, Analytics, Client Portal, Settings, Team)
> have since been built — folder structure, roles, and seeded accounts below
> no longer match reality. Read the actual `app/` tree for current state.

Laravel 12 · PHP 8.3+ · MySQL 8 · Sanctum · REST API. The Vue SPA in
`../client` consumes this API.

## Folder structure
```
server/
├── app/
│   ├── Enums/            UserRole (+ business enums added per module)
│   ├── Events/           broadcast/domain events            (per module)
│   ├── Helpers/          global helpers                     (autoload when added)
│   ├── Http/
│   │   ├── Controllers/  base Controller (uses ApiResponse)
│   │   │   └── Api/       AuthController (+ module controllers later)
│   │   ├── Middleware/    EnsureUserHasRole  → alias `role`
│   │   ├── Requests/      Form Requests (Auth/LoginRequest …)
│   │   └── Resources/     API Resources (UserResource …)
│   ├── Jobs/             queued jobs                          (per module)
│   ├── Listeners/        event listeners                      (per module)
│   ├── Models/           Eloquent models
│   ├── Notifications/    mail/db notifications                (per module)
│   ├── Policies/         authorization policies               (per module)
│   ├── Services/         CloudinaryService (+ domain services)
│   └── Traits/           ApiResponse
├── config/              cors.php, services.php (cloudinary), …
├── database/migrations  auth foundation only (business tables pending review)
├── docs/                DATABASE_DESIGN.md · API_STRUCTURE.md · this file
└── routes/api.php       auth routes now; modules added later
```
Empty module dirs (Jobs/Events/Listeners/Notifications/Policies/Helpers) are
kept with `.gitkeep` — classes are generated as each module needs them, not
speculatively.

## API response standard
One envelope for everything (`App\Traits\ApiResponse`, used by base
`Controller`):
```json
{ "success": true, "message": "", "data": {} }          // success
{ "success": false, "message": "...", "errors": {...} }  // error / 422
{ "success": true, "message": "", "data": [], "meta": {…} } // paginated
```
`bootstrap/app.php` renders **all** `/api/*` exceptions into this shape
(validation → 422, auth → 401, http → status, else 500).

## Authentication
- Sanctum personal-access **bearer tokens** (SPA sends `Authorization: Bearer`).
- `POST /api/login` → token + user · `POST /api/logout` revokes · `GET /api/user`.
- Passwords hashed via the model's `hashed` cast (bcrypt).
- **Roles**: `admin`, `sales`, `project_manager`, `client` (`UserRole` enum,
  cast on `users.role`). Coarse gating via `role:` middleware; ownership via
  Policies (added per module). Analytics/revenue is admin-only.
- Seeded accounts (`php artisan db:seed`), password `password`:
  `bob@vibeyworlds.com` (admin), `jay@vibeyworlds.com` (admin),
  `sales@vibeyworlds.com`, `pm@vibeyworlds.com`.

## File storage
`App\Services\CloudinaryService` — signed REST upload via the HTTP client (no
SDK package). Configure `CLOUDINARY_*` in `.env`. Not wired into modules yet.

## Configuration (`.env`)
MySQL (`crm`), `QUEUE_CONNECTION=database`, `CACHE_STORE=database`,
`SESSION_DRIVER=database`, `MAIL_MAILER=log` (dev), CORS via `config/cors.php`
(`FRONTEND_URL`/`CLIENT_URL`), Sanctum stateful domains, Cloudinary, Reverb
placeholders.

## Local run
```
composer install
php artisan migrate --seed
php artisan serve            # http://127.0.0.1:8000
# queues:      php artisan queue:work
# reverb:      php artisan reverb:start   (after install, when realtime is built)
```
