<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderWorkVisitaModel extends Model
{
    //
    protected $table = 'order_work_visitas';

    protected $fillable = [
        'order_work_id',
        'fecha_visita',
        'observacion',
    ];

    //  RELACIÓN CON ORDEN
    public function orden()
    {
        return $this->belongsTo(OrderWorkModel::class, 'order_work_id', 'id_work_order');
    }
}
