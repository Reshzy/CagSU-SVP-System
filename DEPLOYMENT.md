# Deployment Guide

## Prerequisites
- PHP 8.2+
- Composer
- MySQL 8.0
- Node.js + npm (for assets)

## Environment
1. Copy `.env.example` to `.env` and configure DB and mail settings.
2. Generate key:
```
php artisan key:generate
```

## Install
```
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan db:seed --force
npm ci && npm run build
php artisan storage:link
```

## Optimizations
```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Queue/Jobs (optional)
- Use database queue:
```
php artisan queue:table && php artisan migrate
php artisan queue:work --daemon
```

## Health Check
- GET `/health` returns `{ status: "ok", time: "..." }`.

## Roles
- Seeded roles include System Admin, Supply Officer, Budget Office, BAC roles, Executive Officer, Accounting Office, Supplier.

## Default Accounts
- Check `database/seeders/RolePermissionSeeder.php` for example users (admin/supply).
