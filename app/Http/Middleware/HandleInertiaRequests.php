<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'nombre_completo' => $request->user()->nombre_completo,
                    'codigo' => $request->user()->codigo,
                    'foto' => $request->user()->foto,
                    'roles' => $request->user()->getRoleNames(),
                    'permisos' => $request->user()->getAllPermissions()->pluck('name'),
                    'visualizar_ganancias' => $request->user()->visualizar_ganancias,
                    'omitir_bloqueo_horario' => $request->user()->omitir_bloqueo_horario,
                ] : null,
                'apertura_activa' => $request->user() ? \App\Models\CashOpening::where('user_id', $request->user()->id)->where('status', 'abierta')->with('cierreCaja')->first() : null,
            ],
            'configuracion_sistema' => \App\Models\SystemConfig::first() ?? [
                'alias' => 'SISTEF',
                'logo' => null,
                'moneda' => 'Bs'
            ],
            'flash' => [
                'exito' => fn () => $request->session()->get('exito') ?? $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'filtros_globales' => $request->user() ? [
                'vendedores' => \App\Models\User::role('vendedor')->get(['id', 'nombre_completo']),
                'productos' => \App\Models\ProductService::activos()->orderBy('nombre')->get(['id', 'nombre', 'tipo']),
                'operadores' => ['Entel', 'Viva', 'Tigo', 'Otro'],
            ] : null,
        ];
    }
}
