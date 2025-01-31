<header>
  <h1>Documentación del Proyecto</h1>
  <h2>(Sistema de Gestión de Rifas)</h2>
</header>

<div id="tabla-de-contenidos" class="table-of-content">
  <h3>Tabla de contenido</h3>
  <a href="http://127.0.0.1:8000/admin/login">
      Iniciar sesión
    </a>
</div>

-   [1. Apartado técnico](#1-apartado-técnico)
    -   [1.1. Tecnologías Utilizadas](#11-tecnologías-utilizadas)
        -   [1.1.1. Librerías](#111-librerías)
    -   [1.2. Requisitos Previos](#12-requisitos-previos)
    -   [1.3. Estructura del Proyecto](#13-estructura-del-proyecto)
    -   [1.4. Uso del Sistema](#14-uso-del-sistema)
-   [2. Sobre el sistema](#2-sobre-el-sistema)
    -   [2.1. Módulos Disponibles](#21-módulos-disponibles)
        -   [2.1.1. Inicio](#211-inicio)
        -   [2.1.2. Tasas](#212-tasas)
        -   [2.1.3. Clientes](#213-clientes)
        -   [2.1.4. Rifas](#214-rifas)

## 1. Apartado técnico

<div class="table-of-content">
  <h3 id="11-tecnologías-utilizadas">1.1. Tecnologías Utilizadas</h3>
  <a href="#tabla-de-contenidos">
      Volver a la Tabla de Contenidos
    </a>
</div>

-   [Laravel](https://laravel.com/): Framework PHP para el desarrollo de aplicaciones web.
-   [FilamentPHP](https://filamentphp.com/): Herramienta para construir paneles de administración elegantes y eficientes.
-   [AlpineJS](https://alpinejs.dev/): Framework JavaScript minimalista para interactividad en el frontend.
-   [TailwindCSS](https://tailwindcss.com/): Framework CSS utility-first para diseñar interfaces de usuario modernas.

    <h4 id="111-librerías">1.1.1 Librerías</h4>

    -   [Spatie/Backup](https://spatie.be/docs/laravel-backup/v8/introduction): Librería PHP para el manejo de respaldos de la base de datos.
    -   [Laravel-trend](https://github.com/Flowframe/laravel-trend): Librería PHP para el manejo simple de datos en las gráficas.
    -   [Laravel Apex Charts](https://filamentphp.com/plugins/leandrocfe-apex-charts): Librería PHP para el manejo de gráficas personalizadas.
    -   [WwebJS](whatsapp-web.js): Librería Javascript para el envio de mensajes a través de whatsapp web.
    -   [QRCode Terminal](https://www.npmjs.com/package/qrcode-terminal): Librería Javascript para mostrar el QR de whatsapp en la terminal.

<div class="table-of-content">
  <h3 id="12-requisitos-previos">1.2. Requisitos Previos</h3>
  <a href="#tabla-de-contenidos">
      Volver a la Tabla de Contenidos
    </a>
</div>

-   PHP >= 8.2
-   Composer >= 2.8
-   Node.js >= 20.x
-   NPM >= 10.9
-   Base de datos (SQLite)

<div class="table-of-content">
  <h3 id="13-estructura-del-proyecto">1.3. Estructura del Proyecto</h3>
  <a href="#tabla-de-contenidos">
      Volver a la Tabla de Contenidos
    </a>
</div>

```plaintext
lottery-app/
├── .wwebjs_auth/     # Autenticación de whatsapp
├── .wwebjs_cache/    # Cache de sessión de whatsapp
├── app/              # Lógica de la aplicación (Modelos, Controladores, etc.)
├── app/Filament/     # Recursos del framework para manejar los módulos
├── config/           # Archivos de configuración
├── database/         # Migraciones, seeders y factories
├── public/           # Archivos públicos (CSS, JS, imágenes)
├── resources/        # Vistas, assets sin compilar, lenguajes
├── routes/           # Rutas de la aplicación
├── storage/          # Archivos de almacenamiento (logs, caché, etc.)
├── tests/            # Pruebas automatizadas
└── vendor/           # Dependencias de Composer
```

<div class="table-of-content">
  <h3 id="14-uso-del-sistema">1.4. Uso del Sistema</h3>
  <a href="#tabla-de-contenidos">
      Volver a la Tabla de Contenidos
    </a>
</div>

Para iniciar el servidor ejecute el siguiente comando:

```sh
php artisan serve
```

## 2. Sobre el sistema

<div class="table-of-content">
  <h3 id="21-módulos-disponibles">1.1. Módulos Disponibles</h3>
  <a href="#tabla-de-contenidos">
      Volver a la Tabla de Contenidos
    </a>
</div>

#### 2.1.1. Inicio

Contiene información para una rápida visualización del estado del sistema, muestra gráficas y estadísticas relevantes de cada módulo

-   **Clientes registrados**
-   **Clientes nuevos (Gráfico)**
-   **Boletos totales**
-   **Boletos mensuales**
-   **Boletos vendidos (Gráfico)**
-   **Pagos totales**
-   **Pagos mensuales**
-   **Pagos mensuales (Gráfico)**
-   **Premios del mes (Tabla)**

#### 2.1.2. Tasas

Este módulo permite gestionar las tasas aplicables en el sistema, estas se emplean en la cotización de los boletos.

-   **Registrar**: Permite registrar nuevas tasas.
-   **Editar**: Permite editar tasas existentes.

#### 2.1.3. Clientes

Este módulo permite gestionar la información de los clientes.

-   **Registrar**: Permite registrar nuevos clientes.
-   **Editar**: Permite editar la información de clientes existentes.
-   **Ver**: Permite visualizar la información de los clientes.
-   **Borrar**: Permite eliminar clientes (toda información relacionada a ellos será borrada, los boletos comprados serán reiniciados).
-   **Ver premios**: Permite visualizar todos los premios que ha obtenido de las rifas.
-   **Ver boletos**: Permite visualizar los boletos que ha comprado o estan pendientes por pagar.

#### 2.1.4. Rifas

Este módulo permite gestionar las rifas y la venta de boletos.

-   **Registrar**: Permite registrar nuevas rifas.
-   **Editar**: Permite editar rifas existentes.
-   **Borrar**: Permite eliminar rifas (boletos, pagos y premios también serán borrados).
-   **Ver**: Permite visualizar las rifas registradas.
-   **Venta de boletos**: Permite gestionar la venta de boletos.
-   **Pago individual**: Permite realizar el pago de boletos de manera individual.
-   **Ver boletos vendidos**: Permite visualizar todos los boletos vendidos.
-   **Cancelar boletos pendientes**: Permite cancelar los boletos que no hayan sido pagados.
-   **Notas**:
    -   Puede registrar hasta 10.000 boletos por rifa.
    -   Puede registrar n rifas por mes.
    -   Se puede abonar/pagar todos los boletos vendidos en una sola transacción.
    -   Se puede pagar un boleto posterior a su compra.
-   **Ver premios**: Permite ver los premios registrados.
-   **Notificar a deudores**: Permite enviar un mensaje indicando al cliente los boletos están pendientes por pagar.
-   **Notificar a ganadores**: Permite enviar un mensaje indicando a los clientes ganadores que retiren su premio.
-   **Realizar sorteo**: Permite realizar el sorteo con los boletos que fueron pagados.
