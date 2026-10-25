<?php

declare(strict_types=1);

namespace Tests\Unit\Availability;

use App\Services\Availability\SlotGenerator;
use App\Services\Availability\SlotPresenter;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class SlotPresenterTest extends TestCase
{
    /** A las 10:00 con 1 h de anticipación: 09:00 pasado, 10:00 muy pronto, 11:00 y 12:00 reservables. */
    private function slots(): array
    {
        $tz = new DateTimeZone('America/Bogota');

        return (new SlotGenerator())->generate(
            day: new DateTimeImmutable('2026-10-23 00:00', $tz),
            doctorId: 1,
            addressId: 2,
            blocks: [['start' => '09:00', 'end' => '13:00']],
            durationMinutes: 60,
            now: new DateTimeImmutable('2026-10-23 10:00', $tz),
            minNoticeHours: 1,
        );
    }

    public function test_formato_web_actual_solo_los_reservables_y_hora_con_am_pm(): void
    {
        $this->assertSame(
            [['time' => '11:00 AM', 'available' => true], ['time' => '12:00 PM', 'available' => true]],
            SlotPresenter::legacyWeb($this->slots())
        );
    }

    public function test_formato_api_actual_todos_con_bandera_available(): void
    {
        $this->assertSame(
            [
                ['time' => '09:00', 'available' => false],
                ['time' => '10:00', 'available' => false],
                ['time' => '11:00', 'available' => true],
                ['time' => '12:00', 'available' => true],
            ],
            SlotPresenter::legacyApi($this->slots())
        );
    }

    public function test_formato_v2_trae_estado_y_motivo(): void
    {
        $v2 = SlotPresenter::v2($this->slots());

        $this->assertSame('past', $v2[0]['state']);
        $this->assertSame('too_soon', $v2[1]['state']);
        $this->assertSame('Requiere 1 h de anticipación', $v2[1]['reason']);
        $this->assertSame('11:00 a. m.', $v2[2]['label']);
        $this->assertSame('12:00 p. m.', $v2[3]['label']);
        $this->assertSame('available', $v2[3]['state']);
        $this->assertSame('2026-10-23T11:00:00-05:00', $v2[2]['start']);
    }
}