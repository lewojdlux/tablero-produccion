<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialModel extends Model
{
    //
    protected $table = 'materiales';

    protected $primaryKey = 'id_material';
    public $timestamps = false;

    public function pedidos()
    {
        return $this->hasMany(PedidoMaterialModel::class, 'material_id', 'id_material');
    }

    protected $fillable = [
        'codigo_material',
        'nombre_material',
        'status',
    ];

    public function OrdenesMaterial(){
        return $this->hasMany(WorkOrdersMaterialsModel::class, 'material_id', 'id_material');
    }
}
