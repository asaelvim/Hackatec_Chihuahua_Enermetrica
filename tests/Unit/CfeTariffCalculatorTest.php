<?php

namespace Tests\Unit;

use App\Services\CfeTariffCalculator;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CfeTariffCalculatorTest extends TestCase
{
    public function test_estimate_matches_real_cfe_receipt_for_october(): void
    {
        // Recibo real de referencia: periodo del 2026-08-08 al 2026-10-07
        // (61 días), 1,956 kWh, fuera de temporada de verano.
        $start = Carbon::parse('2026-08-08');
        $end = Carbon::parse('2026-10-07');

        $result = (new CfeTariffCalculator)->estimate(1956, $start, $end);

        $this->assertSame('Resto del año', $result['season']);
        $this->assertEqualsWithDelta(300 * (61 / 30), $result['breakdown'][0]['kwh'], 5);
        $this->assertSame('Básico', $result['breakdown'][0]['label']);
        $this->assertSame('Excedente', $result['breakdown'][array_key_last($result['breakdown'])]['label']);
        // El total aproximado debe estar en el mismo orden de magnitud que
        // el recibo real (~2,480.79 MXN de subtotal de energía).
        $this->assertEqualsWithDelta(2480.79, $result['total'], 100);
    }

    public function test_estimate_uses_summer_rates_within_summer_window(): void
    {
        $start = Carbon::parse('2026-06-01');
        $end = Carbon::parse('2026-07-31');

        $result = (new CfeTariffCalculator)->estimate(500, $start, $end);

        $this->assertSame('Verano', $result['season']);
        $this->assertSame(0.854, $result['breakdown'][0]['rate']);
    }

    public function test_estimate_only_charges_excess_rate_when_over_all_blocks(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end = Carbon::parse('2026-01-30');

        $result = (new CfeTariffCalculator)->estimate(2000, $start, $end);

        $last = $result['breakdown'][array_key_last($result['breakdown'])];
        $this->assertSame('Excedente', $last['label']);
        $this->assertSame(4.016, $last['rate']);
    }

    public function test_estimate_returns_zero_total_for_zero_consumption(): void
    {
        $start = Carbon::parse('2026-01-01');
        $end = Carbon::parse('2026-01-30');

        $result = (new CfeTariffCalculator)->estimate(0, $start, $end);

        $this->assertSame(0.0, $result['total']);
        $this->assertSame([], $result['breakdown']);
    }
}
