# Sistema de Transacciones y Facturación

Este proyecto está construido en **PHP 8.2** utilizando el framework **Laravel 11** y **PostgreSQL** como motor de base de datos. Sigue la guía a continuación para configurar y levantar el proyecto en tu entorno local.

---

## 🛠️ Requisitos Previos

Antes de clonar el proyecto, asegúrate de tener instalado en tu computadora:

1. **PHP (8.2 o superior)**.
2. **Composer** (gestor de dependencias de PHP).
3. **Node.js & NPM** (para la compilación de Tailwind CSS con Vite).
4. **PostgreSQL** (asegúrate de tenerlo activo localmente).

### ⚙️ Extensiones de PHP requeridas en tu php.ini
En tu archivo de configuración `php.ini`, debes tener las siguientes extensiones habilitadas (sin el `;` al inicio):
```ini
extension=curl
extension=fileinfo
extension=mbstring
extension=openssl
extension=pdo_pgsql
extension=pgsql
extension=zip
```

---

## 🚀 Guía de Instalación Local

Sigue estos pasos en orden desde tu consola:

### 1. Clonar el repositorio
```bash
git clone https://github.com/AlfaroSystems/TransaccionesFacturacion.git
cd TransaccionesFacturacion
```

### 2. Instalar dependencias del backend (PHP)
```bash
composer install
```

### 3. Instalar dependencias del frontend (Node/JS)
```bash
npm install
```

### 4. Configurar el archivo de entorno (.env)
Crea una copia del archivo de ejemplo `.env.example` y cámbiale el nombre a `.env`:
```bash
cp .env.example .env
```
*(En Windows PowerShell: `copy .env.example .env`)*.

Abre el archivo `.env` en tu editor de código y asegúrate de que la sección de la base de datos coincida con tus credenciales de PostgreSQL:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=transacciones_facturacion
DB_USERNAME=postgres
DB_PASSWORD=1234
```
> [!IMPORTANT]
> Recuerda crear la base de datos llamada `transacciones_facturacion` en tu servidor PostgreSQL (vía pgAdmin o terminal) antes de continuar.

### 5. Generar la clave de la aplicación
```bash
php artisan key:generate
```

### 6. Ejecutar las migraciones de la base de datos
```bash
php artisan migrate
```

---

## 💻 Ejecución del Proyecto

Para correr la aplicación de forma local, debes iniciar dos procesos (puedes abrirlos en terminales diferentes):

### Servidor PHP (Laravel)
```bash
php artisan serve
```
*Esto iniciará el servidor web en `http://127.0.0.1:8000`.*

### Compilador de Activos (Vite / Tailwind CSS)
```bash
npm run dev
```

---

## 📂 Estructura del Diseño
El diseño del panel de control sigue el estilo del dashboard empresarial (Menú lateral izquierdo, métricas en 3 columnas y gráfico de rendimiento en ancho completo).
*   **Layout principal:** `resources/views/layouts/app.blade.php`
*   **Dashboard view:** `resources/views/dashboard.blade.php`
