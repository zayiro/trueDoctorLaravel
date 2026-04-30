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

    public function search(Request $request)
    {
        $query = Doctor::query()->with(['user', 'specialty', 'addresses.city']);

        if ($request->filled('specialty')) {
            $query->whereHas('specialty', function($q) use ($request) {
                $q->where('slug', $request->specialty);
            });
        }

        if ($request->filled('city')) {
            $query->whereHas('addresses.city', function($q) use ($request) {
                $q->where('slug', $request->city);
            });
        }

        $doctors = $query->paginate(10);

        return view('search-results', compact('doctors'));
    }
}
