<?php

namespace App\Livewire\Order;

use App\Models\DetailProductionOrder;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str; // ✅ para Str::...
use Illuminate\Support\Facades\Notification as Notify; // ya lo tienes
use App\Notifications\ProductionStatusChanged; // ya lo tienes
use App\Models\ProductionStatusNotification; // tabla de dedupe
use App\Models\User;

class ProductionActionModal extends Component
{
    /** Control modal */
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

    protected function rules(): array
    {
        if ($this->readOnly) {
            return [];
        }

        $sameDay = $this->fecha_inicial_produccion && $this->fecha_final_produccion && $this->fecha_inicial_produccion === $this->fecha_final_produccion;

        // ⚠ No valides si es readOnly (por si alguien intenta forzar "Guardar")
        return [
            'nuevo_status' => ['required', 'in:queued,in_progress,done,approved'],

            'fecha_inicial_produccion' => ['nullable', 'date'],
            'fecha_final_produccion' => ['nullable', 'date'],

            // 👇 Requerir horas solo si es el mismo día
            'hora_inicio_produccion' => array_filter(['nullable', 'date_format:H:i', $sameDay ? 'required' : null]),
            'hora_fin_produccion' => array_filter(['nullable', 'date_format:H:i', $sameDay ? 'required' : null]),

            'dias_produccion' => ['nullable', 'integer', 'min:0'],
            'horas_produccion' => ['nullable', 'integer', 'min:0'],
            'minutos_produccion' => ['nullable', 'integer', 'min:0'],
            'segundos_produccion' => ['nullable', 'integer', 'min:0'],
            'cantidad_luminarias' => ['nullable', 'integer', 'min:0'],
            'observacion_produccion' => ['nullable', 'string'],
        ];

        // ⚠ Ya NO exigimos horas cuando hay fechas. Validamos lógica en save().
        return $rules;
    }

