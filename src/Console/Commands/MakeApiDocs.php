<?php

namespace Mexancode\ApiAdmin\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MakeApiDocs extends Command
{
    protected $signature = 'make:api-docs {model} 
                            {--methods=index,store,show,update,destroy}';

    protected $description = 'Genera vistas de documentación de endpoints para un modelo';

    protected $modelColumns = [];

    public function handle()
    {
        $modelName = $this->argument('model');
        $methods = explode(',', $this->option('methods'));

        // Obtener columnas del modelo
        $this->modelColumns = $this->getModelColumns($modelName);

        $this->generateEndpointView($modelName, $methods);

        $this->info("Vista de documentación para {$modelName} generada exitosamente!");
        $this->info("Accede a: /api-docs/" . Str::kebab(Str::plural($modelName)));
    }

    protected function getModelColumns($modelName)
    {
        try {
            $tableName = Str::snake(Str::plural($modelName));
            
            if (!Schema::hasTable($tableName)) {
                $this->warn("Tabla {$tableName} no encontrada. Usando campos por defecto.");
                return $this->getDefaultColumns();
            }

            $columns = Schema::getColumnListing($tableName);
            $columnDetails = [];

            foreach ($columns as $column) {
                // Excluir columnas de sistema
                if (in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                    continue;
                }

                $type = Schema::getColumnType($tableName, $column);
                $columnDetails[] = [
                    'name' => $column,
                    'type' => $this->mapColumnType($type),
                    'required' => $this->isColumnRequired($tableName, $column)
                ];
            }

            return empty($columnDetails) ? $this->getDefaultColumns() : $columnDetails;
        } catch (\Exception $e) {
            $this->warn("Error al obtener columnas: " . $e->getMessage());
            return $this->getDefaultColumns();
        }
    }

    protected function mapColumnType($dbType)
    {
        $typeMap = [
            'integer' => 'integer',
            'bigint' => 'integer',
            'smallint' => 'integer',
            'string' => 'string',
            'text' => 'string',
            'boolean' => 'boolean',
            'date' => 'date',
            'datetime' => 'datetime',
            'timestamp' => 'datetime',
            'decimal' => 'number',
            'float' => 'number',
            'double' => 'number',
            'json' => 'object',
        ];

        return $typeMap[$dbType] ?? 'string';
    }

    protected function isColumnRequired($tableName, $columnName)
    {
        try {
            $column = DB::select("SHOW COLUMNS FROM {$tableName} WHERE Field = ?", [$columnName]);
            if (!empty($column)) {
                return $column[0]->Null === 'NO' && $column[0]->Default === null;
            }
        } catch (\Exception $e) {
            // Si falla, asumir que no es requerido
        }
        return false;
    }

    protected function getDefaultColumns()
    {
        return [
            ['name' => 'name', 'type' => 'string', 'required' => true],
            ['name' => 'description', 'type' => 'string', 'required' => false],
            ['name' => 'status', 'type' => 'boolean', 'required' => false],
        ];
    }

    protected function generateEndpointView($modelName, $methods)
    {
        $routeName = Str::kebab(Str::plural($modelName));
        $endpoints = $this->buildEndpoints($modelName, $methods);

        $viewContent = $this->buildViewContent($modelName, $routeName, $endpoints);

        // Crear carpeta de vistas si no existe
        $viewsDir = resource_path('views/api-docs');
        if (!File::exists($viewsDir)) {
            File::makeDirectory($viewsDir, 0755, true);
        }

        // Guardar vista
        $viewPath = resource_path("views/api-docs/{$routeName}.blade.php");
        File::put($viewPath, $viewContent);

        // Agregar ruta para la vista
        $this->addDocsRoute($modelName, $routeName);
    }

