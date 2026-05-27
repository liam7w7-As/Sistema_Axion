<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveCierreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'observacion_aprobacion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'observacion_aprobacion.string' => 'La observación debe ser un texto válido.',
            'observacion_aprobacion.max' => 'La observación no puede exceder 500 caracteres.',
        ];
    }
}
