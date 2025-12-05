<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin API -{{config('app.name')}}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Administrador de APIs</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($apis as $api)
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-xl font-semibold mb-2">{{$api['name']}}</h3>
                <div class="space-y-2">
                    @foreach($api['endpoints'] as $endpoint)
                    <div class="flex items-center space-x-2">
                        <span class="px-2 py-1 text-xs rounded 
                           {{$endpoint['method'] == 'GET' ? 'bg-green-100 text-green-800' : ''}}
                           {{$endpoint['method'] == 'POST' ? 'bg-blue-100 text-blue-800' : ''}}
                           {{$endpoint['method'] == 'PUT' ? 'bg-yellow-100 text-yellow-800' : ''}}
                           {{$endpoint['method'] == 'DELETE' ? 'bg-red-100 text-red-800' : ''}}">
                           {{$endpoint['method']}}
                        </span>
                        <code class="text-sm">{{$endpoint['path']}}</code>
                    </div>
                    @endforeach
                </div>
                <a href="{{$api['docs_url']}}" 
                   class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                    Ver documentación →
                </a>
            </div>
            @endforeach
        </div>
    </div>
</body>
</html>