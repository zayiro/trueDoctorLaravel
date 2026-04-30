<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $specialties = Specialty::all();
        
        $doctors = Doctor::with(['user', 'specialty', 'addresses'])
            ->where('specialty_id', $request->specialty)
            ->filterByCity($request->city)
            ->paginate(10);


        return view('search.index', compact('doctors', 'specialties'));
    }
}
