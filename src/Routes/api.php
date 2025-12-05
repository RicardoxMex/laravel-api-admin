<?php

use Illuminate\Support\Facades\Route;
use Mexancode\ApiAdmin\Http\Controllers\ApiAdminController;

Route::prefix(config('api-admin.routes_prefix'))
    ->middleware(config('api-admin.default_middleware'))
    ->group(function () {

        // Ruta para la interfaz de administración
        Route::get('/dashboard', function () {
            return view('api-admin::api-admin');
        })->name('api-admin.dashboard');

        // Ruta para documentación
        Route::get('/docs', function () {
            return view('api-admin::api-docs');
        })->name('api-admin.docs');

        // Rutas dinámicas se generarán por comando
    });