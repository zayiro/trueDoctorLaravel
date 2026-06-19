<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Unavailability;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StoreUnavailabilityRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado para hacer esta solicitud.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * Obtiene las reglas de validación que se aplican a la solicitud.
     */
    public function rules(): array
    {
        $user = Auth::user();
        $context = session('doctor_context');
        $doctorId = $this->resolveDoctorId($user);

        return [
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
                'date_format:Y-m-d'
            ],
            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
                'date_format:Y-m-d'
            ],
            'address_id' => [
                // Si es doctor en contexto clínica, forzamos la selección de sede
                ($user->role === 'doctor' && ($context['type'] ?? 'particular') === 'clinic') ? 'required' : 'nullable',
                Rule::exists('addresses', 'id')->whereNull('deleted_at'),
                function ($attribute, $value, $fail) use ($user, $context) {
                    if ($value) {
                        $this->validateAddressOwnership($value, $user, $context, $fail);
                    }
                }
            ],
            'start_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:end_time'
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i',
                'required_with:start_time',
                'after:start_time'
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255'
            ]
        ];
    }

    /**
     * Obtiene los mensajes de validación personalizados.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'La fecha de inicio es obligatoria.',
            'start_date.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy.',
            'end_date.required' => 'La fecha de fin es obligatoria.',
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'address_id.required' => 'Debes seleccionar una sede para registrar la inasistencia en contexto de clínica.',
            'address_id.exists' => 'La sede seleccionada no existe o ha sido eliminada.',
            'start_time.date_format' => 'La hora de inicio debe estar en formato HH:MM.',
            'end_time.date_format' => 'La hora de fin debe estar en formato HH:MM.',
            'end_time.after' => 'La hora de fin debe ser posterior a la hora de inicio.',
            'reason.max' => 'La razón no puede exceder 255 caracteres.'
        ];
    }

    /**
     * Realiza validaciones adicionales después de que las reglas básicas pasen.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $user = Auth::user();
            $doctorId = $this->resolveDoctorId($user);

            // Validación 1: Detectar duplicados y solapamientos
            $this->validateNoDuplicateOrOverlappingUnavailability(
                $validator,
                $doctorId,
                $this->address_id
            );

            // Validación 2: Detectar conflictos con citas existentes (advertencia, no error)
            // Esto se maneja en el controlador con force_save
        });
    }

    /**
     * Resuelve el doctor_id basado en el rol del usuario.
     */
    private function resolveDoctorId($user): int
    {
        if ($user->role === 'clinic') {
            return (int) $this->doctor_id;
        }
        return (int) $user->doctor->id;
    }

    /**
     * Valida que la sede pertenezca al contexto del usuario.
     */
    private function validateAddressOwnership($addressId, $user, $context, $fail): void
    {
        $address = Address::find($addressId);

        if (!$address) {
            return; // Ya validado por exists rule
        }

        if ($user->role === 'clinic') {
            if ($address->clinic_id !== $user->clinic->id) {
                $fail('address_id')->translate('La sede seleccionada no pertenece a tu clínica institucional.');
            }
        } elseif ($user->role === 'doctor') {
            // Sub-caso: Médico en contexto de clínica aliada
            if (($context['type'] ?? 'particular') === 'clinic') {
                if ((int)$address->clinic_id !== (int)$context['id']) {
                    $fail('address_id')->translate('La sede seleccionada no corresponde a la clínica activa de tu espacio de trabajo.');
                }

                // Doble verificación: Garantizar vínculo en nómina aprobada
                $isLinked = DB::table('clinic_doctor')
                    ->where('clinic_id', $address->clinic_id)
                    ->where('doctor_id', $user->doctor->id)
                    ->where('status', 'approved')
                    ->exists();

                if (!$isLinked) {
                    $fail('address_id')->translate('No tienes privilegios de staff autorizados para esta sede.');
                }
            } else {
                // Sub-caso: Médico en consultorio propio
                if ($address->doctor_id !== $user->doctor->id) {
                    $fail('address_id')->translate('La sede seleccionada no te pertenece de forma privada.');
                }
            }
        }
    }

    /**
     * 🔒 VALIDACIÓN DEFENSIVA: Detecta duplicados y solapamientos en la base de datos.
     * 
     * Impide crear inasistencias que:
     * 1. Sean idénticas (mismo rango de fechas/horas)
     * 2. Se solapen parcialmente con inasistencias existentes
     */
    private function validateNoDuplicateOrOverlappingUnavailability($validator, int $doctorId, ?int $addressId): void
    {
        $startDate = $this->start_date;
        $endDate = $this->end_date;
        $startTime = $this->start_time;
        $endTime = $this->end_time;

        // Construir la consulta base
        $query = Unavailability::where('doctor_id', $doctorId);

        // Si se especifica una sede, filtrar por ella
        if ($addressId) {
            $query->where('address_id', $addressId);
        } else {
            // Si no hay sede, buscar bloqueos globales (sin address_id)
            $query->whereNull('address_id');
        }

        // Detectar solapamientos de fechas
        $overlapping = $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($subQ) use ($startDate, $endDate) {
                  $subQ->where('start_date', '<=', $startDate)
                       ->where('end_date', '>=', $endDate);
              });
        })->get();

        if ($overlapping->isEmpty()) {
            return;
        }

        // Si hay solapamientos de fecha, validar horas
        foreach ($overlapping as $existing) {
            // Si ambas inasistencias son de día completo (sin horas), es un duplicado
            if (is_null($startTime) && is_null($endTime) && 
                is_null($existing->start_time) && is_null($existing->end_time)) {
                $validator->errors()->add(
                    'start_date',
                    "Ya existe un bloqueo de día completo para este médico en el rango {$startDate} a {$endDate}."
                );
                return;
            }

            // Si ambas tienen horas específicas, validar solapamiento horario
            if ($startTime && $endTime && $existing->start_time && $existing->end_time) {
                if ($this->timesOverlap($startTime, $endTime, $existing->start_time, $existing->end_time)) {
                    $validator->errors()->add(
                        'start_time',
                        "Ya existe un bloqueo que se solapa en el horario {$existing->start_time} a {$existing->end_time} para este rango de fechas."
                    );
                    return;
                }
            }

            // Si una es de día completo y la otra tiene horas, es un solapamiento
            if ((is_null($startTime) && is_null($endTime)) || 
                (is_null($existing->start_time) && is_null($existing->end_time))) {
                $validator->errors()->add(
                    'start_date',
                    "Ya existe un bloqueo que se solapa con el rango de fechas seleccionado."
                );
                return;
            }
        }
    }

    /**
     * Valida si dos rangos horarios se solapan.
     */
    private function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool
    {
        return $start1 < $end2 && $start2 < $end1;
    }
}
