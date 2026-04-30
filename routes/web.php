<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileDoctorController;
use App\Http\Controllers\PlanController;

Route::redirect('/', '/admin');
/*
Route::get('/', function () {
    return view('welcome');
});*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

// En web.php
Route::post('/upgrade-plan', function() {
    Auth::user()->doctor->update(['plan' => 'avanzado']);
    return back()->with('success', '¡Ahora eres un Doctor Avanzado!');
})->name('plan.upgrade');

// Rutas Públicas (Pacientes)
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Rutas Privadas (Doctores)
Route::middleware(['auth', 'role:doctor'])->group(function () {
    Route::post('/doctor/profile/plan', [PlanController::class, 'update'])->name('doctor.profile.plan.update');
    // Ver y editar el perfil
    Route::get('/doctor/profile', [ProfileDoctorController::class, 'edit'])->name('doctor.profile.edit');
    
    // Gestión de Sedes
    Route::get('/doctor/addresses', [AddressController::class, 'index'])->name('doctor.addresses.index');
    Route::get('/doctor/addresses/create', [AddressController::class, 'create'])->name('doctor.addresses.create');
    Route::post('/doctor/addresses', [AddressController::class, 'store'])->name('doctor.addresses.store');
    Route::put('/doctor/addresses/{address}', [AddressController::class, 'update'])->name('doctor.addresses.update');
    Route::delete('/doctor/addresses/{address}', [AddressController::class, 'destroy'])->name('doctor.addresses.destroy');
    Route::get('/doctor/addresses/{address}/edit', [AddressController::class, 'edit'])->name('doctor.addresses.edit');
    
    // Gestión de Horarios
    Route::get('/doctor/addresses/{address}/schedules', [ScheduleController::class, 'edit'])->name('doctor.schedules.edit');
    Route::post('/doctor/schedules', [ScheduleController::class, 'store'])->name('doctor.schedules.store');
});
