<?php

namespace App\Livewire\User;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortField = 'name';
    public string $sortDir = 'asc';
    public int $perPage = 10;

    public bool $showModal = false;
    public ?int $editingId = null;

    public string $name = '';
    public string $email = '';
    public string $username = '';
    public array $perfiles = [];
    public int $perfil_usuario_id = 2;
    public bool $estado = true;
    public ?string $identificador_asesor = null;
    public ?int $perfilAsesorId = null;

    public ?string $identificador_instalador = null;
    public ?int $perfilInstaladorId = null;

    public string $password = '';
    public string $password_confirmation = '';

    // Mensaje dentro del modal
    public ?string $modalFlash = null;
    public string $modalFlashType = 'success'; // success / danger / warning / info

    public function mount(): void
    {
        $this->perfiles = DB::table('perfil_usuarios')->select('id_perfil_usuario', 'nombre_perfil')->orderBy('nombre_perfil')->get()->all();

        // Detecta el ID del perfil “Asesor” por nombre
        $asesor = collect($this->perfiles)->first(function ($p) {
            $nombre = is_array($p) ? $p['nombre_perfil'] ?? '' : $p->nombre_perfil ?? '';
            return str_contains(mb_strtolower($nombre), 'asesor'); // “asesor”, “asesores”, etc.
        });

        $this->perfilAsesorId = $asesor ? (int) (is_array($asesor) ? $asesor['id_perfil_usuario'] : $asesor->id_perfil_usuario) : null; // si no existe, nunca mostrará el campo
    
        $instalador = collect($this->perfiles)->first(function ($p) {
            $nombre = is_array($p) ? $p['nombre_perfil'] ?? '' : $p->nombre_perfil ?? '';
            $nombre = mb_strtolower($nombre);

            return str_contains($nombre, 'Instalador');
        });

        $this->perfilInstaladorId = collect($this->perfiles)
        ->first(function ($p) {
            return strtolower($p->nombre_perfil) === 'instalador';
        })->id_perfil_usuario ?? null;



    }

    protected function rules(): array
    {
        $isCreate = $this->editingId === null;

        // Regla dinámica para identificador_asesor
        $idRules = [
            // si es create y el perfil es Asesor => required, si no => nullable
            $this->perfilAsesorId !== null && (int) $this->perfil_usuario_id === (int) $this->perfilAsesorId ? ($isCreate ? 'required' : 'nullable') : 'nullable',
            'integer',
            'min:1',
            Rule::unique('users', 'identificador_asesor')->ignore($this->editingId),
        ];

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->editingId)],
            'username' => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($this->editingId)],
            'perfil_usuario_id' => ['required', Rule::exists('perfil_usuarios', 'id_perfil_usuario')],
            'estado' => ['boolean'],
            'identificador_asesor' => $idRules,
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        if ((int) $this->perfil_usuario_id === (int) $this->perfilInstaladorId) {
            $rules['identificador_instalador'] = [
                $isCreate ? 'required' : 'nullable',
                'integer',
                'min:1',
                Rule::unique('users', 'identificador_instalador')->ignore($this->editingId),
            ];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [

            // ====== GENERALES ======
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede superar 120 caracteres.',

            'email.required' => 'El email es obligatorio.',
            'email.email' => 'Debe ingresar un email válido.',
            'email.unique' => 'Este email ya está registrado.',
            'email.max' => 'El email no puede superar 190 caracteres.',

            'username.required' => 'El usuario es obligatorio.',
            'username.unique' => 'Este usuario ya está registrado.',
            'username.max' => 'El usuario no puede superar 60 caracteres.',

            'perfil_usuario_id.required' => 'Debe seleccionar un perfil.',
            'perfil_usuario_id.exists' => 'El perfil seleccionado no es válido.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',

            // ====== ASESOR ======
            'identificador_asesor.required' => 'El identificador de asesor es obligatorio.',
            'identificador_asesor.integer' => 'El identificador de asesor debe ser numérico.',
            'identificador_asesor.min' => 'El identificador de asesor debe ser mayor a 0.',
            'identificador_asesor.unique' => 'Este identificador de asesor ya está registrado.',

            // ====== INSTALADOR ======
            'identificador_instalador.required' => 'El código de instalador es obligatorio.',
            'identificador_instalador.integer' => 'El código de instalador debe ser numérico.',
            'identificador_instalador.min' => 'El código de instalador debe ser mayor a 0.',
            'identificador_instalador.unique' => 'Este código de instalador ya está registrado.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $email = strtolower(trim($this->email));
            $username = strtolower(trim($this->username));
            $idAsesorStr = $this->identificador_asesor !== null ? (string) $this->identificador_asesor : null;
            $idInstaladorStr = $this->identificador_instalador !== null ? (string) $this->identificador_instalador : null;

            // Validación: username no puede ser igual al email
            if ($email !== '' && $username !== '' && $email === $username) {
                $v->errors()->add('username', 'El usuario no puede ser igual al email.');
            }

            // Validaciones asesor
            if ($idAsesorStr !== null) {
                if ($email !== '' && $email === $idAsesorStr) {
                    $v->errors()->add('identificador_asesor', 'El identificador no puede ser igual al email.');
                }
                if ($username !== '' && $username === $idAsesorStr) {
                    $v->errors()->add('identificador_asesor', 'El identificador no puede ser igual al usuario.');
                }
            }

            // Validaciones instalador
            if ($idInstaladorStr !== null) {

                if ($email !== '' && $email === $idInstaladorStr) {
                    $v->errors()->add('identificador_instalador', 'El código no puede ser igual al email.');
                }

                if ($username !== '' && $username === $idInstaladorStr) {
                    $v->errors()->add('identificador_instalador', 'El código no puede ser igual al usuario.');
                }

                // 🔥 NO puede existir como asesor
                $existeEnAsesor = User::where('identificador_asesor', $idInstaladorStr)
                    ->when($this->editingId, fn($q) => $q->where('id', '!=', $this->editingId))
                    ->exists();

                if ($existeEnAsesor) {
                    $v->errors()->add(
                        'identificador_instalador',
                        'Este código ya está registrado como asesor.'
                    );
                }
            }
        });
    }

    protected function ensureCanManage(): void
    {
        $me = Auth::user();
        if (!$me || !in_array((int) $me->perfil_usuario_id, [1, 2], true)) {
            abort(403, 'No autorizado.');
        }
    }

    // si cambia el perfil y ya no es Asesor, limpia el identificador
    public function updatedPerfilUsuarioId($value): void
    {
        if ((int) $value !== (int) $this->perfilAsesorId) {
            $this->identificador_asesor = null;
        }

        if ((int) $value !== (int) $this->perfilInstaladorId) {
            $this->identificador_instalador = null;
        }
    }

    /* ================= Acciones ================= */

    public function create(): void
    {
        $this->ensureCanManage();
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
        $this->dispatch('ui:show-user-modal');
    }

    public function edit(int $id): void
    {
        $this->ensureCanManage();

        $u = User::findOrFail($id);

        $this->editingId = $u->id;
        $this->name = (string) $u->name;
        $this->email = (string) $u->email;
        $this->username = (string) ($u->username ?? '');
        $this->perfil_usuario_id = (int) ($u->perfil_usuario_id ?? 2);
        $this->estado = (bool) ($u->estado ?? true);
        $this->identificador_asesor = $u->identificador_asesor;
        $this->identificador_instalador = $u->identificador_instalador;
        

        $this->password = '';
        $this->password_confirmation = '';

        $this->showModal = true;
        $this->dispatch('ui:show-user-modal');
    }

    public function close(): void
    {
        $this->resetErrorBag();
        $this->resetValidation();
        $this->modalFlash = null; 

         
        $this->dispatch('ui:hide-user-modal');
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->ensureCanManage();
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'username' => $this->username,
            'perfil_usuario_id' => $this->perfil_usuario_id,
            'estado' => $this->estado,
            'identificador_asesor' => $this->identificador_asesor,
            'identificador_instalador' => $this->identificador_instalador,
        ];

        if (!empty($this->password)) {
            $data['password'] = $this->password; // cast hashed en el modelo
        }

        if ($this->editingId) {
            if ($this->editingId === auth()->id()) {
                $this->dispatch('notify', type: 'warning', message: 'No puedes editar tu propio usuario aquí.');
                return;
            }


            // ⚠️ Con update() NO aplica el cast "hashed", así que hasheamos aquí.
            if (!empty($this->password)) {
                $data['password'] = Hash::make($this->password);
            } else {
                unset($data['password']); // no tocar password si vino vacío
            }

            User::whereKey($this->editingId)->update($data);

             // 1) Mensaje dentro del modal (no cerrar, no resetear form)
            $this->modalFlashType = 'success';
            $this->modalFlash = 'Usuario actualizado.';

            // 2) Resetear formulario, pero mantener el modal abierto
            $this->js("setTimeout(() => Livewire.dispatch('ui:clear-flash'), 3000)");

        } else {
            $data['password'] ??= $this->password;
            User::create($data);

            if ((int) $this->perfil_usuario_id === (int) $this->perfilInstaladorId) {

                \App\Models\InstaladorModel::create([
                    'identificador_usuario' => $this->identificador_instalador,
                    'nombre_instalador' => $this->name,
                    'email_instalador' => $this->email,
                    'status' => 'active',
                ]);
            }


            $this->modalFlashType = 'success';
            $this->modalFlash = 'Usuario registrado.';

            // 2) Resetear formulario, pero mantener el modal abierto
            $this->resetForm();
            $this->editingId = null; // mantiene 'Nuevo usuario'


            // 4) Si quieres refrescar lista paginada:
            $this->resetPage();

            // Limpia el mensaje solo
            $this->js("setTimeout(() => Livewire.dispatch('ui:clear-flash'), 3000)");


        }

    }

    public function confirmDelete(int $id): void
    {
        $this->dispatch('open-delete-dialog', id: $id);
    }

    #[On('delete-confirmed')]
    public function delete(int $id): void
    {
        $this->ensureCanManage();
        User::whereKey($id)->delete();
        $this->dispatch('notify', type: 'success', message: 'Usuario eliminado.');
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $this->ensureCanManage();

        if ($id === auth()->id()) {
            $this->modalFlash = 'No puedes cambiar tu propio estado aquí.';
            return;
        }

        $u = User::findOrFail($id);
        $u->estado = !(bool) ($u->estado ?? true);
        $u->save();

        $this->modalFlash = 'Estado del usuario actualizado.';

        $this->js("setTimeout(() => Livewire.dispatch('ui:clear-flash'), 3000)");
    }

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

    protected function resetForm(): void
    {
        $this->name = '';
        $this->email = '';
        $this->username = '';
        $this->perfil_usuario_id = 2;
        $this->estado = true;
        $this->identificador_asesor = null;
        $this->identificador_instalador = null;
        $this->password = '';
        $this->password_confirmation = '';
    }

    /* ==== Eventos disparados desde JS con Livewire.dispatch(...) ==== */

    #[On('ui:open-create')]
    public function openCreateFromJs(): void
    {
        $this->create();
    }

    #[On('ui:open-edit')]
    public function openEditFromJs(int $id): void
    {
        $this->edit($id);
    }

    #[On('ui:clear-flash')]
    public function clearFlash(): void
    {
        $this->modalFlash = null;
    }

    /* ================= Render ================= */
    #[Layout('layouts.app')]
    public function render()
    {
        $meId = auth()->id();

        $users = User::query()
            ->when($meId, fn($q) => $q->whereKeyNot($meId))
            ->when($this->search !== '', function ($q) {
                $s = '%' . str_replace(' ', '%', $this->search) . '%';
                $q->where(fn($qq) => $qq->where('name', 'like', $s)->orWhere('email', 'like', $s)->orWhere('username', 'like', $s));
            })
            ->when(in_array($this->sortField, ['name', 'email', 'username', 'perfil_usuario_id', 'estado', 'created_at'], true), fn($q) => $q->orderBy($this->sortField, $this->sortDir), fn($q) => $q->orderBy('name', 'asc'))
            ->paginate($this->perPage);

        return view('livewire.user.index', [
            'users' => $users,
        ])->title('Gestión de usuarios');
    }
}