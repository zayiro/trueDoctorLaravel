<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPatientAppointmentRequest extends FormRequest
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
        $rules = [
            'notes' => 'required|string|min:10|max:500',
        ];

        // Reglas dinámicas basadas en si el usuario tiene cuenta
        if ($this->input('has_account') === 'yes') {
            $rules['login_email'] = 'required|email|exists:users,email';
            $rules['login_password'] = 'required';
        } else {
            $rules['name'] = 'required|string|min:3|max:100';
            $rules['email'] = 'required|email|unique:users,email';
            $rules['identification'] = 'required|numeric|unique:patients,identification';
            $rules['phone'] = 'required|numeric';
            $rules['country_code'] = 'nullable|string|max:5';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'notes.required' => 'Las notas o síntomas son obligatorios.',
            'notes.min' => 'Las notas deben tener al menos 10 caracteres.',
            'notes.max' => 'Las notas no pueden exceder 500 caracteres.',
            'login_email.required' => 'El correo electrónico es obligatorio.',
            'login_email.email' => 'El correo debe ser una dirección válida.',
            'login_email.exists' => 'No encontramos una cuenta con este correo.',
            'login_password.required' => 'La contraseña es obligatoria.',
            'name.required' => 'El nombre completo es obligatorio.',
            'name.min' => 'El nombre debe tener al menos 3 caracteres.',
            'name.max' => 'El nombre no puede exceder 100 caracteres.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'El correo debe ser una dirección válida.',
            'email.unique' => 'Este correo ya está registrado en el sistema.',
            'identification.required' => 'La identificación es obligatoria.',
            'identification.numeric' => 'La identificación debe ser un número válido.',
            'identification.unique' => 'Esta identificación ya está registrada.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.numeric' => 'El teléfono debe contener solo números.',
        ];
    }
}
