<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionOrder extends Model
{
    //
    protected $table = 'production_orders';
    protected $primaryKey = 'id_production_order';

    protected $fillable = ['ticket_code', 'tipo_transaccion', 'n_documento', 'pedido', 'tercero', 'vendedor', 'vendedor_username', 'luminaria', 
    'observaciones', 'periodo', 'ano', 'fecha_orden_produccion', 'estado_factura', 'n_factura', 'status', 'queued_at', 'started_at', 'paused_at', 
    'paused_accumulated_min', 'finished_at', 'approved_at', 'obsv_production_order'];

    protected $casts = [
        'fecha_orden_produccion' => 'datetime',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'paused_at' => 'datetime',
        'finished_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeQueued($q)
    {
        return $q->where('status', 'queued');
    }
    public function scopeInProgress($q)
    {
        return $q->where('status', 'in_progress');
    }
    public function scopeDone($q)
    {
        return $q->where('status', 'done');
    }
    public function scopeApproved($q)
    {
        return $q->where('status', 'approved');
    }

    // Duración transcurrida (min) si está en producción
    public function elapsedMinutes(): int
    {
        if (!$this->started_at) {
            return 0;
        }
        $end = $this->finished_at ?: now();
        $elapsed = $this->paused_accumulated_min + $this->started_at->diffInMinutes($end);
        return max(0, $elapsed);
    }

    // Remanente estimado (min)
    public function remainingMinutes(): int
    {
        if ($this->status === 'done' || $this->status === 'approved') {
            return 0;
        }
        $remaining = $this->expected_duration_min;
        if ($this->status === 'in_progress') {
            $remaining = max(0, $this->expected_duration_min - $this->elapsedMinutes());
        }
        return $remaining;
    }
}