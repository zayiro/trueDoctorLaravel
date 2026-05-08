<?php

use Illuminate\Http\Request;
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
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PartnerPatientController;

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
Route::get('/register-partner', [RegisterDoctorController::class, 'register'])->name('partner.register');

// Ruta para procesar el registro (POST) - La que definimos antes
Route::post('/register-partner', [RegisterDoctorController::class, 'store'])->name('partner.register.store');

Route::get('/register-clinic', function () {
    return view('auth.register-clinic');
})->name('clinic.register');

Route::post('/register-clinic', [RegisterClinicController::class, 'store'])->name('clinic.register.store');

Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');

// En web.php
/*
Route::post('/upgrade-plan', function() {
    Auth::user()->doctor->update(['plan' => 'avanzado']);
    return back()->with('success', '¡Ahora eres un Doctor Avanzado!');
})->name('plan.upgrade');*/

// Rutas Públicas (Pacientes)
Route::get('/search', [SearchController::class, 'index'])->name('search');

// Rutas Privadas (medical partner)
Route::middleware(['auth', 'role:doctor'])->group(function () {    
    // Ver y editar el perfil
    Route::get('/partner/profile', [ProfileDoctorController::class, 'edit'])->name('partner.profile.edit');
    Route::post('/partner/profile/plan', [PlanController::class, 'update'])->name('partner.profile.plan.update');
            
    //Gestion de servicios
    Route::get('/partner/services', [ServiceController::class, 'index'])->name('partner.services.index');
    Route::get('/partner/services/create', [ServiceController::class, 'create'])->name('partner.services.create');
    Route::post('/partner/services', [ServiceController::class, 'store'])->name('partner.services.store');
    Route::get('/partner/services/{service}/edit', [ServiceController::class, 'edit'])->name('partner.services.edit');
    Route::put('/partner/services/{service}', [ServiceController::class, 'update'])->name('partner.services.update');
    Route::delete('/partner/services/{service}', [ServiceController::class, 'destroy'])->name('partner.services.destroy');

    // Gestión de Sedes
    Route::get('/partner/addresses', [AddressController::class, 'index'])->name('partner.addresses.index');
    Route::get('/partner/addresses/create', [AddressController::class, 'create'])->name('partner.addresses.create');
    Route::post('/partner/addresses', [AddressController::class, 'store'])->name('partner.addresses.store');
    Route::put('/partner/addresses/{address}', [AddressController::class, 'update'])->name('partner.addresses.update');
    Route::delete('/partner/addresses/{address}', [AddressController::class, 'destroy'])->name('partner.addresses.destroy');
    Route::get('/partner/addresses/{address}/edit', [AddressController::class, 'edit'])->name('partner.addresses.edit');
    Route::patch('/partner/services/{service}/toggle', [ServiceController::class, 'toggleStatus'])->name('partner.services.toggle');
    
    // Ver el listado y formulario de horarios de una sede
    Route::get('/partner/addresses/{address}/schedules', [ScheduleController::class, 'index'])->name('partner.schedules.index');
    Route::get('/partner/addresses/{address}/schedules/edit', [ScheduleController::class, 'edit'])->name('partner.schedules.edit');
    Route::put('/partner/addresses/{address}/schedules/update', [ScheduleController::class, 'update'])->name('partner.schedules.update');
    
    // Guardar el horario
    Route::post('/partner/schedules', [ScheduleController::class, 'store'])->name('partner.schedules.store');
    Route::delete('/partner/schedules/{schedule}', [ScheduleController::class, 'destroy'])->name('partner.schedules.destroy');
    Route::get('/partner/appointments', [DoctorAppointmentController::class, 'index'])->name('partner.appointments.index');
    Route::patch('/partner/addresses/{address}/status', [AddressController::class, 'toggleStatus'])->name('partner.addresses.status.toggle');

    //buscador de pacientes
    Route::get('/partner/patients', [PartnerPatientController::class, 'index'])->name('partner.patients.index');
    //vista detallada del paciente
    Route::get('partner/patients/{id}', [PartnerPatientController::class, 'show'])->name('partner.patients.show');
});

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

