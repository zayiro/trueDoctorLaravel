<?php

namespace Tests\Unit\Scheduling;

use App\Services\Scheduling\SlotOverlapChecker;
use PHPUnit\Framework\TestCase;

class SlotOverlapCheckerTest extends TestCase
{
    /** @test */
    public function detecta_solapamiento_parcial(): void
    {
        // Slot 10:00-10:30 vs Cita 10:15-10:45 → SOLAPAN
        $this->assertTrue(
            SlotOverlapChecker::intervalsOverlap('10:00:00', '10:30:00', '10:15:00', '10:45:00')
        );
    }

    /** @test */
    public function detecta_cita_envolvente(): void
    {
        // Slot 10:00-11:30 vs Cita 10:30-11:00 → SOLAPAN (cita dentro del slot)
        $this->assertTrue(
            SlotOverlapChecker::intervalsOverlap('10:00:00', '11:30:00', '10:30:00', '11:00:00')
        );
    }

    /** @test */
    public function detecta_cita_envuelta(): void
    {
        // Slot 10:30-11:00 vs Cita 10:00-11:30 → SOLAPAN (slot dentro de la cita)
        $this->assertTrue(
            SlotOverlapChecker::intervalsOverlap('10:30:00', '11:00:00', '10:00:00', '11:30:00')
        );
    }

    /** @test */
    public function NO_detecta_solapamiento_cuando_es_adyacente(): void
    {
        // Slot 10:00-10:30 vs Cita 10:30-11:00 → NO SOLAPAN (intervalos semi-abiertos)
        $this->assertFalse(
            SlotOverlapChecker::intervalsOverlap('10:00:00', '10:30:00', '10:30:00', '11:00:00')
        );
    }

    /** @test */
    public function NO_detecta_solapamiento_cuando_estan_separados(): void
    {
        $this->assertFalse(
            SlotOverlapChecker::intervalsOverlap('10:00:00', '10:30:00', '11:00:00', '11:30:00')
        );
    }
}