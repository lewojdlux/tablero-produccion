<?php

namespace App\Livewire\Order;

use App\Models\InstaladorModel;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;


// Facades
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;



// models
use App\Models\OrderWorkModel;

class WorkOrdersList extends Component
{


    use WithPagination;

    public string $search = '';
    public string $sortField = 'n_documento';
    public string $sortDir = 'asc';
    public int $perPage = 10;

    // Se ejecuta cada vez que se actualiza una propiedad pública
    public function updated($prop)
    {
        //
    }

    // Lógica para ordenar por un campo específico
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDir = 'asc';
        }

        $this->sortField = $field;
    }


    // Cuando se actualiza el valor de search, resetea la paginación a la página 1
    public function updatingSearch()
    {
        $this->resetPage();
    }

    // Cuando se actualiza el valor de perPage, resetea la paginación a la página 1
    public function updatingPerPage()
    {
        $this->resetPage();
    }




    // Renderiza la vista con los datos necesarios
    #[Layout('layouts.app')]
    public function render()
    {

        $IdInstalador = InstaladorModel::where('identificador_usuario', Auth::user()->id)->first();
        $Id = $IdInstalador->id_instalador;

        $workOrders = OrderWorkModel::with('instalador')->where('tecnico_work_orders', $Id)->paginate(15);
        dd($workOrders);

        return view('livewire.order.work-orders-list', [
            'workOrders' => $workOrders,
        ]);
    }
}
