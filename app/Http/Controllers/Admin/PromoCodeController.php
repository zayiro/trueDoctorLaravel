<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PromoCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PromoCodeController extends Controller
{
    /**
     * Muestra el listado de códigos de promoción.
     */
    public function index()
    {
        // Traemos los códigos paginados y cargamos la relación del usuario
        $promoCodes = PromoCode::with('user')->latest()->paginate(10);

        return view('administrator.promo-codes.index', compact('promoCodes'));
    }

    /**
     * Muestra el formulario para crear un nuevo código.
     */
    public function create()
    {
        return view('administrator.promo-codes.create');
    }

    /**
     * Almacena un código recién creado en la base de datos.
     */
    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());

        // Forzar valor por defecto del checkbox si no se marca
        $validated['is_active'] = $request->has('is_active');
        $validated['applies_to'] = array_values(array_unique($validated['applies_to']));
        // Inicializamos los usos en cero explícitamente
        $validated['uses'] = 0; 

        PromoCode::create($validated);

        return redirect()->route('administrator.promo-codes.index')
            ->with('status', 'Código de promoción creado con éxito.');
    }

    /**
     * Muestra el formulario para editar un código específico.
     */
    public function edit(PromoCode $promoCode)
    {
        return view('administrator.promo-codes.edit', compact('promoCode'));
    }

    /**
     * Actualiza el código en la base de datos.
     */
    public function update(Request $request, PromoCode $promoCode)
    {
        $validated = $request->validate($this->rules($promoCode), $this->messages());

        // Manejo manual del checkbox si viene ausente en el request
        $validated['is_active'] = $request->has('is_active');
        $validated['applies_to'] = array_values(array_unique($validated['applies_to']));

        $promoCode->update($validated);

        return redirect()->route('administrator.promo-codes.index')
            ->with('status', 'Código de promoción actualizado con éxito.');
    }

    /**
     * Elimina el código de la base de datos.
     */
    public function destroy(PromoCode $promoCode)
    {
        $promoCode->delete();

        return redirect()->route('administrator.promo-codes.index')
            ->with('status', 'Código de promoción eliminado correctamente.');
    }

    /**
     * Reglas de validación compartidas por crear y editar.
     */
    private function rules(?PromoCode $promoCode = null): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('promo_codes', 'code')->ignore($promoCode?->id)],
            'type' => ['required', Rule::in(['fixed', 'percent'])],
            'reward' => ['required', 'numeric', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['boolean'],
            // Módulos donde sirve el código: al menos uno, solo valores conocidos
            'applies_to' => ['required', 'array', 'min:1'],
            'applies_to.*' => ['string', Rule::in(PromoCode::MODULES)],
        ];
    }

    private function messages(): array
    {
        return [
            'applies_to.required' => 'Selecciona al menos un módulo donde aplique el código.',
            'applies_to.min' => 'Selecciona al menos un módulo donde aplique el código.',
            'applies_to.*.in' => 'Uno de los módulos seleccionados no es válido.',
        ];
    }
}