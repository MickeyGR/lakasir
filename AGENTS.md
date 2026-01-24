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
- Install PHP deps: `composer install`
- Install JS deps: `npm install`
- Env setup: `cp .env.example .env` then update env values
- App key: `php artisan key:generate`
- Migrations + seed: `php artisan migrate --path=database/migrations/tenant --seed`
- Create user: `php artisan app:create-user`
- Build assets: `npm run build`
- Dev assets: `npm run dev`
- Run tests: `php artisan test` (Pest)

## Notes for agents
- Respect tenancy boundaries; tenant migrations live under `database/migrations/tenant`.
- Filament assets may require `php artisan filament:assets` and `php artisan livewire:publish --assets` during setup.
- Frontend build uses Vite; check `vite.config.js` for entrypoints.

## Local workflows
### First-time setup
- `cp .env.example .env` and set `APP_URL`, database, mail, and any tenant or storage settings.
- `composer install`
- `php artisan key:generate`
- `php artisan migrate --path=database/migrations/tenant --seed`
- `php artisan app:create-user`
- `php artisan filament:assets`
- `php artisan livewire:publish --assets`
- `npm install`
- `npm run dev` (or `npm run build` for production assets)

### Daily dev loop
- Backend: `php artisan serve`
- Frontend: `npm run dev`
- Backgrounds (if enabled): `php artisan queue:work`
- Scheduler (if enabled): `php artisan schedule:work`

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
- Run `php artisan migrate --path=database/migrations/tenant --seed` as needed.
- Run `npm run build` and publish assets.
- Configure queue/scheduler (Supervisor/systemd/cron) if used.
