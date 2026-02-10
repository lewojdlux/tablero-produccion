<?php

namespace App\Livewire\User;

use App\Models\InstaladorModel;
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
    public ?string $identificador_instalador = null;
    public ?int $perfilInstaladorId = null;
    public ?int $perfilAsesorId = null;

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

        // Detecta INSTALADOR
        $instalador = collect($this->perfiles)->first(function ($p) {
            $nombre = is_array($p) ? $p['nombre_perfil'] ?? '' : $p->nombre_perfil ?? '';
            return mb_strtolower($nombre) === 'instalador';
        });
        $this->perfilInstaladorId = $instalador ? (int) (is_array($instalador) ? $instalador['id_perfil_usuario'] : $instalador->id_perfil_usuario) : null;
    }

    protected function rules(): array
    {
        $isCreate = $this->editingId === null;

        $rules = [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($this->editingId)],
            'username' => ['required', 'string', 'max:60', Rule::unique('users', 'username')->ignore($this->editingId)],
            'perfil_usuario_id' => ['required', Rule::exists('perfil_usuarios', 'id_perfil_usuario')],
            'estado' => ['boolean'],
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
        ];

        // ASESOR
        if ((int) $this->perfil_usuario_id === (int) $this->perfilAsesorId) {
            $rules['identificador_asesor'] = [$isCreate ? 'required' : 'nullable', 'integer', 'min:1', Rule::unique('users', 'identificador_asesor')->ignore($this->editingId)];
        }

        // INSTALADOR
        if ((int) $this->perfil_usuario_id === (int) $this->perfilInstaladorId) {
            $rules['identificador_instalador'] = ['required', 'integer', 'min:1', Rule::unique('users', 'identificador_instalador')->ignore($this->editingId)];
        }

        return $rules;
    }

    protected function messages(): array
    {
        return [
            'identificador_instalador.unique' => 'Este identificador de instalador ya está registrado.',
            'identificador_instalador.required' => 'El identificador del instalador es obligatorio.',
            'identificador_instalador.integer' => 'El identificador del instalador debe ser un número.',
            'identificador_instalador.min' => 'El identificador del instalador debe ser mayor a cero.',

            'identificador_asesor.unique' => 'Este identificador de asesor ya está registrado.',
            'identificador_asesor.required' => 'El identificador del asesor es obligatorio.',
            'identificador_asesor.integer' => 'El identificador del asesor debe ser un número.',
            'identificador_asesor.min' => 'El identificador del asesor debe ser mayor a cero.',

            // mensajes comunes
            'email.unique' => 'Este correo ya está registrado.',
            'username.unique' => 'Este usuario ya está registrado.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $email = strtolower(trim($this->email));
            $username = strtolower(trim($this->username));

            // Validación: username no puede ser igual al email
            if ($email !== '' && $username !== '' && $email === $username) {
                $v->errors()->add('username', 'El usuario no puede ser igual al email.');
            }

            // Validación extra ASESOR
            if ($this->identificador_asesor !== null) {
                $idAsesorStr = (string) $this->identificador_asesor;

                if ($email === $idAsesorStr) {
                    $v->errors()->add('identificador_asesor', 'El identificador no puede ser igual al email.');
                }

                if ($username === $idAsesorStr) {
                    $v->errors()->add('identificador_asesor', 'El identificador no puede ser igual al usuario.');
                }
            }

            /* =====================================================
            VALIDACIÓN EXTRA PARA INSTALADOR (tabla instalador)
            ===================================================== */

            if ((int) $this->perfil_usuario_id === (int) $this->perfilInstaladorId) {
                $existeInstalador = \App\Models\InstaladorModel::where('identificador_usuario', $this->identificador_instalador)
                    ->when($this->editingId, function ($q) {
                        $usuario = User::find($this->editingId);
                        if ($usuario) {
                            // No considerar el instalador del mismo usuario cuando estamos editando
                            $q->where('identificador_usuario', '!=', $usuario->identificador_instalador);
                        }
                    })
                    ->exists();

                if ($existeInstalador) {
                    $v->errors()->add('identificador_instalador', 'Este identificador de instalador ya está registrado.');
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
        $this->name = $u->name;
        $this->email = $u->email;
        $this->username = $u->username;
        $this->perfil_usuario_id = (int) $u->perfil_usuario_id;
        $this->estado = (bool) $u->estado;

        // asesor
        $this->identificador_asesor = $u->identificador_asesor;

        // INSTALADOR
        $this->identificador_instalador = $u->identificador_instalador;

        $this->password = '';
        $this->password_confirmation = '';

        $this->dispatch('ui:show-user-modal');
    }

    public function close(): void
    {
        $this->dispatch('ui:hide-user-modal');
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->ensureCanManage();
        $this->validate();

        DB::beginTransaction();
        try {
            /* ==========================================================
           DETERMINAR PERFIL
        ========================================================== */
            $esAsesor = (int) $this->perfil_usuario_id === (int) $this->perfilAsesorId;
            $esInstalador = (int) $this->perfil_usuario_id === (int) $this->perfilInstaladorId;

            // LIMPIAR CAMPOS SEGÚN PERFIL
            if ($esAsesor) {
                $this->identificador_instalador = null;
            } elseif ($esInstalador) {
                $this->identificador_asesor = null;
            } else {
                $this->identificador_asesor = null;
                $this->identificador_instalador = null;
            }

            /* ==========================================================
           ARMAR DATA DE USERS
        ========================================================== */
            $data = [
                'name' => $this->name,
                'email' => $this->email,
                'username' => $this->username,
                'perfil_usuario_id' => $this->perfil_usuario_id,
                'estado' => $this->estado,
                'identificador_asesor' => $this->identificador_asesor,
                'identificador_instalador' => $this->identificador_instalador,
            ];

            // TU LÍNEA ORIGINAL
            if (!empty($this->password)) {
                $data['password'] = $this->password; // hasheado por mutator
            }

            /* ==========================================================
           EDITAR USUARIO
        ========================================================== */
            if ($this->editingId) {
                if ($this->editingId === auth()->id()) {
                    DB::rollBack();
                    $this->dispatch('notify', type: 'warning', message: 'No puedes editar tu propio usuario aquí.');
                    return;
                }

                $oldUser = User::find($this->editingId);
                $oldInstallerId = $oldUser->identificador_instalador;

                // Hash manual si trae password
                if (!empty($this->password)) {
                    $data['password'] = Hash::make($this->password);
                } else {
                    unset($data['password']);
                }

                // *** ACTUALIZA USERS ***
                User::whereKey($this->editingId)->update($data);

                /* ==========================================================
               MANEJO TABLA INSTALADOR
            ========================================================== */
                if ($esInstalador) {
                    if ($oldInstallerId) {
                        if ($oldInstallerId != $this->identificador_instalador) {
                            // CAMBIÓ IDENTIFICADOR → Actualizarlo
                            InstaladorModel::where('identificador_usuario', $oldInstallerId)->update([
                                'identificador_usuario' => $this->identificador_instalador,
                                'nombre_instalador' => $this->name,
                                'email_instalador' => $this->email,
                                'status' => 'active',
                            ]);
                        } else {
                            // NO CAMBIÓ IDENTIFICADOR → solo actualizar datos
                            InstaladorModel::where('identificador_usuario', $oldInstallerId)->update([
                                'nombre_instalador' => $this->name,
                                'email_instalador' => $this->email,
                                'status' => 'active',
                            ]);
                        }
                    } else {
                        // Si no tenía registro, crear uno
                        InstaladorModel::create([
                            'identificador_usuario' => $this->identificador_instalador,
                            'nombre_instalador' => $this->name,
                            'email_instalador' => $this->email,
                            'celular_instalador' => null,
                            'status' => 'active',
                        ]);
                    }
                } else {
                    // Si ya no es instalador → borrar registro existente
                    if ($oldInstallerId) {
                        InstaladorModel::where('identificador_usuario', $oldInstallerId)->update([
                            'status' => 'inactive', // inactivo, no borrar
                        ]);
                    }
                }

                DB::commit();

                $this->modalFlashType = 'success';
                $this->modalFlash = 'Usuario actualizado.';
                $this->js("setTimeout(() => Livewire.dispatch('ui:clear-flash'), 3000)");
                return;
            }

            /* ==========================================================
           CREAR USUARIO
        ========================================================== */
            $data['password'] ??= $this->password;

            // *** CREAR USERS ***
            $user = User::create($data);

            // SI ES INSTALADOR → CREAR REGISTRO
            if ($esInstalador) {
                InstaladorModel::create([
                    'nombre_instalador' => $this->name,
                    'celular_instalador' => null,
                    'email_instalador' => $this->email,
                    'identificador_usuario' => $this->identificador_instalador,
                    'status' => 'active',
                ]);
            }

            DB::commit();

            $this->modalFlashType = 'success';
            $this->modalFlash = 'Usuario registrado.';
            $this->resetForm();
            $this->editingId = null;
            $this->resetPage();
            $this->js("setTimeout(() => Livewire.dispatch('ui:clear-flash'), 3000)");
        } catch (\Throwable $e) {
            DB::rollBack();

            // Mostrar error visible en el modal
            $this->modalFlashType = 'danger';
            $this->modalFlash = 'Error: ' . $e->getMessage();
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


    public function updatedSearch(): void
    {
        $this->resetPage();
    }


    public function updatedPerPage(): void
    {
        $this->resetPage();
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