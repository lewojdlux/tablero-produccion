<?php

namespace App\Livewire\Order;

use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Pagination\LengthAwarePaginator;

// models
use App\Models\OrderWorkModel;
use App\Models\InstaladorModel;
use App\Models\MaterialModel;
use App\Models\PedidoMaterialModel;


// repositories
use App\Repository\ProductionRepository;

class WorkOrders extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'n_documento';
    public string $sortDir = 'asc';
    public int $perPage = 10;

    protected $dataWorkOrders;

    // filtros ERP
    public ?string $erp_start = null;
    public ?string $erp_end = null;
    public ?string $erp_pedido = null;
    public ?string $erp_cliente = null;
    public ?string $erp_asesor = null;
    public ?string $erp_producto = null;

    // resultados obtenidos del ERP (array of arrays)
    public array $erpResults = [];

    // paginación interna del modal
    public int $erpPage = 1;
    public int $erpPerPage = 8;

    protected $listeners = [
        'ui:open-create' => 'openErpModal',
    ];

    // seleccion modal
    public $instaladores = []; // lista para el select
    public $selectedPedido = null;
    public $selectedVendedorUsername = null;
    public $selectedInstaladorId = null;
    public $selectedTercero = null;
    public $selectedVendedor = null;
    public $selectedPeriodo = null;
    public $selectedAno = null;
    public $selectedNFactura = null;
    public $selectedObsvPedido = null;
    public $selectedStatus = 'in_progress'; // default
    public $selectedDescription = null;

    public ?OrderWorkModel $selectedOrder = null;
    public $selectedOrderId = null;
    public $pedidos = []; // colección para la vista
    public $material_id = null;
    public $cantidad = 1;
    public $precio_unitario = null;

    public $csvFile; // archivo subido

    // búsqueda/autocomplete de materiales
    public string $materialSearch = '';
    public array $materialOptions = [];
    public bool $showMaterialDropdown = false;

    /**
     * Inicializa los valores de la clase y se encarga de buscar
     * los datos de las órdenes de trabajo en el ERP.
     */
    public function mount()
    {
        //
    }

    public function updated($prop)
    {
        //
    }

    public function save()
    {
        //
    }

    /* ----------------- Modal / ERP ----------------- */

    public bool $showErpModal = false;

    public bool $showInstaladorModal = false;

    public function openErpModal(): void
    {
        $this->showErpModal = true;
        // reset pager
        $this->erpPage = 1;
        // cargar resultados iniciales (puedes quitar si quieres abrir vacío)
        $this->loadErp();
        // notificar al cliente para mostrar modal (js listener en blade)
        $this->dispatch('ui:show-erp-modal');
    }

    // cerrar modal ERP
    public function closeErpModal(): void
    {
        $this->showErpModal = false;
        $this->dispatch('ui:hide-erp-modal');
    }

    /**
     * Llama al repositorio ERP y carga resultados en $this->erpResults (array)
     */
    public function loadErp(): void
    {
        $repo = new ProductionRepository();

        $filters = [
            'start' => $this->erp_start,
            'end' => $this->erp_end,
            'pedido' => $this->erp_pedido,
            'cliente' => $this->erp_cliente,
            'asesor' => $this->erp_asesor,
            'producto' => $this->erp_producto,
            // opcionales: 'ano','mes' etc.
        ];

        try {
            $rows = $repo->searchOrdersVentas($filters); // devuelve array de arrays (según tu repo)

            $this->erpResults = is_array($rows) ? array_values($rows) : [];

            // Si no hay filas, asignar y terminar
            if (empty($rows)) {
                $this->erpResults = [];
                $this->erpPage = 1;
                return;
            }

            // 1) Extraer lista de pedidos del resultado ERP
            $pedidos = collect($rows)
                ->pluck('Pedido') // tu repo usa 'Pedido' en cada fila
                ->filter() // quitar nulos/vacios
                ->map(fn($p) => (string) $p)
                ->unique()
                ->values()
                ->all();

            // 2) Obtener pedidos ya creados en work_orders
            $existing = \App\Models\OrderWorkModel::whereIn('pedido', $pedidos)->pluck('pedido')->map(fn($p) => (string) $p)->unique()->all();

            // 3) Si existen, removerlos del resultado ERP
            if (!empty($existing)) {
                $rows = array_values(
                    array_filter($rows, function ($r) use ($existing) {
                        $p = (string) ($r['Pedido'] ?? ($r['Ndocumento'] ?? ''));
                        return !in_array($p, $existing, true);
                    }),
                );
            }

            $this->erpResults = $rows;
            $this->erpPage = 1; // reset al recargar búsqueda
        } catch (\Throwable $e) {
            $this->erpResults = [];
            $this->dispatch('notify', ['type' => 'danger', 'message' => 'Error consultando ERP: ' . $e->getMessage()]);
        }
    }

    /**
     * Paginador simple para los resultados del ERP
     */
    protected function makeErpPaginator(): LengthAwarePaginator
    {
        $collection = collect($this->erpResults ?? []);
        $total = $collection->count();
        $current = max(1, (int) $this->erpPage);
        $perPage = max(1, (int) $this->erpPerPage);
        $items = $collection->slice(($current - 1) * $perPage, $perPage)->values();

        // construir LengthAwarePaginator
        return new LengthAwarePaginator($items, $total, $perPage, $current, [
            'path' => url()->current(),
            'query' => request()->query(),
        ]);
    }

    public function erpGoto(int $page): void
    {
        $this->erpPage = max(1, $page);
    }

    /* ----------------- Tabla principal ----------------- */

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    // Resetear filtros ERP
    public function resetErpFilters(): void
    {
        $this->reset(['erp_start', 'erp_end', 'erp_pedido', 'erp_cliente', 'erp_asesor', 'erp_producto']);
        $this->loadErp();
    }

    // Abrir modal de selección de instalador
    public function openInstaladorModal($row)
    {
        $this->showInstaladorModal = true;

        // normalizar $row (puede venir como string JSON, array, object)
        if (is_string($row) && ($decoded = json_decode($row, true)) && json_last_error() === JSON_ERROR_NONE) {
            $row = $decoded;
        }

        // si viene objeto (Eloquent/stdClass) convertir a array parcial
        if (is_object($row)) {
            $row = (array) $row;
        }

        // asignar propiedades con keys comunes
        $this->selectedPedido = isset($row['Pedido']) ? $row['Pedido'] : $row['Ndocumento'] ?? ($row['pedido'] ?? null);
        $this->selectedTercero = $row['Tercero'] ?? ($row['Cliente'] ?? null);
        $this->selectedVendedor = $row['Vendedor'] ?? null;
        $this->selectedVendedorUsername = $row['VendedorUsername'] ?? ($row['vendedor_username'] ?? null);
        $this->selectedPeriodo = $row['Periodo'] ?? ($row['IntPeriodo'] ?? null);
        $this->selectedAno = $row['Ano'] ?? ($row['IntAno'] ?? null);
        $this->selectedNFactura = $row['NFactura'] ?? ($row['n_factura'] ?? null);
        $this->selectedObsvPedido = $row['Observaciones'] ?? ($row['obsv_pedido'] ?? null);
        // opcionales
        $this->selectedDescription = $row['description'] ?? null;
        $this->selectedStatus = $row['status'] ?? 'in_progress';

        // Instalandores
        $insts = InstaladorModel::where('status', 'active')->orderBy('nombre_instalador')->get();
        // convertir a array simple para evitar conversiones extra en Blade
        $this->instaladores = $insts
            ->map(function ($i) {
                return [
                    'id_instalador' => $i->id_instalador ?? $i->getKey(),
                    'nombre_instalador' => $i->nombre_instalador ?? ($i->nombre ?? null),
                    'username' => $i->username ?? null,
                ];
            })
            ->all();

        $this->selectedInstaladorId = null;

        \Log::debug('openInstaladorModal called', [
            'selectedPedido' => $this->selectedPedido,
            'selectedTercero' => $this->selectedTercero,
            'selectedVendedor' => $this->selectedVendedor,
            'selectedVendedorUsername' => $this->selectedVendedorUsername,
            'instaladores_count' => count($this->instaladores),
        ]);

        // disparar evento para mostrar modal (ver script en blade)
        $this->dispatch('ui:show-instalador-modal', ['pedido' => $this->selectedPedido]);
    }

    // Crear orden de trabajo con el instalador seleccionado
    public function confirmAddWorkOrder()
    {
        if (empty($this->selectedInstaladorId)) {
            $this->dispatch('ui:notify', ['type' => 'warning', 'message' => 'Selecciona un instalador']);
            return;
        }

        $inst = InstaladorModel::find($this->selectedInstaladorId);
        if (!$inst) {
            $this->dispatch('ui:notify', ['type' => 'danger', 'message' => 'Instalador inválido']);
            return;
        }

        // Normalizar pedido como scalar
        $pedido = $this->selectedPedido ?? null;
        if (is_array($pedido) && isset($pedido['pedido'])) {
            $pedido = $pedido['pedido'];
        }
        if (is_object($pedido) && property_exists($pedido, 'pedido')) {
            $pedido = $pedido->pedido;
        }
        $pedido = is_numeric($pedido) ? (int) $pedido : (string) $pedido;

        // Tomar valores desde las props (si no vienen, quedan null)
        $tercero = $this->selectedTercero ?? null;
        $vendedor = $this->selectedVendedor ?? null;
        $vendedor_username = $this->selectedVendedorUsername ?? null;
        $periodo = $this->selectedPeriodo ?? null;
        $ano = $this->selectedAno ?? null;
        $n_factura = $this->selectedNFactura ?? null;
        $obsv = $this->selectedObsvPedido ?? null;
        $status = $this->selectedStatus ?? 'in_progress';
        $description = $this->selectedDescription ?? 'Asignada a: ' . ($inst->nombre_instalador ?? ($inst->id_instalador ?? 'instalador'));

        DB::beginTransaction();
        try {
            // Generar consecutivo seguro
            $n_documento = $this->generateNextNDocumento();

            // Payload (array) respetando $fillable de OrderWorkModel
            $payload = [
                'n_documento' => $n_documento,
                'pedido' => $pedido,
                'tercero' => $tercero,
                'vendedor' => $vendedor,
                'vendedor_username' => $vendedor_username,
                // aquí guardamos solo el id del instalador (según tu $fillable)
                'tecnico_work_orders' => $inst->id_instalador ?? $inst->getKey(),
                'periodo' => $periodo,
                'ano' => $ano,
                'n_factura' => $n_factura,
                'obsv_pedido' => $obsv,
                'status' => $status,
                'description' => $description,
            ];

            // Evitar duplicados básicos (opcional)
            $exists = OrderWorkModel::where('pedido', $pedido)->where('tecnico_work_orders', $payload['tecnico_work_orders'])->first();

            if ($exists) {
                DB::rollBack();
                $this->dispatch('ui:notify', ['type' => 'info', 'message' => 'Ya existe una OT para este pedido con ese instalador.']);
                return;
            }

            // Crear registro (IMPORTANTE: create recibe un array)
            $order = OrderWorkModel::create($payload);

            // Quitar pedido de resultados ERP para que no salga inmediatamente
            $this->erpResults = array_values(
                array_filter($this->erpResults ?? [], function ($r) use ($pedido) {
                    $p = $r['Pedido'] ?? ($r['Ndocumento'] ?? ($r['pedido'] ?? null));
                    return ((string) $p) !== ((string) $pedido);
                }),
            );

            DB::commit();

            // Cerrar modal y notificar
            $this->dispatch('ui:hide-instalador-modal');
            $this->dispatch('ui:notify', ['type' => 'success', 'message' => "OT creada: {$n_documento}"]);

            // emitir si necesitas refrescar otros componentes
            $this->dispatch('workOrderCreated', $order->id ?? null);

            return ['status' => 'created', 'model' => $order];
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('confirmAddWorkOrder failed', ['err' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->dispatch('ui:notify', ['type' => 'danger', 'message' => 'Error creando OT: ' . $e->getMessage()]);
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    // cerrar modal de confirmar o.t
    public function closeConfirmModal(): void
    {
        $this->showInstaladorModal = false;
        $this->dispatch('ui:hide-instalador-modal');
    }

    /**
     * Genera n_documento tipo OT-YYYYMMDD-0001 garantizando consecutivo.
     */
    private function generateNextNDocumento(): string
    {
        $prefix = 'OT';
        $today = now()->format('Ymd');

        // Nota: llamar en transacción para lockForUpdate efectivo
        $last = OrderWorkModel::where('n_documento', 'like', "{$prefix}-{$today}-%")
            ->lockForUpdate()
            ->orderByDesc('n_documento')
            ->first();

        if ($last && preg_match('/-(\d+)$/', $last->n_documento, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }

        return sprintf('%s-%s-%04d', $prefix, $today, $next);
    }

    // metodos de materiales aquí...
    public function openViewWorkOrder($idWorkOrder)
    {
        // cargar la orden (usar first en vez de firstOrFail para control)
        $this->selectedOrder = OrderWorkModel::where('id_work_order', $idWorkOrder)->first();

        if (! $this->selectedOrder) {
            $this->dispatch('ui:notify', ['type' => 'warning', 'message' => "Orden {$idWorkOrder} no encontrada."]);
            return;
        }

        $this->selectedOrderId = $idWorkOrder;

        // resetear el autocomplete cuando abrimos la orden
        $this->materialSearch = '';
        $this->materialOptions = [];
        $this->showMaterialDropdown = false;
        $this->cantidad = 1;
        $this->precio_unitario = null;

        $this->loadPedidos();

        $this->dispatch('ui:open-view-work-order', ['id_work_order' => $idWorkOrder]);
    }

    protected $rules = [
        'material_id' => 'nullable|integer|exists:materiales,id',
        'cantidad' => 'required|integer|min:1',
        'precio_unitario' => 'nullable|numeric|min:0',
    ];

    protected function loadPedidos()
    {
        $this->pedidos = $this->selectedOrder->pedidosMateriales()->with('material')->get();
    }

    // agregar manualmente (select material existente o nuevo con código/nombre)
    public function addMaterialManual()
    {
        $this->validate();

        DB::transaction(function () {
            // si pasas material_id existente:
            if ($this->material_id) {
                $material = MaterialModel::findOrFail($this->material_id);
            } else {
                // si quieres crear material dinámicamente aquí, valida y crea
                throw new \Exception('Selecciona un material o usa importación de archivo para crear nuevos.');
            }

            // updateOrCreate para evitar duplicados por orden+material
            PedidoMaterialModel::updateOrCreate(['orden_trabajo_id' => $this->selectedOrderId, 'material_id' => $material->id], ['cantidad' => $this->cantidad, 'precio_unitario' => $this->precio_unitario]);
        });

        $this->reset(['material_id', 'cantidad', 'precio_unitario']);
        $this->loadPedidos();
        session()->flash('success', 'Material agregado.');
    }

    // Importar CSV simple: codigo_material,nombre_material,cantidad,precio_unitario
    public function importMaterials()
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt',
        ]);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');
        if (!$handle) {
            $this->addError('csvFile', 'No se pudo leer el archivo.');
            return;
        }

        DB::transaction(function () use ($handle) {
            $first = true;
            while (($row = fgetcsv($handle, 0, ',')) !== false) {
                // Salta encabezado si detectado (opcional)
                if ($first && $this->looksLikeHeader($row)) {
                    $first = false;
                    continue;
                }
                $first = false;

                // Asegúrate que el CSV tenga por lo menos 3 columnas: codigo,nombre,cantidad[,precio]
                $codigo = isset($row[0]) ? trim($row[0]) : null;
                $nombre = isset($row[1]) ? trim($row[1]) : null;
                $cantidad = isset($row[2]) ? (int) trim($row[2]) : 1;
                $precio = isset($row[3]) ? (float) str_replace(',', '.', trim($row[3])) : null;

                if (!$codigo && !$nombre) {
                    continue;
                } // ignorar filas vacías

                // crear o recuperar material por código (si hay código) o por nombre
                if ($codigo) {
                    $material = MaterialModel::firstOrCreate(['codigo_material' => $codigo], ['nombre_material' => $nombre ?? $codigo]);
                } else {
                    $material = MaterialModel::firstOrCreate(['nombre_material' => $nombre], ['codigo_material' => null]);
                }

                // insertar o actualizar pedido de material
                PedidoMaterialModel::updateOrCreate(['orden_trabajo_id' => $this->selectedOrderId, 'material_id' => $material->id], ['cantidad' => $cantidad, 'precio_unitario' => $precio]);
            }
            fclose($handle);
        });

        $this->csvFile = null;
        $this->loadPedidos();
        session()->flash('success', 'Importación completada.');
    }

    protected function looksLikeHeader(array $row): bool
    {
        $headerCandidates = ['codigo', 'codigo_material', 'nombre_material', 'cantidad', 'precio'];
        foreach ($row as $cell) {
            foreach ($headerCandidates as $candidate) {
                if (stripos($cell, $candidate) !== false) {
                    return true;
                }
            }
        }
        return false;
    }

    public function removePedido($pedidoId)
    {
        PedidoMaterialModel::where('pedido', $pedidoId)->delete();
        $this->loadPedidos();
        session()->flash('success', 'Material removido.');
    }

    /**
     * Livewire hook: cuando $materialSearch cambia.
     * @param string $value
     */
    public function updatedMaterialSearch(string $value): void
    {

        \Log::debug('updatedMaterialSearch called', ['value' => $value]);


        $value = trim($value);

        // mostrar sugerencias sólo si hay al menos 2 caracteres (ajusta si quieres)
        if (strlen($value) < 2) {
            $this->materialOptions = [];
            $this->showMaterialDropdown = false;
            return;
        }

        // consulta rápida: buscar por nombre o código
        $results = \App\Models\MaterialModel::query()
            ->where('nombre_material', 'like', "%{$value}%")
            ->orWhere('codigo_material', 'like', "%{$value}%")
            ->orderBy('nombre_material')
            ->limit(20)
            ->get();

        $this->materialOptions = $results->map(function ($m) {
        return [
            'id_material' => $m->getKey(),                // id usable para selectMaterialOption()
            'id_material' => $m->{$m->getKeyName()} ?? $m->getKey(), // opcional, por compatibilidad
            'codigo_material' => $m->codigo_material ?? null,
            'nombre_material' => $m->nombre_material ?? null,
            ];
        })->all();

        $this->showMaterialDropdown = count($this->materialOptions) > 0;
    }

    /**
     * Selecciona un material de la lista de sugerencias.
     */
    public function selectMaterialOption($idMaterial): void
    {
        $pk = (new MaterialModel())->getKeyName();
        $material = MaterialModel::where($pk, $idMaterial)->first();

        if (! $material) {
            return;
        }

        // guarda la PK real en material_id
        $this->material_id = $material->{$pk};

        // mostrar texto en input
        $this->materialSearch = ($material->codigo_material ? $material->codigo_material . ' - ' : '') . $material->nombre_material;

        // limpiar dropdown
        $this->materialOptions = [];
        $this->showMaterialDropdown = false;
    }


    /**
     * Limpiar selección (opcional, puedes exponer un boton para llamar esto)
     */
    public function clearMaterialSelection(): void
    {
        $this->material_id = null;
        $this->materialSearch = '';
        $this->materialOptions = [];
        $this->showMaterialDropdown = false;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $workOrders = OrderWorkModel::paginateForTable($this->search, $this->sortField, $this->sortDir, $this->perPage, ['instalador']);

        // paginator del modal ERP
        $erpPaginator = $this->makeErpPaginator();

        return view('livewire.order.work-orders', [
            'workOrders' => $workOrders,
            'erpPaginator' => $erpPaginator,
        ]);
    }
}