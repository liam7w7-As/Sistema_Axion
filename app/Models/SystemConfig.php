<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo: Configuración del sistema.
 * Almacena la configuración general: nombre, logo, horarios, moneda.
 */
class SystemConfig extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Nombre de la tabla asociada.
     */
    protected $table = 'system_config';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [
        'nombre_sistema',
        'alias',
        'logo',
        'actividad',
        'moneda',
        'hora_inicio_admin',
        'hora_fin_admin',
        'hora_inicio_vendedor',
        'hora_fin_vendedor',
        'formato_impresion',
        'tamano_papel_thermal',
    ];

    /**
     * Conversión de tipos de atributos.
     */
    protected function casts(): array
    {
        return [
            'hora_inicio_admin' => 'string',
            'hora_fin_admin' => 'string',
            'hora_inicio_vendedor' => 'string',
            'hora_fin_vendedor' => 'string',
        ];
    }
}
