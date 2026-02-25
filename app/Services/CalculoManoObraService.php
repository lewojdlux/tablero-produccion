<?php namespace App\Services;

use Carbon\Carbon;

class CalculoManoObraService
{
    public function calcularPagoJornada($fecha, $horaInicio, $horaFin, $valorHora)
    {
        $inicio = Carbon::parse("$fecha $horaInicio");
        $fin = Carbon::parse("$fecha $horaFin");

        if ($fin->lte($inicio)) {
            $fin->addDay();
        }

        $minutos = [
            'ordinaria' => 0,
            'extra_diurna' => 0,
            'extra_nocturna' => 0,
            'dominical_diurna' => 0,
            'dominical_nocturna' => 0,
        ];

        while ($inicio < $fin) {

            $actual = $inicio->copy();
            $inicio->addMinute();

            $dia = $actual->dayOfWeek; // 0 domingo
            $hora = $actual->format('H:i');

            // ================= DOMINGO =================
            if ($dia == 0) {

                if ($hora >= '06:00' && $hora < '19:00') {
                    $minutos['dominical_diurna']++;
                } else {
                    $minutos['dominical_nocturna']++;
                }

            }

            // ================= LUNES =================
            elseif ($dia == 1) {

                if ($hora >= '07:00' && $hora < '16:00') {
                    $minutos['ordinaria']++;
                }
                elseif ($hora >= '16:00' && $hora < '19:00') {
                    $minutos['extra_diurna']++;
                }
                else {
                    $minutos['extra_nocturna']++;
                }

            }

            // ================= MARTES A VIERNES =================
            elseif ($dia >= 2 && $dia <= 5) {

                if ($hora >= '07:00' && $hora < '17:00') {
                    $minutos['ordinaria']++;
                }
                elseif ($hora >= '17:00' && $hora < '19:00') {
                    $minutos['extra_diurna']++;
                }
                else {
                    $minutos['extra_nocturna']++;
                }

            }

            // ================= SÁBADO =================
            elseif ($dia == 6) {

                if ($hora >= '06:00' && $hora < '19:00') {
                    $minutos['extra_diurna']++;
                }
                else {
                    $minutos['extra_nocturna']++;
                }

            }
        }

        // Convertir minutos a horas
        foreach ($minutos as $key => $value) {
            $minutos[$key] = round($value / 60, 2);
        }

        return [
            [
                'tipo' => 'Ordinaria',
                'horas' => $minutos['ordinaria'],
                'valor_hora' => round($valorHora, 2),
                'total' => round($minutos['ordinaria'] * $valorHora, 2),
            ],
            [
                'tipo' => 'Extra Diurna',
                'horas' => $minutos['extra_diurna'],
                'valor_hora' => round($valorHora * 1.25, 2),
                'total' => round($minutos['extra_diurna'] * ($valorHora * 1.25), 2),
            ],
            [
                'tipo' => 'Extra Nocturna',
                'horas' => $minutos['extra_nocturna'],
                'valor_hora' => round($valorHora * 1.75, 2),
                'total' => round($minutos['extra_nocturna'] * ($valorHora * 1.75), 2),
            ],
            [
                'tipo' => 'Dom/Fest Diurna',
                'horas' => $minutos['dominical_diurna'],
                'valor_hora' => round($valorHora * 2.05, 2),
                'total' => round($minutos['dominical_diurna'] * ($valorHora * 2.05), 2),
            ],
            [
                'tipo' => 'Dom/Fest Nocturna',
                'horas' => $minutos['dominical_nocturna'],
                'valor_hora' => round($valorHora * 2.55, 2),
                'total' => round($minutos['dominical_nocturna'] * ($valorHora * 2.55), 2),
            ],
        ];
    }
}