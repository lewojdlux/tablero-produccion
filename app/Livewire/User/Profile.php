<?php

namespace App\Livewire\User;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

class Profile extends Component
{
    public string $name = '';
    public string $email = '';

    public function mount()
    {
        $u = Auth::user();
        $this->name  = $u->name ?? '';
        $this->email = $u->email ?? '';
    }

    protected function rules(): array
    {
        return [
            'name'  => ['required','string','min:2'],
            'email' => ['required','email', Rule::unique('users','email')->ignore(auth()->id())],
        ];
    }

    public function updateProfileInformation()
    {
        $this->validate();

        $u = Auth::user();
        $u->name  = $this->name;
        $u->email = $this->email;
        $u->save();

        $this->dispatch('profile-updated');
        session()->flash('status', 'profile-updated');
    }

    #[Layout('layouts.app')]
    #[Title('Perfil')]
    public function render()
    {
        return view('livewire.user.profile');
    }
}
