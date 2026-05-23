<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\IndexedSymptom;
use App\Models\Specialty;
use Illuminate\Support\Str;


class InitialSymptomsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Mapeo de los 50 síntomas más buscados con su especialidad objetivo y metadatos SEO
        $symptomsData = [
            // MEDICINA GENERAL / INTERNA
            ['query' => 'Fiebre alta que no baja', 'spec' => 'Medicina General', 'urgency' => 'Media', 'advice' => 'Si la fiebre supera los 38.5°C por más de 48 horas o se acompaña de rigidez en el cuello, es vital una valoración médica presencial.'],
            ['query' => 'Fatiga crónica y cansancio extremo', 'spec' => 'Medicina General', 'urgency' => 'Baja', 'advice' => 'El cansancio persistente puede deberse a anemias, problemas tiroideos o deficiencias vitamínicas. Requiere analítica de sangre.'],
            ['query' => 'Pérdida de peso repentina sin hacer dieta', 'spec' => 'Medicina General', 'urgency' => 'Media', 'advice' => 'Una pérdida de peso inexplicable requiere descartar problemas metabólicos o condiciones subyacentes con un médico internista.'],
            ['query' => 'Sudores nocturnos excesivos', 'spec' => 'Medicina General', 'urgency' => 'Media', 'advice' => 'Sudar intensamente por las noches de forma recurrente amerita un chequeo médico completo para evaluar causas del sistema inmune o linfático.'],
            ['query' => 'Presión arterial alta síntomas', 'spec' => 'Medicina General', 'urgency' => 'Media', 'advice' => 'La hipertensión suele ser silenciosa, pero si presenta dolor de cabeza fuerte o zumbido en oídos, debe tomarse la presión de inmediato.'],

            // PEDIATRÍA
            ['query' => 'Fiebre en bebés de tres meses', 'spec' => 'Pediatría', 'urgency' => 'Alta', 'advice' => 'Cualquier pico febril en lactantes menores de 3 meses debe ser evaluado urgentemente por un pediatra de guardia.'],
            ['query' => 'Cólicos fuertes en lactantes', 'spec' => 'Pediatría', 'urgency' => 'Baja', 'advice' => 'Los cólicos son comunes el primer trimestre, pero si hay vómitos constantes o llanto inconsolable por horas, consulte al pediatra.'],
            ['query' => 'Brote en la piel de niños con fiebre', 'spec' => 'Pediatría', 'urgency' => 'Alta', 'advice' => 'Las erupciones cutáneas acompañadas de fiebre pueden indicar infecciones exantemáticas que requieren diagnóstico pediátrico inmediato.'],
            ['query' => 'Tos perruna en niños por la noche', 'spec' => 'Pediatría', 'urgency' => 'Media', 'advice' => 'Suele deberse a laringitis. Mantener al niño en un ambiente húmedo ayuda, pero vigile que no tenga dificultad para respirar.'],

            // GINECOLOGÍA y OBSTETRICIA
            ['query' => 'Retraso menstrual y dolor de ovarios', 'spec' => 'Ginecología', 'urgency' => 'Baja', 'advice' => 'Descarte primero un embarazo. Si es negativo, los desajustes hormonales o quistes ováricos deben ser evaluados por un ginecólogo.'],
            ['query' => 'Flujo vaginal con mal olor y picazón', 'spec' => 'Ginecología', 'urgency' => 'Baja', 'advice' => 'Son síntomas claros de una infección por hongos o bacteriana. Evite la automedicación y asista a una consulta para frotis.'],
            ['query' => 'Sangrado fuera del periodo menstrual', 'spec' => 'Ginecología', 'urgency' => 'Media', 'advice' => 'El sangrado intermenstrual debe ser valorado mediante ecografía para descartar pólipos, miomas o alteraciones endometriales.'],
            ['query' => 'Dolor menstrual muy fuerte que incapacita', 'spec' => 'Ginecología', 'urgency' => 'Baja', 'advice' => 'La dismenorrea severa no es normal y puede ser señal de endometriosis. Un especialista debe realizar una evaluación detallada.'],

            // CARDIOLOGÍA
            ['query' => 'Dolor en el pecho que se va al brazo izquierdo', 'spec' => 'Cardiología', 'urgency' => 'Alta', 'advice' => 'Este es un síntoma de alarma de infarto. Acuda inmediatamente a la sala de emergencias más cercana. No espere.'],
            ['query' => 'Palpitaciones fuertes en el pecho en reposo', 'spec' => 'Cardiología', 'urgency' => 'Media', 'advice' => 'Sentir el ritmo cardíaco acelerado sin hacer esfuerzo requiere un electrocardiograma para descartar arritmias.'],
            ['query' => 'Falta de aire al caminar poco', 'spec' => 'Cardiología', 'urgency' => 'Media', 'advice' => 'La disnea de esfuerzo puede vincularse a insuficiencia cardíaca o problemas pulmonares. Requiere valoración cardiológica.'],

            // DERMATOLOGÍA
            ['query' => 'Lunar que cambia de color y bordes irregulares', 'spec' => 'Dermatología', 'urgency' => 'Media', 'advice' => 'Sigue la regla ABCDE del melanoma. Cualquier cambio de tamaño, borde o color exige una revisión con dermatoscopio.'],
            ['query' => 'Caída del cabello por estrés en mujeres', 'spec' => 'Dermatología', 'urgency' => 'Baja', 'advice' => 'El efluvio telógeno es común tras periodos de estrés. Un dermatólogo puede guiarte con lociones y suplementos específicos.'],
            ['query' => 'Manchas rojas en la piel que pican mucho', 'spec' => 'Dermatología', 'urgency' => 'Baja', 'advice' => 'Puede tratarse de dermatitis, eccemas o urticaria. Evite rascarse para no causar infecciones bacterianas secundarias.'],
            ['query' => 'Acné quístico severo en la edad adulta', 'spec' => 'Dermatología', 'urgency' => 'Baja', 'advice' => 'El acné nodular requiere tratamientos médicos dermatológicos regulados para evitar cicatrices profundas en la piel.'],

            // TRAUMATOLOGÍA y FISIATRÍA
            ['query' => 'Dolor de espalda baja que baja por la pierna', 'spec' => 'Traumatología', 'urgency' => 'Media', 'advice' => 'Suele indicar una compresión del nervio ciático o hernia discal. Evite cargar peso y consulte con un especialista.'],
            ['query' => 'Dolor de rodilla al bajar escaleras', 'spec' => 'Traumatología', 'urgency' => 'Baja', 'advice' => 'Común en problemas de desgaste de cartílago (condromalacia) o meniscos. Un traumatólogo evaluará si requiere resonancia.'],
            ['query' => 'Esguince de tobillo hinchado y morado', 'spec' => 'Traumatología', 'urgency' => 'Media', 'advice' => 'Aplique hielo y mantenga el pie elevado. Es necesario una radiografía para descartar microfracturas óseas.'],

            // GASTROENTEROLOGÍA
            ['query' => 'Gastritis crónica y ardor en la boca del estómago', 'spec' => 'Gastroenterología', 'urgency' => 'Baja', 'advice' => 'El reflujo y ardor constante pueden requerir una endoscopia para descartar la bacteria Helicobacter pylori.'],
            ['query' => 'Estreñimiento severo de varios días', 'spec' => 'Gastroenterología', 'urgency' => 'Baja', 'advice' => 'Aumente el consumo de agua y fibra. Si se acompaña de dolor abdominal intenso o vómitos, acuda a urgencias.'],
            ['query' => 'Sangre roja brillante al evacuar', 'spec' => 'Gastroenterología', 'urgency' => 'Media', 'advice' => 'Frecuente en hemorroides o fisuras, pero es mandatorio un chequeo proctológico para descartar patologías mayores del colon.'],
            ['query' => 'Hinchazón abdominal y gases después de comer', 'spec' => 'Gastroenterología', 'urgency' => 'Baja', 'advice' => 'Puede estar asociado al síndrome de intestino irritable o intolerancias alimentarias (gluten, lactosa).'],

            // NEUROLOGÍA
            ['query' => 'Dolor de cabeza insoportable con sensibilidad a la luz', 'spec' => 'Neurología', 'urgency' => 'Media', 'advice' => 'Son síntomas clásicos de migraña con fotofobia. Un neurólogo te ayudará a establecer un tratamiento preventivo eficaz.'],
            ['query' => 'Mareos constantes al mover la cabeza', 'spec' => 'Neurología', 'urgency' => 'Baja', 'advice' => 'Puede relacionarse con vértigo posicional paroxístico benigno o problemas del oído interno. Requiere diagnóstico.'],
            ['query' => 'Hormigueo y entumecimiento en las manos por las noches', 'spec' => 'Neurología', 'urgency' => 'Baja', 'advice' => 'Síntoma muy habitual del síndrome del túnel carpiano debido a la compresión del nervio mediano en la muñeca.'],

            // OTORRINOLARINGOLOGÍA
            ['query' => 'Zumbido en el oído constante (Tinnitus)', 'spec' => 'Otorrinolaringología', 'urgency' => 'Baja', 'advice' => 'El tinnitus requiere un estudio audiológico completo para determinar si existe pérdida auditiva asociada.'],
            ['query' => 'Dolor de oído fuerte después de nadar', 'spec' => 'Otorrinolaringología', 'urgency' => 'Baja', 'advice' => 'Indica una otitis externa (oído de nadador). Suele requerir gotas antibióticas recetadas por el médico.'],
            ['query' => 'Pérdida del olfato y congestión nasal crónica', 'spec' => 'Otorrinolaringología', 'urgency' => 'Baja', 'advice' => 'La sinusitis crónica o los pólipos nasales pueden obstruir las vías. Se recomienda una nasosinusoscopia.'],

            // UROLOGÍA
            ['query' => 'Ardor intenso al orinar y ganas constantes', 'spec' => 'Urología', 'urgency' => 'Baja', 'advice' => 'Síntomas comunes de infección urinaria (cistitis). Requiere un examen de orina (urocultivo) para dar el antibiótico correcto.'],
            ['query' => 'Dificultad para orinar y chorro débil en hombres', 'spec' => 'Urología', 'urgency' => 'Baja', 'advice' => 'En hombres mayores de 45 años suele indicar un crecimiento de la próstata (hiperplasia). Requiere control anual urológico.'],

            // OFTALMOLOGÍA
            ['query' => 'Visión borrosa de repente en un ojo', 'spec' => 'Oftalmología', 'urgency' => 'Alta', 'advice' => 'La pérdida súbita de visión es una emergencia médica que puede deberse a desprendimiento de retina o problemas vasculares.'],
            ['query' => 'Ojos rojos con lagaña verde y picazón', 'spec' => 'Oftalmología', 'urgency' => 'Baja', 'advice' => 'Suelen ser signos de conjuntivitis bacteriana. Evite tocarse los ojos para no contagiar el otro ojo y use gotas indicadas.'],
        ];

        // Diccionario local para mapear rápidamente el nombre de la especialidad con su ID real
        $specialtiesMap = Specialty::pluck('id', 'name')->toArray();
        
        // Buscamos un ID de respaldo real: primera especialidad de la tabla o la que se llame 'General'
        $respaldoId = Specialty::where('name', 'LIKE', '%General%')->first()?->id 
                      ?? Specialty::first()?->id;

        foreach ($symptomsData as $item) {
            $slug = Str::slug($item['query']);

            // Buscamos el ID exacto. Si no existe, usamos el ID de respaldo real de tu base de datos
            $specialtyId = $specialtiesMap[$item['spec']] ?? $respaldoId;

            // Si tu tabla de especialidades está totalmente vacía, lanzamos una alerta
            if (!$specialtyId) {
                $this->command->error("⚠️ No se pudo sembrar el síntoma porque tu tabla 'specialties' está completamente vacía. ¡Crea al menos una especialidad primero!");
                return;
            }

            // Forzamos la creación del registro
            IndexedSymptom::firstOrCreate(
                ['slug' => $slug],
                [
                    'search_query'     => $item['query'],
                    'specialty_id'     => $specialtyId,
                    'urgency_level'    => $item['urgency'],
                    'ai_advice'        => $item['advice'],
                    'seo_title'        => '¿Sientes ' . $item['query'] . '? Orientación Médica Online',
                    'seo_description'  => '¿Sufres de ' . Str::lower($item['query']) . '? Lee nuestra guía de orientación médica, conoce el nivel de urgencia y agenda cita con especialistas.',
                    'search_count'     => rand(10, 50),
                ]
            );
        }
        
        $this->command->info("¡Éxito! Se han procesado los síntomas para el SEO.");
    }
}
