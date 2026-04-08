<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoNovedad extends Model
{
    //
    protected $table = 'orden_trabajo_novedades';

    protected $fillable = [
        'orden_trabajo_id',
        'fecha_afectada',
        'tipo_novedad',
        'observacion',
        'reprogramar',
        'nueva_fecha',
        'user_id'
    ];
}