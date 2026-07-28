<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\WarehouseCategoryController;

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
    
    // Gestión de Usuarios (CRUD) protegida por autenticación
    Route::resource('users', UserController::class);

    // Gestión de Roles y Permisos (CRUD) protegida por autenticación
    Route::resource('roles', RoleController::class);

    // Gestión de Empleados (CRUD) protegida por autenticación
    Route::resource('empleados', EmpleadoController::class);

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
