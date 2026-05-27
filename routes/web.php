<?php

use App\Models\City;

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
use App\Http\Controllers\PartnerAppointmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\PartnerPatientController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\UnavailabilityController;
use App\Http\Controllers\MedicalExpertiseController;
use App\Http\Controllers\DoctorSettingController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\ValidationController;
use App\Http\Controllers\MedicalExamController; 
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorClinicController;
use App\Http\Controllers\ClinicDoctorController;
use App\Http\Controllers\ReviewController;

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\UserManagementController;


// URL central del DashboardController analítico
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    //'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

// Rutas Privadas (admin)
Route::middleware(['auth', 'role:admin'])->group(function () {    
    // Listado de validaciones
    Route::get('/validation', [ValidationController::class, 'index'])->name('administrator.validation.index');
    
    // Procesar aprobación o rechazo
    Route::post('/validation/update', [ValidationController::class, 'update'])->name('administrator.validation.update');
    
    // Visualizador seguro de documentos
    Route::get('/document/view/{type}', [ValidationController::class, 'viewDocument'])->name('administrator.document.view');

    //borrar cache desde el navegador cuando este logueado con role de admin
    Route::get('/clear-cache', [AdminController::class, 'clearCache'])->name('administrator.clearcache.index');         

    // Rutas protegidas de administración
    Route::get('/administrator/seo-sintomas', [AdminController::class, 'listIndexedSymptoms'])->name('administrator.symptoms.index');

    // 1. Directorio Principal de Usuarios
    Route::get('/users', [UserManagementController::class, 'index'])
        ->name('administrator.users.index');
    
    // 2. Switch de activación/suspensión urgente
    Route::patch('/users/{user}/toggle', [UserManagementController::class, 'toggleStatus'])
        ->name('administrator.users.toggle');
    
    // 3. Disparador manual de recuperación de contraseña
    Route::post('/users/{user}/reset-password', [UserManagementController::class, 'sendResetLink'])
        ->name('administrator.users.reset');

    // Rutas para la creación de administradores del staff
    Route::get('/users/create-admin', [UserManagementController::class, 'createAdmin'])->name('administrator.users.createAdmin');
    Route::post('/users/create-admin', [UserManagementController::class, 'storeAdmin'])->name('administrator.users.storeAdmin');
});

