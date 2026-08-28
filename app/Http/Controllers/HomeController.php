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

        return view('home', compact('specialties', 'cities', 'symptoms'));
    }
}
