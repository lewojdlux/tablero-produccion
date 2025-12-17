<?php

namespace App\Livewire\Order;

use App\Repository\ProductionRepository;
use App\Repository\ProductionOrderRepository;
use App\Services\ProductionOrderService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Carbon\Carbon;
use App\Models\DetailProductionOrder;
use App\Models\ProductionOrder;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Facades\Mail;

class PendingList extends Component
{
    use WithPagination;
    protected $paginationTheme = 'tailwind';
    public int $perPage = 15;
    public int $prodPerPage = 15; // 👈 NUEVO: producción
    public bool $pausePendingPoll = false; // pausa auto-refresh de pendientes

    public bool $visible = false;
    public bool $readOnly = false;

    public ?string $action = null; // programar|empezar|pausar|finalizar|aprobar|reabrir
    public ?int $docId = null; // tu identificador (n_documento o id)

    /** Campos formulario (según tu migración) */
    public ?string $fecha_inicial_produccion = null;
    public ?string $hora_inicio_produccion = null;
    public ?string $fecha_final_produccion = null;
    public ?string $hora_fin_produccion = null;

    public ?int $dias_produccion = null;
    public ?int $horas_produccion = null;
    public ?int $minutos_produccion = null;
    public ?int $segundos_produccion = null;

    public ?int $cantidad_luminarias = null;
    public ?string $observacion_produccion = null;
    public ?string $ref_id_estado = null; // queued|in_progress|paused|done|approved
    public ?string $nuevo_status = null; // queued|in_progress|paused|done|approved

    #[Layout('layouts.app')]
    #[Url(as: 'start')]
    public ?string $start = null;
    #[Url(as: 'end')]
    public ?string $end = null;
    #[Url]
    public string $asesor = '';
    #[Url]
    public string $cliente = '';
    #[Url]
    public string $pedido = '';
    public string|int|null $selectedDoc = null;
    public ?string $selectedProduct = null;
    public string $activeTab = 'pendientes'; // 'pendientes' | 'produccion'

    // === Modal detalle ===
    public bool $showDetail = false;

    public array $poIndex = []; /** Pedidos que YA existen en production_orders, indexados por pedido */
    public array $prodRows = []; // 👈 listado de production_orders para la pestaña
    public array $detail = ['header' => [], 'lines' => []]; // <- aquí se pasan los datos al modal
    public array $rows = [];

    // === Filtros PRODUCCIÓN ===
    public string $prodQ = ''; // buscador global: ticket, pedido, luminaria, vendedor
    public string $prodPedido = '';
    public string $prodAsesor = '';
    public string $prodProducto = ''; // mapea a columna 'luminaria'
    public string $prodStatus = ''; // queued|in_progress|done|approved
    //public ?int $prodAno = null;          // filtra por year(updated_at)
    //public ?int $prodMes = null;          // filtra por month(updated_at)
    public ?string $prodStart = null; // fecha desde (YYYY-MM-DD) sobre updated_at
    public ?string $prodEnd = null; // fecha hasta  (YYYY-MM-DD) sobre updated_at
    // =========================

    public bool $showObservationModal = false;
    public ?string $observationText = null;
    public ?int $ndocTemp = null;
    public ?string $productoTemp = null;

