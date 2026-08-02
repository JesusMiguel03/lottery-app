# ============================================================================
#  Dockerfile — LotteryApp (Laravel 11 + Filament) con SQLite
#  Build multi-etapa: frontend (Vite) + runtime (Apache + PHP + SQLite)
# ============================================================================

# ----------------------------------------------------------------------------
# Etapa 1 — Frontend (compilación de assets con Vite a public/build)
# ----------------------------------------------------------------------------
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build

# ----------------------------------------------------------------------------
# Etapa 2 — Imagen de ejecución (Apache + PHP + SQLite + Composer)
# ----------------------------------------------------------------------------
FROM php:8.3-apache AS app

# Extensiones PHP necesarias para Laravel + Filament.
# NOTA: pdo_sqlite y sqlite3 ya vienen habilitados por defecto en php:8.3-apache.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libicu-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        zip \
        exif \
        pcntl \
    && docker-php-source delete \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && php -m | grep -q pdo_sqlite \
    && php -m | grep -q sqlite3

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Apache: mod_rewrite + mod_headers y ServerName
RUN a2enmod rewrite headers \
    && echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Dependencias de Composer (aquí ya existen las extensiones requeridas por Filament)
WORKDIR /var/www/html
COPY composer.json composer.lock ./
# --no-scripts evita ejecutar artisan durante la instalación (la app aun no está completa)
RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist \
        --optimize-autoloader

# Copiar el resto de la aplicación (sin lo excluido en .dockerignore)
COPY . .

# Copiar assets compilados por Vite (public/build)
COPY --from=frontend /app/public/build ./public/build

# Permisos y estructura de storage (requerida por Laravel: cache, sessions, vistas)
RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

# Configuración de Apache (DocumentRoot -> public/) y entrypoint de arranque
COPY docker/apache/lottery.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown www-data:www-data /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["apache2-foreground"]
