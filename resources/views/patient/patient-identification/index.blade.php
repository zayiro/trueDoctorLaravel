@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('admin.dashboard'),
    ],
    [
        'name' => 'Ficha de identificación',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    {{-- Mensajes de Éxito --}}
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center">
                <span class="mr-2">✅</span>
                <p class="font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    {{-- Errores de Validación (Formularios) --}}
    @if ($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 shadow-sm rounded-r" role="alert">
            <div class="flex items-center mb-2">
                <span class="mr-2">❌</span>
                <p class="font-bold">Por favor, corrige los siguientes errores:</p>
            </div>
            <ul class="list-disc list-inside text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="py-8">
        <div class="bg-white border rounded-xl p-5 shadow-sm hover:border-blue-200 transition">            
            <form action="{{ isset($patient) ? route('patient.patient-identification.update', $patient) : route('patient.patient-identification.store') }}" method="POST">
                @csrf
                @if(isset($patient)) @method('PUT') @endif

                <!-- SECCIÓN 1: Información Básica -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div>
                        <label for="identification" class="block text-sm font-medium text-gray-700">Identificación</label>
                        <input type="text" name="identification" id="identification" value="{{ old('identification', $patient->identification ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="birth_date" class="block text-sm font-medium text-gray-700">Fecha de Nacimiento</label>
                        <input type="date" name="birth_date" id="birth_date" value="{{ old('birth_date', $patient->birth_date?->format('d/m/Y') ?? 'No registrada') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="blood_type" class="block text-sm font-medium text-gray-700">Tipo de Sangre</label>
                        <select name="blood_type" id="blood_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione...</option>
                            @foreach(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'] as $type)
                                <option value="{{ $type }}" {{ old('blood_type', $patient->blood_type ?? '') == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Datos Clínicos Rápidos -->
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700">Peso (kg)</label>
                        <input type="number" step="0.01" name="weight" id="weight" value="{{ $patient->weight ?? old('weight') }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>
                    
                    <div>
                        <label for="height" class="block text-sm font-medium text-gray-700">Estatura (ej: 1.72)</label>
                        <input type="number" step="0.01" name="height" id="height" value="{{ $patient->height ?? old('height') }}" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium text-gray-700">Género</label>
                        <select name="gender" id="gender" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">
                            <option value="male" {{ (isset($patient) && $patient->gender == 'male') ? 'selected' : '' }}>Masculino</option>
                            <option value="female" {{ (isset($patient) && $patient->gender == 'female') ? 'selected' : '' }}>Femenino</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="department_select" class="block text-sm font-medium text-gray-700">Departamento</label>
                        <select id="department_select" name="department_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">Seleccione...</option>
                            @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $patient->department_id ?? '') == $dept->id ? 'selected' : '' }}>
                                    {{ $dept->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="city_select" class="block text-sm font-medium text-gray-700">Ciudad / Municipio</label>
                        <select id="city_select" name="city_id" class="mt-1 w-full rounded border-gray-300">
                            <option value="">Seleccione Depto...</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label for="permanent_conditions" class="block text-sm font-medium text-gray-700">Condiciones Permanentes</label>
                    <textarea name="permanent_conditions" id="permanent_conditions" rows="3" class="mt-1 w-full border-gray-300 rounded-md shadow-sm">{{ $patient->permanent_conditions ?? old('permanent_conditions') }}</textarea>
                </div>

                <!-- SECCIÓN 2: Ubicación y Socioeconomía -->
                <h3 class="text-lg font-semibold mb-4 text-gray-800 mt-4">Ubicación y Datos Socioeconómicos</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div>
                        <label for="residence_zone" class="block text-sm font-medium text-gray-700">Zona de Residencia</label>
                        <select name="residence_zone" id="residence_zone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="Urbana" {{ old('residence_zone', $patient->residence_zone ?? '') == 'Urbana' ? 'selected' : '' }}>Urbana</option>
                            <option value="Rural" {{ old('residence_zone', $patient->residence_zone ?? '') == 'Rural' ? 'selected' : '' }}>Rural</option>
                        </select>
                    </div>

                    <div>
                        <label for="occupation" class="block text-sm font-medium text-gray-700">Ocupación</label>
                        <select name="occupation" id="occupation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione ocupación...</option>
                            @php
                                $ocupaciones = [
                                    'Empleado/a',
                                    'Independiente',
                                    'Desempleado/a',
                                    'Estudiante',
                                    'Jubilado/a / Pensionado/a',
                                    'Hogar',
                                    'Menor de edad',
                                    'Comerciante',
                                    'Agricultor/a',
                                    'Obrero/a',
                                    'Profesional de la salud',
                                    'Docente',
                                    'Otro'
                                ];
                            @endphp
                            @foreach($ocupaciones as $item)
                                <option value="{{ $item }}" {{ old('occupation', $patient->occupation ?? '') == $item ? 'selected' : '' }}>
                                    {{ $item }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="civil_status" class="block text-sm font-medium text-gray-700">Estado Civil</label>
                        <select name="civil_status" id="civil_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Seleccione...</option>
                            @foreach(['Soltero/a', 'Casado/a', 'Unión Libre', 'Divorciado/a', 'Viudo/a'] as $status)
                                <option value="{{ $status }}" {{ old('civil_status', $patient->civil_status ?? '') == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="ethnicity" class="block text-sm font-medium text-gray-700">Etnia</label>
                        <select name="ethnicity" id="ethnicity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            @foreach(['Indígena', 'Rrom (Gitano)', 'Raizal (San Andrés y Providencia)', 'Palenquero (San Basilio de Palenque)', 'Negro, Mulato, Afrocolombiano', 'Ninguna de las anteriores (Mestizo/Blanco)'] as $eth)
                                <option value="{{ $eth }}" {{ old('ethnicity', $patient->ethnicity ?? 'Ninguna de las anteriores (Mestizo/Blanco)') == $eth ? 'selected' : '' }}>{{ $eth }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- CAMPO CELULAR BLINDADO A 10 NÚMEROS -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 overflow-hidden">
                            <!-- Selector de Indicativo de País (Comienza por Colombia y Suramérica) -->
                            <select name="country_code" id="country_code" required 
                                    class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-5 cursor-pointer">
                                <option value="+57" {{ old('country_code') == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                <option value="+54" {{ old('country_code') == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                                <option value="+591" {{ old('country_code') == '+591' ? 'selected' : '' }}>🇧🇴 +591</option>
                                <option value="+55" {{ old('country_code') == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                <option value="+56" {{ old('country_code') == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                                <option value="+593" {{ old('country_code') == '+593' ? 'selected' : '' }}>🇪🇨 +593</option>
                                <option value="+595" {{ old('country_code') == '+595' ? 'selected' : '' }}>🇵🇾 +595</option>
                                <option value="+51" {{ old('country_code') == '+51' ? 'selected' : '' }}>🇵🇪 +51</option>
                                <option value="+598" {{ old('country_code') == '+598' ? 'selected' : '' }}>🇺🇾 +598</option>
                                <option value="+58" {{ old('country_code') == '+58' ? 'selected' : '' }}>🇻🇪 +58</option>
                                <option value="+592" {{ old('country_code') == '+592' ? 'selected' : '' }}>🇬🇾 +592</option>
                                <option value="+597" {{ old('country_code') == '+597' ? 'selected' : '' }}>🇸🇷 +597</option>
                            </select>

                            <!-- Input del Teléfono Blindado sin bordes nativos -->
                            <input 
                                id="phone" 
                                class="block w-full border-0 focus:ring-0 p-2.5 text-sm text-slate-900 rounded-r-xl" 
                                type="tel" 
                                name="phone" 
                                value="{{ $patient->phone ?? old('phone') }}" 
                                required 
                                maxlength="10"
                                pattern="[0-9]{10}"
                                placeholder="3026433874" 
                                autocomplete="phone"
                            />
                        </div>
                    </div>

                </div>

                <!-- SECCIÓN 3: Seguridad Social (Ley 100) -->
                <h3 class="text-lg font-semibold mb-4 text-gray-800">Seguridad Social</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                    <div>
                        <label for="affiliation_type" class="block text-sm font-medium text-gray-700">Tipo de Afiliación</label>
                        <select name="affiliation_type" id="affiliation_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Seleccione...</option>
                            @foreach(['Contributivo', 'Subsidiado', 'Vinculado', 'Particular', 'Otro'] as $aff)
                                <option value="{{ $aff }}" {{ old('affiliation_type', $patient->affiliation_type ?? '') == $aff ? 'selected' : '' }}>{{ $aff }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="regime_type" class="block text-sm font-medium text-gray-700">Régimen</label>
                        <select name="regime_type" id="regime_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="General" {{ old('regime_type', $patient->regime_type ?? '') == 'General' ? 'selected' : '' }}>General</option>
                            <option value="Especial" {{ old('regime_type', $patient->regime_type ?? '') == 'Especial' ? 'selected' : '' }}>Especial</option>
                            <option value="Excepción" {{ old('regime_type', $patient->regime_type ?? '') == 'Excepción' ? 'selected' : '' }}>Excepción</option>
                        </select>
                    </div>

                    <div>
                        <label for="insurance_id" class="block text-sm font-medium text-gray-700">EPS (Aseguradora)</label>
                        <select name="insurance_id" id="insurance_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Seleccione EPS...</option>
                            @foreach($insurances as $insurance)
                                <option value="{{ $insurance->id }}" {{ old('insurance_id', $patient->insurance_id ?? '') == $insurance->id ? 'selected' : '' }}>{{ $insurance->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="sisben_level" class="block text-sm font-medium text-gray-700">Nivel SISBÉN (IV)</label>
                        <select name="sisben_level" id="sisben_level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">No aplica / No tiene</option>
                            
                            <optgroup label="Grupo A: Pobreza extrema">
                                @for ($i = 1; $i <= 5; $i++)
                                    <option value="A{{ $i }}" {{ old('sisben_level', $patient->sisben_level ?? '') == "A$i" ? 'selected' : '' }}>A{{ $i }}</option>
                                @endfor
                            </optgroup>

                            <optgroup label="Grupo B: Pobreza moderada">
                                @for ($i = 1; $i <= 7; $i++)
                                    <option value="B{{ $i }}" {{ old('sisben_level', $patient->sisben_level ?? '') == "B$i" ? 'selected' : '' }}>B{{ $i }}</option>
                                @endfor
                            </optgroup>

                            <optgroup label="Grupo C: Vulnerable">
                                @for ($i = 1; $i <= 18; $i++)
                                    <option value="C{{ $i }}" {{ old('sisben_level', $patient->sisben_level ?? '') == "C$i" ? 'selected' : '' }}>C{{ $i }}</option>
                                @endfor
                            </optgroup>

                            <optgroup label="Grupo D: No pobre, no vulnerable">
                                @for ($i = 1; $i <= 21; $i++)
                                    <option value="D{{ $i }}" {{ old('sisben_level', $patient->sisben_level ?? '') == "D$i" ? 'selected' : '' }}>D{{ $i }}</option>
                                @endfor
                            </optgroup>
                        </select>
                    </div>

                </div>

                <!-- SECCIÓN 4: Contacto de Emergencia -->
                <h3 class="text-lg font-semibold mb-4 text-red-600">Contacto de Emergencia / Responsable</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8 bg-red-50 p-4 rounded-lg">
                    <div>
                        <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">Nombre Completo</label>
                        <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $patient->emergency_contact_name ?? '') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    </div>

                    <div>
                        <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Teléfono</label>
                        <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 overflow-hidden">
                            <!-- Selector de Indicativo de País (Comienza por Colombia y Suramérica) -->
                            <select name="country_code_contact" id="country_code_contact" required 
                                    class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-5 cursor-pointer">
                                <option value="+57" {{ old('country_code') == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                <option value="+54" {{ old('country_code') == '+54' ? 'selected' : '' }}>🇦🇷 +54</option>
                                <option value="+591" {{ old('country_code') == '+591' ? 'selected' : '' }}>🇧🇴 +591</option>
                                <option value="+55" {{ old('country_code') == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                <option value="+56" {{ old('country_code') == '+56' ? 'selected' : '' }}>🇨🇱 +56</option>
                                <option value="+593" {{ old('country_code') == '+593' ? 'selected' : '' }}>🇪🇨 +593</option>
                                <option value="+595" {{ old('country_code') == '+595' ? 'selected' : '' }}>🇵🇾 +595</option>
                                <option value="+51" {{ old('country_code') == '+51' ? 'selected' : '' }}>🇵🇪 +51</option>
                                <option value="+598" {{ old('country_code') == '+598' ? 'selected' : '' }}>🇺🇾 +598</option>
                                <option value="+58" {{ old('country_code') == '+58' ? 'selected' : '' }}>🇻🇪 +58</option>
                                <option value="+592" {{ old('country_code') == '+592' ? 'selected' : '' }}>🇬🇾 +592</option>
                                <option value="+597" {{ old('country_code') == '+597' ? 'selected' : '' }}>🇸🇷 +597</option>
                            </select>

                            <!-- Input del Teléfono Blindado sin bordes nativos -->
                            <input 
                                id="emergency_contact_phone" 
                                class="block w-full border-0 focus:ring-0 p-2.5 text-sm text-slate-900 rounded-r-xl" 
                                type="tel" 
                                name="emergency_contact_phone" 
                                value="{{ old('emergency_contact_phone', $patient->emergency_contact_phone ?? '') }}" 
                                required 
                                maxlength="10"
                                pattern="[0-9]{10}"
                                placeholder="3026433874" 
                                autocomplete="emergency_contact_phone"
                            />
                        </div>
                    </div>

                    <div>
                        <label for="emergency_contact_relationship" class="block text-sm font-medium text-gray-700">Parentesco</label>
                        <select name="emergency_contact_relationship" id="emergency_contact_relationship" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Seleccione parentesco...</option>
                            @php
                                $parentescos = [
                                    'Padre', 'Madre', 'Cónyuge/Compañero', 'Hijo/a', 
                                    'Hermano/a', 'Abuelo/a', 'Tío/a', 'Primo/a', 
                                    'Sobrino/a', 'Tutor Legal', 'Amigo/a', 'Otro'
                                ];
                            @endphp
                            @foreach($parentescos as $parentesco)
                                <option value="{{ $parentesco }}" {{ old('emergency_contact_relationship', $patient->emergency_contact_relationship ?? '') == $parentesco ? 'selected' : '' }}>
                                    {{ $parentesco }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition">
                        {{ isset($patient) ? 'Actualizar información' : 'Guardar Perfil' }}
                    </button>
                </div>
            </form>

        </div>
    </div>
    <script>
        document.getElementById('department_select').addEventListener('change', function() {
            const deptId = this.value;
            const citySelect = document.getElementById('city_select');
            
            citySelect.innerHTML = '<option value="">Cargando...</option>';

            if (!deptId) return;

            fetch(`/api/departments/${deptId}/cities`)
                .then(res => res.json())
                .then(data => {
                    citySelect.innerHTML = '<option value="">Seleccione Ciudad...</option>';
                    data.forEach(city => {
                        const selected = "{{ old('city_id', $patient->city_id ?? '') }}" === city.id ? 'selected' : '';
                        citySelect.innerHTML += `<option value="${city.id}" ${selected}>${city.name}</option>`;
                    });
                });
        });

        // Disparar el cambio al cargar si ya hay un departamento seleccionado (para modo Edición)
        if (document.getElementById('department_select').value) {
            document.getElementById('department_select').dispatchEvent(new Event('change'));
        }
    </script>    
</x-admin-layout>    
