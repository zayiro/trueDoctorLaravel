<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;

class ServiceController extends Controller
{
    public function index()
    {
        $services = auth()->user()->doctor->services()
            ->with('addresses') // Importante para ver las sedes en la tabla
            ->latest()
            ->get();

        return view('doctor.services.index', compact('services'));
    }

    public function create()
    {
        $doctor = auth()->user()->doctor;
        $addresses = $doctor->addresses()->where('status', true)->get();
        
        // Contamos las sedes
        $hasAddresses = $addresses->isNotEmpty();

        return view('doctor.services.create', compact('addresses', 'hasAddresses'));
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'price'    => 'required|numeric|min:0',
            'type'     => 'required|in:presencial,virtual',
            'duration' => 'required|integer',
            'address_ids' => 'required_if:type,presencial|array'
        ]);

        $service = auth()->user()->doctor->services()->create($validated);

        if ($request->type === 'presencial') {
            $service->addresses()->attach($request->address_ids);
        }

        return redirect()->route('doctor.services.index')
        ->with('success', '¡Servicio creado exitosamente!');
    }

    public function edit(Service $service)
    {
        // Seguridad: verificar que el servicio pertenezca al doctor logueado
        if ($service->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $addresses = auth()->user()->doctor->addresses()->where('status', true)->get();
        
        return view('doctor.services.edit', compact('service', 'addresses'));
    }

    public function update(Request $request, Service $service)
    {
        if ($service->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|numeric|min:0',
            'type' => 'required|in:presencial,virtual',
            'duration' => 'required|integer',
            'address_ids' => 'required_if:type,presencial|array'
        ]);

        // Actualizar datos básicos
        $service->update($validated);

        // Sincronizar sedes
        if ($request->type === 'presencial') {
            $service->addresses()->sync($request->address_ids);
        } else {
            // Si cambió de presencial a virtual, quitamos todas las sedes
            $service->addresses()->detach();
        }

        return redirect()->route('doctor.services.index')
            ->with('success', 'Servicio actualizado correctamente.');
    }

    public function toggleStatus(Service $service)
    {
        if ($service->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        // Cambia de true a false o viceversa
        $service->update(['active' => !$service->active]);

        $mensaje = $service->active ? 'Servicio reactivado.' : 'Servicio desactivado.';
        
        return back()->with('success', $mensaje);
    }

    public function destroy(Service $service)
    {
        if ($service->doctor_id !== auth()->user()->doctor->id) {
            abort(403);
        }

        // En lugar de borrar, cambiamos el estado
        $service->update(['active' => false]);

        return redirect()->route('doctor.services.index')
            ->with('success', 'El servicio ha sido desactivado y ya no estará disponible para nuevas citas.');
    }

}
