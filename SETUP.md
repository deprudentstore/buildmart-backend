# Backend Setup — De Prudent Ecommerce API (Termux)

## Why this isn't a full Laravel skeleton

This package contains the **custom application code**: migrations, models,
controllers, routes, and config for Paystack/Sanctum/Spatie roles. It does
**not** include Laravel's ~40 boilerplate framework files (default
`config/app.php`, `config/database.php`, `config/cache.php`, service
providers, etc.) — those are safer to generate fresh via Composer than to
hand-type, since a single wrong default breaks the boot process in ways
that are painful to debug on a phone.

## Steps

```bash
# 1. Create a fresh Laravel 11 skeleton (this generates all the framework
#    boilerplate correctly)
cd ~/project
composer create-project laravel/laravel:^11.0 backend-laravel-fresh
cd backend-laravel-fresh

# 2. Install the extra packages this app needs
composer require laravel/sanctum spatie/laravel-permission \
    yabacon/paystack-php kreait/firebase-php

# 3. Publish Sanctum + Spatie config (creates config/sanctum.php,
#    config/permission.php)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# 4. Now copy this package's custom files OVER the fresh skeleton,
#    overwriting where they overlap:
cp -r ~/project/backend-laravel/app/Models/* app/Models/
cp -r ~/project/backend-laravel/app/Http/Controllers/Api app/Http/Controllers/
cp ~/project/backend-laravel/routes/api.php routes/api.php
cp ~/project/backend-laravel/bootstrap/app.php bootstrap/app.php
cp ~/project/backend-laravel/config/services.php config/services.php
cp ~/project/backend-laravel/database/seeders/DatabaseSeeder.php database/seeders/
cp ~/project/backend-laravel/database/migrations/2024_01_01_*.php database/migrations/
cp ~/project/backend-laravel/.env.example .env

# 5. Generate app key
php artisan key:generate

# 6. Run the DB setup script (fixes Termux MariaDB auth, creates laravel_user)
chmod +x ~/project/setup_db.sh
~/project/setup_db.sh

# 7. Migrate and seed
php artisan migrate:fresh --seed

# 8. Serve
php artisan serve --host=0.0.0.0 --port=8000
```

## Verify it worked

```bash
curl http://localhost:8000/api/categories
curl http://localhost:8000/api/products
```

Admin login (seeded):
- email: `admin@deprudent.test`
- password: `password123`

## Paystack webhook (local testing)

Paystack needs a public URL to reach your webhook. Use a tunnel:
```bash
pkg install -y openssh
ssh -R 80:localhost:8000 serveo.net
```
Then set the webhook URL in your Paystack dashboard to
`https://<your-serveo-subdomain>/api/webhooks/paystack`.

## Next steps

Once this is confirmed working (migrations run clean, `/api/products`
returns data, admin login works), tell me and I'll build the Next.js
storefront next.
