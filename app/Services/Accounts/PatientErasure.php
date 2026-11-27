<?php

declare(strict_types=1);

namespace App\Services\Accounts;

use App\Models\Patient;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borrado de un paciente desde el panel de administración.
 *
 * - Si tiene historia clínica, certificados, documentos firmados, consentimientos, etc. (config/patient_erasure.php),
 *   NO se elimina: se sugiere desactivar la cuenta.
 * - Si no, sus citas (incluidas las de borrado suave) se conservan para pagos, liquidaciones y el registro del médico,
 *   pero a nombre del «[Paciente eliminado]» anónimo y sin notas ni autor.
 */
class PatientErasure
{
    /** Texto con lo que impide eliminar (p. ej. «historia clínica, certificados médicos») o null si se puede eliminar. */
    public function blockedBy(User $user): ?string
    {
        $patientId = DB::table('patients')->where('user_id', $user->id)->value('id');

        if (!$patientId) {
            return null;
        }

        $reasons = [];

        foreach ((array) config('patient_erasure.protected', []) as [$table, $column, $label]) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column) && DB::table($table)->where($column, $patientId)->exists()) {
                $reasons[] = $label;
            }
        }

        // Archivos adjuntos a sus citas o subidos por él (pueden contener datos de salud).
        if (Schema::hasTable('appointment_files') && Schema::hasColumn('appointment_files', 'appointment_id')) {
            $files = DB::table('appointment_files')->where(function ($q) use ($patientId, $user) {
                $q->whereIn('appointment_id', DB::table('appointments')->where('patient_id', $patientId)->select('id'));
                if (Schema::hasColumn('appointment_files', 'uploaded_by')) {
                    $q->orWhere('uploaded_by', $user->id);
                }
            })->exists();

            if ($files) {
                $reasons[] = 'archivos adjuntos a sus citas';
            }
        }

        return $reasons === [] ? null : implode(', ', $reasons);
    }

    /** Mensaje amable para el administrador. */
    public function blockedMessage(User $user, string $reasons): string
    {
        return str_replace([':name', ':reasons'], [(string) $user->name, $reasons], (string) config('patient_erasure.blocked_message'));
    }

    /**
     * Pasa las citas del paciente (todas, también las de borrado suave) al paciente anónimo y les quita las notas.
     * Lanza DomainException si el paciente tiene datos protegidos (defensa extra: el controlador ya lo comprueba antes).
     *
     * @return int citas conservadas
     */
    public function anonymizeAppointments(User $user, Patient $patient): int
    {
        if (($why = $this->blockedBy($user)) !== null) {
            throw new DomainException('El paciente tiene datos que deben conservarse: ' . $why);
        }

        $ph = app(AbandonedAccountCleaner::class)->placeholder();

        $n = DB::table('appointments')->where('patient_id', $patient->id)
            ->update(['patient_id' => $ph['patient_id'], 'created_by_user_id' => $ph['user_id'], 'notes' => null]);

        DB::table('appointments')->where('created_by_user_id', $user->id)->update(['created_by_user_id' => $ph['user_id']]);

        return $n;
    }
}
