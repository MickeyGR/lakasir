# AGENTS.md

## Project overview
- Lakasir is a Laravel 11 + Filament 3 POS web app with Livewire/Volt and Tailwind/Vite assets.
- Multi-tenant setup via `stancl/tenancy`.

## Tech stack
- PHP 8.2, Laravel 11, Filament 3, Livewire 3, Volt.
- Vite + TailwindCSS for frontend assets.
- MySQL (per README).

## Key directories
- `app/`: application code (models, actions, services, policies, etc.).
- `routes/`: HTTP routes (`web.php`, `api.php`).
- `resources/`: views, assets, and frontend entrypoints.
- `database/`: migrations, factories, seeders (tenant migrations in `database/migrations/tenant`).
- `config/`: application config.
- `public/`: public entry.
- `tests/`: Pest tests.

## Common commands
- Install PHP deps: `composer install`; JS deps: `npm install`.
- Env setup: `cp .env.example .env`, set `APP_URL=http://localdomain.test:8000`, `APP_CENTRAL_DOMAIN=localdomain.test`, DB creds.
- App key: `php artisan key:generate`.
- Migrations (central): `php artisan migrate`.
- Build assets: `npm run build`; dev assets: `npm run dev`.
- Run tests: `php artisan test` (Pest).
- Local hosts: add `localdomain.test` and tenant domains you create (e.g., `tenantdemo.localdomain.test`) to `/etc/hosts`; VS Code task `Hosts: add localdomain.test` handles the base entry.
- Generate API docs: `php artisan scramble:export` (writes `api.json` used by Scalar).

## API docs
- Scalar UI: `/scalar` (OpenAPI JSON served at `/docs/api.json`).
- Generate/refresh spec: `php artisan scramble:export` (uses Scramble; UI route is disabled).
- Examples seeded (tenant `tenantdemo`): auth/login/logout/me, check, about (GET/PUT), settings (GET/POST), category, product (list/create/update/stock), member, payment-method, selling (list/create/detail), dashboard totals, cash-drawer, cashier report, notifications, upload, secure-initial-price, register-fcm-token, domain/register, etc. (most API groups covered with real/sampled payloads).
- Refresh in Scalar: after `php artisan scramble:export`, hard reload the browser (or open in incognito) to bust cache and load the new `/docs/api.json`.

## Notes for agents
- Respect tenancy boundaries; tenant migrations live under `database/migrations/tenant`.
- Filament assets may require `php artisan filament:assets` and `php artisan livewire:publish --assets` during setup.
- Frontend build uses Vite; check `vite.config.js` for entrypoints.

## Local workflows
### First-time setup (multi-tenant)
- `cp .env.example .env` and set `APP_URL=http://localdomain.test:8000`, `APP_CENTRAL_DOMAIN=localdomain.test`, DB/mail/storage as needed.
- `composer install`, `npm install`.
- `php artisan key:generate`.
- `php artisan migrate` (central tables).
- `php artisan filament:assets`, `php artisan livewire:publish --assets`.
- Start servers: `php artisan serve --host=localdomain.test --port=8000` and `npm run dev`.
- Hosts: add `127.0.0.1 localdomain.test` and any tenant domains you register (e.g., `tenantdemo.localdomain.test`).
### Daily dev loop
- Backend: `php artisan serve`; Frontend: `npm run dev`; queue/scheduler if used.
- Web (central): landing `/`; tenant panel: `http://{tenant}.localdomain.test/member`.
- Tenancy: create tenants via UI `/auth/register` or API POST `/api/domain/register`; this migrates `database/migrations/tenant` and seeds permissions/payment methods/categories for that tenant.
### Standalone mode (no central domain)
- If `APP_CENTRAL_DOMAIN` is empty, use `php artisan app:create-user` to create the owner admin and log in at `{APP_URL}/member/login`.

### Useful checks
- `php artisan test`
- `php artisan route:list`
- `php artisan config:clear` when env/config changes

## WebStorm run configurations (suggested)
Create these in WebStorm > Run | Edit Configurations:

### 1) Laravel app server
- Type: PHP Built-in Web Server
- Document root: `public`
- Host: `127.0.0.1`
- Port: `8000`
- PHP interpreter: your local PHP 8.2+
- Alternative: Type `Shell Script` and run `php artisan serve`

### 2) Vite dev server
- Type: npm
- Package manager: project `package.json`
- Command: `run`
- Scripts: `dev`

### 3) Queue worker (optional)
- Type: Shell Script
- Script: `php artisan queue:work`
- Working directory: project root

### 4) Scheduler (optional)
- Type: Shell Script
- Script: `php artisan schedule:work`
- Working directory: project root

### 5) Tests
- Type: PHPUnit or Pest (if plugin installed)
- Config file: `phpunit.xml`
- Working directory: project root

## Deploy notes (fill in for your environment)
- Ensure `.env` is set for production and `APP_ENV=production`.
- Run `composer install --no-dev` on servers.
- Run `php artisan migrate` (central) and tenant migrations via `tenants:migrate` or tenant registration flow as needed.
- Run `npm run build` and publish assets.
- Configure queue/scheduler (Supervisor/systemd/cron) if used.

## Quick routes/URLs
- Central (`APP_URL`): landing `/` (from `resources/views/livewire/pages/welcome.blade.php`), register tenant `/auth/register`, PWA `/offline`, `/serviceworker.js`, API `/api/domain/register`, `/api/test`. No `/admin` panel.
- Tenant domain (e.g., `tenantdemo.localdomain.test`): `/` redirects to `/member` (Filament panel/POS). Extra pages: `/member/sellings/{id}/print`, `/member/*-report/generate`, `/reset-password/{token}`.
- Tenant API: `https://{tenant-domain}/api/*` (auth, master data, transaction selling/cash-drawer, settings, reports, printer, notification, uploads, `GET /api/check`).
