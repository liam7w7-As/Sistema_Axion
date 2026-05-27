<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\ProductService;
use App\Http\Requests\StoreInventarioRequest;
use App\Http\Requests\UpdateInventarioRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InventarioController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $tipo_inventario = $request->input('tipo_inventario', 'fisico'); // Fisico por defecto

        $query = Inventory::with('productoServicio')
            ->where('tipo_inventario', $tipo_inventario);

        if ($search) {
            $query->whereHas('productoServicio', function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%");
            });
        }

        $movimientos = $query->orderBy('fecha_hora', 'desc')->paginate(15)->withQueryString();
        
        $productos = ProductService::activos()->where('tipo', 'producto')->orderBy('nombre')->get();

        return Inertia::render('Inventario/Index', [
            'movimientos' => $movimientos,
            'productos' => $productos,
            'filtros' => [
                'search' => $search,
                'tipo_inventario' => $tipo_inventario,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInventarioRequest $request)
    {
        $validated = $request->validated();
        $validated['fecha_hora'] = Carbon::now();

        DB::beginTransaction();
        try {
            $producto = ProductService::findOrFail($validated['product_service_id']);
            
            $stockAntiguo = $producto->stock_actual;
            $precioAntiguo = $producto->precio_compra;
            
            $cantidad = $validated['cantidad_ingreso'];
            $precioNuevo = $validated['precio_compra'];
            
            if ($validated['tipo_movimiento'] === 'ingreso') {
                // Cálculo de Precio Promedio Ponderado (PPP)
                // PPP = ((stock_actual_antiguo * precio_actual) + (cantidad_ingreso * precio_compra_nuevo)) / (stock_actual_antiguo + cantidad_ingreso)
                $nuevoStock = $stockAntiguo + $cantidad;
                $ppp = 0;
                
                if ($nuevoStock > 0) {
                    $ppp = (($stockAntiguo * $precioAntiguo) + ($cantidad * $precioNuevo)) / $nuevoStock;
                }
                
                $validated['stock_actual'] = $nuevoStock;
                $validated['stock_previsto'] = $nuevoStock;
                
                // Actualizar el producto con el nuevo stock y PPP
                $producto->stock_actual = $nuevoStock;
                $producto->precio_compra = $ppp;
                $producto->save();
                
            } elseif ($validated['tipo_movimiento'] === 'egreso') {
                if ($stockAntiguo < $cantidad) {
                    return redirect()->back()->with('error', 'No hay stock suficiente para realizar el egreso.');
                }
                
                $nuevoStock = $stockAntiguo - $cantidad;
                
                $validated['stock_actual'] = $nuevoStock;
                $validated['stock_previsto'] = $nuevoStock;
                
                $producto->stock_actual = $nuevoStock;
                $producto->save();
                
            } else {
                // Ajuste: La cantidad es el nuevo stock real
                // Por ejemplo si había 10, y el ajuste dice 8, el stock actual pasa a 8.
                $validated['stock_actual'] = $cantidad;
                $validated['stock_previsto'] = $cantidad;
                
                $producto->stock_actual = $cantidad;
                $producto->save();
            }

            Inventory::create($validated);

            DB::commit();

            return redirect()->route('inventario.index')
                ->with('exito', 'Movimiento de inventario registrado correctamente.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al registrar el inventario: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInventarioRequest $request, Inventory $inventario)
    {
        // En un sistema estricto, los movimientos de inventario no deberían editarse.
        // Solo anularse mediante un ajuste. Pero lo proveemos por si acaso (solo para campos menores).
        $validated = $request->only(['observacion']);
        
        $inventario->update($validated);

        return redirect()->route('inventario.index')
            ->with('exito', 'Observación de inventario actualizada.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventario)
    {
        // No permitir eliminar un movimiento, se debe hacer un ajuste.
        // Según ERS, mantener integridad. Hacemos soft delete si es estrictamente necesario,
        // pero preferimos ajuste en la vida real. Aquí aplicamos soft delete.
        $inventario->delete();

        return redirect()->route('inventario.index')
            ->with('exito', 'Registro de inventario eliminado correctamente (Soft Delete).');
    }
}
