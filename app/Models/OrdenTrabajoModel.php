<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrdenTrabajoModel extends Model
{
    //
    protected $table = 'orden_trabajo_jornadas';
    protected $primaryKey = 'id';
    public $timestamps = false;
    protected $fillable = [
        'orden_trabajo_id',
        'acompanante_ot',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'horas_trabajadas',
        'observaciones',
        'fechareg_otj',
        'user_otj',
        'fechaedit_otj',
        'useredit_otj',
    ];

    protected $casts = [
        'fecha' => 'date',
        'hora_inicio' => 'string',
        'hora_fin' => 'string',
        'acompanante_ot' => 'array',
    ];

    public function ordenTrabajo()
    {
        return $this->belongsTo(OrdenTrabajoModel::class, 'orden_trabajo_id', 'id_work_order');
    }

    public function crearJornada(array $data): void
    {
        // Registrar la jornada de trabajo
        $this->OrdenTrabajoModel->create([
            'orden_trabajo_id' => $data['orden_trabajo_id'],
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'horas_trabajadas' => $data['horas_trabajadas'],
            'observaciones' => $data['observaciones'] ?? null,
            'acompanante_ot' => $data['acompanante_ot'],
            'fechareg_otj' => now(),
            'user_otj' => $data['user_otj'],
        ]);
    }
}
