<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAjustesUsuarioRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AjustesUsuarioController extends Controller
{
    public function index()
    {
        return Inertia::render('AjustesUsuario/Index');
    }

    public function update(UpdateAjustesUsuarioRequest $request)
    {
        $user = auth()->user();

        if ($request->filled('nueva_contrasena')) {
            $user->password = Hash::make($request->nueva_contrasena);
        }

        if ($request->hasFile('foto')) {
            if ($user->foto && Storage::disk('public')->exists($user->foto)) {
                Storage::disk('public')->delete($user->foto);
            }
            $user->foto = $request->file('foto')->store('perfiles', 'public');
        }

        $user->save();

        return redirect()->back()->with('success', 'Ajustes de perfil actualizados correctamente.');
    }
}
