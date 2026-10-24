<?php

declare(strict_types=1);

namespace Tests\Unit\Availability;

use App\Services\Availability\AvailabilityCalculator;
use App\Services\Availability\AvailabilityData;
use App\Services\Availability\SlotGenerator;
use App\Services\Availability\SlotState;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;

class AvailabilityCalculatorTest extends TestCase
{
    private DateTimeZone $tz;
    private AvailabilityCalculator $calculator;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('America/Bogota');
        $this->calculator = new AvailabilityCalculator(new SlotGenerator(), [
            'min_notice_hours' => 2,
            'max_advance_days' => 30,
            'fallback_duration' => 20,
        ]);
    }

    private function at(string $dateTime): DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime, $this->tz);
    }

    private function sched(int $doctor, int $address, int $day, string $start, string $end): array
    {
        return ['doctor_id' => $doctor, 'address_id' => $address, 'day' => $day, 'start' => $start, 'end' => $end];
    }

    /** Agenda real del doctor 1 en la sede virtual (la sede 9 está eliminada y no se carga). */
    private function doctorOneData(array $overrides = []): AvailabilityData
    {
        return new AvailabilityData(...array_merge([
            'schedules' => [
                $this->sched(1, 2, 1, '06:00:00', '18:00:00'),
                $this->sched(1, 2, 3, '06:00:00', '18:00:00'),
                $this->sched(1, 2, 4, '06:00:00', '18:00:00'),
                $this->sched(1, 2, 6, '08:00:00', '12:00:00'),
            ],
            'settings' => ['1:2' => ['min_notice_hours' => 1, 'max_advance_days' => 30]],
            'durations' => [2 => 20],
        ], $overrides));
    }

    public function test_caso_real_viernes_el_proximo_turno_es_el_sabado_a_las_8(): void
    {
        $next = $this->calculator->nextSlot($this->doctorOneData(), $this->at('2026-10-23 10:00'));

        $this->assertSame('2026-10-24 08:00', $next->start->format('Y-m-d H:i'));
        $this->assertSame(2, $next->addressId);
    }

    public function test_caso_original_el_martes_ya_no_se_anuncia_un_turno_de_hoy(): void
    {
        // Martes 20 a las 7:50: la sede virtual no atiende los martes; el primer turno real es el miércoles.
        $next = $this->calculator->nextSlot($this->doctorOneData(), $this->at('2026-10-20 07:50'));

        $this->assertSame('2026-10-21 06:00', $next->start->format('Y-m-d H:i'));
    }

    public function test_el_proximo_turno_elige_la_sede_mas_cercana(): void
    {
        $data = new AvailabilityData(
            schedules: [
                $this->sched(1, 2, 5, '15:00:00', '17:00:00'), // viernes tarde, sede 2
                $this->sched(1, 3, 5, '11:30:00', '13:00:00'), // viernes mediodía, sede 3
            ],
            settings: ['1:2' => ['min_notice_hours' => 1], '1:3' => ['min_notice_hours' => 1]],
            durations: [2 => 20, 3 => 30],
        );

        $next = $this->calculator->nextSlot($data, $this->at('2026-10-23 10:00'));

        $this->assertSame('2026-10-23 11:30', $next->start->format('Y-m-d H:i'));
        $this->assertSame(3, $next->addressId);
    }

    public function test_un_festivo_bloquea_el_dia_completo(): void
    {
        $data = new AvailabilityData(
            schedules: [$this->sched(1, 2, 1, '06:00:00', '08:00:00')], // lunes
            settings: ['1:2' => ['min_notice_hours' => 1]],
            durations: [2 => 60],
            holidays: ['2026-10-26' => true],
        );

        $next = $this->calculator->nextSlot($data, $this->at('2026-10-23 10:00'));

        $this->assertSame('2026-11-02 06:00', $next->start->format('Y-m-d H:i'));
    }

    public function test_el_horizonte_maximo_se_respeta(): void
    {
        $data = new AvailabilityData(
            schedules: [$this->sched(1, 2, 1, '06:00:00', '08:00:00')], // solo lunes: 3 días después
            settings: ['1:2' => ['min_notice_hours' => 1, 'max_advance_days' => 2]],
            durations: [2 => 60],
        );

        $this->assertNull($this->calculator->nextSlot($data, $this->at('2026-10-23 10:00')));
    }

    public function test_una_ausencia_de_una_sede_no_bloquea_la_otra(): void
    {
        $data = new AvailabilityData(
            schedules: [
                $this->sched(1, 2, 5, '11:00:00', '12:00:00'),
                $this->sched(1, 3, 5, '11:00:00', '12:00:00'),
            ],
            absences: [['doctor_id' => 1, 'address_id' => 2, 'date' => '2026-10-23', 'start' => null, 'end' => null]],
            settings: ['1:2' => ['min_notice_hours' => 0], '1:3' => ['min_notice_hours' => 0]],
            durations: [2 => 60, 3 => 60],
        );

        $slots = $this->calculator->slotsForDay($data, $this->at('2026-10-23 00:00'), $this->at('2026-10-23 08:00'));
        $byAddress = [];
        foreach ($slots as $slot) {
            $byAddress[$slot->addressId] = $slot->state;
        }

        $this->assertSame(SlotState::Blocked, $byAddress[2]);
        $this->assertSame(SlotState::Available, $byAddress[3]);
    }

    public function test_una_cita_en_otra_sede_ocupa_al_doctor(): void
    {
        $data = new AvailabilityData(
            schedules: [$this->sched(1, 2, 5, '11:00:00', '12:00:00')],
            appointments: [['doctor_id' => 1, 'date' => '2026-10-23', 'start' => '11:00:00', 'end' => '12:00:00']], // en otra sede
            settings: ['1:2' => ['min_notice_hours' => 0]],
            durations: [2 => 60],
        );

        $slots = $this->calculator->slotsForDay($data, $this->at('2026-10-23 00:00'), $this->at('2026-10-23 08:00'));

        $this->assertSame(SlotState::Booked, $slots[0]->state);
    }

    public function test_cada_sede_usa_su_propia_politica_de_anticipacion(): void
    {
        $data = new AvailabilityData(
            schedules: [
                $this->sched(1, 2, 5, '10:00:00', '18:00:00'), // particular: 1 h
                $this->sched(1, 3, 5, '10:00:00', '18:00:00'), // clínica: 24 h
            ],
            settings: ['1:2' => ['min_notice_hours' => 1], '1:3' => ['min_notice_hours' => 24]],
            durations: [2 => 60, 3 => 60],
        );

        $next = $this->calculator->nextSlot($data, $this->at('2026-10-23 10:00'));

        $this->assertSame('2026-10-23 11:00', $next->start->format('Y-m-d H:i'));
        $this->assertSame(2, $next->addressId);
    }

    public function test_el_maximo_de_citas_por_dia_cierra_el_dia(): void
    {
        $data = new AvailabilityData(
            schedules: [$this->sched(1, 2, 5, '11:00:00', '12:00:00')],
            appointments: [
                ['doctor_id' => 1, 'date' => '2026-10-23', 'start' => '08:00:00', 'end' => '08:20:00'],
                ['doctor_id' => 1, 'date' => '2026-10-23', 'start' => '08:20:00', 'end' => '08:40:00'],
            ],
            settings: ['1:2' => ['min_notice_hours' => 0, 'max_per_day' => 2]],
            durations: [2 => 20],
        );

        $slots = $this->calculator->slotsForDay($data, $this->at('2026-10-23 00:00'), $this->at('2026-10-23 07:00'));

        $this->assertSame(SlotState::Full, $slots[0]->state);
        $this->assertSame([], $this->calculator->enabledDates($data, $this->at('2026-10-23 00:00'), $this->at('2026-10-23 00:00'), $this->at('2026-10-23 07:00')));
    }

    public function test_los_pasados_se_ocultan_y_los_muy_pronto_se_devuelven_para_mostrarlos_en_gris(): void
    {
        $data = new AvailabilityData(
            schedules: [$this->sched(1, 2, 5, '09:00:00', '12:00:00')],
            settings: ['1:2' => ['min_notice_hours' => 1]],
            durations: [2 => 60],
        );
        $now = $this->at('2026-10-23 10:00');
        $day = $this->at('2026-10-23 00:00');

        $default = $this->calculator->slotsForDay($data, $day, $now);
        $withPast = $this->calculator->slotsForDay($data, $day, $now, includePast: true);

        $this->assertSame(['10:00', '11:00'], array_map(fn ($s) => $s->start->format('H:i'), $default));
        $this->assertSame(SlotState::TooSoon, $default[0]->state);
        $this->assertSame(['09:00', '10:00', '11:00'], array_map(fn ($s) => $s->start->format('H:i'), $withPast));
    }

    public function test_el_proximo_turno_el_calendario_y_el_listado_siempre_coinciden(): void
    {
        $data = $this->doctorOneData();
        $now = $this->at('2026-10-23 10:00');

        $next = $this->calculator->nextSlot($data, $now);
        $dates = $this->calculator->enabledDates($data, $this->at('2026-10-23 00:00'), $this->at('2026-11-22 00:00'), $now);
        $listed = $this->calculator->slotsForDay($data, $next->start->setTime(0, 0), $now);

        $this->assertSame($next->start->format('Y-m-d'), $dates[0]);
        $this->assertTrue(in_array($next->start->getTimestamp(), array_map(fn ($s) => $s->start->getTimestamp(), $listed), true));
        $this->assertSame($next->start->getTimestamp(), SlotGenerator::firstAvailable($listed)->start->getTimestamp());
    }

    public function test_sin_horarios_no_hay_turnos(): void
    {
        $this->assertNull($this->calculator->nextSlot(AvailabilityData::empty(), $this->at('2026-10-23 10:00')));
    }
}