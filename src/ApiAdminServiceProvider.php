<?php

namespace Mexancode\ApiAdmin;

use Illuminate\Support\ServiceProvider;
use Mexancode\ApiAdmin\Console\Commands\MakeApiAdmin;
use Mexancode\ApiAdmin\Console\Commands\MakeApiDocs;

class ApiAdminServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Publicar configuraciones
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/api-admin.php' => config_path('api-admin.php'),
            ], 'api-admin-config');

            // Registrar comando
            $this->commands([
                MakeApiAdmin::class,
                MakeApiDocs::class,
            ]);
        }

        // Cargar rutas
        $this->loadRoutesFrom(__DIR__ . '/Routes/api.php');

        // Cargar vistas
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'api-admin');
    }

    public function register()
    {
        // Fusionar configuración
        $this->mergeConfigFrom(
            __DIR__ . '/../config/api-admin.php', 'api-admin'
        );
    }
}