<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemConfig;
use Carbon\Carbon;

class VerificarHorarioAcceso
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // Si no está autenticado, continúa
        if (!$user) {
            return $next($request);
        }

        // Verificar si la cuenta está deshabilitada
        if ($user->estado === 'deshabilitado') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'codigo' => 'Su cuenta se encuentra inhabilitada. Contacte al administrador.'
            ]);
        }

        // Si tiene permiso para omitir el bloqueo de horario, continúa
        if ($user->omitir_bloqueo_horario) {
            return $next($request);
        }

        $config = SystemConfig::first();
        
        // Si no hay configuración, permitimos el acceso por defecto
        if (!$config) {
            return $next($request);
        }

        $horaActual = Carbon::now()->format('H:i');
        $fueraDeHorario = false;

        // Verificar según el rol
        if ($user->hasRole('administrador') || $user->role === 'admin') {
            if ($config->hora_inicio_admin && $config->hora_fin_admin) {
                // Si la hora actual es menor al inicio O mayor al fin
                if ($horaActual < $config->hora_inicio_admin || $horaActual > $config->hora_fin_admin) {
                    $fueraDeHorario = true;
                }
            }
        } else {
            // Vendedor u otro rol
            if ($config->hora_inicio_vendedor && $config->hora_fin_vendedor) {
                if ($horaActual < $config->hora_inicio_vendedor || $horaActual > $config->hora_fin_vendedor) {
                    $fueraDeHorario = true;
                }
            }
        }

        if ($fueraDeHorario) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'codigo' => 'Acceso denegado: Se encuentra fuera del horario de atención establecido.'
            ]);
        }

        return $next($request);
    }
}