// Rutas Privadas (medical partner)
Route::middleware(['auth', 'role:doctor'])->group(function () {    
    Route::get('/partner/settings', [DoctorSettingController::class, 'edit'])->name('partner.settings.edit');
    Route::put('/partner/settings', [DoctorSettingController::class, 'update'])->name('partner.settings.update');

    // Ver y editar el perfil
    Route::get('/partner/profile', [ProfileDoctorController::class, 'edit'])->name('partner.profile.edit');
    Route::put('/partner/profile', [ProfileDoctorController::class, 'update'])->name('partner.profile.update');
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

    Route::get('/appointments', [PartnerAppointmentController::class, 'index'])->name('partner.appointments.index');
    Route::get('/partner/appointments', [PartnerAppointmentController::class, 'index'])->name('partner.appointments.index');
    Route::patch('/partner/appointments/{appointment}/complete', [PartnerAppointmentController::class, 'complete'])->name('partner.appointments.complete');
    Route::patch('/partner/appointments/{appointment}/cancel', [PartnerAppointmentController::class, 'cancel'])->name('partner.appointments.cancel');
    Route::delete('/partner/appointments/{appointment}', [PartnerAppointmentController::class, 'destroy'])->name('partner.appointments.destroy');

    Route::patch('/partner/addresses/{address}/status', [AddressController::class, 'toggleStatus'])->name('partner.addresses.status.toggle');

    // ========================================================
    // BLOQUEOS TEMPORALES Y MANEJO DE AUSENCIAS
    // ========================================================
    Route::post('/partner/unavailabilities', [UnavailabilityController::class, 'store'])->name('partner.unavailabilities.store');
    Route::delete('/partner/unavailabilities/{unavailability}', [UnavailabilityController::class, 'destroy'])->name('partner.unavailabilities.destroy');

    // ========================================================
    // EXPEDIENTES DE PACIENTES E HISTORIAL MÉDICO
    // ========================================================
    //buscador de pacientes
    Route::get('/partner/patients', [PartnerPatientController::class, 'index'])->name('partner.patients.index');
    //vista detallada del paciente
    Route::get('partner/patients/{id}', [PartnerPatientController::class, 'show'])->name('partner.patients.show');

    // Vista de Reagendamiento Táctico (Alpine.js / API Slots compatible)
    // Vista para seleccionar el nuevo horario
    Route::get('/partner/appointments/{appointment}/reschedule', [AppointmentController::class, 'rescheduleView'])->name('partner.appointments.reschedule');
    // Acción para procesar el cambio
    Route::put('/partner/appointments/{appointment}/reschedule/process', [AppointmentController::class, 'rescheduleProcess'])->name('partner.appointments.reschedule.process');

    // Ruta para cancelar la cita (la que está causando el error)
    Route::delete('/partner/appointments/{appointment}', [AppointmentController::class, 'destroy'])->name('partner.appointments.destroy');

    // ========================================================
    // SINTOMATOLOGÍAS E INDEXACIÓN DE ENFERMEDADES (SINTOMAS)
    // ========================================================
    // Listado principal (Index)
    Route::get('/partner/expertises', [MedicalExpertiseController::class, 'index'])->name('partner.expertises.index');
    // Procesar el guardado del formulario (Store)
    Route::post('/partner/expertises', [MedicalExpertiseController::class, 'store'])->name('partner.expertises.store');
    // Formulario de edición (Edit)
    Route::get('/partner/expertises/{expertise}/edit', [MedicalExpertiseController::class, 'edit'])->name('partner.expertises.edit');
    // Procesar la actualización (Update)
    Route::put('/partner/expertises/{expertise}', [MedicalExpertiseController::class, 'update'])->name('partner.expertises.update');
    // Eliminar un registro (Destroy)
    Route::delete('/partner/expertises/{expertise}', [MedicalExpertiseController::class, 'destroy'])->name('partner.expertises.destroy');    

    Route::post('/partner/appointments/{id}/generate-zoom', [AppointmentController::class, 'generateZoomLink'])->name('partner.appointments.generate_zoom');

    Route::get('/campaigns', \App\Livewire\Campaigns\CampaignIndex::class)->name('campaigns.index');
    Route::get('/campaigns/create', \App\Livewire\Campaigns\CreateCampaign::class)->name('campaigns.create');

    // Bandeja de clínicas vinculadas para el médico
    Route::get('clinics', [DoctorClinicController::class, 'index'])->name('doctor_clinics.index');
    
    // Procesos de aceptación o desvinculación voluntaria
    Route::patch('clinics/{clinic}/accept', [DoctorClinicController::class, 'accept'])->name('doctor_clinics.accept');
    Route::delete('clinics/{clinic}/reject', [DoctorClinicController::class, 'reject'])->name('doctor_clinics.reject');
});

// Grupo exclusivo para la administración de nóminas de centros médicos
Route::middleware(['auth', 'role:clinic'])->prefix('partner/clinic')->group(function () {    
    Route::get('/clinic/doctors', [ClinicDoctorController::class, 'index'])->name('clinic_doctors.index');
    Route::post('/clinic/doctors', [ClinicDoctorController::class, 'store'])->name('clinic_doctors.store');
    Route::patch('/clinic/doctors/{doctor}/toggle', [ClinicDoctorController::class, 'toggleStatus'])->name('clinic_doctors.toggle');
    Route::delete('/clinic/doctors/{id}', [ClinicDoctorController::class, 'destroy'])->name('clinic_doctors.destroy');
});

