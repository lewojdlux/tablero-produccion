<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudMaterialModel extends Model
{
    //
    protected $table = 'solicitud_material';
    protected $primaryKey = 'id_solicitud_material';
    public $timestamps = false;


    protected $fillable = [
        'n_solicitud',
        'pedido_material_id',
        'instalador_id',
        'proveedor_id',
        'status',
        'observaciones',
        'ref_id_usuario_registro',
        'fecha_registro',
        'ref_id_usuario_modificacion',
        'fecha_modificacion',
    ];

    public function pedidoMaterial()
    {
        return $this->belongsTo(PedidoMaterialModel::class, 'pedido_material_id', 'id_pedido_material');
    }

    public function instalador()
    {
        return $this->belongsTo(InstaladorModel::class, 'instalador_id', 'id_instalador');
    }


    public function proveedor()
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id', 'id_proveedor');
    }

    public function materialesProveedor()
    {
        return $this->hasMany(\App\Models\DetalleMaterialProveedor::class, 'solicitud_material_id', 'id_solicitud_material');
    }


}
