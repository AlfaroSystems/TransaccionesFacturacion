<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseCategoryController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\CompanyController;

Route::resource('branches', BranchController::class);

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Gestión de Empresas (CRUD) protegida por autenticación y permisos
    Route::resource('companies', CompanyController::class);

    // Gestión de Usuarios (CRUD) protegida por autenticación
    Route::resource('users', UserController::class);

    // Gestión de Roles y Permisos (CRUD) protegida por autenticación
    Route::resource('roles', RoleController::class);

    // Gestión de Empleados (CRUD) protegida por autenticación
    Route::resource('empleados', EmpleadoController::class);

    // Mapa de Bodega
    Route::get('locations/map', [LocationController::class, 'map'])->name('locations.map');

    // Gestión de Ubicaciones (CRUD) protegida por autenticación
    Route::resource('locations', LocationController::class);

    // Bitácora de Auditoría (protegida por autenticación y permiso)
    Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('can:bitacora.ver')
        ->name('audit-logs.index');

    // Gestión de Almacenes (CRUD) protegida por autenticación
    Route::resource('warehouses', WarehouseController::class);

    // Gestión de Categorías de Almacenes (CRUD) protegida por autenticación
    Route::resource('warehouse_categories', WarehouseCategoryController::class);
});

require __DIR__.'/auth.php';
