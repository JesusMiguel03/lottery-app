1. Descomprima la carpeta del proyecto ("lottery-app").
2. Instale las dependencias de Composer:

-> composer install

3. Instale las dependencias de Node.js:

-> npm install

4. Ejecute los siguientes comandos dentro de la carpeta del proyecto

-> cp .env.example .env
-> php artisan key:generate

5. Configure el archivo `.env` con las credenciales de base de datos y otras configuraciones necesarias.

-> APP_ENV=production
-> APP_DEBUG=false
-> APP_NAME="Nombre del proyecto"
-> APP_TIMEZONE=America/Caracas
-> APP_LOCALE=es
-> APP_FALLBACK_LOCALE=es
-> APP_FAKER_LOCALE=es_US

6. Ejecuta las migraciones y seeders de la base de datos:

-> php artisan migrate --seed

7. Enlaza el storage:

-> php artisan storage:link

8.  Prepara el sistema para ser usado:

-> php artisan filament:assets
-> composer dump-autoload --optimize
-> npm run build
-> php artisan optimize
