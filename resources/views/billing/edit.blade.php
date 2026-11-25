@php
    $v = fn (string $k) => (string) old($k, $values[$k] ?? '');

    $init = [
        'document_type'   => $v('document_type'),
        'document_number' => $v('document_number'),
        'department_id'   => $v('department_id'),
        'city_id'         => $v('city_id'),
        'address_line'    => $v('address_line'),
        'cities'          => $cities,
        'sedes'           => $sedes,
        'suggested'       => $suggested,
    ];

    $inp = 'mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm';
    $lbl = 'block text-sm font-medium text-gray-700';

    $intro = match ($role) {
        'doctor'  => 'Con estos datos se emitirán tus facturas electrónicas y los soportes de tus liquidaciones cuando ese módulo esté activo. Escríbelos tal como aparecen en tu RUT.',
        'clinic'  => 'Con estos datos se emitirán las facturas electrónicas de tu plan y los soportes de tus liquidaciones cuando ese módulo esté activo. Escríbelos tal como aparecen en el RUT de la clínica.',
        default   => 'Este perfil es opcional: solo lo necesitas si quieres que una factura salga a tu nombre. Solo tú ves estos datos.',
    };
    $declaration = match ($role) {
        'doctor'  => 'Confirmo que estos datos coinciden con mi RUT.',
        'clinic'  => 'Confirmo que estos datos coinciden con el RUT de la clínica.',
        default   => 'Confirmo que estos datos son correctos.',
    };
@endphp

<script>
    window.billingForm = function () {
        const init = @json($init);
        const weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        return {
            type: init.document_type,
            number: init.document_number,
            department: init.department_id,
            city: init.city_id,
            address: init.address_line,
            cities: init.cities,
            sedes: init.sedes,
            suggested: init.suggested,
            touched: {},
            dirty(field) { this.touched[field] = true; },
            hint(field) { return this.suggested.indexOf(field) !== -1 && !this.touched[field]; },
            get cityOptions() { return this.cities.filter((c) => c[2] === this.department); },
            get isNit() { return this.type === 'NIT'; },
            get dv() {
                if (!this.isNit) { return ''; }
                const n = (this.number || '').replace(/[\s.]/g, '').split('-')[0].replace(/\D/g, '');
                if (n.length < 5 || n.length > 15) { return ''; }
                let sum = 0;
                for (let k = 0; k < n.length; k++) { sum += parseInt(n[n.length - 1 - k], 10) * weights[k]; }
                const r = sum % 11;
                return String(r > 1 ? 11 - r : r);
            },
            useSede(i) {
                const s = this.sedes[i];
                if (!s) { return; }
                this.address = s.address || '';
                this.department = s.department_id || '';
                this.$nextTick(() => { this.city = s.city_id || ''; });
                this.touched.address_line = true;
            },
        };
    };
