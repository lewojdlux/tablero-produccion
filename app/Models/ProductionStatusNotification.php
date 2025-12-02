<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionStatusNotification extends Model
{
    //
    protected $table = 'production_status_notifications';
    protected $fillable = ['ref_id_production_order','status','notified_at'];
}