    protected function buildEndpoints($modelName, $methods)
    {
        $routeName = Str::kebab(Str::plural($modelName));
        $modelParam = Str::lower(Str::singular($modelName));
        $endpoints = [];

        $methodsConfig = [
            'index' => [
                'method' => 'GET',
                'path' => "/{$routeName}",
                'description' => "List all {$modelName} instances",
                'color' => 'blue'
            ],
            'store' => [
                'method' => 'POST',
                'path' => "/{$routeName}",
                'description' => "Create a new {$modelName}",
                'color' => 'green'
            ],
            'show' => [
                'method' => 'GET',
                'path' => "/{$routeName}/{{$modelParam}}",
                'description' => "Get a specific {$modelName}",
                'color' => 'blue'
            ],
            'update' => [
                'method' => 'PUT',
                'path' => "/{$routeName}/{{$modelParam}}",
                'description' => "Update a {$modelName}",
                'color' => 'yellow'
            ],
            'destroy' => [
                'method' => 'DELETE',
                'path' => "/{$routeName}/{{$modelParam}}",
                'description' => "Delete a {$modelName}",
                'color' => 'red'
            ]
        ];

        foreach ($methods as $method) {
            if (isset($methodsConfig[$method])) {
                $endpoints[] = $methodsConfig[$method];
            }
        }

        return $endpoints;
    }

    protected function buildViewContent($modelName, $routeName, $endpoints)
    {
        $endpointsHtml = '';
        
        foreach ($endpoints as $endpoint) {
            $endpointsHtml .= $this->buildEndpointCard($endpoint, $modelName);
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$modelName} API - Endpoints</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">Endpoints</h1>
            <p class="text-gray-600">Manage your API endpoints</p>
        </div>

        <div class="space-y-4">
{$endpointsHtml}
        </div>
    </div>

    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                alert('Ruta copiada al portapapeles: ' + text);
            }).catch(err => {
                console.error('Error al copiar:', err);
            });
        }

        function toggleDetails(element) {
            const details = element.nextElementSibling;
            const chevron = element.querySelector('.chevron');
            
            if (details.classList.contains('hidden')) {
                details.classList.remove('hidden');
                chevron.style.transform = 'rotate(180deg)';
            } else {
                details.classList.add('hidden');
                chevron.style.transform = 'rotate(0deg)';
            }
        }
    </script>
