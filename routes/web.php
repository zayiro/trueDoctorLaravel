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
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\MedicalExamController; 
use App\Http\Controllers\DashboardController;

// 🔥 REFACTORIZACIÓN CORE: Conectamos la URL central con tu DashboardController analítico
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
});

// Rutas Privadas (admin)
Route::middleware(['auth', 'role:admin'])->group(function () {
    // Lista de médicos pendientes
    Route::get('/administrator/validation', [ValidationController::class, 'index'])
         ->name('administrator.validation.index');
         
    // Acciones de aprobación/rechazo
    Route::post('/administrator/{doctor}/validate', [ValidationController::class, 'update'])
         ->name('administrator.validation.update');

    // Ruta segura para ver los documentos protegidos
    Route::get('/administrator/{doctor}/document/{type}', [ValidationController::class, 'viewDocument'])
         ->name('administrator.document.view');

    //borrar cache desde el navegador cuando este logueado con role de admin
    Route::get('/clear-cache', [AdminController::class, 'clearCache'])->name('administrator.clearcache.index');         

    // Rutas protegidas de administración
    Route::get('/administrator/seo-sintomas', [AdminController::class, 'listIndexedSymptoms'])->name('administrator.symptoms.index');
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
});

// Grupo exclusivo para la administración de nóminas de centros médicos
Route::middleware(['auth', 'role:clinic'])->prefix('partner/clinic')->name('partner.')->group(function () {
    Route::get('/doctors', [ClinicDoctorController::class, 'index'])->name('clinic_doctors.index');
    Route::post('/doctors', [ClinicDoctorController::class, 'store'])->name('clinic_doctors.store');
    Route::patch('/doctors/{doctor}/toggle', [ClinicDoctorController::class, 'toggleStatus'])->name('clinic_doctors.toggle');
    Route::delete('/doctors/{doctor}', [ClinicDoctorController::class, 'destroy'])->name('clinic_doctors.destroy');
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
    Route::post('/patient/appointments/{id}/cancel', [AppointmentController::class, 'cancelWeb'])->name('patient.appointments.cancel');

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
});

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

// Rutas Públicas
Route::get('/search', [SearchController::class, 'index'])->name('search');
// Asegúrate de colocarla con método GET para que la paginación funcione correctamente
Route::get('/search-by-symptom', [SearchController::class, 'searchBySymptom'])->name('partner.search.symptom');
// Nueva vista dedicada al Triage Inteligente
Route::get('/search-symptom', [SearchController::class, 'searchSymptomView'])->name('search.symptom.view');

Route::get('/{partner_slug}/{campaign_slug}.html', PublicLanding::class)->name('landing.public');

// Ruta para ver el perfil del doctor en la busqueda -- esta es la ruta que causa error al entrar al dashboard logueado
Route::get('/medical-partner/{partner:slug}', [PublicProfileController::class, 'show'])->name('partner.public.profile');

// Ruta API para que FullCalendar cargue los huecos libres
Route::get('/api/{partner}/availability', [PublicProfileController::class, 'getAvailability'])
    ->name('api.partner.availability')
    ->missing(function () {
    return redirect()->route('home'); // Redirige al inicio si no existe
});
/*
Route::get('/api/get-slots', function (Request $request) {
    if (!$request->date) {
        return response()->json(['error' => 'Faltan datos'], 400);
    }

    $fechaConsultada = Carbon::parse($request->date);
    $diaSemana = $fechaConsultada->dayOfWeek; 
    $ahora = Carbon::now();
    $isVirtual = $request->input('is_virtual') === 'true';

    // NUEVO SEGURO: Obtener el doctor_id directamente de la dirección sin requerir auth()
    $address = Address::find($request->address_id);
    if (!$address) {
        return response()->json(['error' => 'Dirección no encontrada'], 404);
    }
    $doctorId = $address->doctor_id;

    // 1. VALIDAR AUSENCIAS (UNAVAILABILITIES) utilizando la variable segura $doctorId
    $isUnavailable = Unavailability::where('doctor_id', $doctorId)
        ->whereDate('start_date', '<=', $fechaConsultada)
        ->whereDate('end_date', '>=', $fechaConsultada)
        ->where(function($q) use ($request, $isVirtual) {
            if ($isVirtual) {
                $q->whereNull('address_id');
            } else {
                $q->whereNull('address_id')
                  ->orWhere('address_id', $request->address_id);
            }
        })
        ->exists();

    if ($isUnavailable) {
        return response()->json([]); // Bloqueo total: el doctor no está disponible
    }

    // 2. Obtener disponibilidad del Schedule (Igual que antes)
    if ($isVirtual) {
        $schedule = Schedule::where('day', $diaSemana)
            ->selectRaw('MIN(start_time) as start_time, MAX(end_time) as end_time')
            ->first();
    } else {
        $schedule = Schedule::where('address_id', $request->address_id)
            ->where('day', $diaSemana)
            ->first();
    }

    if (!$schedule || !$schedule->start_time) return response()->json([]); 

    $duracion = (int) $request->input('duration', 20); 

    // 3. Obtener ocupación (Igual que antes)
    $citasOcupadas = Appointment::where('date', $request->date)
        ->whereIn('status', ['confirmed', 'pending'])
        ->when(!$isVirtual, function($query) use ($request) {
            return $query->where('address_id', $request->address_id);
        })
        ->when($request->exclude_id, function($query) use ($request) {
            return $query->where('id', '!=', $request->exclude_id);
        })
        ->pluck('start_time')
        ->map(fn($time) => Carbon::parse($time)->format('H:i'))
        ->toArray();

    // 4. Generación de Slots (Igual que antes)
    $slots = [];
    $inicio = Carbon::parse($schedule->start_time);
    $fin = Carbon::parse($schedule->end_time);

    while ($inicio->copy()->addMinutes($duracion) <= $fin) {
        $horaSlot = $inicio->format('H:i');
        $objHoraSlot = Carbon::parse($request->date . ' ' . $horaSlot);

        $estaOcupado = in_array($horaSlot, $citasOcupadas);
        $esPasado = $fechaConsultada->isToday() && $objHoraSlot->lt($ahora);

        $slots[] = [
            "time" => $horaSlot,
            "available" => !$estaOcupado && !$esPasado
        ];

        $inicio->addMinutes($duracion); 
    }

    return response()->json($slots);
})->name('api.slots.index');*/


// ========================================================
// ENDPOINTS DE CONSULTA PÚBLICA PARA EL PERFIL (APIs WEB)
// ========================================================
// Endpoint unificado y ultra-seguro para el cálculo de slots en el calendario (Web y Reagendamiento)
Route::get('/api/get-slots', [PartnerAppointmentController::class, 'getSlots'])->name('api.slots.index');
// Indispensable para alimentar el Paso 2 de servicios por sede
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
//Route::redirect('/dashboard', '/admin', 301);

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
