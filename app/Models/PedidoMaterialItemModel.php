<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoMaterialItemModel extends Model
{
    //
    protected $table = 'pedidos_materiales_item';
    protected $primaryKey = 'id_pedido_material_item';
    public $timestamps = false;

    protected $fillable = [
        'pedido_material_id',
        'codigo_material',
        'descripcion_material',
        'cantidad',
    ];
}