</body>
</html>
HTML;
    }

    protected function buildEndpointCard($endpoint, $modelName)
    {
        $method = $endpoint['method'];
        $path = $endpoint['path'];
        $description = $endpoint['description'];
        
        $colorClasses = [
            'GET' => 'bg-blue-100 text-blue-800',
            'POST' => 'bg-green-100 text-green-800',
            'PUT' => 'bg-yellow-100 text-yellow-800',
            'DELETE' => 'bg-red-100 text-red-800'
        ];

        $bgColor = $colorClasses[$method] ?? 'bg-gray-100 text-gray-800';
        
        // Generar detalles según el método
        $details = $this->buildEndpointDetails($method, $modelName);

        return <<<HTML
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 hover:shadow-md transition-shadow">
                <div class="p-6 cursor-pointer" onclick="toggleDetails(this)">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-2">
                                <span class="px-3 py-1 text-sm font-semibold rounded {$bgColor}">
                                    {$method}
                                </span>
                                <code class="text-gray-700 font-mono text-sm">{$path}</code>
                                <button onclick="event.stopPropagation(); copyToClipboard('{$path}')" class="text-gray-400 hover:text-gray-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-gray-600 text-sm">{$description}</p>
                            <p class="text-blue-600 text-sm mt-1">Model: {$modelName}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400 transform transition-transform chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            <button onclick="event.stopPropagation();" class="text-red-500 hover:text-red-700">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="details hidden border-t border-gray-200 p-6 bg-gray-50">
{$details}
                </div>
            </div>

HTML;
    }

    protected function buildEndpointDetails($method, $modelName)
    {
        $modelVar = Str::camel($modelName);
        $modelLower = Str::lower($modelName);
        
        switch ($method) {
            case 'GET':
                return $this->buildGetDetails($modelName, $modelVar);
            case 'POST':
                return $this->buildPostDetails($modelName, $modelVar);
            case 'PUT':
                return $this->buildPutDetails($modelName, $modelVar, $modelLower);
            case 'DELETE':
                return $this->buildDeleteDetails($modelName, $modelLower);
            default:
                return '';
        }
    }


    protected function buildFieldsList()
    {
        $html = '';
        foreach ($this->modelColumns as $column) {
            $badge = $column['required'] 
                ? '<span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded">required</span>'
                : '<span class="px-2 py-0.5 bg-gray-100 text-gray-800 text-xs rounded">optional</span>';
            
            $html .= <<<HTML
                                <div class="flex items-center gap-2 text-sm">
                                    <code class="bg-white px-2 py-1 rounded">{$column['name']}</code>
                                    <span class="text-gray-600">{$column['type']}</span>
                                    {$badge}
                                </div>

HTML;
        }
        return $html;
    }

    protected function buildExampleRequest()
    {
        $fields = [];
        foreach ($this->modelColumns as $column) {
            $fields[$column['name']] = $this->getExampleValue($column['type'], $column['name']);
        }
        return json_encode($fields, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function buildExampleResponse($modelName)
    {
        $fields = ['id' => 1];
        foreach ($this->modelColumns as $column) {
            $fields[$column['name']] = $this->getExampleValue($column['type'], $column['name']);
        }
        $fields['created_at'] = '2024-01-01T00:00:00.000000Z';
        $fields['updated_at'] = '2024-01-01T00:00:00.000000Z';
        
        $response = [
            'data' => $fields,
            'message' => "{$modelName} created successfully"
        ];
        
        return json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    protected function getExampleValue($type, $fieldName)
    {
        // Valores específicos por nombre de campo
        $nameExamples = [
            'email' => 'user@example.com',
            'phone' => '+1234567890',
            'url' => 'https://example.com',
            'price' => 99.99,
            'quantity' => 10,
            'age' => 25,
        ];

        if (isset($nameExamples[$fieldName])) {
            return $nameExamples[$fieldName];
        }

        // Valores por tipo
        switch ($type) {
            case 'string':
                return Str::contains($fieldName, 'name') ? 'Example Name' : 'Example ' . Str::title($fieldName);
            case 'integer':
                return 1;
            case 'number':
                return 99.99;
            case 'boolean':
                return true;
            case 'date':
                return '2024-01-01';
            case 'datetime':
                return '2024-01-01T00:00:00.000000Z';
            case 'object':
                return ['key' => 'value'];
            default:
                return 'example value';
        }
    }

    protected function buildGetDetails($modelName, $modelVar)
    {
        $plural = Str::plural($modelVar);
        $exampleFields = ['id' => 1];
        foreach ($this->modelColumns as $column) {
            $exampleFields[$column['name']] = $this->getExampleValue($column['type'], $column['name']);
        }
        $exampleFields['created_at'] = '2024-01-01T00:00:00.000000Z';
        $exampleFields['updated_at'] = '2024-01-01T00:00:00.000000Z';
        
        $exampleJson = json_encode([
            'data' => [$exampleFields],
            'meta' => [
                'current_page' => 1,
                'total' => 1
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<HTML
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Response Example</h4>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{$exampleJson}</code></pre>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Query Parameters (Optional)</h4>
                            <div class="space-y-2">
                                <div class="flex items-center gap-2 text-sm">
                                    <code class="bg-white px-2 py-1 rounded">page</code>
                                    <span class="text-gray-600">integer</span>
                                    <span class="text-gray-500">- Número de página</span>
                                </div>
                                <div class="flex items-center gap-2 text-sm">
                                    <code class="bg-white px-2 py-1 rounded">per_page</code>
                                    <span class="text-gray-600">integer</span>
                                    <span class="text-gray-500">- Elementos por página</span>
                                </div>
                            </div>
                        </div>
                    </div>
HTML;
    }

    protected function buildPostDetails($modelName, $modelVar)
    {
        $fieldsHtml = $this->buildFieldsList();
        $exampleRequest = $this->buildExampleRequest();
        $exampleResponse = $this->buildExampleResponse($modelName);
        
        return <<<HTML
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Request Body</h4>
                            <div class="space-y-2 mb-4">
{$fieldsHtml}
                            </div>
                            
                            <h5 class="font-medium text-gray-700 mb-2">Example Request</h5>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{$exampleRequest}</code></pre>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Response Example (201 Created)</h4>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{$exampleResponse}</code></pre>
                        </div>
                    </div>
HTML;
    }

    protected function buildPutDetails($modelName, $modelVar, $modelLower)
    {
        $fieldsHtml = $this->buildFieldsList();
        $exampleRequest = $this->buildExampleRequest();
        
        $exampleFields = ['id' => 1];
        foreach ($this->modelColumns as $column) {
            $exampleFields[$column['name']] = $this->getExampleValue($column['type'], $column['name']);
        }
        $exampleFields['created_at'] = '2024-01-01T00:00:00.000000Z';
        $exampleFields['updated_at'] = '2024-01-01T12:00:00.000000Z';
        
        $exampleResponse = json_encode([
            'data' => $exampleFields,
            'message' => "{$modelName} updated successfully"
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        return <<<HTML
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">URL Parameters</h4>
                            <div class="flex items-center gap-2 text-sm mb-4">
                                <code class="bg-white px-2 py-1 rounded">{$modelLower}</code>
                                <span class="text-gray-600">integer</span>
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded">required</span>
                                <span class="text-gray-500">- ID del {$modelName}</span>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Request Body</h4>
                            <div class="space-y-2 mb-4">
{$fieldsHtml}
                            </div>
                            
                            <h5 class="font-medium text-gray-700 mb-2">Example Request</h5>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{$exampleRequest}</code></pre>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Response Example (200 OK)</h4>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{$exampleResponse}</code></pre>
                        </div>
                    </div>
HTML;
    }

    protected function buildDeleteDetails($modelName, $modelLower)
    {
        return <<<HTML
                    <div class="space-y-4">
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">URL Parameters</h4>
                            <div class="flex items-center gap-2 text-sm mb-4">
                                <code class="bg-white px-2 py-1 rounded">{$modelLower}</code>
                                <span class="text-gray-600">integer</span>
                                <span class="px-2 py-0.5 bg-red-100 text-red-800 text-xs rounded">required</span>
                                <span class="text-gray-500">- ID del {$modelName}</span>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Response Example (200 OK)</h4>
                            <pre class="bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "message": "{$modelName} deleted successfully"
}</code></pre>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-gray-900 mb-2">Error Response (404 Not Found)</h4>
                            <pre class="bg-gray-900 text-red-400 p-4 rounded-lg overflow-x-auto text-sm"><code>{
  "message": "{$modelName} not found"
}</code></pre>
                        </div>
                    </div>
HTML;
    }

    protected function addDocsRoute($modelName, $routeName)
    {
        $routesPath = base_path('routes/web.php');

        if (!File::exists($routesPath)) {
            $this->warn("El archivo routes/web.php no existe.");
            return;
        }

        $routeLine = "Route::get('/api-docs/{$routeName}', function () { return view('api-docs.{$routeName}'); });";

        $routesContent = File::get($routesPath);

        if (strpos($routesContent, $routeLine) !== false) {
            $this->info("La ruta de documentación ya existe en web.php");
            return;
        }

        $routesContent = rtrim($routesContent) . "\n\n" . $routeLine . "\n";
        File::put($routesPath, $routesContent);

        $this->info("Ruta de documentación agregada a routes/web.php");
    }
}
