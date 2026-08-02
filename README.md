# 🎰 LotteryApp — Sistema de Administración de Loterías y Rifas

Sistema web completo para la **gestión de loterías y rifas**: registro de clientes, creación de sorteos,
venta de boletos, cobros, control de tasas de cambio y premiación con seguimiento de ganadores.

> ⚠️ Este repositorio está configurado en **modo demostración** con datos precargados.

---

## ✨ Funcionalidades

| Módulo | Descripción |
|---|---|
| 🗓️ **Rifas / Sorteos** | Crear, editar, cerrar y eliminar sorteos (hasta 10.000 boletos por rifa). |
| 🎟️ **Venta de boletos** | Selección múltiple de boletos libres, pago individual o abono posterior. |
| 💰 **Pagos y cobros** | Pago total, abonos y control de deudores con estados por boleto. |
| 👥 **Clientes** | Registro, edición, borrado y consulta de clientes con su historial. |
| 💵 **Tasas de cambio** | Registro diario de la tasa oficial utilizada para pagos en Bs. |
| 🏆 **Premios** | Configuración de premiación por sorteo y ejecución del sorteo (raffle) con registro de ganadores. |
| 📊 **Dashboard** | Estadísticas, gráficas (ApexCharts) y widgets de control en tiempo real. |

---

## 🔑 Credenciales de demostración

| Campo | Valor |
|---|---|
| **URL del panel** | `http://localhost/admin` |
| **Usuario** | `admin@demo.com` |
| **Contraseña** | `demo` |

El formulario de inicio de sesión muestra las credenciales de demo para facilitar el acceso.

### Datos precargados (seeder)

- 1 usuario administrador (`admin@demo.com` / `demo`).
- 8 clientes de ejemplo con cédulas y teléfonos ficticios.
- 3 rifas: **activa**, **finalizada (con 3 ganadores)** y **próxima**.
- Tasas de cambio de los últimos 2 días.
- Boletos vendidos, pagados y/o pendientes en cada rifa.
- Boletos ganadores en la rifa finalizada (visibles en la landing).

---

## 🐳 Demo con Docker + SQLite (recomendado)

Levantar toda la aplicación en modo demostración con un solo comando. El `Dockerfile`
build multi-etapa instala dependencias de Composer, compila los assets (Vite) y arranca
Apache + PHP + SQLite. Los datos de demo se migran y siembran automáticamente al iniciar.

> Requisito: Docker Engine **20.10+** y Docker Compose **v2+**.

```bash
# Construir y levantar la aplicación
docker compose up -d --build
```

La aplicación quedará disponible en:

- **Panel de administración:** `http://localhost:8080/admin`
- **Usuario:** `admin@demo.com`
- **Contraseña:** `demo`

### Comandos útiles

```bash
docker compose up -d --build      # Construir y arrancar
docker compose logs -f app        # Ver logs
docker compose restart            # Reiniciar
docker compose stop               # Detener (sin borrar datos)
docker compose down               # Detener y eliminar contenedor
docker compose down -v            # Detener y borrar también la base de datos
```

La base de datos SQLite se guarda en un volumen Docker (`lottery_data`), así que los
datos persisten entre reinicios. Para dejar los datos de demo como nuevos:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

> 💡 Puedes cambiar el puerto expuesto con la variable `APP_PORT`:
> `APP_PORT=90 docker compose up -d --build`

---

## 🚀 Instalación


### Requisitos

- PHP `^8.2`
- Composer
- Node.js `18+`
- SQLite (por defecto) — opcionalmente MySQL/PostgreSQL

### Pasos

```bash
# 1. Instalar dependencias
composer install
npm install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate
# → Edita .env si necesitas otra base de datos o configuración

# 3. Crear la base de datos y sembrar datos de demo
php artisan migrate --seed

# 4. Assets de Filament y compilación frontend
php artisan storage:link
php artisan filament:assets
npm run build

# 5. Optimización para producción/demo
php artisan optimize
```

### Ejecución en desarrollo

```bash
composer run dev
```

Esto levanta en paralelo: servidor PHP (`http://localhost:8000`), cola de jobs, logs y Vite.

---

## 🎯 ¿Cómo restablecer la demo?

Cualquier dato que se modifique durante una prueba puede restaurarse en segundos:

```bash
php artisan migrate:fresh --seed
```

---

## 🧰 Comandos útiles

```bash
php artisan tinker            # Consola interactiva
php artisan db:seed           # Re-ejecuta los seeders (idempotente)
php artisan make:filament-resource  # Nuevo recurso del panel
```

---

## 🧱 Tecnologías

- [Laravel 11](https://laravel.com)
- [Filament 3](https://filamentphp.com) (panel de administración + Livewire)
- [Tailwind CSS](https://tailwindcss.com) + Vite
- [Filament ApexCharts](https://github.com/leandrocfe/filament-apex-charts)
- SQLite / MySQL / PostgreSQL

---

## 📁 Estructura principal

```
app/
├── Filament/
│   ├── Pages/            # Dashboard y páginas del panel
│   ├── Resources/        # Recursos: Client, Currency, Lottery
│   └── Widgets/          # Widgets (estadísticas y gráficas)
├── Livewire/             # Componentes Livewire (tickets, gráficas)
├── Models/               # User, Client, Lottery, Ticket, Payment, Prize...
├── Providers/            # AppServiceProvider, BackupServiceProvider, AdminPanelProvider
database/seeders/         # DemoUserSeeder + DemoDataSeeder
docs/                     # Instalación, requerimientos y TODO
resources/views/landing.blade.php   # Landing pública
```

---

## 📄 Licencia

Proyecto de demostración basado en Laravel (MIT).
