<?php

namespace App\Http\Controllers;

use App\Models\CashOpening;
use App\Models\User;
use App\Models\SellerDashboard;
use App\Http\Requests\StoreAperturaCajaRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AperturaCajaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $vendedor_id = $request->input('vendedor_id');
        $estado = $request->input('estado');

        $query = CashOpening::with('usuario');

        if ($search) {
            $query->whereHas('usuario', function ($q) use ($search) {
                $q->where('nombre_completo', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        if ($vendedor_id) {
            $query->where('user_id', $vendedor_id);
        }

        if ($estado) {
            $query->where('status', $estado);
        }

        $aperturas = $query->orderBy('fecha_hora_apertura', 'desc')->paginate(15)->withQueryString();
        $vendedores = User::role('vendedor')->habilitados()->get();

        // Obtener vendedores con caja abierta (y su apertura)
        $vendedoresConCaja = User::role('vendedor')->habilitados()
            ->whereHas('aperturasCaja', function ($q) {
                $q->where('status', 'abierta');
            })
            ->with(['aperturasCaja' => function ($q) {
                $q->where('status', 'abierta')->latest('id');
            }])
            ->get();

        // Obtener vendedores sin caja abierta (y su última apertura)
        $vendedoresSinCaja = User::role('vendedor')->habilitados()
            ->whereDoesntHave('aperturasCaja', function ($q) {
                $q->where('status', 'abierta');
            })
            ->with(['aperturasCaja' => function ($q) {
                $q->latest('id');
            }])
            ->get();

        $operadores = \App\Models\ProductService::whereNotNull('operador')
            ->where('operador', '!=', '')
            ->distinct()
            ->pluck('operador');

        $stock_tarjetas_global = [];
        $tarjetas = \App\Models\ProductService::whereIn('categoria', ['tarjetas_unidad', 'tarjetas_mayor'])
            ->get(['operador', 'categoria', 'stock_actual']);
            
        foreach($tarjetas as $t) {
            $key = strtolower($t->operador) . '_' . str_replace('tarjetas_', '', $t->categoria);
            $stock_tarjetas_global[$key] = $t->stock_actual;
        }

        return Inertia::render('AperturasCaja/Index', [
            'aperturas' => $aperturas,
            'vendedores' => $vendedores,
            'vendedoresConCaja' => $vendedoresConCaja,
            'vendedoresSinCaja' => $vendedoresSinCaja,
            'operadores' => $operadores,
            'stock_tarjetas_global' => $stock_tarjetas_global,
            'filtros' => [
                'search' => $search,
                'vendedor_id' => $vendedor_id,
                'estado' => $estado,
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAperturaCajaRequest $request)
    {
        $validated = $request->validated();
        
        $validated['fecha_hora_apertura'] = Carbon::now();
        $validated['status'] = 'abierta';

        // Validar stock de tarjetas físicas asignadas
        if (isset($validated['servicios_asignados_json']) && in_array('tarjetas', $validated['servicios_asignados_json'])) {
            $lote = $validated['lote_tarjetas_json'] ?? [];
            $tarjetas = \App\Models\ProductService::whereIn('categoria', ['tarjetas_unidad', 'tarjetas_mayor'])->get();
            
            $key_map = [
                'tigo_unidad' => ['Tigo', 'tarjetas_unidad'],
                'tigo_mayor' => ['Tigo', 'tarjetas_mayor'],
                'entel_unidad' => ['Entel', 'tarjetas_unidad'],
                'entel_mayor' => ['Entel', 'tarjetas_mayor'],
                'viva_unidad' => ['Viva', 'tarjetas_unidad'],
                'viva_mayor' => ['Viva', 'tarjetas_mayor'],
            ];

            foreach ($key_map as $lote_key => $info) {
                if (!empty($lote[$lote_key]) && $lote[$lote_key] > 0) {
                    $producto = $tarjetas->where('operador', $info[0])->where('categoria', $info[1])->first();
                    $stock_actual = $producto ? $producto->stock_actual : 0;
                    if ($lote[$lote_key] > $stock_actual) {
                        return redirect()->back()->withErrors(['lote_tarjetas_json' => "Stock insuficiente para tarjetas {$info[0]} ({$info[1]}). Disponible: {$stock_actual} und."]);
                    }
                }
            }
        }

        DB::beginTransaction();
        try {
            $apertura = CashOpening::create($validated);

            // Crear el registro inicial en el seller_dashboard (resumen de sumatorias)
            SellerDashboard::create([
                'user_id' => $apertura->user_id,
                'cash_opening_id' => $apertura->id,
            ]);

            DB::commit();

            return redirect()->route('aperturas-caja.index')
                ->with('exito', 'Apertura de caja registrada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al crear apertura de caja: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(CashOpening $aperturas_caja) // la ruta usa este nombre de variable por defecto
    {
        $aperturas_caja->load(['usuario', 'panelVendedor', 'cierreCaja']);
        // Aquí podríamos mostrar el detalle si fuera necesario
        return back()->with('info', 'Función de visualización en desarrollo.');
    }
}
