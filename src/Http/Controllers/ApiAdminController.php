<?php

namespace Mexancode\ApiAdmin\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

class ApiAdminController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    protected $modelClass;
    protected $resourceClass;
    protected $requestClass;

    public function index()
    {
        $items = $this->modelClass::paginate(
            request('per_page', config('api-admin.pagination_limit'))
        );

        return $this->resourceClass::collection($items);
    }

    public function store()
    {
        $validated = app($this->requestClass)->validated();
        $item = $this->modelClass::create($validated);

        return response()->json([
            'message' => 'Registro creado exitosamente',
            'data' => new $this->resourceClass($item)
        ], 201);
    }

    public function show($id)
    {
        $item = $this->modelClass::findOrFail($id);
        return new $this->resourceClass($item);
    }

    public function update($id)
    {
        $item = $this->modelClass::findOrFail($id);
        $validated = app($this->requestClass)->validated();
        $item->update($validated);

        return response()->json([
            'message' => 'Registro actualizado exitosamente',
            'data' => new $this->resourceClass($item)
        ]);
    }

    public function destroy($id)
    {
        $item = $this->modelClass::findOrFail($id);
        $item->delete();

        return response()->json([
            'message' => 'Registro eliminado exitosamente'
        ]);
    }
}