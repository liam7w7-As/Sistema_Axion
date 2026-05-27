<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;

class UpdateAjustesUsuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'contrasena_actual' => [
                'required_with:nueva_contrasena', 
                function ($attribute, $value, $fail) {
                    if ($this->filled('nueva_contrasena') && !Hash::check($value, auth()->user()->password)) {
                        $fail('La contraseña actual no es correcta.');
                    }
                }
            ],
            'nueva_contrasena' => ['nullable', 'string', 'min:8', 'confirmed'],
            'foto' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'contrasena_actual.required_with' => 'Debe ingresar su contraseña actual para establecer una nueva.',
            'nueva_contrasena.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'nueva_contrasena.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'foto.image' => 'La foto de perfil debe ser una imagen válida.',
            'foto.max' => 'La foto no debe pesar más de 2MB.',
        ];
    }
}
