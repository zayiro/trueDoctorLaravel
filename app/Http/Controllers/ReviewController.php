<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'rating'          => 'required|integer|between:1,5',
            'comment'         => 'nullable|string|max:500',
            'reviewable_id'   => 'required|integer',
            'reviewable_type' => 'required|string',
        ]);

        $userId = Auth::id();
        $reviewableId = $request->reviewable_id;
        $reviewableType = $request->reviewable_type;

        // 1. Validar si el paciente tuvo una cita con esta entidad
        // Asumimos que tienes una tabla 'appointments' con 'user_id' y 'doctor_id' (o clinic_id)
        $hasAppointment = Appointment::where('patient_id', $userId)
            ->where(function($query) use ($reviewableId, $reviewableType) {
                if ($reviewableType === Doctor::class) {
                    $query->where('doctor_id', $reviewableId);
                } else {
                    $query->where('clinic_id', $reviewableId);
                }
            })
            ->where('status', 'completed') // Solo si la cita ya ocurrió
            ->exists();

        if (!$hasAppointment) {
            return back()->with('error', 'Solo puedes calificar después de asistir a tu cita.');
        }

        // 2. (Opcional) Evitar que califique dos veces por la misma entidad
        $alreadyReviewed = Review::where('user_id', $userId)
            ->where('reviewable_id', $reviewableId)
            ->where('reviewable_type', $reviewableType)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with('error', 'Ya has dejado una reseña para este perfil.');
        }

        // 3. Crear la reseña si pasa las validaciones
        $model = $reviewableType::findOrFail($reviewableId);
        $model->reviews()->create([
            'user_id' => $userId,
            'rating'  => $request->rating,
            'comment' => $request->comment,
        ]);

        $this->updateAverageRating($model);

        return back()->with('success', 'Reseña publicada con éxito.');
    }

    private function updateAverageRating($model)
    {
        $model->update([
            'rating' => $model->reviews()->avg('rating'),
            'reviews_count' => $model->reviews()->count(),
        ]);
    }
}