</script>

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-8 px-4 sm:px-6" x-data="billingForm()">

        {{-- Mensajes --}}
        @if (session('success'))
            <div id="alert-success" class="flex items-center p-4 mb-6 text-green-800 rounded-2xl bg-green-50 border border-green-100 shadow-sm" role="alert">
                <span class="text-sm font-medium">{{ session('success') }}</span>
                <button type="button" class="ms-auto text-green-600 hover:text-green-800 text-lg leading-none" onclick="document.getElementById('alert-success').remove()" aria-label="Cerrar">&times;</button>
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 mb-6 text-sm font-medium text-rose-800 rounded-2xl bg-rose-50 border border-rose-100 shadow-sm" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 p-4 mb-6 shadow-sm rounded-2xl" role="alert">
                <p class="font-bold text-sm">Revisa estos datos:</p>
                <ul class="list-disc list-inside text-xs mt-1 space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 sm:p-8">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-2">
                <h1 class="text-xl font-bold text-gray-900">Datos fiscales</h1>
                @if ($profile && $profile->isConfirmed())
                    <span class="inline-flex items-center rounded-full bg-green-50 text-green-700 border border-green-200 text-xs font-semibold px-3 py-1">
                        Confirmados el {{ $profile->confirmed_at->timezone('America/Bogota')->format('d/m/Y') }}
                    </span>
                @else
                    <span class="inline-flex items-center rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-xs font-semibold px-3 py-1">
                        Pendiente de confirmar
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-600 mb-6">{{ $intro }}</p>

            @if (! $profile && count($suggested))
                <div class="mb-6 rounded-xl bg-blue-50 border border-blue-100 text-blue-800 text-xs p-3">
                    Hay datos precargados con lo que ya tenemos de ti. Están marcados como <strong>«Sugerido»</strong>:
                    revísalos y corrígelos si hace falta, porque no se guardan hasta que pulses <strong>Guardar y confirmar</strong>.
                </div>
            @endif

            <form action="{{ route($updateRoute) }}" method="POST" class="space-y-6" novalidate>
                @csrf
                @method('PUT')

                {{-- Documento --}}
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="document_type" class="{{ $lbl }}">Tipo de documento
                            <span x-show="hint('document_type')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                        </label>
                        <select name="document_type" id="document_type" x-model="type" @change="dirty('document_type')" class="{{ $inp }}">
                            @if (count($allowedTypes) > 1)
                                <option value="">Selecciona...</option>
                            @endif
                            @foreach ($allowedTypes as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('document_type') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="document_number" class="{{ $lbl }}">Número de documento
                            <span x-show="hint('document_number')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                        </label>
                        <input type="text" name="document_number" id="document_number" x-model="number" @input="dirty('document_number')"
                               inputmode="numeric" autocomplete="off" class="{{ $inp }}" placeholder="Sin puntos ni guiones">
                        @error('document_number') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div x-show="isNit" x-cloak>
                        <label class="{{ $lbl }}">Dígito de verificación</label>
                        <input type="text" :value="dv" readonly tabindex="-1" class="{{ $inp }} bg-gray-50 text-gray-700">
                        <p class="text-[11px] text-gray-500 mt-1">Se calcula solo a partir del NIT.</p>
                    </div>
                </div>

                {{-- Nombre y contacto --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="legal_name" class="{{ $lbl }}">Nombre completo o razón social
                            <span x-show="hint('legal_name')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                        </label>
                        <input type="text" name="legal_name" id="legal_name" value="{{ $v('legal_name') }}" @input="dirty('legal_name')" class="{{ $inp }}">
                        <p class="text-[11px] text-gray-500 mt-1">
                            {{ $role === 'patient' ? 'Como quieres que aparezca en la factura.' : 'Debe ser igual al que aparece en el RUT; si no, la factura puede ser rechazada.' }}
                        </p>
                        @error('legal_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="billing_email" class="{{ $lbl }}">Correo de facturación
                            <span x-show="hint('billing_email')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                        </label>
                        <input type="email" name="billing_email" id="billing_email" value="{{ $v('billing_email') }}" @input="dirty('billing_email')" class="{{ $inp }}">
                        <p class="text-[11px] text-gray-500 mt-1">Aquí llegarán las facturas.</p>
                        @error('billing_email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="phone" class="{{ $lbl }}">Teléfono <span class="text-gray-400 font-normal">(opcional)</span>
                            <span x-show="hint('phone')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                        </label>
                        <input type="tel" name="phone" id="phone" value="{{ $v('phone') }}" @input="dirty('phone')" class="{{ $inp }}" placeholder="+573001234567">
                        @error('phone') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Dirección fiscal --}}
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 class="text-sm font-bold text-gray-800">Dirección fiscal</h2>
                        <template x-if="sedes.length">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-500">
                                <span>¿Es la de tu consultorio?</span>
                                <template x-for="(s, i) in sedes" :key="i">
                                    <button type="button" @click="useSede(i)"
                                            class="rounded-full border border-gray-200 bg-white px-3 py-1 font-medium text-gray-700 hover:border-blue-300 hover:text-blue-700"
                                            x-text="'Usar: ' + s.name"></button>
                                </template>
                            </div>
                        </template>
                    </div>
                    <p class="text-[11px] text-gray-500 -mt-2">
                        {{ $role === 'patient' ? 'La dirección donde quieres recibir la factura.' : 'Debe ser la dirección que aparece en tu RUT; puede ser distinta a la de tu consultorio.' }}
                    </p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="department_id" class="{{ $lbl }}">Departamento
                                <span x-show="hint('department_id')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                            </label>
                            <select name="department_id" id="department_id" x-model="department" @change="city = ''; dirty('department_id')" class="{{ $inp }}">
                                <option value="">Selecciona...</option>
                                @foreach ($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                @endforeach
                            </select>
                            @error('department_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="city_id" class="{{ $lbl }}">Ciudad o municipio
                                <span x-show="hint('city_id')" x-cloak class="ml-1 text-[11px] font-semibold text-amber-600">Sugerido, confírmalo</span>
                            </label>
                            <select name="city_id" id="city_id" @change="city = $event.target.value; dirty('city_id')" :disabled="!department" class="{{ $inp }} disabled:bg-gray-50">
                                <option value="">Selecciona...</option>
                                <template x-for="c in cityOptions" :key="c[0]">
                                    <option :value="c[0]" x-text="c[1]" :selected="c[0] === city"></option>
                                </template>
                            </select>
                            @error('city_id') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="address_line" class="{{ $lbl }}">Dirección</label>
                            <input type="text" name="address_line" id="address_line" x-model="address" @input="dirty('address_line')"
                                   class="{{ $inp }}" placeholder="Calle 10 # 20-30, Barrio, Oficina">
                            @error('address_line') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="{{ $lbl }}">País</label>
                            <input type="text" value="Colombia" disabled class="{{ $inp }} bg-gray-50 text-gray-500">
                        </div>
                    </div>
                </div>

                {{-- Responsabilidad fiscal --}}
                @if ($taxVisible)
                    <div>
                        <label for="tax_responsibility" class="{{ $lbl }}">Responsabilidad fiscal</label>
                        <select name="tax_responsibility" id="tax_responsibility" class="{{ $inp }}">
                            <option value="">Selecciona...</option>
                            @foreach ($taxOptions as $key => $label)
                                <option value="{{ $key }}" @selected($v('tax_responsibility') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-gray-500 mt-1">Míralo en la sección de responsabilidades de tu RUT. Si no estás seguro, consúltalo con tu contador antes de guardar.</p>
                        @error('tax_responsibility') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                {{-- Confirmación --}}
                <div class="border-t border-gray-100 pt-5">
                    <label class="flex items-start gap-3 text-sm text-gray-700">
                        <input type="checkbox" name="declaration" value="1" class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('declaration'))>
                        <span>{{ $declaration }}</span>
                    </label>
                    @error('declaration') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror

                    <div class="mt-5 flex justify-end">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                            Guardar y confirmar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-admin-layout>
