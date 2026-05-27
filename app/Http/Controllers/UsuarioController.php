<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UsuarioController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with(['roles', 'permissions'])->where('id', '!=', 1); // Excluir al superadmin base si se desea, o quitar esta condición.

        if ($request->filled('buscar')) {
            $query->where(function ($q) use ($request) {
                $q->where('nombre_completo', 'like', '%' . $request->buscar . '%')
                  ->orWhere('codigo', 'like', '%' . $request->buscar . '%');
            });
        }

        if ($request->filled('rol')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->rol);
            });
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        $usuarios = $query->orderBy('nombre_completo', 'asc')->paginate(15)->withQueryString();

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios
        ]);
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        if ($request->hasFile('foto')) {
            $data['foto'] = $request->file('foto')->store('perfiles', 'public');
        }

        // Mapear el 'rol' que viene del form al 'role' que exige la tabla users
        $data['role'] = $data['rol'] === 'administrador' ? 'admin' : 'seller';

        $usuario = User::create($data);
        $usuario->syncRoles([$request->rol]);

        if (isset($data['modulos_habilitados']) && is_array($data['modulos_habilitados'])) {
            $usuario->syncPermissions($data['modulos_habilitados']);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if ($request->hasFile('foto')) {
            if ($usuario->foto && Storage::disk('public')->exists($usuario->foto)) {
                Storage::disk('public')->delete($usuario->foto);
            }
            $data['foto'] = $request->file('foto')->store('perfiles', 'public');
        } else {
            unset($data['foto']); // No sobrescribir con null si no se envía nada
        }

        // Mapear el 'rol' que viene del form al 'role' que exige la tabla users
        $data['role'] = $data['rol'] === 'administrador' ? 'admin' : 'seller';

        $usuario->update($data);
        $usuario->syncRoles([$request->rol]);

        if (isset($data['modulos_habilitados']) && is_array($data['modulos_habilitados'])) {
            $usuario->syncPermissions($data['modulos_habilitados']);
        } else {
            $usuario->syncPermissions([]);
        }

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario)
    {
        // Verificar dependencias (Ventas, Aperturas de Caja, Movimientos)
        $tieneVentas = \App\Models\Sale::where('user_id', $usuario->id)->exists();
        $tieneAperturas = \App\Models\CashOpening::where('user_id', $usuario->id)->exists();
        $tieneMovimientos = \App\Models\SellerMovement::where('user_id', $usuario->id)->exists();

        if ($tieneVentas || $tieneAperturas || $tieneMovimientos) {
            return redirect()->route('usuarios.index')->with('error', 'No se puede eliminar: el usuario tiene registros asociados (ventas, caja o movimientos). Puede cambiar su estado a "inactivo".');
        }

        if ($usuario->foto && Storage::disk('public')->exists($usuario->foto)) {
            Storage::disk('public')->delete($usuario->foto);
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
    }
}
