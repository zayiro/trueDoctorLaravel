<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoint público o privado para ver los horarios disponibles
Route::get('/get-slots', [AppointmentController::class, 'getSlots'])->name('api.slots.index');

// Endpoint para guardar la cita (Se recomienda envolverlo en auth:sanctum si requiere token)
Route::post('/appointments', [AppointmentController::class, 'store'])->name('api.appointments.store');

