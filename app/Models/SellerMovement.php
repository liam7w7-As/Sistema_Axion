<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\RegistraLog;

/**
 * Modelo: Movimiento del Vendedor.
 * Registra cada acción realizada a través de los botones rápidos del Dashboard.
 */
class SellerMovement extends Model
{
    use HasFactory, SoftDeletes, RegistraLog;

    protected $table = 'seller_movements';

    protected $fillable = [
        'cash_opening_id',
        'seccion',
        'cantidad',
        'monto',
        'observacion',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'monto' => 'decimal:2',
        ];
    }

    public function aperturaCaja()
    {
        return $this->belongsTo(CashOpening::class, 'cash_opening_id');
    }
}
