<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración para el generador automático de APIs
    |
    */

    'default_middleware' => ['api', 'auth:sanctum'],

    'routes_prefix' => 'api/admin',

    'routes_middleware' => ['api'],

    'pagination_limit' => 15,

    'models_namespace' => 'App\\Models\\',

    'controllers_namespace' => 'App\\Http\\Controllers\\Api\\',

    'requests_namespace' => 'App\\Http\\Requests\\',

    'resource_namespace' => 'App\\Http\\Resources\\',

    // Modelos que se deben excluir automáticamente
    'exclude_models' => [
        'User',
        'PasswordResetToken',
        'PersonalAccessToken',
    ],

    // Métodos HTTP a generar por defecto
    'methods' => ['index', 'store', 'show', 'update', 'destroy'],

    // Incluir documentación OpenAPI/Swagger
    'generate_documentation' => true,

    // Estilos para la interfaz de administración
    'ui' => [
        'theme' => 'default',
        'primary_color' => '#3b82f6',
    ],
];