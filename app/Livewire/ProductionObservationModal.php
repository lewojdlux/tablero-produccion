<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductionObservationModal extends Component
{
    public $docId;
    public $ticket;
    public $observation = '';

    protected $listeners = ['production.addObservation' => 'open'];

    public function open($data = null)
    {
        $this->resetValidation();
        $this->reset(['observation']);

        // ✅ Soporta ambos formatos: objeto, array o valores sueltos
        if (is_array($data)) {
            $this->docId = $data['docId'] ?? null;
            $this->ticket = $data['ticket'] ?? '—';
        } elseif (is_object($data)) {
            $this->docId = $data->docId ?? null;
            $this->ticket = $data->ticket ?? '—';
        } elseif (is_numeric($data)) {
            // Caso de que venga solo el docId
            $this->docId = $data;
        }

        // 🔹 Mostrar el modal
        $this->dispatch('ui:show-observation-modal');
    }

    public function save()
    {
        $this->validate([
            'observation' => 'required|string|max:500',
        ]);

        dd($this->docId);

        DB::table('production_orders')->where('id_production_order', $this->docId)->update([
            'observation' => $this->observation,
        ]);

        $this->dispatch('notification', [
            'type' => 'success',
            'message' => '✅ Observación guardada correctamente.'
        ]);

        // 🔹 Cierra el modal por JS
        $this->dispatch('ui:hide-observation-modal');
    }

    public function render()
    {
        return view('livewire.production-observation-modal');
    }
}