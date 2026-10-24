<?php

declare(strict_types=1);

namespace Tests\Unit\Availability;

use App\Services\Availability\SlotGenerator;
use App\Services\Availability\SlotState;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SlotGeneratorTest extends TestCase
{
    private DateTimeZone $tz;
    private SlotGenerator $generator;

    protected function setUp(): void
    {
        $this->tz = new DateTimeZone('America/Bogota');
        $this->generator = new SlotGenerator();
    }

    private function at(string $dateTime): DateTimeImmutable
    {
        return new DateTimeImmutable($dateTime, $this->tz);
    }

    /** @return array<string, SlotState> hora "H:i" => estado */
    private function states(array $slots): array
    {
        $result = [];
        foreach ($slots as $slot) {
            $result[$slot->start->format('H:i')] = $slot->state;
        }

        return $result;
    }

    private function day(string $date, string $now, array $blocks, int $duration = 20, int $notice = 1, array $extra = []): array
    {
        return $this->generator->generate(
            day: $this->at($date . ' 00:00'),
            doctorId: 1,
            addressId: 2,
            blocks: $blocks,
            durationMinutes: $duration,
            now: $this->at($now),
            minNoticeHours: $notice,
            booked: $extra['booked'] ?? [],
            blocked: $extra['blocked'] ?? [],
            bufferMinutes: $extra['buffer'] ?? 0,
            maxAppointmentsPerDay: $extra['max'] ?? null,
            bookedCountForDay: $extra['count'] ?? 0,
        );
    }

    public function test_a_las_10_con_1_hora_de_anticipacion_los_turnos_pasados_y_muy_pronto_no_son_reservables(): void
    {
        $slots = $this->day('2026-10-20', '2026-10-20 10:00', [['start' => '06:00', 'end' => '18:00']]);
        $states = $this->states($slots);

        $this->assertSame(SlotState::Past, $states['09:40']);
        $this->assertSame(SlotState::TooSoon, $states['10:00']);   // empieza justo ahora: ya no se puede
        $this->assertSame(SlotState::TooSoon, $states['10:40']);
        $this->assertSame(SlotState::Available, $states['11:00']); // ahora + 1 h exactas
        $this->assertSame('11:00', SlotGenerator::firstAvailable($slots)->start->format('H:i'));
    }

    public function test_varios_bloques_no_generan_turnos_entre_ellos(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [
            ['start' => '14:00', 'end' => '16:00'],
            ['start' => '08:00', 'end' => '10:00'], // desordenado a propósito
        ], 60);

        $this->assertSame(['08:00', '09:00', '14:00', '15:00'], array_keys($this->states($slots)));
    }

    public function test_el_turno_debe_caber_dentro_del_bloque(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '08:00', 'end' => '08:50']]);

        $this->assertSame(['08:00', '08:20'], array_keys($this->states($slots))); // 08:40-09:00 se sale del bloque
    }

    public function test_una_cita_dentro_de_un_turno_mas_largo_lo_bloquea(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '10:00', 'end' => '11:20']], 40, 1, [
            'booked' => [['start' => $this->at('2026-10-21 10:10'), 'end' => $this->at('2026-10-21 10:30')]],
        ]);
        $states = $this->states($slots);

        $this->assertSame(SlotState::Booked, $states['10:00']);
        $this->assertSame(SlotState::Available, $states['10:40']); // 10:40-11:20 no toca la cita
    }

    public function test_una_cita_que_termina_justo_cuando_empieza_el_turno_no_lo_bloquea(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '10:00', 'end' => '11:00']], 20, 1, [
            'booked' => [['start' => $this->at('2026-10-21 10:00'), 'end' => $this->at('2026-10-21 10:20')]],
        ]);
        $states = $this->states($slots);

        $this->assertSame(SlotState::Booked, $states['10:00']);
        $this->assertSame(SlotState::Available, $states['10:20']);
    }

    public function test_el_buffer_bloquea_los_turnos_vecinos(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '09:00', 'end' => '11:00']], 20, 1, [
            'booked' => [['start' => $this->at('2026-10-21 10:00'), 'end' => $this->at('2026-10-21 10:20')]],
            'buffer' => 10,
        ]);
        $states = $this->states($slots);

        $this->assertSame(SlotState::Available, $states['09:20']);
        $this->assertSame(SlotState::Booked, $states['09:40']);  // termina a las 10:00, dentro del buffer
        $this->assertSame(SlotState::Booked, $states['10:00']);
        $this->assertSame(SlotState::Booked, $states['10:20']);  // empieza dentro del buffer posterior
        $this->assertSame(SlotState::Available, $states['10:40']);
    }

    public function test_una_ausencia_parcial_bloquea_solo_su_rango(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '11:00', 'end' => '15:00']], 60, 1, [
            'blocked' => [['start' => $this->at('2026-10-21 12:00'), 'end' => $this->at('2026-10-21 14:00')]],
        ]);

        $this->assertSame(
            ['11:00' => SlotState::Available, '12:00' => SlotState::Blocked, '13:00' => SlotState::Blocked, '14:00' => SlotState::Available],
            $this->states($slots)
        );
    }

    public function test_la_anticipacion_cruza_la_medianoche(): void
    {
        $block = [['start' => '06:00', 'end' => '18:00']];

        // 24 h de aviso desde el 20 a las 20:00: todo el 21 queda "muy pronto"...
        $day21 = $this->day('2026-10-21', '2026-10-20 20:00', $block, 60, 24);
        $this->assertNull(SlotGenerator::firstAvailable($day21));
        $this->assertSame(SlotState::TooSoon, $this->states($day21)['17:00']);

        // ...y el 22 desde las 06:00 ya es reservable.
        $day22 = $this->day('2026-10-22', '2026-10-20 20:00', $block, 60, 24);
        $this->assertSame('06:00', SlotGenerator::firstAvailable($day22)->start->format('H:i'));
    }

    public function test_el_maximo_de_citas_por_dia_cierra_los_turnos_libres(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '09:00', 'end' => '10:00']], 20, 1, [
            'max' => 3,
            'count' => 3,
        ]);

        foreach ($slots as $slot) {
            $this->assertSame(SlotState::Full, $slot->state);
        }
        $this->assertNull(SlotGenerator::firstAvailable($slots));
    }

    public function test_un_turno_pasado_y_ocupado_cuenta_como_pasado(): void
    {
        $slots = $this->day('2026-10-20', '2026-10-20 12:00', [['start' => '09:00', 'end' => '10:00']], 20, 1, [
            'booked' => [['start' => $this->at('2026-10-20 09:00'), 'end' => $this->at('2026-10-20 09:20')]],
        ]);

        $this->assertSame(SlotState::Past, $this->states($slots)['09:00']);
    }

    public function test_el_reloj_puede_venir_en_otra_zona_horaria(): void
    {
        // 15:00 UTC = 10:00 en Bogotá
        $now = new DateTimeImmutable('2026-10-20 15:00', new DateTimeZone('UTC'));

        $slots = $this->generator->generate(
            day: $this->at('2026-10-20 00:00'),
            doctorId: 1,
            addressId: 2,
            blocks: [['start' => '09:00', 'end' => '12:00']],
            durationMinutes: 20,
            now: $now,
            minNoticeHours: 1,
        );
        $states = $this->states($slots);

        $this->assertSame(SlotState::Past, $states['09:40']);
        $this->assertSame(SlotState::Available, $states['11:00']);
    }

    public function test_los_bloques_superpuestos_no_duplican_turnos(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [
            ['start' => '08:00', 'end' => '09:00'],
            ['start' => '08:30', 'end' => '09:30'],
        ], 30);

        $this->assertSame(['08:00', '08:30', '09:00'], array_keys($this->states($slots)));
    }

    public function test_una_duracion_invalida_lanza_excepcion(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->day('2026-10-21', '2026-10-20 08:00', [['start' => '08:00', 'end' => '09:00']], 0);
    }

    public function test_el_resultado_siempre_esta_ordenado_y_el_primero_disponible_pertenece_a_la_lista(): void
    {
        $slots = $this->day('2026-10-21', '2026-10-20 08:00', [
            ['start' => '14:00', 'end' => '15:00'],
            ['start' => '08:00', 'end' => '09:00'],
        ]);

        $times = array_map(fn ($s) => $s->start->getTimestamp(), $slots);
        $sorted = $times;
        sort($sorted);

        $this->assertSame($sorted, $times);
        $this->assertTrue(in_array(SlotGenerator::firstAvailable($slots), $slots, true));
    }
}