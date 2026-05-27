<?php

namespace App\Http\Controllers;

use App\Models\ProductService;
use App\Http\Requests\StoreProductoServicioRequest;
use App\Http\Requests\UpdateProductoServicioRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ProductoServicioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Filtro de búsqueda
        $search = $request->input('search');
        $tipo = $request->input('tipo');
        $operador = $request->input('operador');
        $estado = $request->input('estado');

        $query = ProductService::query();

        // Si es vendedor, solo ver activos. El admin ve todo.
        if ($user->hasRole('vendedor')) {
            $query->activos();
        } elseif ($estado) {
            $query->where('estado', $estado);
        }

        if ($search) {
            $query->where('nombre', 'like', "%{$search}%");
        }

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        if ($operador) {
            $query->where('operador', $operador);
        }

        $productos = $query->orderBy('nombre')->get();

        return Inertia::render('ProductosServicios/Index', [
            'productos' => $productos,
            'filtros' => [
                'search' => $search,
                'tipo' => $tipo,
                'operador' => $operador,
                'estado' => $estado,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductoServicioRequest $request)
    {
        $validated = $request->validated();

        $validated = $this->prepararDatosCalculados($validated);

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $validated['imagen'] = $path;
        }

        ProductService::create($validated);

        return redirect()->route('productos-servicios.index')
            ->with('exito', 'Producto/Servicio creado correctamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductoServicioRequest $request, ProductService $productoServicio)
    {
        $validated = $request->validated();

        $validated = $this->prepararDatosCalculados($validated);

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe
            if ($productoServicio->imagen && Storage::disk('public')->exists($productoServicio->imagen)) {
                Storage::disk('public')->delete($productoServicio->imagen);
            }
            $path = $request->file('imagen')->store('productos', 'public');
            $validated['imagen'] = $path;
        }

        $productoServicio->update($validated);

        return redirect()->route('productos-servicios.index')
            ->with('exito', 'Producto/Servicio actualizado correctamente.');
    }

    /**
     * Prepara y calcula la ganancia/comisión según el tipo.
     */
    private function prepararDatosCalculados(array $data)
    {
        // Sincronizar seccion_reporte con la categoría
        $data['seccion_reporte'] = $data['categoria'];

        if ($data['tipo'] === 'producto') {
            $data['precio_compra'] = $data['precio_compra'] ?? 0;
            $data['ganancia'] = max(0, $data['precio_venta'] - $data['precio_compra']);
            $data['tipo_ganancia'] = 'fija';
            $data['comision'] = 0;
        } else {
            // Servicio
            $tipo_ganancia = $data['tipo_ganancia'] ?? 'fija';
            $comision = floatval($data['comision'] ?? 0);
            $precio_venta = floatval($data['precio_venta'] ?? 0);

            if ($tipo_ganancia === 'porcentaje') {
                $ganancia = $precio_venta * ($comision / 100);
            } elseif ($tipo_ganancia === 'fija') {
                $ganancia = $comision;
            } else { // ninguna
                $ganancia = 0;
                $comision = 0;
            }

            $data['ganancia'] = max(0, $ganancia);
            // El costo real para nosotros es el precio de venta menos lo que ganamos
            $data['precio_compra'] = max(0, $precio_venta - $data['ganancia']);
        }

        return $data;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductService $productoServicio)
    {
        // Verificar dependencias antes de eliminar
        if ($productoServicio->saleItems()->exists() || $productoServicio->inventarios()->exists()) {
            return redirect()->back()->with('error', 'No se puede eliminar: existen ventas o inventario asociado.');
        }

        if ($productoServicio->imagen && Storage::disk('public')->exists($productoServicio->imagen)) {
             Storage::disk('public')->delete($productoServicio->imagen);
        }

        $productoServicio->delete();

        return redirect()->route('productos-servicios.index')
            ->with('eliminado', 'Producto/Servicio eliminado correctamente.');
    }
}
