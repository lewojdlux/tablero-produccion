<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoJornadaModel extends Model
{
    //
    protected $table = 'orden_trabajo_jornadas';

    protected $primaryKey = 'id'; // si es diferente ajústelo

    public $timestamps = false; // porque usted usa fechareg_otj manual

    protected $fillable = [
        'orden_trabajo_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'horas_trabajadas',
        'observaciones',
        'acompanante_ot',
        'fechareg_otj',
        'user_otj',
    ];
}