<?php

namespace Database\Seeders;

use App\Models\Specialty;
use Illuminate\Database\Seeder;

/**
 * Catálogo de especialidades, sincronizado con la base de producción (25-nov-2026).
 *
 * Se busca por ID (no por nombre), así volver a correrlo NUNCA crea duplicados:
 * solo deja cada fila con el nombre, el slug y el estado de esta lista.
 * Formato de cada fila: [id, nombre, slug, activa].
 * Las inactivas (false) son roles que no dan consulta médica.
 */
class SpecialtySeeder extends Seeder
{
    /** @return array<int, array{0:int,1:string,2:string,3:bool}> */
    public static function rows(): array
    {
        return [
            [1, 'Medicina General', 'medicina-general', true],
            [2, 'Psicología', 'psicologia', true],
            [3, 'Cardiología', 'cardiologia', true],
            [4, 'Pediatría', 'pediatria', true],
            [5, 'Dermatología', 'dermatologia', true],
            [6, 'Ginecología', 'ginecologia', true],
            [7, 'Otorrinolaringología', 'otorrinolaringologia', true],
            [8, 'Oftalmología', 'oftalmologia', true],
            [9, 'Psiquiatría', 'psiquiatria', true],
            [10, 'Traumatología', 'traumatologia', true],
            [11, 'Urología', 'urologia', true],
            [12, 'Ortopedista y Traumatología', 'ortopedista-y-traumatologia', true],
            [13, 'Odontología', 'odontologia', true],
            [14, 'Cirujano Plástico', 'cirujano-plastico', true],
            [15, 'Internista', 'internista', true],
            [16, 'Endocrinología', 'endocrinologia', true],
            [17, 'Neurología', 'neurologia', true],
            [18, 'Gastroenterología', 'gastroenterologia', true],
            [19, 'Cirujano General', 'cirujano-general', true],
            [20, 'Neurocirujano', 'neurocirujano', true],
            [21, 'Nutricionista', 'nutricionista', true],
            [22, 'Fisioterapeuta', 'fisioterapeuta', true],
            [23, 'Terapeuta Complementario', 'terapeuta-complementario', true],
            [24, 'Alergología', 'alergologia', true],
            [25, 'Reumatología', 'reumatologia', true],
            [26, 'Neumología', 'neumologia', true],
            [27, 'Cirujano Maxilofacial', 'cirujano-maxilofacial', true],
            [28, 'Cirujano Vascular', 'cirujano-vascular', true],
            [29, 'Médico Alternativo', 'medico-alternativo', true],
            [30, 'Fonoaudiología', 'fonoaudiologia', true],
            [31, 'Médico Fisiatra Rehabilitador', 'medico-fisiatra-rehabilitador', true],
            [32, 'Nefrología', 'nefrologia', true],
            [33, 'Médico Estético', 'medico-estetico', true],
            [34, 'Sexología', 'sexologia', true],
            [35, 'Hematología', 'hematologia', true],
            [36, 'Infectología', 'infectologia', true],
            [37, 'Neuropsicología', 'neuropsicologia', true],
            [38, 'Oncólogo', 'oncologo', true],
            [39, 'Endocrinología Pediátrica', 'endocrinologia-pediatrica', true],
            [40, 'Neurología Pediátrica', 'neurologia-pediatrica', true],
            [41, 'Nutriología', 'nutriologia', true],
            [42, 'Neumología Pediátrica', 'neumologia-pediatrica', true],
            [43, 'Médico Laboral', 'medico-laboral', true],
            [44, 'Radiología', 'radiologia', true],
            [45, 'Especialista en Medicina Deportiva', 'especialista-en-medicina-deportiva', true],
            [46, 'Cirujano Pediátrico', 'cirujano-pediatrico', true],
            [47, 'Geriatra', 'geriatra', true],
            [48, 'Cirujano de Cabeza y Cuello', 'cirujano-de-cabeza-y-cuello', true],
            [49, 'Anestesiología', 'anestesiologia', true],
            [50, 'Cirujano Cardiovascular', 'cirujano-cardiovascular', true],
            [51, 'Mastología', 'mastologia', true],
            [52, 'Medicina Familiar', 'medicina-familiar', true],
            [53, 'Podólogo', 'podologo', true],
            [54, 'Genetista', 'genetista', true],
            [55, 'Proctólogo', 'proctologo', true],
            [56, 'Terapeuta Ocupacional', 'terapeuta-ocupacional', true],
            [57, 'Especialista en Tratamiento del Dolor', 'especialista-en-tratamiento-del-dolor', true],
            [58, 'Audiología', 'audiologia', true],
            [59, 'Cirujano de Tórax', 'cirujano-de-torax', true],
            [60, 'Nefrología Pediátrica', 'nefrologia-pediatrica', true],
            [61, 'Coloproctología', 'coloproctologia', true],
            [62, 'Técnico en Laboratorio', 'tecnico-en-laboratorio', false],
            [63, 'Hepatología', 'hepatologia', true],
            [64, 'Gastroenterólogo Pediátrico', 'gastroenterologo-pediatrico', true],
            [65, 'Toxicología', 'toxicologia', true],
            [66, 'Terapeuta Respiratorio', 'terapeuta-respiratorio', false],
            [67, 'Especialista en Cuidados Críticos', 'especialista-en-cuidados-criticos', true],
            [68, 'Ortopedista Infantil', 'ortopedista-infantil', true],
            [69, 'Cardiología Pediátrica', 'cardiologia-pediatrica', true],
            [70, 'Cirujano de Mama y Tejidos Blandos', 'cirujano-de-mama-y-tejidos-blandos', true],
            [71, 'Oftalmólogo Pediátrico', 'oftalmologo-pediatrico', true],
            [72, 'Epidemiología', 'epidemiologia', true],
            [73, 'Psiquiatra Infantil', 'psiquiatra-infantil', true],
            [74, 'Neurofisiología', 'neurofisiologia', true],
            [75, 'Infectólogo Pediatra', 'infectologo-pediatra', true],
            [76, 'Oncólogo Radioterápico', 'oncologo-radioterapico', true],
            [77, 'Alergólogo Pediátrico', 'alergologo-pediatrico', true],
            [78, 'Especialista en Medicina Nuclear', 'especialista-en-medicina-nuclear', true],
            [79, 'Especialista en Medicina Domiciliaria', 'especialista-en-medicina-domiciliaria', true],
            [80, 'Psicoanalista', 'psicoanalista', true],
            [81, 'Médico Vascular', 'medico-vascular', true],
            [82, 'Enfermero', 'enfermero', false],
            [83, 'Especialista en Medicina de Urgencias', 'especialista-en-medicina-de-urgencias', true],
            [84, 'Neuro Oftalmólogo', 'neuro-oftalmologo', true],
            [85, 'Perinatólogo', 'perinatologo', true],
            [86, 'Urólogo Pediátrico', 'urologo-pediatrico', true],
            [87, 'Auxiliar de Enfermería', 'auxiliar-de-enfermeria', false],
            [88, 'Ortodoncista', 'ortodoncista', true],
            [89, 'Reumatólogo Pediátrico', 'reumatologo-pediatrico', true],
            [90, 'Dermatólogo Pediátrico', 'dermatologo-pediatrico', true],
            [91, 'Especialista en Salud Pública', 'especialista-en-salud-publica', true],
            [92, 'Radioterapeuta', 'radioterapeuta', true],
            [93, 'Psicopedagogo', 'psicopedagogo', true],
            [94, 'Especialista en Medicina Aeroespacial', 'especialista-en-medicina-aeroespacial', false],
            [95, 'Odontopediatría', 'odontopediatria', true],
            [96, 'Homeópata', 'homeopata', true],
            [97, 'Oncólogo Pediátrico', 'oncologo-pediatrico', true],
            [98, 'Cirujano Oncólogo', 'cirujano-oncologo', true],
            [99, 'Bacteriólogo', 'bacteriologo', false],
            [100, 'Neuropediatría', 'neuropediatria', true],
            [101, 'Optometría', 'optometria', true],
        ];
    }

    public function run(): void
    {
        foreach (self::rows() as [$id, $name, $slug, $active]) {
            Specialty::updateOrCreate(
                ['id' => $id],
                ['name' => $name, 'slug' => $slug, 'status' => $active]
            );
        }
    }
}
