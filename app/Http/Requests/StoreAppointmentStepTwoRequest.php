<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppointmentStepTwoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'service_id' => 'required|integer|exists:services,id',
            'address_id' => 'required|integer|exists:addresses,id,deleted_at,NULL',
            'date'       => 'required|date|after_or_equal:today',
            'hour'       => 'required|string',
            'doctor_id'  => 'nullable|integer|exists:doctors,id',
            'clinic_id'  => 'nullable|integer|exists:clinics,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_id.required' => 'El servicio médico es obligatorio.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'address_id.required' => 'La sede es obligatoria.',
            'address_id.exists' => 'La sede seleccionada no existe o ha sido eliminada.',
            'date.required' => 'La fecha de la cita es obligatoria.',
            'date.date' => 'La fecha debe ser una fecha válida.',
            'date.after_or_equal' => 'La fecha no puede ser anterior a hoy.',
            'hour.required' => 'La hora es obligatoria.',
            'doctor_id.exists' => 'El médico seleccionado no existe.',
            'clinic_id.exists' => 'La clínica seleccionada no existe.',
        ];
    }
}
