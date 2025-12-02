<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailProductionOrder extends Model
{
    //

    protected $table = 'detalle_production_orders';
    protected $primaryKey = 'id_detalle_production_order';
    public $timestamps = false;

    protected $fillable = [
        'ref_id_production_order',
        'fecha_inicial_produccion',
        'fecha_final_produccion',
        'dias_produccion',
        'hora_inicio_produccion',
        'hora_fin_produccion',
        'horas_produccion',
        'minutos_produccion',
        'segundos_produccion',
        'cantidad_luminarias',
        'fecha_registro',
        'ref_id_usuario_registro',
        'fecha_actualizacion',
        'ref_id_usuario_actualizacion',
        'fecha_estado',
        'ref_id_usuario_estado',
        'observacion_produccion'
    ];
}