<?php

namespace Database\Seeders;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Crear permisos
        $permissions = [
            // Usuarios
            [
                'id_permission' => 'usuarios.ver',
                'name' => 'Ver Usuarios',
                'description' => 'Permite listar y ver la información de los usuarios.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'usuarios.crear',
                'name' => 'Crear Usuarios',
                'description' => 'Permite crear nuevos usuarios en el sistema.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'usuarios.editar',
                'name' => 'Editar Usuarios',
                'description' => 'Permite modificar la información de los usuarios existentes.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'usuarios.eliminar',
                'name' => 'Eliminar Usuarios',
                'description' => 'Permite eliminar usuarios del sistema.',
                'action' => 'destroy',
            ],
            // Roles y Bitácora
            [
                'id_permission' => 'roles.administrar',
                'name' => 'Administrar Roles y Permisos',
                'description' => 'Permite configurar roles y asignarles permisos.',
                'action' => 'manage',
            ],
            [
                'id_permission' => 'bitacora.ver',
                'name' => 'Ver Bitácora de Logs',
                'description' => 'Permite revisar la bitácora de auditoría de actividad del sistema.',
                'action' => 'logs',
            ],
            // Sucursales
            [
                'id_permission' => 'branches.ver',
                'name' => 'Ver Sucursales',
                'description' => 'Permite ver el listado y detalle de las sucursales.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'branches.crear',
                'name' => 'Crear Sucursales',
                'description' => 'Permite registrar nuevas sucursales.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'branches.editar',
                'name' => 'Editar Sucursales',
                'description' => 'Permite modificar la información de las sucursales.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'branches.eliminar',
                'name' => 'Eliminar Sucursales',
                'description' => 'Permite eliminar sucursales del sistema.',
                'action' => 'destroy',
            ],
            // Bodegas
            [
                'id_permission' => 'warehouses.ver',
                'name' => 'Ver Bodegas',
                'description' => 'Permite ver el listado y detalle de las bodegas.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'warehouses.crear',
                'name' => 'Crear Bodegas',
                'description' => 'Permite registrar nuevas bodegas.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'warehouses.editar',
                'name' => 'Editar Bodegas',
                'description' => 'Permite modificar la información de las bodegas.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'warehouses.eliminar',
                'name' => 'Eliminar Bodegas',
                'description' => 'Permite eliminar bodegas del sistema.',
                'action' => 'destroy',
            ],
            // Categorías de Bodega
            [
                'id_permission' => 'warehouse_categories.ver',
                'name' => 'Ver Categorías de Bodegas',
                'description' => 'Permite ver el listado de categorías de bodegas.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'warehouse_categories.crear',
                'name' => 'Crear Categorías de Bodegas',
                'description' => 'Permite registrar nuevas categorías de bodegas.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'warehouse_categories.editar',
                'name' => 'Editar Categorías de Bodegas',
                'description' => 'Permite modificar las categorías de bodegas.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'warehouse_categories.eliminar',
                'name' => 'Eliminar Categorías de Bodegas',
                'description' => 'Permite eliminar categorías de bodegas.',
                'action' => 'destroy',
            ],
            // Ubicaciones
            [
                'id_permission' => 'locations.ver',
                'name' => 'Ver Ubicaciones y Mapa',
                'description' => 'Permite ver el listado y mapa de ubicaciones físicas de bodega.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'locations.crear',
                'name' => 'Crear Ubicaciones',
                'description' => 'Permite crear nuevas ubicaciones físicas.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'locations.editar',
                'name' => 'Editar Ubicaciones',
                'description' => 'Permite modificar ubicaciones físicas existentes.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'locations.eliminar',
                'name' => 'Eliminar Ubicaciones',
                'description' => 'Permite eliminar ubicaciones físicas.',
                'action' => 'destroy',
            ],
            // Empresas
            [
                'id_permission' => 'companies.ver',
                'name' => 'Ver Empresas',
                'description' => 'Permite ver el listado y detalle de las empresas.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'companies.crear',
                'name' => 'Crear Empresas',
                'description' => 'Permite registrar nuevas empresas.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'companies.editar',
                'name' => 'Editar Empresas',
                'description' => 'Permite modificar la información de las empresas.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'companies.eliminar',
                'name' => 'Eliminar Empresas',
                'description' => 'Permite eliminar empresas del sistema.',
                'action' => 'destroy',
            ],
            // Categorías de Productos
            [
                'id_permission' => 'categories.ver',
                'name' => 'Ver Categorías',
                'description' => 'Permite ver el listado de categorías de productos.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'categories.crear',
                'name' => 'Crear Categorías',
                'description' => 'Permite registrar nuevas categorías de productos.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'categories.editar',
                'name' => 'Editar Categorías',
                'description' => 'Permite modificar categorías de productos.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'categories.eliminar',
                'name' => 'Eliminar Categorías',
                'description' => 'Permite eliminar categorías de productos.',
                'action' => 'destroy',
            ],
            // Unidades de Medida
            [
                'id_permission' => 'units.ver',
                'name' => 'Ver Unidades de Medida',
                'description' => 'Permite ver el listado de unidades de medida.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'units.crear',
                'name' => 'Crear Unidades de Medida',
                'description' => 'Permite registrar nuevas unidades de medida.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'units.editar',
                'name' => 'Editar Unidades de Medida',
                'description' => 'Permite modificar unidades de medida existentes.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'units.desactivar',
                'name' => 'Activar/Desactivar Unidades',
                'description' => 'Permite cambiar el estado (activo/inactivo) de una unidad de medida.',
                'action' => 'toggle',
            ],
            // Productos
            [
                'id_permission' => 'products.ver',
                'name' => 'Ver Productos',
                'description' => 'Permite ver el catálogo general y ficha técnica de productos.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'products.crear',
                'name' => 'Crear Productos',
                'description' => 'Permite registrar nuevos productos en el catálogo.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'products.editar',
                'name' => 'Editar Productos',
                'description' => 'Permite modificar los datos de los productos.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'products.eliminar',
                'name' => 'Eliminar Productos',
                'description' => 'Permite eliminar productos del catálogo.',
                'action' => 'destroy',
            ],
            // Subcategorías
            [
                'id_permission' => 'subcategories.ver',
                'name' => 'Ver Subcategorías',
                'description' => 'Permite ver el listado de subcategorías.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'subcategories.crear',
                'name' => 'Crear Subcategorías',
                'description' => 'Permite registrar nuevas subcategorías.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'subcategories.editar',
                'name' => 'Editar Subcategorías',
                'description' => 'Permite modificar subcategorías.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'subcategories.eliminar',
                'name' => 'Eliminar Subcategorías',
                'description' => 'Permite eliminar subcategorías.',
                'action' => 'destroy',
            ],
            // Proveedores
            [
                'id_permission' => 'suppliers.ver',
                'name' => 'Ver Proveedores',
                'description' => 'Permite ver el listado y detalle de los proveedores.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'suppliers.crear',
                'name' => 'Crear Proveedores',
                'description' => 'Permite registrar nuevos proveedores.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'suppliers.editar',
                'name' => 'Editar Proveedores',
                'description' => 'Permite modificar información de proveedores.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'suppliers.eliminar',
                'name' => 'Eliminar Proveedores',
                'description' => 'Permite eliminar proveedores del sistema.',
                'action' => 'destroy',
            ],
            // Tipos de Gastos Adicionales
            [
                'id_permission' => 'expense_types.ver',
                'name' => 'Ver Tipos de Gastos',
                'description' => 'Permite ver los tipos de gastos adicionales.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'expense_types.crear',
                'name' => 'Crear Tipos de Gastos',
                'description' => 'Permite crear nuevos tipos de gastos adicionales.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'expense_types.editar',
                'name' => 'Editar Tipos de Gastos',
                'description' => 'Permite modificar tipos de gastos adicionales.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'expense_types.eliminar',
                'name' => 'Eliminar Tipos de Gastos',
                'description' => 'Permite activar/desactivar o eliminar tipos de gastos.',
                'action' => 'destroy',
            ],
            // Solicitudes de Compra
            [
                'id_permission' => 'purchase_requests.ver',
                'name' => 'Ver Solicitudes de Compra',
                'description' => 'Permite listar y ver el detalle de solicitudes de compra.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'purchase_requests.crear',
                'name' => 'Crear Solicitudes de Compra',
                'description' => 'Permite registrar nuevas solicitudes de compra.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'purchase_requests.editar',
                'name' => 'Editar Solicitudes de Compra',
                'description' => 'Permite editar solicitudes de compra existentes.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'purchase_requests.eliminar',
                'name' => 'Eliminar Solicitudes de Compra',
                'description' => 'Permite eliminar solicitudes de compra.',
                'action' => 'destroy',
            ],
            [
                'id_permission' => 'purchase_requests.enviar',
                'name' => 'Enviar Solicitudes de Compra',
                'description' => 'Permite enviar solicitudes de compra al departamento de compras.',
                'action' => 'send',
            ],
            [
                'id_permission' => 'purchase_requests.aprobar',
                'name' => 'Aprobar Solicitudes de Compra',
                'description' => 'Permite aprobar solicitudes enviadas para generar su solicitud de cotización.',
                'action' => 'approve',
            ],
            [
                'id_permission' => 'purchase_requests.devolver',
                'name' => 'Devolver/Rechazar Solicitudes de Compra',
                'description' => 'Permite devolver a la sucursal o rechazar solicitudes enviadas, indicando el motivo.',
                'action' => 'review',
            ],
            // Solicitudes de Cotización a Proveedores
            [
                'id_permission' => 'purchase_quotation_requests.ver',
                'name' => 'Ver Solicitudes de Cotización',
                'description' => 'Permite listar y consultar solicitudes de cotización a proveedores.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'purchase_quotation_requests.crear',
                'name' => 'Crear Solicitudes de Cotización',
                'description' => 'Permite generar solicitudes de cotización enviadas a proveedores.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'purchase_quotation_requests.seleccionar_cotizacion',
                'name' => 'Seleccionar Cotización Ganadora',
                'description' => 'Permite adjudicar o seleccionar la cotización ganadora de un proveedor.',
                'action' => 'selectQuotation',
            ],
            // Cotizaciones y Ofertas de Proveedor
            [
                'id_permission' => 'purchase_quotations.ver',
                'name' => 'Ver Cotizaciones de Proveedores',
                'description' => 'Permite consultar cotizaciones u ofertas registradas de proveedores.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'purchase_quotations.crear',
                'name' => 'Registrar Cotización de Proveedor',
                'description' => 'Permite registrar ofertas enviadas por proveedores.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'purchase_quotations.eliminar',
                'name' => 'Eliminar Cotización de Proveedor',
                'description' => 'Permite anular o eliminar ofertas registradas.',
                'action' => 'destroy',
            ],
            // Órdenes de Compra
            [
                'id_permission' => 'purchase_orders.ver',
                'name' => 'Ver Órdenes de Compra',
                'description' => 'Permite consultar el listado y detalle de órdenes de compra.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'purchase_orders.crear',
                'name' => 'Crear Órdenes de Compra',
                'description' => 'Permite registrar nuevas órdenes de compra.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'purchase_orders.editar',
                'name' => 'Editar Órdenes de Compra',
                'description' => 'Permite modificar órdenes de compra existentes.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'purchase_orders.eliminar',
                'name' => 'Eliminar Órdenes de Compra',
                'description' => 'Permite anular o eliminar órdenes de compra.',
                'action' => 'destroy',
            ],
            [
                'id_permission' => 'purchase_orders.aprobar',
                'name' => 'Aprobar/Rechazar Órdenes de Compra',
                'description' => 'Permite autorizar o cambiar el estado de órdenes de compra.',
                'action' => 'updateStatus',
            ],
            [
                'id_permission' => 'purchase_orders.pdf',
                'name' => 'Generar PDF de Órdenes de Compra',
                'description' => 'Permite descargar y visualizar el comprobante PDF de una orden de compra.',
                'action' => 'generatePdf',
            ],
            // Facturas y Recepciones de Compra
            [
                'id_permission' => 'purchases.ver',
                'name' => 'Ver Compras',
                'description' => 'Permite consultar el listado y detalle de facturas y recepciones de compra.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'purchases.crear',
                'name' => 'Registrar Compras',
                'description' => 'Permite registrar nuevas recepciones y compras a partir de órdenes.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'purchases.editar',
                'name' => 'Editar Compras',
                'description' => 'Permite modificar compras registradas en borrador.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'purchases.eliminar',
                'name' => 'Eliminar Compras',
                'description' => 'Permite anular o eliminar compras en borrador.',
                'action' => 'destroy',
            ],
            [
                'id_permission' => 'purchases.cambiar_estado',
                'name' => 'Cambiar Estado de Compras',
                'description' => 'Permite cambiar el estado de las compras (recibida, completada, anulada).',
                'action' => 'updateStatus',
            ],
            // Retaceos / Prorrateo de Costos de Importación
            [
                'id_permission' => 'retaceos.ver',
                'name' => 'Ver Retaceos',
                'description' => 'Permite consultar el listado y detalle de retaceos de importación.',
                'action' => 'index',
            ],
            [
                'id_permission' => 'retaceos.crear',
                'name' => 'Calcular Retaceos',
                'description' => 'Permite registrar y liquidar retaceos de importación.',
                'action' => 'create',
            ],
            [
                'id_permission' => 'retaceos.editar',
                'name' => 'Editar Retaceos',
                'description' => 'Permite modificar retaceos en borrador.',
                'action' => 'edit',
            ],
            [
                'id_permission' => 'retaceos.eliminar',
                'name' => 'Eliminar Retaceos',
                'description' => 'Permite eliminar o anular retaceos en borrador.',
                'action' => 'destroy',
            ],
            [
                'id_permission' => 'retaceos.cambiar_estado',
                'name' => 'Cambiar Estado de Retaceos',
                'description' => 'Permite cambiar el estado de un retaceo (calculado, aplicado, anulado).',
                'action' => 'updateStatus',
            ],
        ];

        // Guardar o actualizar permisos
        foreach ($permissions as $permissionData) {
            Permission::updateOrCreate(
                ['id_permission' => $permissionData['id_permission']],
                $permissionData
            );
        }

        // 2. Crear rol administrador
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'description' => 'Administrador General del Sistema con acceso total.',
            ]
        );

        // 3. Asignar todos los permisos al administrador
        $allPermissionIds = Permission::pluck('id_permission')->toArray();

        $adminRole->permissions()->sync($allPermissionIds);

        // 4. Asignar rol administrador a usuarios
        $adminUsers = User::whereIn('email', [
            'gracia@login.com',
            'jon.virgi@gmail.com',
        ])->get();

        foreach ($adminUsers as $user) {
            $user->roles()->sync([$adminRole->id_role]);
        }
    }
}