// Rutas Privadas (patient)
Route::middleware(['auth', 'role:patient'])->group(function () {        
    Route::get('/patient/patient-identification', [PatientController::class, 'index'])->name('patient.patient-identification.index');
    Route::put('/patient/patient-identification/{patient}', [PatientController::class, 'update'])->name('patient.patient-identification.update');
    Route::get('/patient/appointments', [PatientController::class, 'appointments'])->name('patient.appointments.index');
    Route::get('/patient/allergies', [PatientController::class, 'indexAllergy'])->name('patient.allergies.index');
    Route::get('/patient/history', [PatientController::class, 'history'])->name('patient.history.index');
    Route::get('/patient/surgeries', [PatientController::class, 'surgeries'])->name('patient.surgeries.index');
    Route::post('/patient/surgeries', [PatientController::class, 'storeSurgery'])->name('patient.surgeries.store');
    Route::get('/patient/surgeries/{surgery}/edit', [PatientController::class, 'editSurgery'])->name('patient.surgeries.edit');
    Route::put('/patient/surgeries/{surgery}', [PatientController::class, 'updateSurgery'])->name('patient.surgeries.update');
    Route::delete('/patient/surgeries/{surgery}', [PatientController::class, 'destroySurgery'])->name('patient.surgeries.destroy');
    Route::post('/patient/{id}/allergies', [PatientController::class, 'storeAllergy'])->name('patient.allergies.store');
    Route::delete('/patient/allergies/{allergy}', [PatientController::class, 'destroyAllergy'])->name('patient.allergies.destroy');
    Route::get('/patient/family-history', [PatientController::class, 'indexFamilyHistory'])->name('patient.family-history.index');
    Route::post('/patient/family-history', [PatientController::class, 'storeFamilyHistory'])->name('patient.family-history.store');
    Route::delete('/patient/allergies/{id}', [PatientController::class, 'destroyFamilyHistory'])->name('patient.family-history.destroy');

    Route::get('/patient/medications', [PatientController::class, 'indexMedication'])->name('patient.medications.index');
    Route::delete('/patient/medications/{medication}', [PatientController::class, 'destroyMedication'])->name('patient.medications.destroy');

    Route::post('/patient/medications', [PatientController::class, 'storeMedication'])->name('patient.medications.store');
    Route::put('/patient/medications/{medication}', [PatientController::class, 'updateMedication'])->name('patient.medications.update');
    Route::patch('/patient/medications/{medication}/toggle', [PatientController::class, 'toggleStatusMedication'])->name('patient.medications.toggle');
    Route::get('/patient/pdf/clinical-history/{patient}', [PatientController::class, 'downloadClinicalHistory'])->name('patient.pdf.clinical-history');

    // Ruta para que el paciente ejecute la cancelación desde la web
    Route::post('/patient/appointments/{id}/cancel', [PatientController::class, 'cancelWeb'])->name('patient.appointments.cancel');

    // Ruta de la sala de espera para el paciente
    Route::get('/patient/meet/{room_code}', [AppointmentController::class, 'waitingRoom'])->name('patient.appointments.waiting_room');
});

//pasos de la reservacion
Route::middleware(['auth'])->group(function () {    
    // Vista principal de todas las notificaciones
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    // Acción para marcar una o todas como leídas
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    // Esta es la que pones en el FORMULARIO
    Route::get('/appointments/confirm/{id}', [AppointmentController::class, 'confirm'])->name('appointments.confirm');    

    // Ruta para procesar el formulario de documentos
    Route::post('/partner/verify-documents', [VerificationController::class, 'store'])->name('partner.verify.store');

    // Buscador de citas por referencia
    Route::get('/appointments/search', [AppointmentController::class, 'searchByReference'])->name('appointments.search');

    Route::get('/appointments/search', [AppointmentController::class, 'searchByReference'])->name('appointments.search');
    
    // Ruta de actualización parcial de estado con variables en inglés
    Route::patch('/appointments/{appointment}/status', [AppointmentController::class, 'updateStatus'])->name('appointments.updateStatus');
});

// ========================================================
// 🌍 ENDPOINTS PÚBLICOS DE CONSULTA (ACCESO LIBRE PARA LOS USUARIOS)
// ========================================================

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/contact', [ContactController::class, 'showContact'])->name('contact.show');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

Route::get('/terms', [ContactController::class, 'showTerms'])->name('terms.show');
Route::get('/privacy', [ContactController::class, 'showPrivacy'])->name('privacy.show');
Route::get('/support', [ContactController::class, 'showSupport'])->name('support.show');

Route::get('/register-options', function () {
    return view('auth.register-options');
})->name('register.options');

