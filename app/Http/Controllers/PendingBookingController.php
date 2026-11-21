<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Services\Appointments\PendingBookingService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * "Olvidar" una reserva en curso (sin pagar) desde el aviso de la página de inicio.
 */
class PendingBookingController extends Controller
{
    public function discard(Request $request, int $id, PendingBookingService $service)
    {
        $user = $request->user();

        $appointment = Appointment::where('id', $id)
            ->where('patient_id', $user->patient?->id ?? 0)
            ->first();

        if (!$appointment) {
            return redirect()->route('home')->with('error', 'No encontramos esa reserva.');
        }

        try {
            $result = $service->discard($appointment, $user);
        } catch (AuthorizationException $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }

        return redirect()->route('home')->with($result['ok'] ? 'booking_discarded' : 'error', $result['message']);
    }
}
