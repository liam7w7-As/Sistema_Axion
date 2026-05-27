<?php

namespace App\Traits;

use App\Models\Log;
use Illuminate\Support\Facades\Auth;

trait RegistraLog
{
    public static function bootRegistraLog()
    {
        static::created(function ($model) {
            self::logAction('creado', $model, null, $model->toArray());
        });

        static::updated(function ($model) {
            // Guardamos solo los atributos que cambiaron (excluyendo timestamps que suelen cambiar siempre)
            $cambios = $model->getChanges();
            $originales = [];
            
            foreach (array_keys($cambios) as $key) {
                if ($key !== 'updated_at') {
                    $originales[$key] = $model->getOriginal($key);
                } else {
                    unset($cambios[$key]);
                }
            }

            if (!empty($cambios)) {
                self::logAction('actualizado', $model, $originales, $cambios);
            }
        });

        static::deleted(function ($model) {
            self::logAction('eliminado', $model, $model->toArray(), null);
        });
    }

    protected static function logAction($accion, $model, $valoresAntiguos = null, $valoresNuevos = null)
    {
        Log::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'accion' => $accion,
            'modelo' => class_basename($model),
            'modelo_id' => $model->id ?? null,
            'valores_antiguos' => $valoresAntiguos,
            'valores_nuevos' => $valoresNuevos,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
