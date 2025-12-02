<?php

namespace App\Livewire\Order;

use App\Repository\ProductionRepository;
use Livewire\Attributes\On;
use Livewire\Component;

class OrderDetailModal extends Component
{
    public array $detail = ['header' => [], 'lines' => []];
    public int|string|null $selectedDoc = null;
    public array $cachedDetail = []; // 👈 Debe ser PUBLIC, no protected
    public bool $showingFicha = false; // 👈 Controla el modo “insumos”

    #[On('open-order-detail')]
    public function open(array $detail, int|string|null $selectedDoc = null): void
    {
        $this->detail = $detail;
        $this->cachedDetail = $detail; // ✅ Guarda el detalle original
        $this->selectedDoc = $selectedDoc;
        $this->showingFicha = false;
        $this->dispatch('show-order-modal');
    }

    #[On('close-order-detail')]
    public function close(): void
    {
        $this->dispatch('hide-order-modal');
        $this->reset(['detail', 'selectedDoc', 'cachedDetail', 'showingFicha']);
        $this->detail = ['header' => [], 'lines' => []];
    }

    #[On('open-ficha-desde-modal')]
    public function openDetailsByFichaProducto(ProductionRepository $repo, string|int $ndoc, string $producto): void
    {
        $this->selectedDoc = $ndoc;
        $this->detail = $repo->getOrderDetailFichaProducto($ndoc, $producto);
        $this->showingFicha = true; // 👈 Activa vista de ficha
    }

    public function volverDetalle(): void
    {
        // 👈 Restaura los datos originales
        $this->detail = $this->cachedDetail;
        $this->showingFicha = false;
    }

    public function render()
    {
        return view('livewire.order.order-detail-modal');
    }
}