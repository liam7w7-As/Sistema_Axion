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

        return Inertia::render('AperturasCaja/Index', [
            'aperturas' => $aperturas,
            'vendedores' => $vendedores,
            'vendedoresConCaja' => $vendedoresConCaja,
            'vendedoresSinCaja' => $vendedoresSinCaja,
            'operadores' => $operadores,
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
