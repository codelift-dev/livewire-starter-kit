#!/usr/bin/env bash
#
# Reproducible verification run for laravel/react-starter-kit.
# Runs from inside the Docker image defined in codelift/Dockerfile.
# Idempotent: safe to re-run; reuses vendor/ and node_modules/ if present.
#
set -euo pipefail

cd /app

echo "== composer install =="
[ -d vendor ] || composer install --prefer-dist

echo "== .env =="
[ -f .env ] || cp .env.example .env
if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate
fi

echo "== sqlite DB =="
mkdir -p database
touch database/database.sqlite

echo "== migrate =="
php artisan migrate --force

echo "== npm install =="
[ -d node_modules ] || npm install

echo "== npm run build =="
npm run build

echo "== php artisan test =="
php artisan test
