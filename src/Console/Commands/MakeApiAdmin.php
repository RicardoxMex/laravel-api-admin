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
        $this->info("Rutas disponibles en: routes/api-{$modelName}.php");
    }

    protected function generateController($modelName, $methods)
    {
        $controllerStub = File::get(__DIR__ . '/../../../stubs/controller.stub');
        
        $replacements = [
            '{{namespace}}' => config('api-admin.controllers_namespace'),
            '{{modelNamespace}}' => config('api-admin.models_namespace'),
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
            '{{namespace}}' => config('api-admin.requests_namespace', 'App\\Http\\Requests'),
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
            '{{namespace}}' => config('api-admin.resources_namespace', 'App\\Http\\Resources'),
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
        $controller = config('api-admin.controllers_namespace') . $modelName . 'Controller';
        
        $routesContent = <<<PHP
<?php

use Illuminate\Support\Facades\Route;
use {$controller};

Route::prefix('{$routeName}')->group(function () {
    Route::get('/', [{$modelName}Controller::class, 'index']);
    Route::post('/', [{$modelName}Controller::class, 'store']);
    Route::get('/{id}', [{$modelName}Controller::class, 'show']);
    Route::put('/{id}', [{$modelName}Controller::class, 'update']);
    Route::delete('/{id}', [{$modelName}Controller::class, 'destroy']);
});
PHP;

        $routesPath = base_path("routes/api-{$routeName}.php");
        File::put($routesPath, $routesContent);
    }

    protected function generateTest($modelName)
    {
        $stub = File::get(__DIR__ . '/../../../stubs/test.stub');
        
        $replacements = [
            '{{namespace}}' => 'Tests\\Feature\\Api',
            '{{class}}' => "{$modelName}ApiTest",
            '{{model}}' => $modelName,
            '{{modelVariable}}' => Str::camel($modelName),
            '{{routeName}}' => Str::kebab(Str::plural($modelName)),
            
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