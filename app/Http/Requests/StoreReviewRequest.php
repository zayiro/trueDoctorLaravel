<?php

namespace App\Http\Requests;

use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReviewRequest extends FormRequest
{
    /** Lista blanca: nunca se instancia una clase que venga del cliente sin pasar por aquí. */
    private const REVIEWABLES = [
        'doctor' => Doctor::class,
        'clinic' => Clinic::class,
    ];

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'rating'          => ['required', 'integer', 'between:1,5'],
            'comment'         => ['nullable', 'string', 'max:500'],
            'reviewable_id'   => ['required', 'integer'],
            'reviewable_type' => [
                'required',
                'string',
                Rule::in([...array_keys(self::REVIEWABLES), ...array_values(self::REVIEWABLES)]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required'          => 'Selecciona una calificación de 1 a 5 estrellas.',
            'rating.between'           => 'La calificación debe estar entre 1 y 5 estrellas.',
            'comment.max'              => 'El comentario no puede superar los 500 caracteres.',
            'reviewable_type.in'       => 'No se puede reseñar este tipo de perfil.',
        ];
    }

    /** Entidad a reseñar, resuelta solo a partir de la lista blanca. */
    public function reviewable(): Model
    {
        $type  = $this->validated('reviewable_type');
        $class = self::REVIEWABLES[$type] ?? $type;

        return $class::query()->findOrFail($this->validated('reviewable_id'));
    }
}