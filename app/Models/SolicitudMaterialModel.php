<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\DetalleSolicitudMaterialModel;

class SolicitudMaterialModel extends Model
{
    //
    protected $table = 'solicitud_material';
    protected $primaryKey = 'id_solicitud_material';
    public $timestamps = false;


    protected $fillable = [
        'n_solicitud',
        'pedido_material_id',
        'consecutivo_compra',
        'status',
        'ref_id_usuario_registro',
        'fecha_registro',
        'ref_id_usuario_modificacion',
        'fecha_modificacion',
    ];

    public function pedidoMaterial()
    {
        return $this->belongsTo(PedidoMaterialModel::class, 'pedido_material_id', 'id_pedido_material');
    }

    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'ref_id_usuario_registro');
    }

    public function usuarioModificacion()
    {
        return $this->belongsTo(User::class, 'ref_id_usuario_modificacion');
    }



    public function detalles()
    {
        return $this->hasMany(
            DetalleSolicitudMaterialModel::class,
            'solicitud_material_id',
            'id_solicitud_material'
        );
    }

}