<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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
        // Solo mostramos sedes activas para el doctor logueado
        $addresses = auth()->user()->doctor->addresses()->where('status', true)->get();
        
        return view('doctor.services.create', compact('addresses'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'type' => 'required|in:presencial,virtual',     
            'address_ids' => 'required_if:type,presencial|array', 
        ]);

        $service = auth()->user()->doctor->services()->create($validated);

        if ($request->type === 'presencial') {
            $service->addresses()->attach($request->address_ids);
        }

        return back()->with('success', 'Servicio configurado correctamente.');
    }
}