// Ruta para mostrar el formulario de registro de doctores
Route::get('/register-partner', [RegisterDoctorController::class, 'register'])->name('partner.register');
Route::post('/register-partner', [RegisterDoctorController::class, 'store'])->name('partner.register.store');

// Ruta para mostrar el formulario de registro de clinicas
Route::get('/register-clinic', [RegisterClinicController::class, 'register'])->name('clinic.register');
Route::post('/register-clinic', [RegisterClinicController::class, 'store'])->name('clinic.register.store');

Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store')->middleware('auth');

Route::get('/search', [SearchController::class, 'index'])->name('search');
// Asegúrate de colocarla con método GET para que la paginación funcione correctamente
Route::get('/search-by-symptom', [SearchController::class, 'searchBySymptom'])->name('partner.search.symptom');
// Nueva vista dedicada al Triage Inteligente
Route::get('/search-symptom', [SearchController::class, 'searchSymptomView'])->name('search.symptom.view');

Route::get('/{partner_slug}/{campaign_slug}.html', PublicLanding::class)->name('landing.public');

// Ruta para ver el perfil del doctor en la busqueda
Route::get('/medical-partner/{partner:slug}', [PublicProfileController::class, 'show'])->name('partner.public.profile');

// Ruta API para que FullCalendar cargue los huecos libres
Route::get('/api/{partner}/availability', [PublicProfileController::class, 'getAvailability'])
    ->name('api.partner.availability')
    ->missing(function () {
    return redirect()->route('home'); // Redirige al inicio si no existe
});

// Endpoint unificado y ultra-seguro para el cálculo de slots en el calendario (Web y Reagendamiento)
Route::get('/api/slots', [PartnerAppointmentController::class, 'getSlots'])->name('api.slots.index');

// Catálogo dinámico de servicios por sede
Route::get('/api/addresses/{address}/services', [PartnerAppointmentController::class, 'getServices'])->name('api.addresses.services');

Route::post('/appointments/step-two', [AppointmentController::class, 'storeStepTwo'])->name('appointments.step-two');
Route::get('/appointments/patient', [AppointmentController::class, 'patient'])->name('appointments.patient');
Route::post('/appointments/process-patient', [AppointmentController::class, 'processPatient'])->name('appointments.process-patient');
Route::get('/appointments/preview/{id}', [AppointmentController::class, 'preview'])->name('appointments.preview');
Route::get('/appointments/success/{id}', [AppointmentController::class, 'success'])->name('appointments.success');

// Vista de la tabla de precios
Route::get('/plans/show', [PlanController::class, 'showPlans'])->name('plans.index');
// Acción de seleccionar/suscribirse a un plan
Route::post('/plans/{plan}/subscribe', [PlanController::class, 'subscribe'])->name('plans.subscribe');

// routes/api.php o routes/web.php
Route::get('/api/departments/{department}/cities', function ($deptId) {
    return City::where('department_id', $deptId)
        ->where('state', true)
        ->orderBy('name')
        ->get(['id', 'name']);
});

// Ruta pública para que Google rastree todos tus enlaces indexables
Route::get('/sitemap.xml', [SearchController::class, 'generateSitemap'])->name('seo.sitemap');

//Páginas de Síntomas Indexables Automáticas

// LA RUTA SEO DINÁMICA: Google y los usuarios entrarán aquí
Route::get('/sintomas/{slug}', [SearchController::class, 'showSymptomLanding'])->name('symptoms.landing');

// Directorio médico de socios ordenado por planes de suscripción
Route::get('/medical-directory', [SearchController::class, 'medicalDirectory'])->name('medical.directory');

Route::get('/examenes', [MedicalExamController::class, 'index'])->name('exams.index');
Route::post('/examenes', [MedicalExamController::class, 'store'])->name('exams.store');
Route::get('/examenes/{id}/pago', [MedicalExamController::class, 'checkout'])->name('exams.checkout');
Route::post('/examenes/{id}/pagar', [MedicalExamController::class, 'processPayment'])->name('exams.id_pago');
Route::get('/examenes/{id}/resultado', [MedicalExamController::class, 'showResult'])->name('exams.result');
Route::get('/examenes/{id}/pago', [MedicalExamController::class, 'checkout'])->name('exams.checkout');
