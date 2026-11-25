<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Patients\PatientDirectory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClinicPatientController extends Controller
{
    /**
     * Directorio de pacientes atendidos en la clínica: solo la lista. La historia clínica queda con el médico tratante.
     */
    public function index(Request $request, PatientDirectory $directory)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'clinic') {
            abort(403, 'Acceso exclusivo para centros médicos.');
        }

        $clinic = $user->clinic ?? abort(403, 'No encontramos el perfil de tu clínica.');
        $plan = $clinic->plan;

        $canSearch = (bool) ($plan?->can_search_patients);
        $cap = (int) ($plan?->max_patients_list ?? 15);
        $term = trim((string) $request->input('query')) ?: null;

        $addressIds = DB::table('addresses')->where('clinic_id', $clinic->id)->pluck('id')->all();

        $patients = $directory->paginate(['clinic_id' => $clinic->id, 'address_ids' => $addressIds], $canSearch, $cap, $term)->withQueryString();

        return view('partner.patients.index', [
            'patients'   => $patients,
            'plan'       => $plan,
            'isClinic'   => true,
            'searchTerm' => $term,
            'searching'  => $canSearch && $term !== null && mb_strlen($term) >= PatientDirectory::SEARCH_MIN_LENGTH,
            'cap'        => $cap,
        ]);
    }
}
