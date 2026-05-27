<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMovimientoDashboardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'seccion' => ['required', 'in:tarjetas_unidad,tarjetas_mayor,recuperaciones,chips,recargas,megas,servicios_digitales,banca_digital,servicio_tecnico,efectivo_monedas'],
            'cantidad' => ['nullable', 'integer', 'min:0'],
            'monto' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'seccion.required' => 'La sección del movimiento es obligatoria.',
            'seccion.in' => 'La sección seleccionada no es válida.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'La cantidad no puede ser negativa.',
            'monto.required' => 'El monto es obligatorio.',
            'monto.numeric' => 'El monto debe ser un número.',
            'monto.min' => 'El monto no puede ser negativo.',
        ];
    }
}
