<?php

use App\Livewire\PublicLanding;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ProfileDoctorController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\RegisterDoctorController;
use App\Http\Controllers\RegisterClinicController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\PublicProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DoctorAppointmentController;
use App\Http\Controllers\ContactController;

//Route::redirect('/', '/admin');

//para que se muestre de una el view login

/*Route::get('/', function () {
    return view('welcome');
});
*/

Route::get('/', [HomeController::class, 'index'])->name('home');

/*
//si usamos el middleware role.redirect
Route::get('/', function () {
    return view('dashboard');
})->middleware(['auth', 'role.redirect'])->name('dashboard');*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/contact', [ContactController::class, 'show'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/register-options', function () {
    return view('auth.register-options');
})->name('register.options');

// Ruta para mostrar el formulario (GET)
Route::get('/register-doctor', [RegisterDoctorController::class, 'register'])->name('doctor.register');

// Ruta para procesar el registro (POST) - La que definimos antes
Route::post('/register-doctor', [RegisterDoctorController::class, 'store'])->name('doctor.register.store');

Route::get('/register-clinic', function () {
    return view('auth.register-clinic');
})->name('clinic.register');

Route::post('/register-clinic', [RegisterClinicController::class, 'store'])->name('clinic.register.store');

Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');

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
    
    //Gestion de servicios
    Route::get('/doctor/services', [ServiceController::class, 'index'])->name('doctor.services.index');
    Route::get('/doctor/services/create', [ServiceController::class, 'create'])->name('doctor.services.create');
    Route::post('/doctor/services', [ServiceController::class, 'store'])->name('doctor.services.store');
    Route::get('/doctor/services/{service}/edit', [ServiceController::class, 'edit'])->name('doctor.services.edit');
    Route::put('/doctor/services/{service}', [ServiceController::class, 'update'])->name('doctor.services.update');
    Route::delete('/doctor/services/{service}', [ServiceController::class, 'destroy'])->name('doctor.services.destroy');

    // Gestión de Sedes
    Route::get('/doctor/addresses', [AddressController::class, 'index'])->name('doctor.addresses.index');
    Route::get('/doctor/addresses/create', [AddressController::class, 'create'])->name('doctor.addresses.create');
    Route::post('/doctor/addresses', [AddressController::class, 'store'])->name('doctor.addresses.store');
    Route::put('/doctor/addresses/{address}', [AddressController::class, 'update'])->name('doctor.addresses.update');
    Route::delete('/doctor/addresses/{address}', [AddressController::class, 'destroy'])->name('doctor.addresses.destroy');
    Route::get('/doctor/addresses/{address}/edit', [AddressController::class, 'edit'])->name('doctor.addresses.edit');
    Route::patch('/doctor/services/{service}/toggle', [ServiceController::class, 'toggleStatus'])->name('doctor.services.toggle');
    
    // Ver el listado y formulario de horarios de una sede
    Route::get('/doctor/addresses/{address}/schedules', [ScheduleController::class, 'index'])->name('doctor.schedules.index');
    Route::get('/doctor/addresses/{address}/schedules/edit', [ScheduleController::class, 'edit'])->name('doctor.schedules.edit');
    Route::put('/doctor/addresses/{address}/schedules/update', [ScheduleController::class, 'update'])->name('doctor.schedules.update');
    
    // Guardar el horario
    Route::post('/doctor/schedules', [ScheduleController::class, 'store'])->name('doctor.schedules.store');
    Route::delete('/doctor/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('doctor.schedules.destroy');
    Route::get('/doctor/appointments', [DoctorAppointmentController::class, 'index'])->name('doctor.appointments.index');
    Route::patch('/doctor/addresses/{address}/status', [AddressController::class, 'toggleStatus'])->name('doctor.addresses.status.toggle');
});

Route::get('/appointment/patient-data', [PublicProfileController::class, 'patientStep'])->name('appointments.patient');
Route::post('/appointment/process-patient', [PublicProfileController::class, 'processPatient'])->name('appointments.process_patient');

Route::post('/appointment/confirm', [PublicProfileController::class, 'book'])
    ->name('appointments.book')
    ->middleware('auth'); // Solo pacientes logueados
Route::get('/appointment/success/{appointment}', [PublicProfileController::class, 'success'])->name('appointments.success')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    // Vista principal de todas las notificaciones
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Acción para marcar una o todas como leídas
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
});

// Rutas protegidas (dentro del grupo de auth)
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/campaigns', \App\Livewire\Campaigns\CampaignIndex::class)->name('campaigns.index');
    Route::get('/campaigns/create', \App\Livewire\Campaigns\CreateCampaign::class)->name('campaigns.create');
});


Route::get('/{doctor_slug}/{campaign_slug}.html', PublicLanding::class)->name('landing.public');

// Ruta para ver el perfil y disponibilidad
Route::get('/{doctor:slug}', [PublicProfileController::class, 'show'])->name('doctor.public.profile');

// Ruta API para que FullCalendar cargue los huecos libres
Route::get('/api/{doctor}/availability', [PublicProfileController::class, 'getAvailability'])
    ->name('api.doctor.availability')
    ->missing(function () {
    return redirect()->route('home'); // Redirige al inicio si no existe
});
