<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderWorkFotoModel extends Model
{
    //
    protected $table = 'order_work_fotos';

    protected $fillable = [
        'order_work_id',
        'ruta',
        'tipo'
    ];
}