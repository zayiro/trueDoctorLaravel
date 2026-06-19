<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Clinic;
use App\Models\ClinicSetting;
use App\Models\City;
use App\Models\Department;
use App\Models\Doctor;
use App\Models\DoctorSetting;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Specialty;
use App\Models\Unavailability;
use App\Models\User;
use App\Services\AppointmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AppointmentService $appointmentService;
    protected Doctor $doctor;
    protected Clinic $clinic;
    protected Address $address;
    protected Service $service;
    protected Patient $patient;
    protected User $doctorUser;
    protected User $clinicUser;
    protected User $patientUser;

    /**
     * Setup inicial para todas las pruebas.
     * Crea la estructura base: Doctor, Clínica, Dirección, Servicio, Paciente.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->appointmentService = app(AppointmentService::class);

        // 1. Crear departamento y ciudad (requeridos para direcciones)
        $department = Department::factory()->create();
        $city = City::factory()->create(['department_id' => $department->id]);

        // 2. Crear usuario y doctor independiente
        $this->doctorUser = User::factory()->create(['role' => 'doctor']);
        $this->doctor = Doctor::factory()->create(['user_id' => $this->doctorUser->id]);

        // 3. Crear usuario y clínica
        $this->clinicUser = User::factory()->create(['role' => 'clinic']);
        $this->clinic = Clinic::factory()->create(['user_id' => $this->clinicUser->id]);

        // 4. Crear configuración de clínica (necesaria para políticas)
        ClinicSetting::factory()->create([
            'clinic_id' => $this->clinic->id,
            'min_notice_hours' => 2,
            'cancellation_notice_hours' => 2,
            'allow_patient_cancellation' => true,
        ]);

        // 5. Crear configuración de doctor
        DoctorSetting::factory()->create([
            'doctor_id' => $this->doctor->id,
            'min_notice_hours' => 24,
            'cancellation_notice_hours' => 24,
            'allow_patient_cancellation' => true,
        ]);

        // 6. Crear dirección física para el doctor
        $this->address = Address::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'city_id' => $city->id,
            'type' => 'physical',
            'status' => true,
        ]);

        // 7. Crear especialidad y servicio
        $specialty = Specialty::factory()->create();
        $this->service = Service::factory()->create(['type' => 'physical']);
        $this->service->specialties()->attach($specialty->id);
        $this->service->addresses()->attach($this->address->id, ['duration' => 30, 'price' => 50000]);

        // 8. Crear usuario y paciente
        $this->patientUser = User::factory()->create(['role' => 'patient']);
        $this->patient = Patient::factory()->create(['user_id' => $this->patientUser->id]);
    }

    /**
     * TEST 1: checkIfCanModify() - Caso exitoso (cita modificable)
     * 
     * Verifica que una cita reciente pueda ser modificada.
     */
    public function test_check_if_can_modify_allows_modification_for_recent_appointment()
    {
        // Arrange: Crear cita para dentro de 48 horas
        $futureDate = Carbon::now()->addHours(48);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'patient_id' => $this->patient->id,
            'date' => $futureDate->format('Y-m-d'),
            'start_time' => $futureDate->format('H:i:s'),
            'status' => 'confirmed',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe permitir modificación
        $this->assertTrue($result['allowed']);
        $this->assertStringContainsString('permitida', strtolower($result['message']));
    }

    /**
     * TEST 2: checkIfCanModify() - Cita cancelada (no modificable)
     * 
     * Verifica que una cita cancelada no pueda ser modificada.
     */
    public function test_check_if_can_modify_denies_modification_for_cancelled_appointment()
    {
        // Arrange: Crear cita cancelada
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'patient_id' => $this->patient->id,
            'status' => 'cancelled',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe denegar modificación
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('cancelada', strtolower($result['message']));
    }

    /**
     * TEST 3: checkIfCanModify() - Cita completada (no modificable)
     * 
     * Verifica que una cita completada no pueda ser modificada.
     */
    public function test_check_if_can_modify_denies_modification_for_completed_appointment()
    {
        // Arrange: Crear cita completada
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'patient_id' => $this->patient->id,
            'status' => 'completed',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe denegar modificación
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('completada', strtolower($result['message']));
    }

    /**
     * TEST 4: checkIfCanModify() - Cita dentro del período de anticipación mínima
     * 
     * Verifica que una cita muy próxima no pueda ser modificada.
     */
    public function test_check_if_can_modify_denies_modification_within_notice_period()
    {
        // Arrange: Crear cita para dentro de 12 horas (menos que las 24 requeridas)
        $soonDate = Carbon::now()->addHours(12);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'patient_id' => $this->patient->id,
            'date' => $soonDate->format('Y-m-d'),
            'start_time' => $soonDate->format('H:i:s'),
            'status' => 'confirmed',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe denegar modificación
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('horas', strtolower($result['message']));
    }

    /**
     * TEST 5: checkIfCanModify() - Cita en clínica con políticas diferentes
     * 
     * Verifica que se respeten las políticas de la clínica (menor anticipación).
     */
    public function test_check_if_can_modify_respects_clinic_policies()
    {
        // Arrange: Crear dirección en clínica
        $clinicAddress = Address::factory()->create([
            'doctor_id' => null,
            'clinic_id' => $this->clinic->id,
            'status' => true,
        ]);

        // Crear cita en clínica para dentro de 3 horas (más que las 2 requeridas por clínica)
        $futureDate = Carbon::now()->addHours(3);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => $this->clinic->id,
            'address_id' => $clinicAddress->id,
            'patient_id' => $this->patient->id,
            'date' => $futureDate->format('Y-m-d'),
            'start_time' => $futureDate->format('H:i:s'),
            'status' => 'confirmed',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe permitir modificación (3 horas > 2 horas requeridas)
        $this->assertTrue($result['allowed']);
    }

    /**
     * TEST 6: checkIfCanModify() - Cita inexistente
     * 
     * Verifica que se maneje correctamente cuando la cita no existe.
     */
    public function test_check_if_can_modify_handles_nonexistent_appointment()
    {
        // Act: Verificar cita inexistente
        $result = $this->appointmentService->checkIfCanModify(99999);

        // Assert: Debe retornar error
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('no existe', strtolower($result['message']));
    }

    /**
     * TEST 7: checkIfCanModify() - Cancelación deshabilitada por política
     * 
     * Verifica que se respete la política de cancelación deshabilitada.
     */
    public function test_check_if_can_modify_respects_disabled_cancellation_policy()
    {
        // Arrange: Actualizar política del doctor para deshabilitar cancelación
        $this->doctor->settings()->update(['allow_patient_cancellation' => false]);

        $futureDate = Carbon::now()->addHours(48);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $this->doctor->id,
            'clinic_id' => null,
            'patient_id' => $this->patient->id,
            'date' => $futureDate->format('Y-m-d'),
            'start_time' => $futureDate->format('H:i:s'),
            'status' => 'confirmed',
        ]);

        // Act: Verificar si se puede modificar
        $result = $this->appointmentService->checkIfCanModify($appointment->id);

        // Assert: Debe denegar modificación
        $this->assertFalse($result['allowed']);
        $this->assertStringContainsString('no permiten', strtolower($result['message']));
    }
}
