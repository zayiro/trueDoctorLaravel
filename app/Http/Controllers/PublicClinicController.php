<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Ruta heredada /clinic/{slug}/{specialty?}.
 *
 * La vista pública de una clínica vive ahora SOLO en /medical-partner/{slug} (PublicProfileController),
 * donde se aplican las reglas de visibilidad (aprobada, activa y con el mínimo de médicos).
 * Esta ruta se conserva para que los enlaces y buscadores que ya la conocen sigan funcionando.
 */
class PublicClinicController extends Controller
{
    public function showClinicStaff(Request $request, $slug, $specialtySlug = null)
    {
        $params = array_filter([
            'slug'       => $slug,
            'specialty'  => $specialtySlug ?? $request->input('specialty'),
            'address_id' => $request->input('address_id'),
        ], fn ($value) => $value !== null && $value !== '');

        return redirect()->route('partner.public.profile', $params, 301);
    }
}
