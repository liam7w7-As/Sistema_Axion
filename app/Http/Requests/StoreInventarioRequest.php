<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInventarioRequest extends FormRequest
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
            'tipo_inventario' => ['required', 'in:fisico,digital'],
            'product_service_id' => ['required', 'exists:products_services,id'],
            'cantidad_ingreso' => ['required', 'integer', 'min:1'],
            'precio_compra' => ['required', 'numeric', 'min:0'],
            'tipo_movimiento' => ['required', 'in:ingreso,egreso,ajuste'],
            'observacion' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_inventario.required' => 'El tipo de inventario es obligatorio.',
            'tipo_inventario.in' => 'El tipo de inventario debe ser Físico o Digital.',
            'product_service_id.required' => 'Debe seleccionar un producto o servicio.',
            'product_service_id.exists' => 'El producto seleccionado no existe en el sistema.',
            'cantidad_ingreso.required' => 'La cantidad es obligatoria.',
            'cantidad_ingreso.integer' => 'La cantidad debe ser un número entero.',
            'cantidad_ingreso.min' => 'La cantidad debe ser mayor a 0.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'tipo_movimiento.required' => 'El tipo de movimiento es obligatorio.',
            'tipo_movimiento.in' => 'El tipo de movimiento debe ser Ingreso, Egreso o Ajuste.',
        ];
    }
}
