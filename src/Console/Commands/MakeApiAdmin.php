<?php

namespace Mexancode\ApiAdmin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeApiAdmin extends Command
{
    protected $signature = 'make:api-admin {model} 
                            {--methods=index,store,show,update,destroy}
                            {--force : Sobrescribir archivos existentes}';

    protected $description = 'Genera una API RESTful completa para un modelo';

    public function handle()
    {
        $modelName = $this->argument('model');
        $methods = explode(',', $this->option('methods'));

        $this->generateController($modelName, $methods);
        $this->generateRequest($modelName);
        $this->generateResource($modelName);
        $this->generateRoutes($modelName);
        $this->generateTest($modelName);

        $this->info("API para {$modelName} generada exitosamente!");
        $this->info("Rutas disponibles en: routes/admin-api/" . Str::kebab(Str::plural($modelName)) . ".php");
    }

    protected function generateController($modelName, $methods)
    {
        $controllerStub = File::get(__DIR__ . '/../../../stubs/controller.stub');

        $replacements = [
            '{{namespace}}' => rtrim(config('api-admin.controllers_namespace'), '\\'),
            '{{modelNamespace}}' => rtrim(config('api-admin.models_namespace'), '\\'),
            '{{model}}' => $modelName,
            '{{modelVariable}}' => Str::camel($modelName),
            '{{methods}}' => $this->generateMethods($modelName, $methods),
            '{{requestClass}}' => "{$modelName}Request",
            '{{resourceClass}}' => "{$modelName}Resource",
        ];

        $controllerContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $controllerStub
        );

        $controllerPath = app_path('Http/Controllers/Api/' . $modelName . 'Controller.php');

        $this->ensureDirectoryExists($controllerPath);
        File::put($controllerPath, $controllerContent);
    }

    protected function generateMethods($modelName, $methods)
    {
        $methodTemplates = [];
        $modelVariable = Str::camel($modelName);

        foreach ($methods as $method) {
            $stubFile = __DIR__ . "/../../../stubs/methods/{$method}.stub";

            if (File::exists($stubFile)) {
                $stub = File::get($stubFile);

                $replacements = [
                    '{{model}}' => $modelName,
                    '{{modelVariable}}' => $modelVariable,
                    '{{requestClass}}' => "{$modelName}Request",
                    '{{resourceClass}}' => "{$modelName}Resource",
                ];

                $methodTemplates[] = str_replace(
                    array_keys($replacements),
                    array_values($replacements),
                    $stub
                );
            }
        }

        return implode("\n\n", $methodTemplates);
    }

    protected function generateRequest($modelName)
    {
        $stub = File::get(__DIR__ . '/../../../stubs/request.stub');

        $replacements = [
            '{{namespace}}' => rtrim(config('api-admin.requests_namespace', 'App\\Http\\Requests'), '\\'),
            '{{class}}' => "{$modelName}Request",
        ];

        $requestContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $requestPath = app_path('Http/Requests/' . $modelName . 'Request.php');

        $this->ensureDirectoryExists($requestPath);
        File::put($requestPath, $requestContent);
    }

    protected function generateResource($modelName)
    {
        $stub = File::get(__DIR__ . '/../../../stubs/resource.stub');

        $replacements = [
            '{{namespace}}' => rtrim(config('api-admin.resources_namespace', 'App\\Http\\Resources'), '\\'),
            '{{class}}' => "{$modelName}Resource",
            '{{model}}' => $modelName,
            '{{modelVariable}}' => Str::camel($modelName),
        ];

        $resourceContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $resourcePath = app_path('Http/Resources/' . $modelName . 'Resource.php');

        $this->ensureDirectoryExists($resourcePath);
        File::put($resourcePath, $resourceContent);
    }

    protected function generateRoutes($modelName)
    {
        $routeName = Str::kebab(Str::plural($modelName));
        $modelNameParam = Str::lower(Str::singular($modelName));
        $controller = config('api-admin.controllers_namespace') . $modelName . 'Controller';

        $routesContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use {$controller};

Route::prefix('{$routeName}')->group(function () {
    Route::get('/', [{$modelName}Controller::class, 'index']);
    Route::post('/', [{$modelName}Controller::class, 'store']);
    Route::get('/{{$modelNameParam}}', [{$modelName}Controller::class, 'show']);
    Route::put('/{{$modelNameParam}}', [{$modelName}Controller::class, 'update']);
    Route::delete('/{{$modelNameParam}}', [{$modelName}Controller::class, 'destroy']);
});
PHP;

        // Crear carpeta admin-api si no existe
        $adminApiDir = base_path('routes/admin-api');
        if (!File::exists($adminApiDir)) {
            File::makeDirectory($adminApiDir, 0755, true);
        }

        // Guardar archivo de rutas en admin-api
        $routesPath = base_path("routes/admin-api/{$routeName}.php");
        File::put($routesPath, $routesContent);

        // Agregar importación en routes/api.php
        $this->addRouteImport($routeName);
    }

    protected function addRouteImport($routeName)
    {
        $apiRoutesPath = base_path('routes/api.php');

        if (!File::exists($apiRoutesPath)) {
            $this->warn("El archivo routes/api.php no existe. Créalo manualmente.");
            return;
        }

        $apiRoutesContent = File::get($apiRoutesPath);
        $importLine = "require __DIR__ . '/admin-api/{$routeName}.php';";

        // Verificar si ya existe la importación
        if (strpos($apiRoutesContent, $importLine) !== false) {
            $this->info("La ruta {$routeName} ya está importada en api.php");
            return;
        }

        // Agregar la importación al final del archivo
        $apiRoutesContent = rtrim($apiRoutesContent) . "\n\n" . $importLine . "\n";
        File::put($apiRoutesPath, $apiRoutesContent);

        $this->info("Ruta importada en routes/api.php");
    }

    protected function generateTest($modelName)
    {
        $stub = File::get(__DIR__ . '/../../../stubs/test.stub');

        $replacements = [
            '{{namespace}}' => 'Tests\\Feature\\Api',
            '{{class}}' => "{$modelName}ApiTest",
            '{{model}}' => $modelName,
            '{{modelNamespace}}' => rtrim(config('api-admin.models_namespace', 'App\\Models'), '\\'),
            '{{modelVariable}}' => Str::camel($modelName),
            '{{modelPlural}}' => Str::lower(Str::plural($modelName)),
            '{{modelSingular}}' => Str::lower($modelName),
            '{{route}}' => '/api/' . Str::kebab(Str::plural($modelName)),
            '{{table}}' => Str::snake(Str::plural($modelName)),
        ];

        $testContent = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        $testPath = base_path("tests/Feature/Api/{$modelName}ApiTest.php");

        $this->ensureDirectoryExists($testPath);
        File::put($testPath, $testContent);
    }

    protected function ensureDirectoryExists($filePath)
    {
        $directory = dirname($filePath);

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }
    }
}