Route::get('/{partner_slug}/{campaign_slug}.html', PublicLanding::class)->name('landing.public');

// Ruta para ver el perfil del doctor en la busqueda -- esta es la ruta que causa error al entrar al dashboard logueado
Route::get('/medical-partner/{partner:slug}', [PublicProfileController::class, 'show'])->name('partner.public.profile');

// Ruta API para que FullCalendar cargue los huecos libres
Route::get('/api/{partner}/availability', [PublicProfileController::class, 'getAvailability'])
    ->name('api.partner.availability')
    ->missing(function () {
    return redirect()->route('home'); // Redirige al inicio si no existe
});

Route::get('/api/get-slots', function (Request $request) {
    // Validamos que lleguen los datos
    if (!$request->address_id || !$request->date) {
        return response()->json(['error' => 'Faltan datos'], 400);
    }

    // Lógica para buscar turnos ocupados y devolver los libres
    // Ejemplo de respuesta manual:
    return [
        ["time" => "08:00", "available" => true],
        ["time" => "08:20", "available" => true],
        ["time" => "08:40", "available" => true],
        ["time" => "09:00", "available" => true],
        ["time" => "09:20", "available" => true],
        ["time" => "09:40", "available" => true],
        ["time" => "10:00", "available" => false],
        ["time" => "10:20", "available" => false],
        ["time" => "10:40", "available" => true],
        ["time" => "12:00", "available" => true],
        ["time" => "12:20", "available" => false],
        ["time" => "12:40", "available" => true],
        ["time" => "13:00", "available" => true],
        ["time" => "13:20", "available" => true],
        ["time" => "13:40", "available" => true],
        ["time" => "14:00", "available" => true],
        ["time" => "14:20", "available" => false],
        ["time" => "14:40", "available" => true],
        ["time" => "15:00", "available" => true],
        ["time" => "15:20", "available" => true],
        ["time" => "15:40", "available" => true],
        ["time" => "16:00", "available" => true],
        ["time" => "16:20", "available" => true],
        ["time" => "16:40", "available" => true],
        ["time" => "17:00", "available" => true],
        ["time" => "17:20", "available" => true],
        ["time" => "17:40", "available" => false],
        ["time" => "18:00", "available" => true],
        ["time" => "18:20", "available" => true],
        ["time" => "18:40", "available" => false],
        ["time" => "19:00", "available" => true],
        ["time" => "19:20", "available" => true],
        ["time" => "19:40", "available" => false],
        ["time" => "20:00", "available" => true],
        ["time" => "20:20", "available" => true],
        ["time" => "20:40", "available" => false],
        ["time" => "21:00", "available" => true],
        ["time" => "21:20", "available" => false],
        ["time" => "21:40", "available" => true],
        ["time" => "22:00", "available" => true],
    ];
})->name('api.slots.index');

//pasos de la reservacion
Route::middleware(['auth'])->group(function () {    
    // Esta es la que pones en el FORMULARIO
    Route::get('/appointments/confirm/{id}', [AppointmentController::class, 'confirm'])->name('appointments.confirm');    
});

Route::post('/appointments/step-two', [AppointmentController::class, 'storeStepTwo'])->name('appointments.step-two');
Route::get('/appointments/patient', [AppointmentController::class, 'patient'])->name('appointments.patient');
Route::post('/appointments/process-patient', [AppointmentController::class, 'processPatient'])->name('appointments.process-patient');
Route::get('/appointments/preview/{id}', [AppointmentController::class, 'preview'])->name('appointments.preview');
Route::get('/appointments/success/{id}', [AppointmentController::class, 'success'])->name('appointments.success');

// Vista de la tabla de precios
Route::get('/plans/show', [PlanController::class, 'showPlans'])->name('plans.index');
// Acción de seleccionar/suscribirse a un plan
Route::post('/planes/{plan}/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');