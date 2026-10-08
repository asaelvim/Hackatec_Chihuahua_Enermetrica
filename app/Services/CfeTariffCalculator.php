<?php

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * Calcula un costo monetario aproximado a partir de un consumo en kWh,
 * usando la tarifa residencial por bloques de CFE para Baja California
 * (temporada de verano: mayo-octubre). Los valores están fijos en este
 * servicio porque CFE los actualiza cada mes; si cambian hay que
 * actualizarlos aquí manualmente.
 *
 * Es una aproximación: CFE prorratea los límites de cada bloque según el
 * número exacto de días del periodo de facturación (normalmente bimestral).
 * Aquí se prorratean de forma simple contra un mes de 30 días, lo cual es
 * suficiente para una estimación, pero no reemplaza el recibo oficial.
 */
class CfeTariffCalculator
{
    /**
     * Mes (1-12) en que inicia y termina la temporada de verano en Baja
     * California para efectos de esta tarifa. CFE define el verano como una
     * ventana de 4 meses (mayo-agosto); se confirmó contra un recibo real
     * de octubre, que ya aplica tarifas de "resto del año".
     */
    private const SUMMER_START_MONTH = 5;

    private const SUMMER_END_MONTH = 8;

    /**
     * Límites de cada bloque (kWh) y su precio ($/kWh) para un periodo de
     * 30 días, según la temporada.
     *
     * @var array<string, array{blocks: array<int, array{limit: float, rate: float}>, excess_rate: float}>
     */
    private const TARIFF = [
        'verano' => [
            'blocks' => [
                ['limit' => 300, 'rate' => 0.854],
                ['limit' => 450, 'rate' => 1.054],
                ['limit' => 150, 'rate' => 1.368],
            ],
            'excess_rate' => 4.054,
        ],
        'resto_del_anio' => [
            'blocks' => [
                ['limit' => 300, 'rate' => 0.845],
                ['limit' => 450, 'rate' => 1.045],
                ['limit' => 150, 'rate' => 1.356],
            ],
            'excess_rate' => 4.016,
        ],
    ];

    /**
     * @return array{
     *     total: float,
     *     season: string,
     *     breakdown: array<int, array{label: string, kwh: float, rate: float, subtotal: float}>,
     *     capacity: float,
     * }
     */
    public function estimate(float $kwh, Carbon $periodStart, Carbon $periodEnd): array
    {
        $start = $periodStart->copy()->startOfDay();
        $end = $periodEnd->copy()->startOfDay();

        $days = max(1, (int) $start->diffInDays($end) + 1);
        $factor = $days / 30;

        $isSummer = $this->isSummer($start, $end);
        $tariff = self::TARIFF[$isSummer ? 'verano' : 'resto_del_anio'];

        $remaining = $kwh;
        $total = 0.0;
        $breakdown = [];

        $labels = ['Básico', 'Intermedio 1', 'Intermedio 2'];

        foreach ($tariff['blocks'] as $index => $block) {
            if ($remaining <= 0) {
                break;
            }

            $blockLimit = $block['limit'] * $factor;
            $used = min($remaining, $blockLimit);
            $subtotal = $used * $block['rate'];

            $breakdown[] = [
                'label' => $labels[$index],
                'kwh' => round($used, 2),
                'rate' => $block['rate'],
                'subtotal' => round($subtotal, 2),
            ];

            $total += $subtotal;
            $remaining -= $used;
        }

        if ($remaining > 0) {
            $subtotal = $remaining * $tariff['excess_rate'];

            $breakdown[] = [
                'label' => 'Excedente',
                'kwh' => round($remaining, 2),
                'rate' => $tariff['excess_rate'],
                'subtotal' => round($subtotal, 2),
            ];

            $total += $subtotal;
        }

        return [
            'total' => round($total, 2),
            'season' => $isSummer ? 'Verano' : 'Resto del año',
            'breakdown' => $breakdown,
            'capacity' => round(array_sum(array_column($tariff['blocks'], 'limit')) * $factor, 2),
        ];
    }

    /**
     * Determina la temporada usando el punto medio del periodo, para que un
     * rango que cruce la frontera verano/invierno se clasifique según donde
     * caiga la mayor parte de los días.
     */
    private function isSummer(Carbon $periodStart, Carbon $periodEnd): bool
    {
        $midpoint = $periodStart->copy()->addDays((int) ($periodStart->diffInDays($periodEnd) / 2));

        return $midpoint->month >= self::SUMMER_START_MONTH && $midpoint->month <= self::SUMMER_END_MONTH;
    }
}
