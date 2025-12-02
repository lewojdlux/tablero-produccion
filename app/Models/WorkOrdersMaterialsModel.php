<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkOrdersMaterialsModel extends Model
{
    //
    protected $table = 'work_orders_materials';
    protected $primaryKey = 'id_work_order_material';

    public $timestamps = false;

    protected $fillable = [
        'work_order_id',
        'material_id',
        'cantidad',
    ];

    public function work_order()
    {
        return $this->belongsTo(OrderWorkModel::class, 'work_order_id', 'id_work_order');
    }

    public function material()
    {
        return $this->belongsTo(MaterialModel::class, 'material_id', 'id_material');
    }
}
