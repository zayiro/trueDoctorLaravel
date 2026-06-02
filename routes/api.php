<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ZoomWebhookController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoint público o privado para ver los horarios disponibles
Route::get('/get-slots', [AppointmentController::class, 'getSlots'])->name('api.slots.index');

// Endpoint para guardar la cita (Se recomienda envolverlo en auth:sanctum si requiere token)
Route::post('/appointments', [AppointmentController::class, 'store'])->name('api.appointments.store');

// Ruta pública o protegida por token para la cancelación de citas externas
Route::put('/appointments/{id}/cancel', [AppointmentController::class, 'cancel'])->name('api.appointments.cancel');

// Ruta oficial para recibir las notificaciones de la API de Zoom
//Route::post('/webhooks/zoom', [ZoomWebhookController::class, 'handle'])->name('zoom.webhook');
Route::any('/webhooks/zoom', [ZoomWebhookController::class, 'handle']);
Route::get('/appointments/{id}/status', [AppointmentController::class, 'getStatus']);
