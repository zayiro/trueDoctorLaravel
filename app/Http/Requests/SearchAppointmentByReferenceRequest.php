<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SearchAppointmentByReferenceRequest extends FormRequest
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
            'reference' => 'required|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'reference.required' => 'La referencia de la cita es obligatoria.',
            'reference.string' => 'La referencia debe ser un texto válido.',
            'reference.max' => 'La referencia no puede exceder 20 caracteres.',
        ];
    }
}
