<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PedidoMaterialModel extends Model
{
    //
    protected $table = 'pedidos_materiales';
    protected $primaryKey = 'id_pedido_material';
    public $timestamps = false;

    // Campos asignables en masa
    protected $fillable = [
        'orden_trabajo_id',
        'instalador_id',
        'fecha_solicitud',
        'status',
        'fecha_aprobacion',
        'observaciones',
        'fecha_registro',
        'ref_id_usuario_registro',
        'fecha_modificacion',
        'ref_id_usuario_modificacion'
    ];

    // Ocultar campos al convertir a array o JSON
    protected $hidden = [
        'created_at',
        'updated_at'
    ];


    // Si deseas manejar los tipos de datos automáticamente
    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'float'
    ];

    // Si deseas manejar las fechas automáticamente
    protected $dates = [
        'created_at',
        'updated_at'
    ];



    public function ordenTrabajo()
    {
        return $this->belongsTo(OrderWorkModel::class, 'orden_trabajo_id', 'id_work_order');
    }


    public function items()
    {
        return $this->hasMany(
            PedidoMaterialItemModel::class,
            'pedido_material_id',     // FK en pedidos_materiales_item
            'id_pedido_material'      // PK en pedidos_materiales
        );
    }

    public function instalador()
    {
        return $this->belongsTo(
            InstaladorModel::class,
            'instalador_id',     // FK en pedidos_materiales
            'id_instalador'      // PK en instalador
        );
    }

    public function proveedor()
    {
        return $this->belongsTo(ProveedorModel::class, 'proveedor_id');
    }

    public function detalles()
    {
        return $this->hasMany(
            DetalleSolicitudMaterialModel::class,
            'solicitud_material_id',
            'id_pedido_material'
        );
    }




}
