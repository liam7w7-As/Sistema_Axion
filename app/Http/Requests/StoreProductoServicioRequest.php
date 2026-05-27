<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductoServicioRequest extends FormRequest
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
            'tipo' => ['required', 'in:producto,servicio'],
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'operador' => ['nullable', 'in:Entel,Viva,Tigo,Otro'],
            'categoria' => ['required', 'in:tarjetas_unidad,tarjetas_mayor,recuperaciones,chips,recargas,megas,servicios_digitales,banca_digital,servicio_tecnico,efectivo_monedas'],
            'seccion_reporte' => ['nullable', 'in:tarjetas_unidad,tarjetas_mayor,recuperaciones,chips,recargas,megas,servicios_digitales,banca_digital,servicio_tecnico,efectivo_monedas'],
            'estado' => ['required', 'in:activo,inactivo'],
            'unidad_venta' => ['nullable', 'string'],
            'precio_compra' => ['required_if:tipo,producto', 'nullable', 'numeric', 'min:0'],
            'precio_venta' => ['required', 'numeric', 'min:0'],
            'tipo_ganancia' => ['nullable', 'in:fija,porcentaje,ninguna'],
            'ganancia' => ['nullable', 'numeric', 'min:0'],
            'comision' => ['nullable', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.required' => 'El tipo es obligatorio.',
            'tipo.in' => 'El tipo debe ser Producto o Servicio.',
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'operador.in' => 'El operador debe ser válido (Entel, Viva, Tigo, Otro).',
            'categoria.required' => 'La categoría es obligatoria.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.in' => 'El estado debe ser Activo o Inactivo.',
            'seccion_reporte.in' => 'La sección de reporte seleccionada no es válida.',
            'precio_compra.required' => 'El precio de compra es obligatorio.',
            'precio_compra.numeric' => 'El precio de compra debe ser un número.',
            'precio_compra.min' => 'El precio de compra no puede ser negativo.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'ganancia.required' => 'La ganancia es obligatoria.',
            'ganancia.numeric' => 'La ganancia debe ser un número.',
            'ganancia.min' => 'La ganancia no puede ser negativa.',
            'comision.numeric' => 'La comisión debe ser un número.',
            'comision.min' => 'La comisión no puede ser negativa.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no debe pesar más de 2MB.',
        ];
    }
}
