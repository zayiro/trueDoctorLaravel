<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use App\Models\City;
use App\Models\IndexedSymptom;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {        
        $specialties = Specialty::where('status', 1)->orderBy('name', 'asc')->get();
        $cities = City::orderBy('name', 'asc')->get();
        $symptoms = IndexedSymptom::inRandomOrder()->limit(10)->pluck('search_query')->toArray();

        return view('home', [
            'specialties' => $specialties,
            'cities' => $cities,
            'symptoms' => $symptoms,
            'name' => 'OpenDoctor | Agenda tu Cita Médica Virtual o Presencial',
            'description' => 'Agenda tu Cita Médica Virtual o Presencial con los mejores especialistas de tu ciudad. Rápido, seguro y sin filas.',
            'finalKeywords' => 'medicos, citas medicas, telemedicina, colombia',
            'finalRobots' => 'index, follow',
            'finalCanonical' => route('home')
        ]);
    }
}
