<?php

namespace App\Http\Controllers;

use App\Models\Specialty;
use App\Models\City;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index()
    {
        $specialties = Specialty::orderBy('name')->get();
        $cities = City::orderBy('name')->get();

        return view('home', compact('specialties', 'cities'));
    }

}
