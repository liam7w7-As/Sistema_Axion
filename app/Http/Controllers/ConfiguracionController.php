<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateConfiguracionRequest;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ConfiguracionController extends Controller
{
    public function index()
    {
        $configuracion = SystemConfig::first() ?? new SystemConfig();
        return Inertia::render('Configuracion/Index', [
            'configuracion' => $configuracion
        ]);
    }

    public function update(UpdateConfiguracionRequest $request)
    {
        $configuracion = SystemConfig::first() ?? new SystemConfig();

        $data = $request->validated();

        if ($request->hasFile('logo')) {
            // Eliminar logo anterior si existe
            if ($configuracion->logo && Storage::disk('public')->exists($configuracion->logo)) {
                Storage::disk('public')->delete($configuracion->logo);
            }
            
            // Guardar nuevo logo
            $path = $request->file('logo')->store('logos', 'public');
            $data['logo'] = $path;
        } else {
            // Si no se envía logo nuevo, mantener el actual (eliminarlo del array de update para no sobreescribir con null)
            unset($data['logo']);
        }

        $configuracion->fill($data);
        $configuracion->save();

        return redirect()->back()->with('success', 'Configuración del sistema actualizada correctamente.');
    }
}
