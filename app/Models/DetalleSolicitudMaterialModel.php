<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleSolicitudMaterialModel extends Model
{
    //
    protected $table = 'detalle_solicitud_material';
    protected $primaryKey = 'id_detalle_solicitud_material';
    public $timestamps = false;

    protected $fillable = [
        'solicitud_material_id',
        'codigo_material',
        'descripcion_material',
        'cantidad',
        'precio_unitario',
        'iva',
        'descuento',
        'total',
        'fecha_registro',
        'user_reg',
        'fecha_edit',
        'user_edit'
    ];


    public  function solicitudMaterial()
    {
        return $this->belongsTo(SolicitudMaterialModel::class, 'solicitud_material_id', 'id_solicitud_material');
    }

}
