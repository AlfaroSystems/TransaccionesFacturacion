<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;

Route::get('/', function () {
    return view('welcome');
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

    // Bitácora de Auditoría (protegida por autenticación y permiso)
    Route::get('/audit-logs', [App\Http\Controllers\AuditLogController::class, 'index'])
        ->middleware('can:bitacora.ver')
        ->name('audit-logs.index');
});

require __DIR__.'/auth.php';
