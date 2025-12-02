<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleMaterialProveedor extends Model
{
    //
    protected $table = 'detalle_material_proveedor';

    protected $primaryKey = 'id_detalle_proveedor';

    protected $fillable = [
        'solicitud_material_id',
        'codigo_material',
        'nombre_material',
        'cantidad',
        'precio_unitario',
        'total',
        'proveedor',
        'fecha_registro',
        'user_reg',
    ];

    public $timestamps = true;
}