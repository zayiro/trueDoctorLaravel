<?php

namespace App\Http\Requests;

use App\Models\Service;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validación de crear/editar servicio.
 *
 * Crear:  name, type, duration, specialties[] + (virtual) price_virtual  |  (physical) address_ids[] y prices[addressId]
 * Editar: name, duration, specialties[], price. El tipo NO es editable: se toma del servicio guardado.
 */
class ServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización por propietario se aplica en el controlador y en OwnerServiceManager
        return $this->user() !== null;
    }

    private function editingService(): ?Service
    {
        $service = $this->route('service');

        return $service instanceof Service ? $service : null;
    }

    protected function prepareForValidation(): void
    {
        $data = ['name' => trim(preg_replace('/\s+/', ' ', (string) $this->input('name')))];

        if ($service = $this->editingService()) {
            $data['type'] = $service->type; // se ignora lo que envíe el formulario
        }

        $this->merge($data);
    }

    public function rules(): array
    {
        $rules = [
            'name'          => ['required', 'string', 'max:100'],
            'type'          => ['required', 'in:physical,virtual'],
            'duration'      => ['required', 'integer', 'min:1', 'max:480'],
            'specialties'   => ['required', 'array', 'min:1'],
            'specialties.*' => ['integer', 'exists:specialties,id'],
        ];

        if ($this->editingService()) {
            return $rules + ['price' => ['required', 'numeric', 'min:0', 'max:999999.99']];
        }

        if ($this->input('type') === 'virtual') {
            return $rules + ['price_virtual' => ['required', 'numeric', 'min:0', 'max:999999.99']];
        }

        return $rules + [
            'address_ids'   => ['required', 'array', 'min:1'],
            'address_ids.*' => ['integer', 'exists:addresses,id,deleted_at,NULL'],
            'prices'        => ['required', 'array'],
            'prices.*'      => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->editingService() || $this->input('type') !== 'physical') {
                return;
            }

            foreach ((array) $this->input('address_ids', []) as $addressId) {
                $price = $this->input("prices.{$addressId}");

                if ($price === null || $price === '') {
                    $validator->errors()->add("prices.{$addressId}", 'Debes asignar un precio a cada sede seleccionada.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre del servicio es obligatorio.',
            'name.max'               => 'El nombre del servicio no puede superar los 100 caracteres.',
            'type.required'          => 'La modalidad del servicio es obligatoria.',
            'duration.required'      => 'La duración es obligatoria.',
            'specialties.required'   => 'Selecciona al menos una especialidad.',
            'specialties.min'        => 'Selecciona al menos una especialidad.',
            'price.required'         => 'El valor del servicio es obligatorio.',
            'price_virtual.required' => 'El valor del servicio virtual es obligatorio.',
            'address_ids.required'   => 'Selecciona al menos una sede.',
            'address_ids.min'        => 'Selecciona al menos una sede.',
        ];
    }
}