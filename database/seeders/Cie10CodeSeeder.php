<?php

namespace Database\Seeders;

use App\Models\Cie10Code;
use Illuminate\Database\Seeder;

class Cie10CodeSeeder extends Seeder
{
    /**
     * Pobla códigos CIE-10 frecuentes en consulta médica general y telemedicina.
     */
    public function run(): void
    {
        $codes = [
            ['code' => 'J00', 'description' => 'Rinofaringitis aguda (resfriado común)'],
            ['code' => 'J02.9', 'description' => 'Faringitis aguda, no especificada'],
            ['code' => 'J03.9', 'description' => 'Amigdalitis aguda, no especificada'],
            ['code' => 'J06.9', 'description' => 'Infección aguda de las vías respiratorias superiores, no especificada'],
            ['code' => 'J20.9', 'description' => 'Bronquitis aguda, no especificada'],
            ['code' => 'J45.9', 'description' => 'Asma, no especificada'],
            ['code' => 'I10', 'description' => 'Hipertensión esencial (primaria)'],
            ['code' => 'E11.9', 'description' => 'Diabetes mellitus tipo 2, sin mención de complicación'],
            ['code' => 'E78.5', 'description' => 'Hiperlipidemia, no especificada'],
            ['code' => 'E66.9', 'description' => 'Obesidad, no especificada'],
            ['code' => 'K21.9', 'description' => 'Enfermedad del reflujo gastroesofágico sin esofagitis'],
            ['code' => 'K29.7', 'description' => 'Gastritis, no especificada'],
            ['code' => 'K30', 'description' => 'Dispepsia funcional'],
            ['code' => 'A09', 'description' => 'Diarrea y gastroenteritis de presunto origen infeccioso'],
            ['code' => 'N39.0', 'description' => 'Infección de vías urinarias, sitio no especificado'],
            ['code' => 'G43.9', 'description' => 'Migraña, no especificada'],
            ['code' => 'G44.2', 'description' => 'Cefalea debida a tensión'],
            ['code' => 'R51', 'description' => 'Cefalea'],
            ['code' => 'M54.5', 'description' => 'Lumbago no especificado'],
            ['code' => 'M54.2', 'description' => 'Cervicalgia'],
            ['code' => 'F41.1', 'description' => 'Trastorno de ansiedad generalizada'],
            ['code' => 'F32.9', 'description' => 'Episodio depresivo, no especificado'],
            ['code' => 'L20.9', 'description' => 'Dermatitis atópica, no especificada'],
            ['code' => 'L50.9', 'description' => 'Urticaria, no especificada'],
            ['code' => 'R50.9', 'description' => 'Fiebre, no especificada'],
            ['code' => 'Z00.0', 'description' => 'Examen médico general'],
            ['code' => 'Z76.0', 'description' => 'Consulta para repetición de receta'],
        ];

        foreach ($codes as $item) {
            Cie10Code::updateOrCreate(
                ['code' => $item['code']],
                ['description' => $item['description'], 'is_active' => true]
            );
        }
    }
}
