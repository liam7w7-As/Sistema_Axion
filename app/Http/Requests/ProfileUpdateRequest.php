<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request de actualización de perfil.
 * Valida nombre_completo (el código no se puede cambiar desde perfil).
 */
class ProfileUpdateRequest extends FormRequest
{
    /**
     * Reglas de validación para actualización de perfil.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre_completo' => ['required', 'string', 'max:255'],
        ];
    }
}
