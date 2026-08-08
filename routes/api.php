<?php

use App\Http\Controllers\SubCategoryController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/categories/{id}/sub-categories',
    [SubCategoryController::class, 'apiByCategory']
);