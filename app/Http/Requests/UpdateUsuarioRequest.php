<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar_usuarios');
    }

    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
            'codigo' => ['required', 'string', 'max:50', 'unique:users,codigo,' . $this->route('usuario')->id],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['required', 'string', 'in:administrador,vendedor'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'estado' => ['required', 'string', 'in:habilitado,deshabilitado'],
            'servicios_asignados_json' => ['nullable', 'array'],
            'visualizar_ganancias' => ['boolean'],
            'omitir_bloqueo_horario' => ['boolean'],
            'modulos_habilitados' => ['nullable', 'array'],
            'modulos_habilitados.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'codigo.required' => 'El código de acceso es obligatorio.',
            'codigo.unique' => 'Este código ya está en uso por otro usuario.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'rol.required' => 'Debe seleccionar un rol válido.',
            'foto.image' => 'La foto de perfil debe ser una imagen válida.',
            'foto.max' => 'La foto no debe pesar más de 2MB.',
            'estado.required' => 'El estado es obligatorio.',
        ];
    }
}