    /** Escucha evento del padre para abrir el modal con payload */
    #[On('production.open')]
    public function open(int $docId, ?string $action = null): void
    {
        $this->resetForm();
        $this->docId = $docId;
        $this->action = $action;

        // 👇 Define solo-lectura si perfil es 5 (asesor)
        $perfil = (int) (Auth::user()->perfil_usuario_id ?? 0);
        $this->readOnly = $perfil === 5 || $perfil === 2; // admin o asesor

        // mapea acción → estado del enum (sin 'paused')
        $this->nuevo_status = match ($this->action) {
            'programar' => 'queued',
            'empezar' => 'in_progress',
            'finalizar' => 'done',
            'aprobar' => 'approved',
            'reabrir' => 'in_progress',
            default => null,
        };

        $now = now(); // usa timezone de config/app.php
        $this->fecha_inicial_produccion = $now->toDateString(); // YYYY-MM-DD
        $this->hora_inicio_produccion = null; //$now->format('H:i'); // HH:MM

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

    public function close(): void
    {
        $this->visible = false;
        $this->dispatch('ui:hide-production-modal');
    }

    /** ============== AUTOCÁLCULOS ============== */
    public function updatedFechaInicialProduccion(): void
    {
        // normalmente readonly, pero por si acaso:
        $this->validaCronologia(live: true);
        $this->recalcularPorFechas();
        $this->recalcularPorFechasYHoras();
    }
    public function updatedFechaFinalProduccion(): void
    {
        $this->validaCronologia(live: true);
        $this->recalcularPorFechas();
        $this->recalcularPorFechasYHoras();
    }
    public function updatedHoraInicioProduccion(): void
    {
        $this->hora_inicio_produccion = $this->normalizeTime($this->hora_inicio_produccion);
        $this->validaCronologia(live: true);
        $this->recalcularPorFechasYHoras();
    }
    public function updatedHoraFinProduccion(): void
    {
        $this->hora_fin_produccion = $this->normalizeTime($this->hora_fin_produccion);
        $this->validaCronologia(live: true);
        $this->recalcularPorFechasYHoras();
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

    protected function setFieldError(string $field, string $message): void
    {
        $this->resetErrorBag($field);
        $this->addError($field, $message);
    }

    protected function validaCronologia(bool $live = false): bool
{
    $ok = true;
    $now = now();

    // 1) Normalizar horas (acepta “3 pm”, etc.)
    $this->hora_inicio_produccion = $this->normalizeTime($this->hora_inicio_produccion);
    $this->hora_fin_produccion = $this->normalizeTime($this->hora_fin_produccion);

    // 2) Fechas obligatorias
    if (!$this->fecha_inicial_produccion) {
        $this->setFieldError('fecha_inicial_produccion', 'Debes indicar la fecha inicial.');
        return false;
    }

    if (!$this->fecha_final_produccion) {
        if ($live) {
            $this->setFieldError('fecha_final_produccion', 'Debes indicar la fecha final.');
        }
        return false;
    }

    $startDate = Carbon::parse($this->fecha_inicial_produccion)->startOfDay();
    $endDate   = Carbon::parse($this->fecha_final_produccion)->startOfDay();

    // 3) Si es el mismo día, las horas son obligatorias
    if ($endDate->isSameDay($startDate)) {
        if (empty($this->hora_inicio_produccion)) {
            $this->setFieldError('hora_inicio_produccion', 'Cuando las fechas son iguales, debes indicar la hora inicial.');
            $ok = false;
        } else {
            $this->resetErrorBag('hora_inicio_produccion');
        }

        if (empty($this->hora_fin_produccion)) {
            $this->setFieldError('hora_fin_produccion', 'Cuando las fechas son iguales, debes indicar la hora final.');
            $ok = false;
        } else {
            $this->resetErrorBag('hora_fin_produccion');
        }
    }

    // 4) Regla actualizada: solo validar coherencia entre inicio y fin (no con hoy)
    if ($endDate->lt($startDate)) {
        $this->setFieldError('fecha_final_produccion', 'La fecha final no puede ser menor a la fecha inicial.');
        $ok = false;
    } else {
        $this->resetErrorBag('fecha_final_produccion');
    }

    // 5) Construir datetimes para comparar cronología con horas
    $startTime = $this->hora_inicio_produccion ?: '00:00';
    $start = Carbon::parse($startDate->toDateString() . ' ' . $startTime)->seconds(0);

    if ($this->hora_fin_produccion) {
        $end = Carbon::parse($endDate->toDateString() . ' ' . $this->hora_fin_produccion)->seconds(0);

        // b) Si es el mismo día: fin > inicio
        if ($endDate->isSameDay($startDate)) {
            if ($end->lessThanOrEqualTo($start)) {
                $this->setFieldError('hora_fin_produccion', 'La hora final debe ser mayor a la hora inicial.');
                $ok = false;
            } else {
                $this->resetErrorBag('hora_fin_produccion');
            }
        } else {
            // c) Si fecha final es posterior, hora fin no puede ser menor que hora inicio
            $startClock = Carbon::createFromTimeString($startTime);
            $endClock = Carbon::createFromTimeString($this->hora_fin_produccion);

            if ($endClock->lt($startClock)) {
                $this->setFieldError('hora_fin_produccion', 'Cuando la fecha final es posterior, la hora final no puede ser menor a la hora inicial.');
                $ok = false;
            } else {
                $this->resetErrorBag('hora_fin_produccion');
            }
        }
    } else {
        // Sin hora fin, validar solo fechas
        if ($endDate->lt($startDate)) {
            $this->setFieldError('fecha_final_produccion', 'La fecha final no puede ser menor a la fecha inicial.');
            $ok = false;
        } else {
            $this->resetErrorBag('fecha_final_produccion');
        }
    }

    return $ok;
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

    private function normalizeTime(?string $val): ?string
    {
        if (!$val) {
            return null;
        }
        try {
            // Acepta "3:00 pm", "03:00 p. m.", "15:00", etc. y lo deja en 24h HH:MM
            return \Carbon\Carbon::parse(trim($val))->format('H:i');
        } catch (\Throwable $e) {
            return $val; // deja el original si parsear falla; la validación lo atrapará
        }
    }

    /**
     * Devuelve el correo del asesor asociado a la orden.
     * Ajusta los nombres de campo según tu tabla de production_orders.
     */
    protected function resolveAsesorEmail(ProductionOrder $order): ?string
    {
        Log::info('resolveAsesorEmail: entrada', [
            'order_id' => $order->id_production_order,
            'vendedor' => $order->vendedor,
            'vendedor_username' => $order->vendedor_username,
        ]);

        // 1) Intento por código del vendedor (lo ideal): users.username o users.identificador_asesor
        $code = trim((string) $order->vendedor_username);
        if ($code !== '') {
            $user = User::query()->where('username', $code)->orWhere('identificador_asesor', $code)->first();

            Log::info('resolveAsesorEmail: lookup por código', [
                'code' => $code,
                'found_user' => $user?->id,
                'found_email' => $user?->email,
            ]);

            if (!empty($user?->email)) {
                return $user->email;
            }
        } else {
            Log::warning('resolveAsesorEmail: vendedor_username vacío o NULL', [
                'order_id' => $order->id_production_order,
            ]);
        }

        // 2) Fallback por NOMBRE exacto normalizado (menos confiable)
        $name = trim((string) $order->vendedor);
        if ($name !== '') {
            $norm = fn($s) => Str::of($s)->upper()->replace(' ', '')->value();

            $user = User::select('id', 'name', 'email')
                ->whereRaw("REPLACE(UPPER(name),' ','') = ?", [$norm($name)])
                ->first();

            Log::info('resolveAsesorEmail: lookup por nombre normalizado', [
                'name' => $name,
                'normalized' => $norm($name),
                'found_user' => $user?->id,
                'found_email' => $user?->email,
            ]);

            if (!empty($user?->email)) {
                return $user->email;
            }

            // 3) Último recurso: LIKE de las primeras 2 palabras
            $needle = (string) Str::of($name)->words(2, '');
            if ($needle !== '') {
                $matches = User::where('name', 'like', '%' . $needle . '%')
                    ->limit(5)
                    ->get(['id', 'name', 'email']);

                Log::info('resolveAsesorEmail: lookup por LIKE nombre', [
                    'needle' => $needle,
                    'count' => $matches->count(),
                    'user_ids' => $matches->pluck('id'),
                    'emails' => $matches->pluck('email'),
                ]);

                if ($matches->count() === 1 && !empty($matches[0]->email)) {
                    return $matches[0]->email;
                }
            }
        } else {
            Log::warning('resolveAsesorEmail: vendedor (nombre visible) vacío o NULL', [
                'order_id' => $order->id_production_order,
            ]);
        }

        // 4) Fallback global (opcional) para no perder el evento
        $fallback = config('mail.fallback_to'); // define esto en config/mail.php
        Log::warning('resolveAsesorEmail: no match; usando fallback (si existe)', [
            'order_id' => $order->id_production_order,
            'fallback' => $fallback,
        ]);

        return $fallback ?: null;
    }

    /** ============== GUARDAR - ACTUALIZAR ============== */
    public function save(): void
    {
        // ❌ Bloquea guardado si es asesor (readOnly)
        if ($this->readOnly) {
            $this->addError('observacion_produccion', 'Solo lectura: no tienes permisos para editar.');
            return;
        }

        if (!$this->validaCronologia()) {
            return; // ya dejó los errores en pantalla
        }

        try {
            $this->hora_inicio_produccion = $this->normalizeTime($this->hora_inicio_produccion);
            $this->hora_fin_produccion = $this->normalizeTime($this->hora_fin_produccion);

            $this->validate();

            $tieneFechas = $this->fecha_inicial_produccion && $this->fecha_final_produccion;
            $tieneHoras = $this->hora_inicio_produccion && $this->hora_fin_produccion;

            if (!$tieneFechas && !$tieneHoras) {
                $this->addError('fecha_inicial_produccion', 'Debes indicar fechas (o fechas+horas).');
                return;
            }
            if ($tieneFechas && !$tieneHoras) {
                $this->recalcularPorFechas();
            }
            if ($tieneFechas && $tieneHoras) {
                $this->recalcularPorFechasYHoras();
            }

            $now = Carbon::now();

            // Detalle
            DetailProductionOrder::updateOrCreate(
                ['ref_id_production_order' => $this->docId],
                [
                    'ref_id_production_order' => $this->docId,
                    'fecha_inicial_produccion' => $this->fecha_inicial_produccion,
                    'fecha_final_produccion' => $this->fecha_final_produccion,
                    'dias_produccion' => $this->dias_produccion,
                    'hora_inicio_produccion' => $this->hora_inicio_produccion,
                    'hora_fin_produccion' => $this->hora_fin_produccion,
                    'horas_produccion' => $this->horas_produccion,
                    'minutos_produccion' => $this->minutos_produccion,
                    'segundos_produccion' => $this->segundos_produccion,
                    'cantidad_luminarias' => $this->cantidad_luminarias,
                    'observacion_produccion' => $this->observacion_produccion,

                    'fecha_estado' => $now,
                    'ref_id_usuario_estado' => Auth::id(),

                    'fecha_registro' => $now,
                    'ref_id_usuario_registro' => Auth::id(),
                    'fecha_actualizacion' => $now,
                    'ref_id_usuario_actualizacion' => Auth::id(),
                ],
            );

            // Encabezado: status (enum)
            \App\Models\ProductionOrder::where('id_production_order', $this->docId)->update(['status' => $this->nuevo_status]);

            // Trae la orden ya actualizada
            $order = ProductionOrder::findOrFail($this->docId);

            // Evitar enviar 2 veces el mismo estado por orden
            $log = ProductionStatusNotification::firstOrCreate(
                [
                    'ref_id_production_order' => $order->id_production_order,
                    'status' => $this->nuevo_status,
                ],
                ['notified_at' => now()],
            );

            if ($log->wasRecentlyCreated) {
                $email = $this->resolveAsesorEmail($order);

                if (!$email) {
                    \Log::warning('No se pudo resolver email del asesor para la orden', [
                        'order_id' => $order->id_production_order,
                    ]);
                } else {
                    try {
                        $changedBy = auth()->user()?->name ?? 'Sistema';
                        //$luminariaNombre = $order->luminaria ?? ($order->producto ?? null);
                        // 🔥 CAMBIO AQUÍ — Convertir JSON de luminarias en texto legible
                        $luminarias = json_decode($order->luminaria, true);

                        if (!is_array($luminarias)) {
                            $luminarias = [$order->luminaria]; // fallback si llega texto suelto
                        }

                        // Texto final: “A, B, C”
                        $luminariaNombre = implode(', ', $luminarias);

                        \Log::info('Enviando notificación de estado...', [
                            'order_id' => $order->id_production_order,
                            'status' => $this->nuevo_status,
                            'to' => $email,
                        ]);

                        Notify::route('mail', $email)->notify(new ProductionStatusChanged(orderId: $order->id_production_order, nproduccion: $order->n_documento ?? 0, pedido: $order->pedido ?? 0, status: $this->nuevo_status, observacion: $this->observacion_produccion, fechaInicial: $this->fecha_inicial_produccion, horaInicial: $this->hora_inicio_produccion, fechaFinal: $this->fecha_final_produccion, horaFinal: $this->hora_fin_produccion, dias: $this->dias_produccion, horas: $this->horas_produccion, mins: $this->minutos_produccion, segs: $this->segundos_produccion, cantidadLuminarias: $this->cantidad_luminarias, luminariaNombre: $luminariaNombre, cambioPor: $changedBy));

                        \Log::info('Notificación enviada OK.', [
                            'order_id' => $order->id_production_order,
                            'status' => $this->nuevo_status,
                        ]);
                    } catch (\Throwable $tx) {
                        // Esto te deja el motivo exacto (TLS, auth, puerto, etc.)
                        \Log::error('Fallo enviando correo de ProductionStatusChanged', [
                            'order_id' => $order->id_production_order,
                            'status' => $this->nuevo_status,
                            'to' => $email,
                            'error' => $tx->getMessage(),
                        ]);
                    }
                }
            }

            // Notificar y cerrar
            $this->dispatch('production.saved');
            $this->close();
        } catch (\Throwable $e) {
            // Mostrar algo claro en la UI para que sepas por qué no guarda
            $this->addError('observacion_produccion', 'Error: ' . $e->getMessage());
            // (opcional) log
            \Log::error('ProductionActionModal save() error', ['ex' => $e]);
        }
    }

    protected function resetForm(): void
    {
        $this->reset(['fecha_inicial_produccion', 'hora_inicio_produccion', 'fecha_final_produccion', 'hora_fin_produccion', 'dias_produccion', 'horas_produccion', 'minutos_produccion', 'segundos_produccion', 'cantidad_luminarias', 'observacion_produccion', 'ref_id_estado']);
    }

    public function render()
    {
        return view('livewire.order.production-action-modal');
    }
}