<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Clinic;
use App\Models\ValidationNotice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Mail\ValidationActionRequired;
use App\Mail\ValidationStatusNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class ValidationController extends Controller
{
    /** Estados en los que una cuenta espera la revisión del administrador. */
    private const PENDING_STATUSES = ['pending_validation', 'missing'];

    /** Horas mínimas entre dos avisos a la misma cuenta. */
    private const NOTICE_COOLDOWN_HOURS = 24;

    private const REASONS = [
        'missing'   => ['label' => 'No lo hemos recibido', 'hint' => 'Súbelo de nuevo desde tu panel de verificación.'],
        'illegible' => ['label' => 'No se lee con claridad', 'hint' => 'Sube una foto o escaneo nítido, completo y bien iluminado.'],
        'mismatch'  => ['label' => 'No corresponde al documento solicitado', 'hint' => 'Verifica que sea el documento correcto y que los datos coincidan con tu registro.'],
        'expired'   => ['label' => 'Está vencido', 'hint' => 'Sube la versión vigente del documento.'],
    ];

    /**
     * Muestra la lista unificada de médicos y clínicas esperando validación.
     */
    public function index()
    {
        // 1. Cuentas corporativas con validación pendiente. Cada rol se filtra contra SU perfil
        //    (antes el orWhereHas dejaba escapar el filtro de rol).
        $partners = User::whereIn('role', ['doctor', 'clinic'])
            ->where(function ($query) {
                $query->where(function ($doctor) {
                    $doctor->where('role', 'doctor')
                        ->whereHas('doctor', fn ($p) => $p->whereIn('validation_status', self::PENDING_STATUSES));
                })->orWhere(function ($clinic) {
                    $clinic->where('role', 'clinic')
                        ->whereHas('clinic', fn ($p) => $p->whereIn('validation_status', self::PENDING_STATUSES));
                });
            })
            ->with(['doctor', 'clinic'])
            ->latest()
            ->paginate(10);

        // 2. Avisos ya enviados a las cuentas de esta página (una sola consulta)
        $doctorIds = $partners->getCollection()->pluck('doctor.id')->filter()->all();
        $clinicIds = $partners->getCollection()->pluck('clinic.id')->filter()->all();

        $notices = ValidationNotice::query()
            ->where(function ($q) use ($doctorIds, $clinicIds) {
                $q->whereIn('doctor_id', $doctorIds)->orWhereIn('clinic_id', $clinicIds);
            })
            ->orderByDesc('sent_at')
            ->get();

        // 3. La vista recibe perfiles (médico o clínica) con su usuario y los datos del aviso
        $profiles = $partners->getCollection()
            ->map(function (User $user) use ($notices) {
                $profile = $user->role === 'clinic' ? $user->clinic : $user->doctor;

                if (!$profile) {
                    return null;
                }

                $profile->setRelation('user', $user);

                return $this->withNoticeInfo($profile, $user, $notices);
            })
            ->filter()
            ->values();

        $partners->setCollection($profiles);

        return view('administrator.validation.index', compact('partners'));
    }

    /**
     * Avisa al médico o clínica que debe corregir uno o más documentos.
     * No cambia el estado de la cuenta ni borra archivos: solo notifica y deja registro.
     */
    public function notify(Request $request)
    {
        // Solo cuentan los documentos marcados en el modal
        $request->merge([
            'items' => collect($request->input('items', []))
                ->filter(fn ($item) => is_array($item) && !empty($item['doc']))
                ->values()
                ->all(),
        ]);

        $request->validate([
            'doctor_id'      => ['required_without:clinic_id', 'nullable', 'exists:doctors,id'],
            'clinic_id'      => ['required_without:doctor_id', 'nullable', 'exists:clinics,id'],
            'items'          => ['required', 'array', 'min:1', 'max:2'],
            'items.*.doc'    => ['required', 'in:identity,second', 'distinct'],
            'items.*.reason' => ['required', 'in:' . implode(',', array_keys(self::REASONS))],
            'note'           => ['nullable', 'string', 'max:500'],
        ], [
            'items.required'          => 'Marca al menos un documento para avisar.',
            'items.min'               => 'Marca al menos un documento para avisar.',
            'items.*.reason.required' => 'Elige el motivo de cada documento marcado.',
        ]);

        $isClinic = $request->filled('clinic_id');
        $profile  = $isClinic
            ? Clinic::with('user')->findOrFail($request->clinic_id)
            : Doctor::with('user')->findOrFail($request->doctor_id);
        $user = $profile->user;

        if (!$user) {
            return back()->with('error', 'La cuenta no tiene un usuario asociado.');
        }

        if (!in_array($profile->validation_status, self::PENDING_STATUSES, true)) {
            return back()->with('error', 'Esta cuenta ya no está pendiente de validación.');
        }

        if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
            return back()->with('error', 'El correo de esta cuenta no es válido: no se puede enviar el aviso.');
        }

        $lock = Cache::lock('validation-notice:' . ($isClinic ? 'clinic' : 'doctor') . ':' . $profile->id, 15);

        if (!$lock->get()) {
            return back()->with('error', 'Ya se está enviando un aviso a esta cuenta. Espera unos segundos.');
        }

        try {
            $last = ValidationNotice::query()
                ->where($isClinic ? 'clinic_id' : 'doctor_id', $profile->id)
                ->latest('sent_at')
                ->first();

            if ($last && $last->sent_at->gt(now()->subHours(self::NOTICE_COOLDOWN_HOURS))) {
                $availableAt = $last->sent_at->copy()->addHours(self::NOTICE_COOLDOWN_HOURS);

                return back()->with('error', 'Ya se avisó a esta cuenta ' . $last->sent_at->locale('es')->diffForHumans()
                    . '. Podrás volver a avisar el ' . $availableAt->timezone(config('app.timezone', 'America/Bogota'))->format('d/m/Y H:i') . '.');
            }

            $labels = $this->documentLabels($isClinic);
            $items  = [];

            foreach ($request->input('items') as $item) {
                $reason  = self::REASONS[$item['reason']];
                $items[] = [
                    'doc'    => $item['doc'],
                    'label'  => $labels[$item['doc']],
                    'reason' => $reason['label'],
                    'hint'   => $reason['hint'],
                ];
            }

            $note = filled($request->note) ? trim($request->note) : null;

            try {
                Mail::to($user->email)->send(new ValidationActionRequired(
                    $user,
                    $isClinic ? 'clinic' : 'doctor',
                    $items,
                    $note,
                    $this->actionUrl()
                ));
            } catch (Throwable $e) {
                Log::error('Validación: no se pudo enviar el aviso de documentos', [
                    'user_id' => $user->id,
                    'error'   => $e->getMessage(),
                ]);

                return back()->with('error', 'No se pudo enviar el correo. Inténtalo de nuevo en unos minutos.');
            }

            ValidationNotice::create([
                'doctor_id'       => $isClinic ? null : $profile->id,
                'clinic_id'       => $isClinic ? $profile->id : null,
                'sent_by'         => $request->user()?->id,
                'recipient_email' => $user->email,
                'items'           => $items,
                'note'            => $note,
                'sent_at'         => now(),
            ]);
        } finally {
            $lock->release();
        }

        return back()->with('success', 'Se envió el aviso a ' . $user->email . ' con los documentos por corregir.');
    }

    /**
     * Actualiza el estado del perfil comercial (Aprobado o Rechazado).
     */
    public function update(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'doctor_id' => ['required_without:clinic_id', 'exists:doctors,id'],
            'clinic_id' => ['required_without:doctor_id', 'exists:clinics,id'],
        ]);

        $status = $request->status;

        // 1. Identificar e inyectar dinámicamente las columnas asimétricas por Rol
        if ($request->filled('clinic_id')) {
            $model = Clinic::findOrFail($request->clinic_id);
            $typeLabel = "La clínica";
            $secondField = 'reps_code_card_path'; // 🔒 Ajustado a la nomenclatura de tu BD de clínicas
        } else {
            $model = Doctor::findOrFail($request->doctor_id);
            $typeLabel = "El médico";
            $secondField = 'professional_card_path';
        }

        // 2. Procesar la lógica transaccional según el nuevo estado
        if ($status === 'rejected') {
            
            // 🔒 CORREGIDO: Eliminación usando el disco 'local' privado unificado
            if ($model->identity_card_path) {
                Storage::delete($model->identity_card_path);
            }
            if ($model->{$secondField}) {
                Storage::delete($model->{$secondField});
            }
            
            $model->update([
                'validation_status' => 'rejected',
                'identity_card_path' => null,
                $secondField         => null,
                'active'             => false // Oculto del directorio del SaaS
            ]);
            
            $message = "{$typeLabel} ha sido rechazado(a) y se le ha solicitado nueva documentación.";
        } else {
            // Activar perfil comercial en el ecosistema público
            $model->update([
                'validation_status' => 'approved',
                'active'            => true 
            ]);

            // Despierta la autogeneración de la Sede Virtual de Telemedicina
            if (method_exists($model, 'createVirtualAddress')) {
                $model->createVirtualAddress();
            }
            
            $message = "¡{$typeLabel} aprobado(a) con éxito! Su perfil ya es público.";
        }

        // 3. Notificación automatizada al usuario
        Mail::to($model->user->email)->send(new ValidationStatusNotification($model->user, $status));

        return redirect()->back()->with('success', $message);
    }

    /**
     * Sirve los archivos confidenciales forzando la visualización inline y segura en el navegador.
     */
    public function viewDocument(Request $request, $type)
    {
        $request->validate([
            'doctor_id' => ['required_without:clinic_id', 'exists:doctors,id'],
            'clinic_id' => ['required_without:doctor_id', 'exists:clinics,id'],
        ]);

        // 1. Resolver el modelo e inyectar el campo dinámico correcto
        if ($request->filled('clinic_id')) {
            $model = Clinic::with('user')->findOrFail($request->clinic_id);
            $secondField = 'reps_code_card_path'; // 🔒 Mapeo exacto de la clínica
        } else {
            $model = Doctor::with('user')->findOrFail($request->doctor_id);
            $secondField = 'professional_card_path'; // 🔒 Mapeo exacto del médico
        }

        // 2. Extraer el path almacenado
        $relativePath = ($type === 'cedula') ? $model->identity_card_path : $model->{$secondField};

        // 3. Validar existencia en el disco 'local' privado
        if (!$relativePath || !Storage::exists($relativePath)) {
            abort(404, 'Lo sentimos, el archivo confidencial no se encuentra en el servidor.');
        }

        // 4. Obtención de propiedades físicas del storage protegido
        $fullPath = Storage::path($relativePath);
        $mimeType = Storage::mimeType($relativePath);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        
        $cleanName = $type . '-' . Str::slug($model->user->name) . '.' . $extension;

        // 5. Cabeceras estrictas de inyección inline anti-malware
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'inline; filename="' . $cleanName . '"',
            'X-Content-Type-Options' => 'nosniff', 
            'Content-Length' => filesize($fullPath),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ];

        return response()->file($fullPath, $headers);
    }

    /**
     * Nombres de los dos documentos según el tipo de cuenta.
     */
    private function documentLabels(bool $isClinic): array
    {
        return $isClinic
            ? ['identity' => 'RUT / NIT', 'second' => 'Certificado REPS']
            : ['identity' => 'Identificación', 'second' => 'Tarjeta profesional'];
    }

    /**
     * Un documento "falta" si no hay ruta guardada o el archivo ya no existe en el disco.
     */
    private function fileMissing(?string $path): bool
    {
        return blank($path) || !Storage::exists($path);
    }

    /**
     * Enlace que lleva al médico a su cuenta para volver a subir los documentos.
     */
    private function actionUrl(): string
    {
        return \Illuminate\Support\Facades\Route::has('login') ? route('login') : url('/login');
    }

    /**
     * Agrega al perfil (sin tocar sus atributos) el estado de cada documento y del último aviso.
     * Se guarda como relación para que nunca se escriba en la base de datos por accidente.
     */
    private function withNoticeInfo($profile, User $user, $notices)
    {
        $isClinic    = $profile instanceof Clinic;
        $secondField = $isClinic ? 'reps_code_card_path' : 'professional_card_path';
        $labels      = $this->documentLabels($isClinic);

        $docs = [
            ['key' => 'identity', 'label' => $labels['identity'], 'missing' => $this->fileMissing($profile->identity_card_path)],
            ['key' => 'second',   'label' => $labels['second'],   'missing' => $this->fileMissing($profile->{$secondField})],
        ];

        $last = $notices->first(fn ($n) => $isClinic
            ? (int) $n->clinic_id === (int) $profile->id
            : (int) $n->doctor_id === (int) $profile->id);

        $locked = $last && $last->sent_at->gt(now()->subHours(self::NOTICE_COOLDOWN_HOURS));

        $profile->setRelation('notice_info', (object) [
            'docs'        => $docs,
            'has_missing' => collect($docs)->contains('missing', true),
            'valid_email' => filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false,
            'last_notice' => $last,
            'locked'      => $locked,
            'unlock_at'   => $locked ? $last->sent_at->copy()->addHours(self::NOTICE_COOLDOWN_HOURS) : null,
        ]);

        return $profile;
    }
}
