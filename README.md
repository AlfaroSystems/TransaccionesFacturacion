# Sistema de Transacciones y Facturación ERP 🚀

Sistema integral de gestión empresarial, compras, inventario y facturación construido con **Laravel 11**, **PHP 8.3**, **Tailwind CSS** y **PostgreSQL**, desplegable nativamente mediante **Docker Compose**.

---

## 🌟 Características Principales

### 🛒 Módulo de Compras (Procurement & Purchasing)
- **Solicitudes de Compra:** Registro de requerimientos internos por área/sucursal.
- **Solicitudes de Cotización:** Emisión de solicitudes a proveedores para ítems específicos.
- **Ofertas y Cotizaciones de Proveedores:** Registro de ofertas recibidas, comparación de precios, descuentos, impuestos y selección de ofertas ganadoras.
- **Órdenes de Compra (OC):**
  - Generación de código correlativo automático (`OC-AAAA-0001`).
  - Flujo de estados: `Borrador` ➔ `Emitida` ➔ `Recepción Parcial` ➔ `Completada` / `Cancelada`.
  - Asignación de **Gastos Adicionales** (fletes, seguros, aranceles) con autocompletado inteligente de descripción.
  - Modales Tailwind de confirmación personalizados para transiciones de estado.
  - Exportación e Impresión de **PDF de la Orden de Compra**.
- **Tipos de Gastos:** Gestión parametrizable de conceptos de gastos adicionales.

### 📦 Módulo de Inventario y Productos
- **Productos:** Catálogo completo con SKU, precios, impuestos, unidades de medida y soporte multi-imagen con galería interactiva.
- **Categorías y Subcategorías:** Clasificación jerárquica de productos.
- **Unidades de Medida:** Configuración de unidades comerciales e industriales.
- **Estructura Logística:**
  - Empresas y Sucursales.
  - Categorías de Bodega, Bodegas y Ubicaciones físicas.

### 👥 Módulo de Administración y Seguridad
- **Proveedores y Contactos:** Directorio comercial con geolocalización (País, Departamento, Municipio, Distrito).
- **Usuarios, Roles y Permisos:** Control de acceso basado en roles (`RBAC`).
- **Logs de Auditoría:** Registro automatizado de acciones y trazabilidad de eventos.

### 🎨 Experiencia de Usuario (UI/UX)
- **Modo Oscuro / Claro:** Alternancia dinámica de temas visuales.
- **Sistema de Notificaciones Sileo Toast:** Notificaciones flotantes animadas e interactivas.
- **Diseño Responsive & Premium:** Interfaz basada en Tailwind CSS con alto contraste y micro-animaciones.

---

## 🛠️ Requisitos del Sistema

### Opción A: Entorno Docker (Recomendado)
- **Docker Desktop** (con Docker Engine y Docker Compose).

### Opción B: Entorno Local Tradicional
- **PHP 8.2** o superior (Extensiones: `pdo_pgsql`, `pgsql`, `fileinfo`, `mbstring`, `openssl`, `curl`, `zip`).
- **Composer** 2.x
- **Node.js 18+** y **NPM**
- **PostgreSQL 15+**

---

## 🚀 Despliegue Rápido con Docker (Recomendado)

Sigue estos pasos para iniciar todo el entorno de desarrollo en segundos:

### 1. Clonar el repositorio
```bash
git clone https://github.com/AlfaroSystems/TransaccionesFacturacion.git
cd TransaccionesFacturacion
```

### 2. Iniciar contenedores Docker
```bash
docker compose up -d --build
```
> [!NOTE]
> Esto creará y levantará los servicios:
> - **Web (Nginx):** `http://localhost:8005`
> - **App (PHP 8.3 FPM + Node + Vite):** Contenedor principal de la aplicación.
> - **DB (PostgreSQL 15):** Puerto `5434`.

### 3. Instalar dependencias y ejecutar migraciones (si es la primera vez)
```bash
docker exec transaccionesfacturacion-app-1 composer install
docker exec transaccionesfacturacion-app-1 php artisan key:generate
docker exec transaccionesfacturacion-app-1 php artisan migrate
docker exec transaccionesfacturacion-app-1 npm run build
```

Accede a la aplicación en tu navegador: **`http://localhost:8005`**

---

## 💻 Instalación Local Sin Docker

### 1. Clonar e instalar dependencias
```bash
git clone https://github.com/AlfaroSystems/TransaccionesFacturacion.git
cd TransaccionesFacturacion
composer install
npm install
```

### 2. Configurar entorno (.env)
```bash
cp .env.example .env
```
Ajusta tus credenciales de PostgreSQL en `.env`:
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=transacciones_facturacion
DB_USERNAME=postgres
DB_PASSWORD=tu_contraseña
```

### 3. Clave, Migraciones y Assets
```bash
php artisan key:generate
php artisan migrate
npm run build
```

### 4. Iniciar servidores de desarrollo
```bash
php artisan serve
npm run dev
```

---

## ⚡ Comandos Útiles de Mantenimiento

| Acción | Comando |
| :--- | :--- |
| **Limpiar caché de vistas Blade** | `docker exec transaccionesfacturacion-app-1 php artisan view:clear` |
| **Recompilar assets Vite / Tailwind** | `docker exec transaccionesfacturacion-app-1 npm run build` |
| **Ejecutar migraciones incrementales** | `docker exec transaccionesfacturacion-app-1 php artisan migrate` |
| **Ver estado de migraciones** | `docker exec transaccionesfacturacion-app-1 php artisan migrate:status` |
| **Estado de contenedores** | `docker compose ps` |

---

## 🛡️ Reglas de Desarrollo
- ⚠️ **Preservación de Datos:** NUNCA ejecutar `migrate:fresh` ni `migrate:refresh` en entornos de prueba/producción con datos existentes. Toda modificación estructural de tablas debe realizarse mediante nuevas migraciones incrementales (`php artisan migrate`).
- 💬 **Commits en Git:** Todos los mensajes de commit se redactan en **español**.
