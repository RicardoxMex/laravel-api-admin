# Generador de Vistas de Documentación de API

## Uso del Comando

Para generar las vistas de documentación de endpoints para un modelo:

```bash
php artisan make:api-docs {Model}
```

### Ejemplos

```bash
# Generar documentación para el modelo User con todos los métodos
php artisan make:api-docs User

# Generar documentación solo para métodos específicos
php artisan make:api-docs Product --methods=index,show,store

# Generar documentación para el modelo Post
php artisan make:api-docs Post --methods=index,store,show,update,destroy
```

## Opciones Disponibles

- `--methods`: Especifica qué métodos incluir (por defecto: index,store,show,update,destroy)

## Resultado

El comando generará:

1. **Vista Blade**: `resources/views/api-docs/{model-plural}.blade.php`
2. **Ruta Web**: Agregará automáticamente la ruta en `routes/web.php`

## Acceso a la Documentación

Después de ejecutar el comando, podrás acceder a la documentación en:

```
http://tu-app.test/api-docs/{model-plural}
```

Por ejemplo:
- `/api-docs/users` - Para el modelo User
- `/api-docs/products` - Para el modelo Product
- `/api-docs/posts` - Para el modelo Post

## Características de la Vista

La vista generada incluye:

- ✅ Lista de todos los endpoints del modelo
- ✅ Método HTTP con colores distintivos (GET, POST, PUT, DELETE)
- ✅ Ruta del endpoint
- ✅ Descripción de cada endpoint
- ✅ Botón para copiar la ruta al portapapeles
- ✅ Botón de eliminar (visual)
- ✅ Diseño responsive con Tailwind CSS

## Flujo de Trabajo Recomendado

1. Primero genera tu API completa:
   ```bash
   php artisan make:api-admin Product
   ```

2. Luego genera la documentación visual:
   ```bash
   php artisan make:api-docs Product
   ```

3. Accede a la documentación:
   ```
   http://localhost/api-docs/products
   ```

## Personalización

Puedes editar las vistas generadas en `resources/views/api-docs/` para personalizar:
- Estilos
- Información adicional
- Funcionalidad de los botones
- Agregar formularios de prueba
