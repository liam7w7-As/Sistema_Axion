<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use App\Models\CashOpening;
use App\Models\CashClosure;
use Illuminate\Support\Facades\Auth;

class StoreVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cash_opening_id' => ['required', 'exists:cash_openings,id'],
            'tipo_pago' => ['required', 'in:efectivo,transferencia,qr,mixto'],
            'observacion' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_service_id' => ['required', 'exists:products_services,id'],
            'items.*.cantidad' => ['required', 'integer', 'min:1'],
            'items.*.precio_venta' => ['required', 'numeric', 'min:0'],
            'cliente_nombre' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'cash_opening_id.required' => 'Se requiere una apertura de caja activa.',
            'cash_opening_id.exists' => 'La apertura de caja seleccionada no existe.',
            'tipo_pago.required' => 'El tipo de pago es obligatorio.',
            'tipo_pago.in' => 'El tipo de pago seleccionado no es válido.',
            'items.required' => 'Debe agregar al menos un producto al carrito.',
            'items.min' => 'Debe agregar al menos un producto al carrito.',
            'items.*.product_service_id.required' => 'Cada ítem debe tener un producto asociado.',
            'items.*.product_service_id.exists' => 'Uno de los productos seleccionados no existe.',
            'items.*.cantidad.required' => 'La cantidad es obligatoria para cada ítem.',
            'items.*.cantidad.integer' => 'La cantidad debe ser un número entero.',
            'items.*.cantidad.min' => 'La cantidad mínima es 1.',
            'items.*.precio_venta.required' => 'El precio de venta es obligatorio.',
            'items.*.precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'items.*.precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'cliente_nombre.max' => 'El nombre del cliente no debe exceder los 100 caracteres.',
        ];
    }

    /**
     * Validaciones adicionales después de las reglas básicas.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (!$this->cash_opening_id) return;

                $apertura = CashOpening::find($this->cash_opening_id);

                if (!$apertura || $apertura->status !== 'abierta') {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'No existe una apertura de caja activa para registrar ventas.'
                    );
                    return;
                }

                // Verificar que la apertura le pertenezca al usuario (excepto admin)
                $user = Auth::user();
                if (!$user->hasRole('administrador') && $apertura->user_id !== $user->id) {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'La apertura de caja no corresponde a su usuario.'
                    );
                    return;
                }

                // Verificar que no exista cierre aprobado
                $cierreAprobado = CashClosure::where('cash_opening_id', $apertura->id)
                    ->where('status', 'aprobado')
                    ->exists();

                if ($cierreAprobado) {
                    $validator->errors()->add(
                        'cash_opening_id',
                        'No se permiten ventas después del cierre de caja aprobado.'
                    );
                }
            }
        ];
    }
}
