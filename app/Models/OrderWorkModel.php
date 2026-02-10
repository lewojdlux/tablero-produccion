<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrderWorkModel extends Model
{
    protected $table = 'work_orders';
    protected $primaryKey = 'id_work_order';
    public $timestamps = false;

    protected $fillable = [
        'n_documento',
        'pedido',
        'tercero',
        'vendedor',
        'instalador_id',
        'periodo',
        'ano',
        'n_factura',
        'obsv_pedido',
        'status',
        'started_at',
        'finished_at',
        'duration_minutes',
        'installation_notes	',
        'description',
        'fechafinalizacion',
        'usuario_finalizacion',
        'usereg_ot'
    ];

    // campos permitidos para ordenar (reutilizable)
    protected static array $allowedSorts = [
        'n_documento',
        'pedido',
        'tercero',
        'vendedor',
        'periodo',
        'ano',
        'n_factura',
        'status',
        'id_work_order',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'fechareg_ot' => 'datetime',
    ];

    /**
     * Relación con instalador (ejemplo)
     */
    public function instalador()
    {
        // foreign key on this model, owner key on InstaladorModel
        return $this->belongsTo(InstaladorModel::class, 'instalador_id', 'id_instalador');
    }

    /**
     * Scope para búsqueda (busca en varios campos)
     */
    public function scopeSearch($query, ?string $term)
    {
        if (empty($term)) {
            return $query;
        }

        $s = '%' . str_replace(' ', '%', trim($term)) . '%';

        return $query->where(function ($q) use ($s) {
            $q->where('n_documento', 'like', $s)
              ->orWhere('pedido', 'like', $s)
              ->orWhere('tercero', 'like', $s)
              ->orWhere('vendedor', 'like', $s)
              ->orWhere('n_factura', 'like', $s);
        });
    }

    /**
     * Scope para ordenar de forma segura (valida el campo)
     */
    public function scopeSorted($query, ?string $field, ?string $dir = 'asc')
    {
        $dir = strtolower($dir) === 'desc' ? 'desc' : 'asc';

        if (! in_array($field, self::$allowedSorts, true)) {
            $field = 'n_documento';
        }

        return $query->orderBy($field, $dir);
    }

    /**
     * Método utilitario para paginar la tabla con filtros y orden
     * Puedes añadir ->with('instalador') si quieres eager loading
     */
    public static function paginateForTable(?string $search, ?string $sortField, ?string $sortDir, int $perPage = 10)
    {
        return self::query()
            ->search($search)
            // ->with('instalador') // descomenta si necesitas la relación
            ->sorted($sortField, $sortDir)
            ->paginate($perPage);

    }

    /**
     * Último work order (reemplaza getOrderWork)
     */
    public static function latestWorkOrder()
    {
        return DB::table((new self)->getTable())
                 ->select('*')
                 ->orderBy('id_work_order', 'desc')
                 ->limit(1)
                 ->get();
    }

    public function pedidosMateriales()
    {
        return $this->hasMany(PedidoMaterialModel::class, 'orden_trabajo_id', 'id_work_order');
    }

    public function UsuariosOT(){
        return $this->belongsTo(User::class, 'usuario_finalizacion', 'id');
    }



}