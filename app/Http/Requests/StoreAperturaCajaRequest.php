<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\CashOpening;

class StoreAperturaCajaRequest extends FormRequest
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
            'user_id' => ['required', 'exists:users,id'],
            'saldo_inicial' => ['required', 'numeric', 'min:0'],
            'limite_venta' => ['nullable', 'numeric', 'min:0'],
            'servicios_asignados_json' => ['nullable', 'array'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Debe seleccionar un vendedor.',
            'user_id.exists' => 'El vendedor seleccionado no existe.',
            'saldo_inicial.required' => 'El saldo inicial es obligatorio.',
            'saldo_inicial.numeric' => 'El saldo inicial debe ser un número.',
            'saldo_inicial.min' => 'El saldo inicial no puede ser negativo.',
            'limite_venta.numeric' => 'El límite de venta debe ser un número.',
            'limite_venta.min' => 'El límite de venta no puede ser negativo.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($this->user_id) {
                    $aperturaActiva = CashOpening::where('user_id', $this->user_id)
                        ->where('status', 'abierta')
                        ->exists();

                    if ($aperturaActiva) {
                        $validator->errors()->add(
                            'user_id',
                            'El vendedor ya tiene una apertura de caja activa (abierta).'
                        );
                    }
                }
            }
        ];
    }
}
