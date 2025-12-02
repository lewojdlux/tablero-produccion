<?php

namespace App\Livewire\Order;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Illuminate\Pagination\LengthAwarePaginator;

use Livewire\Attributes\On;
/* services */
use App\Services\ProductionOrderService;

/* repository */
use App\Repository\ProductionRepository;
use App\Repository\ProductionOrderRepository;

class PendingProduction extends Component
{


    use WithPagination;
    protected ProductionOrderService $service;
    protected ProductionOrderRepository $poRepo;

    public array $detail = ['header' => [], 'lines' => []];


    protected $paginationTheme = 'tailwind';
    public int $perPage = 15;
    public int $prodPerPage = 15;

    #[Layout('layouts.app')]

    // Si necesitas filtros:

    #[Url(as: 'ano')]
    public ?int $ano = null;
    #[Url(as: 'mes')]
    public ?int $mes = null;
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



    public array $prodRows = []; // 👈 listado de production_orders para la pestaña
    public array $rows = []; // arreglo de rows

    public bool $showDetail = false;


    // === Filtros PRODUCCIÓN ===
    public string $prodQ = '';            // buscador global: ticket, pedido, luminaria, vendedor
    public string $prodPedido = '';
    public string $prodAsesor = '';
    public string $prodProducto = '';     // mapea a columna 'luminaria'
    public string $prodStatus = '';       // queued|in_progress|done|approved
    //public ?int $prodAno = null;          // filtra por year(updated_at)
    //public ?int $prodMes = null;          // filtra por month(updated_at)
    public ?string $prodStart = null;     // fecha desde (YYYY-MM-DD) sobre updated_at
    public ?string $prodEnd = null;       // fecha hasta  (YYYY-MM-DD) sobre updated_at





    // Livewire llama a boot() y soporta DI aquí
    public function boot(ProductionOrderService $service, ProductionOrderRepository $poRepo): void
    {
        $this->service = $service;
        $this->poRepo  = $poRepo;
    }

    public function mount(  ): void
    {
       $this->reloadProduction();
    }



    public function clearProductionFilters(): void
    {
        $this->reset([
            'prodStart','prodEnd',
            'prodStatus','prodQ','prodPedido','prodAsesor','prodProducto',
        ]);

        $this->resetPage();      // importante: resetea paginado
        $this->reloadProduction();

    }




    public function updated($prop): void
    {
        if (in_array($prop, ['perPage'])) {
            $this->resetPage();
        }

        // Si usas wire:model.live en alguno, puedes recargar al vuelo:
        if (in_array($prop, ['prodStart','prodEnd', 'prodStatus', 'prodQ', 'prodPedido', 'prodAsesor', 'prodProducto'])) {
            // resetea la paginación de la pestaña producción
            $this->resetPage();
            // recarga desde MySQL con los filtros
            $this->reloadProduction();
        }
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

    // FILTROS para producción
    protected function prodFilters(): array
    {
        return [
            'q'         => $this->prodQ,
            'pedido'    => $this->prodPedido,
            'asesor'    => $this->prodAsesor,
            'luminaria' => $this->prodProducto,  // tu repo espera 'luminaria'
            'status'    => $this->prodStatus,
            'start'     => $this->prodStart,
            'end'       => $this->prodEnd,
        ];
    }


     // Recarga solo la pestaña de producción (si quieres usar un poll separado)

    #[On('production.saved')]
    public function reloadProduction(): void
    {
        $this->prodRows = $this->poRepo->getOrdersForTable($this->prodFilters());
    }


    public function openDetailsByDoc(ProductionRepository $repo, string|int $ndoc, string $producto): void
    {
        $this->selectedDoc = $ndoc;
        $this->selectedProduct = $producto;

        // 1) Carga el detalle desde tu repo/servicio
        $this->detail = $repo->getOrderDetail($ndoc,  $producto); // <-- implementa este método en tu repo

        // 2) Marca visible y dispara evento para que Bootstrap lo muestre
        /*$this->showDetail = true;
        $this->dispatch('show-order-modal');*/
        // Enviar al hijo (OrderDetailModal) y él abre el modal
        $this->dispatch('open-order-detail', detail: $this->detail, selectedDoc: $ndoc);
    }

    public function openDetailsByFicha(ProductionRepository $repo,string|int $ndoc):void{
        $this->selectedDoc = $ndoc;

        $this->detail = $repo->getOrderDetailFicha($ndoc); // <-- implementa este método en tu repo


        $this->dispatch('open-order-detail-ficha', detail: $this->detail, selectedDoc: $ndoc);
    }


    public function render()
    {

        $page = $this->getPage(); // página actual de Livewire
        $prodPerPage = $this->prodPerPage;
        $total = count($this->prodRows);
        $items = array_slice($this->prodRows, ($page - 1) * $prodPerPage, $prodPerPage);

        $prodRowsPaginate = new LengthAwarePaginator($items, $total, $prodPerPage, $page, ['path' => request()->url(), 'query' => request()->query()]);



        return view('livewire.order.pending-production',
        [
            'prodRowsPaginated' => $prodRowsPaginate
        ]);
    }
}