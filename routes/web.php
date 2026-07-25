<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpleadoController;

Route::get('/', function () {
    return view('dashboard');
})->name('dashboard');

Route::resource('users', UserController::class);

Route::resource('empleados', EmpleadoController::class);