    /** Carga ERP y construye índice de production_orders */
    public function mount(ProductionRepository $repo, ProductionOrderRepository $poRepo): void
    {
        // ERP
        $this->rows = $repo->searchOrders($this->filters());

        // Índice para filtrar pendientes (no mostrarlos si ya están en producción)
        $this->poIndex = collect($poRepo->getOrdersBasicInfo())->keyBy('pedido')->toArray();

        // 👇 Cargar tabla de producción (para la pestaña)
        $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());
    }

    /** Actualiza filtros y refresca paginación */
    public function updated($prop, \App\Repository\ProductionRepository $repo, \App\Repository\ProductionOrderRepository $poRepo): void
    {
        // filtros ERP (ya lo tenías)
        if (in_array($prop, ['start', 'end', 'asesor', 'cliente', 'pedido', 'perPage'])) {
            $this->resetPage();
            $this->reload($repo, $poRepo);
        }

        // filtros PRODUCCIÓN (incluye buscador y perPage)
        if (in_array($prop, ['prodQ', 'prodPedido', 'prodAsesor', 'prodProducto', 'prodStatus', 'prodStart', 'prodEnd', 'prodPerPage'])) {
            // resetea la paginación de la pestaña producción
            $this->setPage(1, 'prodPage');
            // recarga desde MySQL con los filtros
            $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());
        }
    }

    /** Limpia filtros y recarga desde ERP */
    public function clearPendingFilters(): void
    {
        $repo = app(\App\Repository\ProductionRepository::class);
        $poRepo = app(\App\Repository\ProductionOrderRepository::class);

        // Limpia SOLO filtros de pendientes
        $this->reset(['start', 'end', 'asesor', 'cliente', 'pedido']);

        // Primera página
        $this->resetPage();

        // Recarga pendientes sin filtros
        $this->rows = $repo->searchOrders($this->filters());

        // Refresca índice de producción (opcional pero útil)
        $this->poIndex = collect($poRepo->getOrdersBasicInfo())->keyBy('pedido')->toArray();
    }

    protected function recalcularPorFechas(): void
    {
        // Si hay fechas, calculamos días automáticamente y NO exigimos horas
        if ($this->fecha_inicial_produccion && $this->fecha_final_produccion) {
            $ini = Carbon::parse($this->fecha_inicial_produccion . ' 00:00:00');
            $fin = Carbon::parse($this->fecha_final_produccion . ' 00:00:00');
            if ($fin->lessThan($ini)) {
                $this->dias_produccion = null;
                return;
            }
            // Diferencia en días (no inclusivo). Si quieres inclusivo, suma +1.
            $this->dias_produccion = $ini->diffInDays($fin);
        }
        // Si no hay ambas fechas, no tocamos los campos de duración
    }

    protected function recalcularPorFechasYHoras(): void
    {
        // Si hay fechas y horas completas, calculamos d/h/m/s con precisión
        if ($this->fecha_inicial_produccion && $this->hora_inicio_produccion && $this->fecha_final_produccion && $this->hora_fin_produccion) {
            $start = Carbon::parse("{$this->fecha_inicial_produccion} {$this->hora_inicio_produccion}");
            $end = Carbon::parse("{$this->fecha_final_produccion} {$this->hora_fin_produccion}");

            if ($end->lessThanOrEqualTo($start)) {
                $this->dias_produccion = $this->horas_produccion = $this->minutos_produccion = $this->segundos_produccion = null;
                return;
            }

            $secs = $start->diffInSeconds($end);

            $dias = intdiv($secs, 86400);
            $secs %= 86400; // 24*60*60
            $horas = intdiv($secs, 3600);
            $secs %= 3600;
            $mins = intdiv($secs, 60);
            $segs = $secs % 60;

            $this->dias_produccion = $dias;
            $this->horas_produccion = $horas;
            $this->minutos_produccion = $mins;
            $this->segundos_produccion = $segs;
        }
    }

    /** Aplica filtros y recarga desde ERP y producción */
    public function applyPendingFilters(): void
    {
        $repo = app(\App\Repository\ProductionRepository::class);

        // Ir a la primera página de la tabla de pendientes
        $this->resetPage();

        // Recargar pendientes con los filtros actuales
        $this->rows = $repo->searchOrders($this->filters());
    }

    /** Limpia filtros de producción y recarga desde MySQL */
    public function clearProductionFilters(): void
    {
        $this->reset(['prodQ', 'prodPedido', 'prodAsesor', 'prodProducto', 'prodStatus', 'prodStart', 'prodEnd']);
        $this->resetPage('prodPage');
        $this->prodRows = app(\App\Repository\ProductionOrderRepository::class)->getOrdersForTable($this->prodFilters());
    }

    /** Recarga datos desde ERP y refresca índice de production_orders */
    public function reload(ProductionRepository $repo, ProductionOrderRepository $poRepo): void
    {
        $this->resetPage();
        // Recarga ERP
        $this->rows = $repo->searchOrders($this->filters());

        // Refresca índice y tabla de producción
        $this->poIndex = collect($poRepo->getOrdersBasicInfo())->keyBy('pedido')->toArray();
        $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());
    }

    #[On('production.open')]
    public function open(int $docId, ?string $action = null): void
    {
        $this->resetForm();
        $this->docId = $docId;
        $this->action = $action;

        // 👇 Define solo-lectura si perfil es 5 (asesor)
        $perfil = (int) (Auth::user()->perfil_usuario_id ?? 0);
        $this->readOnly = $perfil === 2 || $perfil === 5; // admin o asesor

        // mapea acción → estado del enum (sin 'paused')
        $this->nuevo_status = match ($this->action) {
            'programar' => 'queued',
            'empezar' => 'in_progress',
            'finalizar' => 'done',
            'aprobar' => 'approved',
            'reabrir' => 'in_progress',
            default => null,
        };

        // 2) Traer estado actual por si quieres mostrarlo o no viene action
        if (!$this->action && !$this->nuevo_status) {
            $actual = ProductionOrder::find($docId);
            $this->nuevo_status = $actual?->status; // puede ser null si no existe
        }

        // 3) Precargar detalle si existe
        if ($detalle = DetailProductionOrder::where('ref_id_production_order', $docId)->first()) {
            $this->fecha_inicial_produccion = $detalle->fecha_inicial_produccion;
            $this->hora_inicio_produccion = $detalle->hora_inicio_produccion;
            $this->fecha_final_produccion = $detalle->fecha_final_produccion;
            $this->hora_fin_produccion = $detalle->hora_fin_produccion;

            $this->dias_produccion = $detalle->dias_produccion;
            $this->horas_produccion = $detalle->horas_produccion;
            $this->minutos_produccion = $detalle->minutos_produccion;
            $this->segundos_produccion = $detalle->segundos_produccion;

            $this->cantidad_luminarias = $detalle->cantidad_luminarias;
            $this->observacion_produccion = $detalle->observacion_produccion;
        }

        // 4) Si hay datos, recalcula por si acaso (no rompe nada)
        $this->recalcularPorFechas();
        $this->recalcularPorFechasYHoras();

        $this->visible = true;
        $this->dispatch('ui:show-production-modal');
    }

    protected function resetForm(): void
    {
        $this->reset(['fecha_inicial_produccion', 'hora_inicio_produccion', 'fecha_final_produccion', 'hora_fin_produccion', 'dias_produccion', 'horas_produccion', 'minutos_produccion', 'segundos_produccion', 'cantidad_luminarias', 'observacion_produccion', 'ref_id_estado']);
    }

    /**
     * Poll para la tabla de pendientes que NO toca paginación
     * ni borra filtros. Solo vuelve a pedir los datos con los
     * filtros actuales, salvo que esté pausado.
     */
    public function autoRefreshPendientes(ProductionRepository $repo): void
    {
        if ($this->pausePendingPoll) {
            return; // si estás tecleando en filtros, no refresca
        }

        // refresca solo las filas, respetando los filtros vigentes
        $this->rows = $repo->searchOrders($this->filters());
    }

    /** Limpia filtros */
    protected function filters(): array
    {
        return [
            'start' => $this->start,
            'end' => $this->end,
            'asesor' => $this->asesor,
            'cliente' => $this->cliente,
            'pedido' => $this->pedido,
        ];
    }

    /** Filtros para producción */
    protected function prodFilters(): array
    {
        return [
            'q' => $this->prodQ,
            'pedido' => $this->prodPedido,
            'asesor' => $this->prodAsesor,
            'luminaria' => $this->prodProducto, // tu repo espera 'luminaria'
            'status' => $this->prodStatus,
            'start' => $this->prodStart,
            'end' => $this->prodEnd,
        ];
    }

    /**
     *  Abre modal (sin cambios)
     */
    public function openDetailsByDoc(ProductionRepository $repo, string|int $ndoc, string $producto): void
    {
        $this->selectedDoc = $ndoc;
        $this->selectedProduct = $producto;

        // 1) Carga el detalle desde tu repo/servicio
        $this->detail = $repo->getOrderDetail($ndoc, $producto); // <-- implementa este método en tu repo

        // 2) Marca visible y dispara evento para que Bootstrap lo muestre
        /*$this->showDetail = true;
         $this->dispatch('show-order-modal');*/
        // Enviar al hijo (OrderDetailModal) y él abre el modal
        $this->dispatch('open-order-detail', detail: $this->detail, selectedDoc: $ndoc);
    }

    /** Cierra modal (sin cambios) */
    #[On('close-order-detail')]
    public function closeDetails()
    {
        $this->showDetail = false;
        $this->detail = ['header' => [], 'lines' => []];
        $this->selectedDoc = null;
        $this->selectedProduct = null;
        $this->dispatch('hide-order-modal');
    }

    /** Render con EXCLUSIÓN de pedidos ya en production_orders */
    public function render()
    {
        // Filtra pendientes (excluir los ya en production_orders)
        $source = array_values(
            array_filter($this->rows, function ($r) {
                $pedido = $r['Pedido'] ?? null;
                return !isset($this->poIndex[$pedido]);
            }),
        );

        // Paginación de pendientes (ERP)
        $page = $this->getPage();
        $perPage = $this->perPage;
        $total = count($source);
        $items = array_slice($source, ($page - 1) * $perPage, $perPage);

        $rowsPaginated = new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => request()->url(), 'query' => request()->query()]);

        // === PRODUCCIÓN: paginación independiente (usa $this->prodPerPage) ===
        $prodPageName = 'prodPage'; // nombre distinto para no chocar con la otra
        $prodPage = \Illuminate\Pagination\Paginator::resolveCurrentPage($prodPageName) ?: 1;
        $prodPerPage = $this->prodPerPage;
        $prodTotal = count($this->prodRows);
        $prodItems = array_slice($this->prodRows, ($prodPage - 1) * $prodPerPage, $prodPerPage);

        $prodRowsPaginated = new LengthAwarePaginator($prodItems, $prodTotal, $prodPerPage, $prodPage, ['path' => request()->url(), 'query' => request()->query(), 'pageName' => $prodPageName]);

        // (La pestaña de producción la mostraremos sin paginar para simplificar)
        return view('livewire.order.pending-list', [
            'rowsPaginated' => $rowsPaginated, // pendientes
            'prodRowsPaginated' => $prodRowsPaginated, // producción
            'activeTab' => $this->activeTab,
        ]);
    }

    // Recarga solo la pestaña de producción (si quieres usar un poll separado)
    public function reloadProduction(ProductionOrderRepository $poRepo): void
    {
        $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());
    }

    public function openObservationModal(int|string $ndoc, string $producto): void
    {
        $this->ndocTemp = $ndoc;
        $this->productoTemp = $producto;
        $this->observationText = null;
        $this->showObservationModal = true;

        // Dispara evento JS para abrir el modal Bootstrap
        $this->dispatch('ui:show-observation-pendientes');
    }

    /**
     * Encola y SACA del listado el pedido recién creado
     * - Refresca el índice poIndex desde MySQL
     * - Elimina de $this->rows el registro cuyo 'Pedido' coincida
     */

    /*public function enqueue(int|string $ndoc, string $producto, ProductionRepository $repo, ProductionOrderRepository $poRepo): void
    {
        try {
            $result = $repo->enqueueFromErp($ndoc, $producto);

            // Refresca índice y tabla de producción
            $this->poIndex  = collect($poRepo->getOrdersBasicInfo())->keyBy('pedido')->toArray();
            $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());

            // Saca localmente el pedido del array $rows (desaparece al instante)
            $pedidoToRemove = null;
            foreach ($this->rows as $row) {
                if (($row['Ndocumento'] ?? null) == $ndoc && ($row['Luminaria'] ?? null) === $producto) {
                    $pedidoToRemove = $row['Pedido'] ?? null;
                    break;
                }
            }
            if ($pedidoToRemove !== null) {
                $this->rows = array_values(array_filter($this->rows, fn($r) => ($r['Pedido'] ?? null) !== $pedidoToRemove));
            }

            if ($result['status'] === 'created') {
                $this->dispatch('toast', type: 'success', message: 'OP encolada: '.$result['model']->ticket_code);
            } else {
                $this->dispatch('toast', type: 'info', message: 'Ya existía en producción: '.$result['model']->ticket_code);
            }
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'No se pudo encolar la OP.');
        }
    }*/

    public function enqueue(int|string $ndoc, string|array $productos, ProductionRepository $repo, ProductionOrderRepository $poRepo, ?string $observacion = null): void
    {
        try {
            // Asegurar array
            $productos = is_array($productos) ? $productos : [$productos];

            $tickets = [];

            // Procesar cada producto individualmente
            foreach ($productos as $producto) {
                $result = $repo->enqueueFromErp($ndoc, $producto);

                if (!empty($observacion) && isset($result['model']->id_production_order)) {
                    \DB::table('production_orders')
                        ->where('id_production_order', $result['model']->id_production_order)
                        ->update(['obsv_production_order' => $observacion]);
                }

                // Guardar tickets para el correo
                $tickets[] = $result['model']->ticket_code ?? '—';

                // Quitar de la tabla local
                $this->rows = array_values(
                    array_filter($this->rows, function ($r) use ($ndoc, $producto) {
                        return !(($r['Ndocumento'] ?? null) == $ndoc && ($r['Luminaria'] ?? null) === $producto);
                    }),
                );
            }

            // Refrescar UI
            $this->poIndex = collect($poRepo->getOrdersBasicInfo())->keyBy('pedido')->toArray();
            $this->prodRows = $poRepo->getOrdersForTable($this->prodFilters());

            // ----------------------------
            //  ARMAR LISTA DE PRODUCTOS
            // ----------------------------
            $luminarias = json_decode($result['model']->luminaria, true);


            $listaProductos = "<ul style='margin:0;padding-left:18px;'>";
            foreach ($luminarias ?? [] as $p) {
                $listaProductos .= '<li>' . e($p) . '</li>';
            }
            $listaProductos .= '</ul>';

            // Lista de tickets
            $listaTickets = implode(', ', $tickets);

            // ----------------------------
            //  CORREO
            // ----------------------------
            $asunto = "Nuevas OP procesadas: {$listaTickets}";

            $mensaje = "
            Se han procesado las siguientes Órdenes de Producción:<br><br>

            <b>Pedido ERP:</b> {$ndoc}<br>
            <b>Tickets:</b> {$listaTickets}<br>
            <b>Productos:</b><br>
            {$listaProductos}
            <br>
            <b>Observación:</b> {$observacion}<br><br>

            Este mensaje fue generado automáticamente desde el sistema de producción D-LUX.
        ";

            Mail::html($mensaje, function ($msg) use ($asunto) {
                $msg->to('sistemas1@dlux.com.co')->subject($asunto)->from('no-reply@dlux.com.co', 'Sistema Producción D-LUX');
            });

            // Notificación
            $this->dispatch('toast', type: 'success', message: 'Pedido enviado a producción correctamente.');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', type: 'error', message: 'No se pudo encolar la OP.');
        }
    }

    public function confirmObservation()
    {
        $this->enqueue($this->ndocTemp, $this->productoTemp, app(\App\Repository\ProductionRepository::class), app(\App\Repository\ProductionOrderRepository::class), trim($this->observationText) ?: null);

        $this->dispatch('ui:hide-observation-pendientes');
        $this->showObservationModal = false;

        $this->dispatch('toast', type: 'success', message: 'Pedido enviado a producción correctamente.');
    }
}