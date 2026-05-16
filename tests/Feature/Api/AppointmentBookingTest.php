<?php

namespace Tests\Feature\Api;

use App\Models\Address;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    // Esta propiedad vacía y recrea la base de datos limpia en memoria antes de CADA test
    use RefreshDatabase;

    protected $doctor;
    protected $address;
    protected $patient;
    protected $service;

    /**
     * El método setUp se ejecuta automáticamente antes de iniciar las pruebas.
     * Lo usamos para crear datos base falsos (Seeds en caliente).
     */
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear un usuario y su perfil de doctor asociado
        $user = User::factory()->create(['name' => 'Dr. Gregory House']);
        $this->doctor = Doctor::create([
            'phone' => '123456789',
            'medical_license' => 'LIC-12345',
            'identification' => 'ID-999',
        ]);
        // Vincular el usuario manualmente si no usas fábricas anidadas
        $this->doctor->user()->associate($user)->save();

        // 2. Crear una dirección física para el doctor
        $this->address = Address::create([
            'doctor_id' => $this->doctor->id,
            'name'      => 'Consultorio Norte',
            'address'   => 'Calle Falsa 123',
            'type'      => 'presencial',
            'city_id'   => '11001',
            'status'    => true,
        ]);

        // 3. Crear el horario de atención: Lunes (day = 1) de 08:00 a 10:00
        Schedule::create([
            'address_id' => $this->address->id,
            'day'        => 1, // Lunes
            'start_time' => '08:00:00',
            'end_time'   => '10:00:00',
        ]);

        // 4. Crear un paciente y un servicio ficticio
        $this->patient = Patient::factory()->create(); // Asumiendo que tienes PatientFactory
        $this->service = Service::factory()->create(); // Asumiendo que tienes ServiceFactory
    }

    /** @test */
    public function un_sistema_externo_puede_consultar_slots_disponibles()
    {
        // Simulamos que el próximo lunes es la fecha de consulta (ejemplo: 2026-05-18 es un Lunes)
        $proximoLunes = '2026-05-18'; 

        // Enviamos una petición GET a la API tal como lo haría una app externa
        $response = $this->getJson("/api/get-slots?" . http_build_query([
            'date' => $proximoLunes,
            'address_id' => $this->address->id,
            'is_virtual' => 'false',
            'duration' => 30 // Bloques de 30 minutos
        ]));

        // Verificaciones (Asserts):
        $response->assertStatus(200); // Esperamos un HTTP 200 OK
        
        // De 08:00 a 10:00 con duración de 30 mins, deberían salir 4 slots (08:00, 08:30, 09:00, 09:30)
        $response->assertJsonCount(4); 
        
        // Validamos que el primer slot esté libre
        $response->assertJsonFragment([
            'time' => '08:00',
            'available' => true
        ]);
    }

    /** @test */
    public function no_se_puede_agendar_una_cita_en_un_horario_que_no_existe()
    {
        // Intentamos agendar a las 05:00 AM (El doctor abre a las 08:00)
        $datosCita = [
            'patient_id' => $this->patient->id,
            'service_id' => $this->service->id,
            'address_id' => $this->address->id,
            'date' => '2026-05-18', // Es lunes
            'start_time' => '05:00', // Fuera de horario laboral
            'duration' => 30,
            'price' => 50.00,
            'is_virtual' => false,
        ];

        // Enviamos la petición POST para crear la cita
        $response = $this->postJson('/api/appointments', $datosCita);

        // Verificaciones (Asserts):
        $response->assertStatus(422); // Esperamos error 422 (Unprocessable Entity)
        $response->assertJsonStructure(['error']); // Esperamos que devuelva la estructura con la clave 'error'
    }
}
