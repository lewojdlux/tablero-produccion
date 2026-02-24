<?php namespace App\Services;

use Carbon\Carbon;

class CalculoManoObraService
{
    public function calcularPagoJornada($fecha, $horaInicio, $horaFin, $valorHora)
    {
        if (!$fecha || !$horaInicio || !$horaFin) {
            return [];
        }

        $valorHora = (float) $valorHora;

        $inicio = Carbon::parse("$fecha $horaInicio");
        $fin = Carbon::parse("$fecha $horaFin");

        if ($fin->lte($inicio)) {
            $fin->addDay();
        }

        $resultados = [
            'ordinaria' => 0,
            'extra_diurna' => 0,
            'extra_nocturna' => 0,
            'dominical_diurna' => 0,
            'dominical_nocturna' => 0,
        ];

        while ($inicio < $fin) {
            $minutoActual = $inicio->copy();
            $inicio->addMinute();

            $diaSemana = $minutoActual->dayOfWeek;
            $hora = $minutoActual->format('H:i');

            if ($diaSemana == 0) {
                if ($hora >= '06:00' && $hora < '19:00') {
                    $resultados['dominical_diurna']++;
                } else {
                    $resultados['dominical_nocturna']++;
                }
            } else {
                if ($diaSemana == 1) {
                    if ($hora >= '07:00' && $hora < '16:00') {
                        $resultados['ordinaria']++;
                    } elseif ($hora >= '16:00' && $hora < '19:00') {
                        $resultados['extra_diurna']++;
                    } else {
                        $resultados['extra_nocturna']++;
                    }
                } elseif ($diaSemana >= 2 && $diaSemana <= 5) {
                    if ($hora >= '07:00' && $hora < '17:00') {
                        $resultados['ordinaria']++;
                    } elseif ($hora >= '17:00' && $hora < '19:00') {
                        $resultados['extra_diurna']++;
                    } else {
                        $resultados['extra_nocturna']++;
                    }
                } else {
                    if ($hora >= '06:00' && $hora < '19:00') {
                        $resultados['dominical_diurna']++;
                    } else {
                        $resultados['dominical_nocturna']++;
                    }
                }
            }
        }
        foreach ($resultados as $key => $value) {
            $resultados[$key] = round($value / 60, 2);
        }

        return [
            [
                'tipo' => 'Ordinaria',
                'horas' => $resultados['ordinaria'],
                'valor_hora' => round($valorHora, 2),
                'total' => round($resultados['ordinaria'] * $valorHora, 2),
            ],
            [
                'tipo' => 'Extra Diurna',
                'horas' => $resultados['extra_diurna'],
                'valor_hora' => round($valorHora * 1.0125, 2),
                'total' => round($resultados['extra_diurna'] * ($valorHora * 1.0125), 2),
            ],
            [
                'tipo' => 'Extra Nocturna',
                'horas' => $resultados['extra_nocturna'],
                'valor_hora' => round($valorHora * 1.0175, 2),
                'total' => round($resultados['extra_nocturna'] * ($valorHora * 1.0175), 2),
            ],
            [
                'tipo' => 'Dom/Fest Diurna',
                'horas' => $resultados['dominical_diurna'],
                'valor_hora' => round($valorHora * 1.0205, 2),
                'total' => round($resultados['dominical_diurna'] * ($valorHora * 1.0205), 2),
            ],
            [
                'tipo' => 'Dom/Fest Nocturna',
                'horas' => $resultados['dominical_nocturna'],
                'valor_hora' => round($valorHora * 1.0255, 2),
                'total' => round($resultados['dominical_nocturna'] * ($valorHora * 1.0255), 2),
            ],
        ];
    }
}