<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IndexedSymptom;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndexedSymptomController extends Controller
{
    public function index(Request $request)
    {
        $query = IndexedSymptom::query()->with('specialty');

        if ($search = $request->input('q')) {
            $query->where(function ($sub) use ($search) {
                $sub->where('search_query', 'like', "%{$search}%")
                    ->orWhere('seo_title', 'like', "%{$search}%");
            });
        }

        if ($urgency = $request->input('urgency_level')) {
            $query->where('urgency_level', $urgency);
        }

        if ($specialtyId = $request->input('specialty_id')) {
            $query->where('specialty_id', $specialtyId);
        }

        if ($request->boolean('solo_urgentes')) {
            $query->whereIn('urgency_level', IndexedSymptom::URGENCIAS_INMEDIATAS);
        }

        $sort = $request->input('sort', 'search_count');
        $direction = $request->input('direction', 'desc');
        $allowedSorts = ['search_count', 'created_at', 'urgency_level', 'search_query'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'search_count';
        }

        $symptoms = $query->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate(20)
            ->withQueryString();

        $specialties = Specialty::orderBy('name')->get(['id', 'name']);

        $stats = [
            'total' => IndexedSymptom::count(),
            'urgentes' => IndexedSymptom::whereIn('urgency_level', IndexedSymptom::URGENCIAS_INMEDIATAS)->count(),
            'sin_especialidad' => IndexedSymptom::whereNull('specialty_id')->count(),
        ];

        return view('administrator.symptoms.index', compact('symptoms', 'specialties', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['search_query']);
        }

        IndexedSymptom::create($data);

        return redirect()->route('administrator.symptoms.index')
            ->with('success', 'Síntoma creado correctamente.');
    }

    public function update(Request $request, IndexedSymptom $symptom)
    {
        $data = $this->validateData($request, $symptom->id);

        $symptom->update($data);

        return redirect()->route('administrator.symptoms.index')
            ->with('success', 'Síntoma actualizado correctamente.');
    }

    public function destroy(IndexedSymptom $symptom)
    {
        $symptom->delete();

        return redirect()->route('administrator.symptoms.index')
            ->with('success', 'Síntoma eliminado correctamente.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'search_query' => [
                'required', 'string', 'max:255',
                Rule::unique('indexed_symptoms', 'search_query')->ignore($ignoreId),
            ],
            'slug' => [
                'nullable', 'string', 'max:255',
                Rule::unique('indexed_symptoms', 'slug')->ignore($ignoreId),
            ],
            'specialty_id' => ['nullable', 'exists:specialties,id'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string'],
            'urgency_level' => ['nullable', 'string', Rule::in(IndexedSymptom::NIVELES_URGENCIA)],
            'ai_advice' => ['nullable', 'string'],
            'search_count' => ['nullable', 'integer', 'min:0'],
        ]);
    }
}