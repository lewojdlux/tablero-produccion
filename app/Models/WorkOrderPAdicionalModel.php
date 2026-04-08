<?php

namespace App\Models;

use App\Livewire\Order\WorkOrders;
use Illuminate\Database\Eloquent\Model;

class WorkOrderPAdicionalModel extends Model
{
    //
    protected $table = 'work_order_pd_adicionales';

    protected $fillable = [
        'work_order_id',
        'pd_agregado',
        'asesor_hgi_id',
        'usuario_registra_id',
        'usuario_modifica_id',
        'observacion',
        'fecha_registro',
        'fecha_modificacion'
    ];

    public $timestamps = false;

    public function workOrder()
    {
        return $this->belongsTo(WorkOrders::class, 'work_order_id', 'id_work_order');
    }

    public function usuarioRegistra()
    {
        return $this->belongsTo(User::class, 'usuario_registra_id');
    }


}