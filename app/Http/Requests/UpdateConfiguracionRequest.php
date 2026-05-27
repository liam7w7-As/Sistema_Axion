<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('gestionar_configuracion');
    }

    public function rules(): array
    {
        return [
            'nombre_sistema' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'actividad' => ['nullable', 'string', 'max:255'],
            'moneda' => ['required', 'string', 'max:50'],
            'hora_inicio_admin' => ['required', 'string'],
            'hora_fin_admin' => ['required', 'string'],
            'hora_inicio_vendedor' => ['required', 'string'],
            'hora_fin_vendedor' => ['required', 'string'],
            'formato_impresion' => ['nullable', 'string', 'max:50'],
            'tamano_papel_thermal' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre_sistema.required' => 'El nombre del sistema es obligatorio.',
            'alias.required' => 'El alias/nombre corto es obligatorio.',
            'logo.image' => 'El logo debe ser una imagen válida (jpeg, png, jpg).',
            'logo.max' => 'El logo no debe pesar más de 2MB.',
            'moneda.required' => 'La moneda es obligatoria.',
            'hora_inicio_admin.required' => 'La hora de inicio para el administrador es obligatoria.',
            'hora_fin_admin.required' => 'La hora de fin para el administrador es obligatoria.',
            'hora_inicio_vendedor.required' => 'La hora de inicio para el vendedor es obligatoria.',
            'hora_fin_vendedor.required' => 'La hora de fin para el vendedor es obligatoria.',
        ];
    }
}
