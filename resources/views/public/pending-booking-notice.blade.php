{{-- Aviso de "reserva en curso" de la página de inicio. Lo decide App\Services\Appointments\PendingBookingService. --}}
@auth
    @php
        $pendingAppointment = app(\App\Services\Appointments\PendingBookingService::class)->current(auth()->user());
    @endphp

    @if(session('booking_discarded'))
        <div class="relative z-40 bg-white pt-6">
            <div class="max-w-7xl mx-auto px-4">
                <div class="p-4 text-sm text-emerald-800 rounded-2xl bg-emerald-50 border border-emerald-100 shadow-sm" role="status">
                    {{ session('booking_discarded') }}
                </div>
            </div>
        </div>
    @endif

    @if($pendingAppointment)
        @php
            $pendingDoctor = $pendingAppointment->doctor;
            $pendingDoctorName = $pendingDoctor
                ? (($pendingDoctor->gender === 'male' ? 'Dr. ' : 'Dra. ') . ucfirst($pendingDoctor->user->name ?? ''))
                : 'tu especialista';
            $pendingWhen = \Carbon\Carbon::parse($pendingAppointment->date)->translatedFormat('D d M')
                . ' · ' . \Carbon\Carbon::parse($pendingAppointment->start_time)->format('g:i A');
            $pendingUntil = $pendingAppointment->payment_expires_at?->timezone('America/Bogota')->format('g:i A');
        @endphp

        <div class="relative z-40 bg-white pt-6 pb-2">
            <div class="max-w-7xl mx-auto px-4">
                <div class="p-4 sm:p-5 rounded-2xl bg-indigo-50 border border-indigo-100 shadow-lg flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4" role="alert">
                    <div class="space-y-1 text-sm text-indigo-800">
                        <p class="font-bold text-indigo-900">¡Tienes una reserva en curso!</p>
                        <p>
                            Videoconsulta con <strong>{{ $pendingDoctorName }}</strong> · {{ $pendingWhen }}.
                            @if($pendingUntil)
                                Tienes hasta las <strong>{{ $pendingUntil }}</strong> para pagarla.
                            @else
                                Completa el pago para no perder tu reservación médica.
                            @endif
                        </p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-2 shrink-0">
                        <a href="{{ route('appointments.preview', $pendingAppointment->id) }}"
                           class="inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold uppercase tracking-wider px-5 py-3 rounded-xl transition shadow-sm">
                            Retomar y pagar
                        </a>

                        <form method="POST" action="{{ route('appointments.discard', $pendingAppointment->id) }}"
                              onsubmit="return confirm('¿Descartar esta reserva? El turno quedará libre y tendrás que agendar de nuevo.');">
                            @csrf
                            <button type="submit"
                                    class="w-full inline-flex items-center justify-center bg-white hover:bg-indigo-100 text-indigo-700 border border-indigo-200 text-xs font-bold uppercase tracking-wider px-5 py-3 rounded-xl transition cursor-pointer">
                                Olvidar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endauth
