#!/bin/sh
set -e

cd /var/www/html

echo ">> LotteryApp: preparando entorno..."

# 0) Asegurar la estructura de storage que Laravel requiere
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs

# 1) Garantizar una APP_KEY válida
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
    echo ">> Generando APP_KEY..."
    php artisan key:generate --force
fi

# 2) Asegurar que existe el archivo de la base de datos SQLite
DB_FILE="${DB_DATABASE:-/var/www/html/database/database.sqlite}"
mkdir -p "$(dirname "$DB_FILE")"
if [ ! -f "$DB_FILE" ]; then
    echo ">> Creando base de datos SQLite: $DB_FILE"
    touch "$DB_FILE"
fi
# Permisos para el directorio y el archivo: el proceso web (www-data) debe poder
# escribir en el directorio para crear los archivos -wal/-shm de SQLite WAL.
chown -R www-data:www-data "$(dirname "$DB_FILE")" 2>/dev/null || true
chmod -R ug+rwx "$(dirname "$DB_FILE")" 2>/dev/null || true
chown www-data:www-data "$DB_FILE" 2>/dev/null || true
chmod 664 "$DB_FILE" 2>/dev/null || true

# 3) Publicar assets de Filament y enlazar storage
echo ">> Publicando assets de Filament..."
php artisan filament:assets --ansi >/dev/null 2>&1 || true
php artisan vendor:publish --tag=laravel-assets --ansi --force >/dev/null 2>&1 || true
php artisan storage:link >/dev/null 2>&1 || true

# 4) Migrar y sembrar datos demo (solo migraciones pendientes; el seeder es idempotente)
echo ">> Ejecutando migraciones y datos de demostración..."
php artisan migrate --seed --force

echo ">> LotteryApp lista. Iniciando Apache..."
exec "$@"
