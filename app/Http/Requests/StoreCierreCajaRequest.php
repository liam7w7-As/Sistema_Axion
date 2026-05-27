<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\CashOpening;
use App\Models\CashClosure;
use Illuminate\Support\Facades\Auth;

class StoreCierreCajaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_opening_id' => ['required', 'exists:cash_openings,id'],
            'saldo_entregado' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'cash_opening_id.required' => 'La apertura de caja es obligatoria.',
            'cash_opening_id.exists' => 'La apertura de caja seleccionada no existe.',
            'saldo_entregado.required' => 'El saldo entregado es obligatorio.',
            'saldo_entregado.numeric' => 'El saldo entregado debe ser un valor numérico.',
            'saldo_entregado.min' => 'El saldo entregado no puede ser negativo.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->cash_opening_id) return;

                $apertura = CashOpening::find($this->cash_opening_id);
                $user = Auth::user();

                if (!$apertura || $apertura->status !== 'abierta') {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'No existe una apertura de caja abierta para realizar el cierre.'
                    );
                    return;
                }

                if (!$user->hasRole('administrador') && $apertura->user_id !== $user->id) {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'Esta apertura de caja pertenece a otro vendedor.'
                    );
                    return;
                }

                $cierrePrevio = CashClosure::where('cash_opening_id', $apertura->id)->exists();
                if ($cierrePrevio) {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'Esta apertura ya tiene un cierre de caja registrado.'
                    );
                }
            }
        ];
    }
}
