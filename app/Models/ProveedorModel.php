<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProveedorModel extends Model
{
    //
    protected $table = 'suppliers';
    protected $primaryKey = 'id_supplier';
    public $timestamps = false;

    protected $fillable = [
        'code_supplier',
        'name_supplier',
        'status',
        'fecha_registro',
        'user_reg',
        'fecha_edit',
        'user_edit'
    ];


    public function Solicitudes()
    {
        return $this->hasMany(SolicitudMaterialModel::class, 'proveedor_id', 'id_supplier');
    }


}
