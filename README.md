# Laravel API Admin

Paquete Laravel para generar APIs RESTful completas con documentación interactiva automática.

## Características

- ✅ Generación automática de Controllers, Requests, Resources y Tests
- ✅ Documentación interactiva de endpoints con ejemplos
- ✅ Detección automática de columnas y tipos desde la base de datos
- ✅ Índice centralizado de todas tus APIs
- ✅ Ejemplos de Request/Response basados en tu estructura real
- ✅ Indicadores de campos requeridos/opcionales
- ✅ Interfaz moderna con Tailwind CSS

## Instalación

```bash
composer require mexancode/api-admin
```

Publicar configuración:

```bash
php artisan vendor:publish --tag=api-admin-config
```

## Uso

### 1. Generar API Completa

```bash
php artisan make:api-admin {Model}
```

Esto genera:
- Controller en `app/Http/Controllers/Api/{Model}Controller.php`
- Request en `app/Http/Requests/{Model}Request.php`
- Resource en `app/Http/Resources/{Model}Resource.php`
- Rutas en `routes/admin-api/{model-plural}.php`
- Tests en `tests/Feature/Api/{Model}ApiTest.php`

**Opciones:**

```bash
# Generar solo métodos específicos
php artisan make:api-admin Product --methods=index,store,show

# Sobrescribir archivos existentes
php artisan make:api-admin Product --force
```

### 2. Generar Documentación

```bash
php artisan make:api-docs {Model}
```

Esto genera:
- Vista de documentación en `resources/views/api-docs/{model-plural}.blade.php`
- Registra la API en el índice central
- Agrega rutas en `routes/web.php`

**Opciones:**

```bash
# Documentar solo métodos específicos
php artisan make:api-docs Product --methods=index,store,show
```

### 3. Acceder a la Documentación

**Índice de todas las APIs:**
```
http://tu-app.test/api-docs
```

**Documentación específica:**
```
http://tu-app.test/api-docs/products
http://tu-app.test/api-docs/users
http://tu-app.test/api-docs/tasks
```

## Ejemplo Completo

```bash
# 1. Crear migración y modelo
php artisan make:model Task -m

# 2. Definir estructura en la migración
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('description')->nullable();
    $table->boolean('completed')->default(false);
    $table->timestamps();
});

# 3. Ejecutar migración
php artisan migrate

# 4. Generar API completa
php artisan make:api-admin Task

# 5. Generar documentación
php artisan make:api-docs Task

# 6. Acceder a la documentación
# http://localhost/api-docs/tasks
```

## Documentación Generada

La documentación incluye para cada endpoint:

### GET (Index/Show)
- Ejemplo de respuesta con paginación
- Parámetros de query opcionales
- Estructura de datos completa

### POST (Store)
- Lista de campos con tipos de datos
- Indicadores de required/optional
- Ejemplo de request JSON
- Ejemplo de response (201 Created)

### PUT (Update)
- Parámetros de URL requeridos
- Lista de campos actualizables
- Ejemplo de request JSON
- Ejemplo de response (200 OK)

### DELETE (Destroy)
- Parámetros de URL requeridos
- Ejemplo de respuesta exitosa
- Ejemplo de respuesta de error (404)

## Características de la Documentación

- **Detección automática de columnas**: Lee la estructura real de tu tabla
- **Tipos de datos precisos**: Mapea correctamente string, integer, boolean, date, etc.
- **Campos requeridos**: Detecta automáticamente según la configuración de la BD
- **Valores de ejemplo inteligentes**: Genera ejemplos apropiados según el tipo y nombre del campo
- **Interfaz expandible**: Click para ver/ocultar detalles de cada endpoint
- **Copiar al portapapeles**: Botón para copiar rutas fácilmente
- **Navegación fluida**: Volver al índice desde cualquier vista

## Configuración

Edita `config/api-admin.php`:

```php
return [
    'controllers_namespace' => 'App\\Http\\Controllers\\Api\\',
    'models_namespace' => 'App\\Models\\',
    'requests_namespace' => 'App\\Http\\Requests\\',
    'resources_namespace' => 'App\\Http\\Resources\\',
];
```

## Métodos Disponibles

- `index` - Listar todos los recursos (GET)
- `store` - Crear nuevo recurso (POST)
- `show` - Mostrar un recurso específico (GET)
- `update` - Actualizar un recurso (PUT)
- `destroy` - Eliminar un recurso (DELETE)

## Rutas Generadas

Para el modelo `Product`:

```
GET    /api/products          - Listar productos
POST   /api/products          - Crear producto
GET    /api/products/{id}     - Ver producto
PUT    /api/products/{id}     - Actualizar producto
DELETE /api/products/{id}     - Eliminar producto
```

## Actualizar Documentación

Si modificas tu estructura de base de datos, simplemente vuelve a ejecutar:

```bash
php artisan make:api-docs {Model}
```

La documentación se actualizará automáticamente con los nuevos campos y tipos.

## Requisitos

- PHP >= 8.0
- Laravel >= 9.0
- MySQL/MariaDB (para detección automática de columnas)

## Licencia

MIT

## Autor

Mexancode
