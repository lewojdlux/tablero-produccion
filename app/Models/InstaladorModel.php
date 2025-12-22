<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstaladorModel extends Model
{
    //
    protected $table = 'instalador';
    protected $primaryKey = 'id_instalador';
    public $timestamps = false;

    protected $fillable = [
        'nombre_instalador',
        'celular_instalador',
        'email_instalador',
        'identificador_usuario',
        'status',
    ];

    public function orders()
    {
        // foreign key on OrderWorkModel, local key on this model
        return $this->hasMany(OrderWorkModel::class, 'tecnico_work_orders', 'id_instalador');
    }
}