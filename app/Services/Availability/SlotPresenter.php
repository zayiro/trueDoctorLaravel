<?php

declare(strict_types=1);

namespace App\Services\Availability;

/**
 * Convierte los turnos del motor único al formato que espera cada cliente.
 * Así cada endpoint conserva su respuesta actual (la web y la app móvil no se rompen)
 * y solo cambia de dónde sale la información.
 */
final class SlotPresenter
{
    /**
     * /slots web (formato actual): solo los turnos reservables, con la hora como "g:i A".
     *
     * @param Slot[] $slots
     */
    public static function legacyWeb(array $slots): array
    {
        $available = array_filter($slots, fn (Slot $slot) => $slot->isAvailable());

        return array_values(array_map(
            fn (Slot $slot) => ['time' => $slot->start->format('g:i A'), 'available' => true],
            $available
        ));
    }

    /**
     * API móvil (formato actual): todos los turnos, hora "H:i" y bandera `available`.
     *
     * @param Slot[] $slots
     */
    public static function legacyApi(array $slots): array
    {
        return array_values(array_map(
            fn (Slot $slot) => ['time' => $slot->start->format('H:i'), 'available' => $slot->isAvailable()],
            $slots
        ));
    }

    /**
     * Formato nuevo (?format=v2): trae el estado y el motivo, para mostrar en gris los
     * turnos "muy pronto" u "ocupados" con una nota.
     *
     * @param Slot[] $slots
     */
    public static function v2(array $slots): array
    {
        return array_values(array_map(fn (Slot $slot) => [
            'start' => $slot->start->format(DATE_ATOM),
            'time' => $slot->start->format('H:i'),
            'label' => $slot->start->format('g:i') . ' ' . ($slot->start->format('A') === 'AM' ? 'a. m.' : 'p. m.'),
            'doctor_id' => $slot->doctorId,
            'address_id' => $slot->addressId,
            'state' => $slot->state->value,
            'reason' => $slot->reason,
        ], $slots));
    }
}