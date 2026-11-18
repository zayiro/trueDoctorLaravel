@php
$breadcrumbs = [
    ['name' => 'Dashboard', 'href' => route('admin.dashboard')],
    ['name' => 'Agenda Médica', 'href' => route('partner.appointments.index')],
    ['name' => 'Agendar Cita Médica']
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="py-10 px-4">

        <script>
            window.BOOKING_URLS = {
                options:  @json(route('partner.booking.options')),
                services: @json(route('partner.booking.services')),
                calendar: @json(route('partner.booking.calendar')),
                slots:    @json(route('partner.booking.slots')),
                search:   @json(route('partner.booking.search_patient')),
                store:    @json(route('partner.booking.store')),
                agenda:   @json(route('partner.appointments.index')),
            };
            window.BOOKING_CSRF = @json(csrf_token());

            function bookingFlow() {
                const json = (r) => r.json().catch(() => ({}));
                const headers = { 'Accept': 'application/json', 'X-CSRF-TOKEN': window.BOOKING_CSRF };

                return {
                    step: 1,
                    loadingOptions: true,
                    optionsError: '',
                    // paciente
                    patient: { identification: '', found: null, name: '', email: '', countryCode: '+57', phone: '' },
                    searching: false,
                    patientMsg: '',
                    // cita
                    doctors: [], addresses: [], services: [],
                    doctorId: '', addressId: '', serviceId: '',
                    month: null, calDates: [], loadingCal: false,
                    date: '', slots: [], loadingSlots: false, time: '',
                    paymentMethod: 'online', notes: '',
                    // envío
                    submitting: false, error: '', result: null, copied: false,

                    async init() {
                        try {
                            const r = await fetch(window.BOOKING_URLS.options, { headers });
                            const d = await json(r);
                            if (!r.ok) { this.optionsError = d.message || 'No se pudo cargar tu configuración de agenda.'; }
                            else {
                                this.doctors = d.doctors || [];
                                this.addresses = d.addresses || [];
                                if (this.doctors.length === 1) this.doctorId = this.doctors[0].id;
                            }
                        } catch (e) { this.optionsError = 'No se pudo cargar tu configuración de agenda.'; }
                        this.loadingOptions = false;
                    },

                    // ---------- paso 1: paciente
                    async searchPatient() {
                        const id = this.patient.identification.trim();
                        if (!id) return;
                        this.searching = true; this.patientMsg = '';
                        try {
                            const r = await fetch(window.BOOKING_URLS.search, {
                                method: 'POST', headers: { ...headers, 'Content-Type': 'application/json' },
                                body: JSON.stringify({ identification: id })
                            });
                            const d = await json(r);
                            if (d.found) {
                                this.patient = { ...this.patient, found: true, name: d.patient.name, email: d.patient.email, phone: d.patient.phone };
                            } else {
                                this.patient = { ...this.patient, found: false, name: '', email: '', phone: '' };
                                this.patientMsg = d.message || '';
                            }
                        } catch (e) { this.patientMsg = 'Error al consultar el servidor.'; }
                        this.searching = false;
                    },
                    get emailOk() { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.patient.email); },
                    get phoneOk() { return /^[0-9]{10}$/.test(this.patient.phone); },
                    get canContinue() {
                        if (this.patient.found === true) return true;
                        return this.patient.found === false && !this.patientMsg.includes('inactiva') && this.patient.name.trim().length > 2 && this.emailOk && this.phoneOk;
                    },

                    // ---------- paso 2: cita
                    get visibleAddresses() {
                        if (!this.doctorId) return this.addresses;
                        return this.addresses.filter(a => (a.doctor_ids || []).includes(Number(this.doctorId)));
                    },
                    get address() { return this.addresses.find(a => a.id == this.addressId) || null; },
                    get service() { return this.services.find(s => s.id == this.serviceId) || null; },
                    get isVirtual() { return !!(this.service && this.service.type === 'virtual') || !!(this.address && this.address.type === 'virtual'); },
                    get isFree() { return !!this.service && this.service.price <= 0; },
                    fmt(v) { return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(v); },

                    onDoctorChange() { this.addressId = ''; this.resetFromAddress(); },
                    resetFromAddress() { this.services = []; this.serviceId = ''; this.resetFromService(); },
                    resetFromService() { this.calDates = []; this.date = ''; this.slots = []; this.time = ''; },

                    async onAddressChange() {
                        this.resetFromAddress();
                        if (!this.addressId) return;
                        const q = new URLSearchParams({ address_id: this.addressId, doctor_id: this.doctorId || '' });
                        const r = await fetch(window.BOOKING_URLS.services + '?' + q, { headers });
                        const d = await json(r);
                        this.services = r.ok ? d : [];
                        if (!r.ok) this.error = d.message || 'No se pudieron cargar los servicios.';
                    },

                    async onServiceChange() {
                        this.resetFromService();
                        if (!this.serviceId) return;
                        if (this.isVirtual) this.paymentMethod = 'online';
                        const now = new Date();
                        this.month = new Date(now.getFullYear(), now.getMonth(), 1);
                        await this.loadCalendar();
                    },

                    get monthKey() { return this.month ? `${this.month.getFullYear()}-${String(this.month.getMonth() + 1).padStart(2, '0')}` : ''; },
                    get monthLabel() { return this.month ? this.month.toLocaleDateString('es-CO', { month: 'long', year: 'numeric' }) : ''; },
                    get canGoPrev() { const n = new Date(); return this.month && (this.month.getFullYear() > n.getFullYear() || this.month.getMonth() > n.getMonth()); },
                    get days() {
                        if (!this.month) return [];
                        const y = this.month.getFullYear(), m = this.month.getMonth();
                        const lead = (new Date(y, m, 1).getDay() + 6) % 7; // lunes primero
                        const total = new Date(y, m + 1, 0).getDate();
                        const out = Array(lead).fill(null);
                        for (let d = 1; d <= total; d++) {
                            const key = `${y}-${String(m + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                            out.push({ d, key, enabled: this.calDates.includes(key) });
                        }
                        return out;
                    },
                    async shiftMonth(delta) {
                        this.month = new Date(this.month.getFullYear(), this.month.getMonth() + delta, 1);
                        this.date = ''; this.slots = []; this.time = '';
                        await this.loadCalendar();
                    },
                    async loadCalendar() {
                        this.loadingCal = true; this.calDates = [];
                        const q = new URLSearchParams({ address_id: this.addressId, doctor_id: this.doctorId || '', service_id: this.serviceId, month: this.monthKey });
                        try {
                            const r = await fetch(window.BOOKING_URLS.calendar + '?' + q, { headers });
                            const d = await json(r);
                            this.calDates = r.ok ? (d.dates || []) : [];
                            if (!r.ok) this.error = d.message || 'No se pudo consultar el calendario.';
                        } catch (e) { this.error = 'No se pudo consultar el calendario.'; }
                        this.loadingCal = false;
                    },
                    async pickDate(key) {
                        this.date = key; this.time = ''; this.slots = []; this.loadingSlots = true; this.error = '';
                        const q = new URLSearchParams({ address_id: this.addressId, doctor_id: this.doctorId || '', service_id: this.serviceId, date: key });
                        try {
                            const r = await fetch(window.BOOKING_URLS.slots + '?' + q, { headers });
                            const d = await json(r);
                            this.slots = r.ok ? (Array.isArray(d) ? d : (d.slots || Object.values(d))) : [];
                            if (!r.ok) this.error = d.message || 'No se pudieron cargar los turnos.';
                        } catch (e) { this.error = 'No se pudieron cargar los turnos.'; }
                        this.loadingSlots = false;
                    },
                    // "9:00 AM" -> "09:00"
                    to24(label) {
                        let [t, mod] = String(label).split(' ');
                        let [h, m] = t.split(':');
                        h = parseInt(h, 10);
                        if (mod === 'PM' && h < 12) h += 12;
                        if (mod === 'AM' && h === 12) h = 0;
                        return String(h).padStart(2, '0') + ':' + m;
                    },
                    get dateLabel() {
                        if (!this.date) return '';
                        const [y, m, d] = this.date.split('-').map(Number);
                        return new Date(y, m - 1, d).toLocaleDateString('es-CO', { weekday: 'long', day: 'numeric', month: 'long' });
                    },
                    get canSubmit() { return this.addressId && this.serviceId && this.date && this.time && (this.doctorId || this.doctors.length === 1); },

                    async submit() {
                        this.submitting = true; this.error = '';
                        try {
                            const r = await fetch(window.BOOKING_URLS.store, {
                                method: 'POST', headers: { ...headers, 'Content-Type': 'application/json' },
                                body: JSON.stringify({
                                    identification: this.patient.identification.trim(),
                                    name: this.patient.name, email: this.patient.email,
                                    country_code: this.patient.countryCode, phone: this.patient.phone,
                                    doctor_id: this.doctorId || null, address_id: this.addressId, service_id: this.serviceId,
                                    date: this.date, start_time: this.time, payment_method: this.isFree ? 'online' : this.paymentMethod,
                                    notes: this.notes
                                })
                            });
                            const d = await json(r);
                            if (r.ok && d.success) { this.result = d; this.step = 3; }
                            else if (r.status === 422 && d.errors) { this.error = Object.values(d.errors).flat().join(' '); }
                            else {
                                this.error = d.message || 'No se pudo crear la cita.';
                                if (r.status === 409) { await this.pickDate(this.date); await this.loadCalendar(); }
                            }
                        } catch (e) { this.error = 'No se pudo crear la cita. Revisa tu conexión.'; }
                        this.submitting = false;
                    },
                    copyLink() {
                        navigator.clipboard.writeText(this.result.link).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000); });
                    },
                    restart() {
                        this.patient = { identification: '', found: null, name: '', email: '', countryCode: '+57', phone: '' };
                        this.patientMsg = ''; this.addressId = ''; this.resetFromAddress(); this.notes = '';
                        this.paymentMethod = 'online'; this.result = null; this.error = ''; this.step = 1;
                        if (this.doctors.length !== 1) this.doctorId = '';
                    },
                };
            }
        </script>

        <section class="relative" x-data="bookingFlow()">
            <div class="bg-slate-50/90 rounded-3xl p-6 sm:p-10 border border-slate-200/80 shadow-sm">

                <!-- Progreso -->
                <div class="flex items-center justify-between mb-8 pb-4 border-b border-slate-200/60 text-xs font-bold tracking-wider uppercase">
                    <span :class="step === 1 ? 'text-blue-600' : 'text-slate-400'">1. Paciente</span>
                    <div class="h-[2px] bg-slate-200 flex-1 mx-4 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-600 transition-all duration-500" :style="'width:' + (step === 1 ? '33%' : step === 2 ? '66%' : '100%')"></div>
                    </div>
                    <span :class="step >= 2 ? 'text-blue-600' : 'text-slate-400'">2. Cita y pago</span>
                </div>

                <div x-show="loadingOptions" class="text-sm text-slate-500">Cargando tu agenda...</div>
                <div x-show="optionsError" x-cloak class="p-4 rounded-2xl bg-red-50 border border-red-100 text-red-700 text-sm font-bold" x-text="optionsError"></div>

                <!-- ================= PASO 1 ================= -->
                <div x-show="step === 1 && !loadingOptions && !optionsError" x-cloak class="space-y-6">
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-900">Buscar o registrar paciente</h3>
                        <p class="text-sm text-slate-500">Escribe el documento de identidad. Si no existe, lo registramos con su correo y le enviamos todo por ahí.</p>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <input type="text" x-model="patient.identification" @keydown.enter.prevent="searchPatient()" maxlength="20" placeholder="Ej. 10402030"
                               class="flex-1 bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                        <button type="button" @click="searchPatient()" :disabled="searching || !patient.identification.trim()"
                                class="bg-slate-900 hover:bg-slate-800 disabled:opacity-50 text-white font-semibold px-6 py-3 rounded-xl text-sm">
                            <span x-text="searching ? 'Buscando...' : 'Verificar identificación'"></span>
                        </button>
                    </div>

                    <p x-show="patientMsg" x-text="patientMsg" x-cloak class="text-xs font-bold text-red-600"></p>

                    <!-- Encontrado -->
                    <div x-show="patient.found === true" x-cloak class="bg-emerald-50 border border-emerald-200/60 p-4 rounded-xl flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-bold text-slate-800" x-text="patient.name"></p>
                            <p class="text-xs text-slate-500" x-text="'Paciente verificado • ' + patient.email"></p>
                        </div>
                        <button type="button" @click="step = 2" class="text-xs font-bold text-blue-600 bg-white border border-slate-200 shadow-sm px-3 py-2 rounded-lg shrink-0">Continuar &rarr;</button>
                    </div>

                    <!-- No existe: registro express -->
                    <div x-show="patient.found === false && !patientMsg.includes('inactiva')" x-cloak class="bg-white border border-slate-200/80 p-5 rounded-2xl space-y-4 shadow-sm">
                        <div class="text-xs font-bold text-amber-600 bg-amber-50 border border-amber-200/40 px-3 py-1.5 rounded-lg w-fit">⚠️ Paciente no registrado. Completa los datos para crearlo.</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Nombre completo *</label>
                                <input type="text" x-model="patient.name" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Correo electrónico *</label>
                                <input type="email" x-model="patient.email" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-600">
                                <p x-show="patient.email && !emailOk" x-cloak class="text-[11px] text-red-600 font-bold">Correo no válido</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Teléfono móvil *</label>
                                <div class="flex rounded-xl shadow-sm border border-slate-200 bg-white overflow-hidden">
                                    <select x-model="patient.countryCode" class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 px-3 cursor-pointer">
                                        <option value="+57">🇨🇴 +57</option><option value="+54">🇦🇷 +54</option><option value="+591">🇧🇴 +591</option>
                                        <option value="+55">🇧🇷 +55</option><option value="+56">🇨🇱 +56</option><option value="+593">🇪🇨 +593</option>
                                        <option value="+595">🇵🇾 +595</option><option value="+51">🇵🇪 +51</option><option value="+598">🇺🇾 +598</option>
                                        <option value="+58">🇻🇪 +58</option>
                                    </select>
                                    <input type="tel" x-model="patient.phone" maxlength="10" placeholder="3026433874" class="block w-full border-0 focus:ring-0 p-2.5 text-sm">
                                </div>
                                <p x-show="patient.phone && !phoneOk" x-cloak class="text-[11px] text-red-600 font-bold">Deben ser 10 dígitos</p>
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-bold text-slate-500">Identificación</label>
                                <input type="text" :value="patient.identification" readonly class="w-full bg-slate-100 border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-500">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="button" @click="step = 2" :disabled="!canContinue" class="disabled:opacity-50 bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">Crear y continuar &rarr;</button>
                        </div>
                    </div>
                </div>

                <!-- ================= PASO 2 ================= -->
                <div x-show="step === 2" x-cloak class="space-y-6">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-xl font-extrabold text-slate-900">Detalles de la cita</h3>
                            <p class="text-sm text-slate-500">Agendando para: <strong x-text="patient.name"></strong></p>
                        </div>
                        <button type="button" @click="step = 1" class="text-xs font-bold text-slate-400 hover:text-slate-600">&larr; Volver</button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="space-y-1" x-show="doctors.length > 1" x-cloak>
                            <label class="text-xs font-bold text-slate-500">Especialista *</label>
                            <select x-model="doctorId" @change="onDoctorChange()" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm">
                                <option value="">Selecciona...</option>
                                <template x-for="d in doctors" :key="d.id"><option :value="d.id" x-text="d.name"></option></template>
                            </select>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Sede *</label>
                            <select x-model="addressId" @change="onAddressChange()" :disabled="doctors.length > 1 && !doctorId" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm disabled:bg-slate-100">
                                <option value="">Selecciona...</option>
                                <template x-for="a in visibleAddresses" :key="a.id"><option :value="a.id" x-text="a.name + (a.type === 'virtual' ? ' (virtual)' : '')"></option></template>
                            </select>
                            <p x-show="addresses.length === 0" x-cloak class="text-[11px] text-amber-600 font-bold">No tienes sedes activas. Crea una en "Sedes".</p>
                        </div>
                        <div class="space-y-1">
                            <label class="text-xs font-bold text-slate-500">Servicio *</label>
                            <select x-model="serviceId" @change="onServiceChange()" :disabled="!addressId" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm disabled:bg-slate-100">
                                <option value="">Selecciona...</option>
                                <template x-for="s in services" :key="s.id"><option :value="s.id" x-text="s.name + ' — ' + fmt(s.price) + ' · ' + s.duration + ' min'"></option></template>
                            </select>
                        </div>
                    </div>

                    <!-- Calendario -->
                    <div x-show="serviceId" x-cloak class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <div class="flex items-center justify-between mb-3">
                                <button type="button" @click="shiftMonth(-1)" :disabled="!canGoPrev || loadingCal" class="px-3 py-1 rounded-lg text-slate-600 hover:bg-slate-100 disabled:opacity-30">&larr;</button>
                                <span class="text-sm font-black text-slate-800 capitalize" x-text="monthLabel"></span>
                                <button type="button" @click="shiftMonth(1)" :disabled="loadingCal" class="px-3 py-1 rounded-lg text-slate-600 hover:bg-slate-100 disabled:opacity-30">&rarr;</button>
                            </div>
                            <div class="grid grid-cols-7 gap-1 text-center text-[10px] font-black text-slate-400 uppercase mb-1">
                                <span>L</span><span>M</span><span>M</span><span>J</span><span>V</span><span>S</span><span>D</span>
                            </div>
                            <div class="grid grid-cols-7 gap-1" :class="loadingCal ? 'opacity-40' : ''">
                                <template x-for="(c, i) in days" :key="i">
                                    <div>
                                        <template x-if="c === null"><span></span></template>
                                        <template x-if="c !== null">
                                            <button type="button" @click="pickDate(c.key)" :disabled="!c.enabled"
                                                    :class="date === c.key ? 'bg-blue-600 text-white' : (c.enabled ? 'bg-blue-50 text-blue-700 hover:bg-blue-100' : 'text-slate-300 cursor-not-allowed')"
                                                    class="w-full aspect-square rounded-lg text-sm font-bold" x-text="c.d"></button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            <p x-show="!loadingCal && calDates.length === 0" x-cloak class="mt-3 text-xs font-bold text-slate-400">Sin turnos este mes. Prueba el siguiente.</p>
                        </div>

                        <!-- Turnos -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-4">
                            <p class="text-xs font-black text-slate-500 uppercase tracking-wider mb-3">
                                Turnos <span x-show="date" x-cloak class="normal-case text-slate-400" x-text="'· ' + dateLabel"></span>
                            </p>
                            <p x-show="!date" class="text-xs text-slate-400">Elige un día del calendario.</p>
                            <p x-show="loadingSlots" x-cloak class="text-xs font-bold text-blue-600">Consultando turnos...</p>
                            <div class="grid grid-cols-3 gap-2 max-h-64 overflow-y-auto">
                                <template x-for="s in slots" :key="s.time">
                                    <button type="button" @click="time = to24(s.time)"
                                            :class="time === to24(s.time) ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-slate-700 border-slate-200 hover:border-blue-400'"
                                            class="border rounded-lg py-2 text-xs font-bold" x-text="s.time"></button>
                                </template>
                            </div>
                            <p x-show="date && !loadingSlots && slots.length === 0" x-cloak class="text-xs font-bold text-red-600">Sin turnos libres ese día.</p>
                        </div>
                    </div>

                    <!-- Pago -->
                    <div x-show="serviceId" x-cloak class="bg-white border border-slate-200/80 p-4 rounded-2xl space-y-3">
                        <label class="text-xs font-bold text-slate-500 uppercase tracking-wider block">Cobro</label>
                        <template x-if="isFree">
                            <p class="text-sm text-slate-600">Este servicio no tiene costo: la cita queda confirmada.</p>
                        </template>
                        <div x-show="!isFree" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 rounded-xl border cursor-pointer" :class="paymentMethod === 'online' ? 'border-blue-600 bg-blue-50/30' : 'border-slate-100 bg-slate-50/50'">
                                <input type="radio" value="online" x-model="paymentMethod" class="mt-1 text-blue-600">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Enviar enlace de pago</p>
                                    <p class="text-[11px] text-slate-400">El paciente paga desde su correo, sin iniciar sesión. El turno queda reservado hasta 24 h (o hasta la hora de la cita); si no paga, se libera solo.</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-3 p-3 rounded-xl border" :class="[isVirtual ? 'opacity-50 cursor-not-allowed' : 'cursor-pointer', paymentMethod === 'onsite' ? 'border-emerald-600 bg-emerald-50/30' : 'border-slate-100 bg-slate-50/50']">
                                <input type="radio" value="onsite" x-model="paymentMethod" :disabled="isVirtual" class="mt-1 text-emerald-600">
                                <div>
                                    <p class="text-sm font-bold text-slate-800">Pago en consulta</p>
                                    <p class="text-[11px] text-slate-400" x-text="isVirtual ? 'Las citas virtuales se cobran siempre en línea.' : 'Queda confirmada de inmediato; el cobro lo haces en el consultorio.'"></p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div x-show="serviceId" x-cloak class="space-y-1">
                        <label class="text-xs font-bold text-slate-500">Notas (opcional)</label>
                        <textarea x-model="notes" rows="2" maxlength="1000" class="w-full bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-sm"></textarea>
                    </div>

                    <p x-show="error" x-text="error" x-cloak class="p-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-xs font-bold"></p>

                    <div class="flex justify-end pt-2">
                        <button type="button" @click="submit()" :disabled="!canSubmit || submitting"
                                class="disabled:opacity-50 disabled:cursor-not-allowed bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl text-sm shadow-md">
                            <span x-text="submitting ? 'Procesando...' : ((isFree || paymentMethod === 'onsite') ? 'Confirmar y agendar cita' : 'Reservar y enviar enlace de pago')"></span>
                        </button>
                    </div>
                </div>

                <!-- ================= RESULTADO ================= -->
                <div x-show="step === 3" x-cloak class="space-y-5 text-center">
                    <div class="mx-auto w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h3 class="text-xl font-extrabold text-slate-900" x-text="result && result.message"></h3>
                    <p class="text-sm text-slate-500">Referencia <span class="font-mono font-bold text-slate-800" x-text="result && result.reference"></span></p>

                    <div x-show="result && result.link" x-cloak class="bg-white border border-slate-200 rounded-2xl p-4 text-left space-y-3">
                        <p class="text-xs font-black text-slate-500 uppercase tracking-wider">Enlace de pago</p>
                        <p class="text-xs text-slate-500 break-all font-mono" x-text="result && result.link"></p>
                        <p class="text-xs font-bold text-amber-700">Reservado hasta <span x-text="result && result.expires_at"></span>. Si no paga antes, el turno se libera.</p>
                        <button type="button" @click="copyLink()" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-4 py-2 rounded-xl text-xs">
                            <span x-text="copied ? '¡Enlace copiado!' : 'Copiar enlace'"></span>
                        </button>
                    </div>

                    <div class="flex justify-center gap-3 pt-2">
                        <a :href="window.BOOKING_URLS.agenda" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-slate-900 text-white">Ver agenda</a>
                        <button type="button" @click="restart()" class="px-5 py-2.5 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-700">Agendar otra</button>
                    </div>
                </div>
            </div>
        </section>
    </div>
</x-admin-layout>
