@props([
    'value' => '',
    'disabledExpr' => 'false',
    'placeholder' => 'Buscar por código (ej: J00) o diagnóstico (ej: migraña)...',
    'inputClass' => 'w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-indigo-500 focus:ring-indigo-500',
])

<div x-data="{
        query: @js($value ?? ''),
        results: [],
        open: false,
        loading: false,
        selectedIndex: -1,
        async search() {
            const term = this.query.trim();
            if (term.length < 2) {
                this.results = [];
                this.open = false;
                return;
            }

            this.loading = true;
            try {
                const response = await fetch(`{{ route('cie10.search') }}?q=${encodeURIComponent(term)}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    this.results = await response.json();
                    this.open = this.results.length > 0;
                    this.selectedIndex = -1;
                }
            } catch (e) {
                console.error('Error buscando CIE-10:', e);
            } finally {
                this.loading = false;
            }
        },
        selectItem(item) {
            this.query = item.full_label;
            this.open = false;
            this.results = [];
            this.selectedIndex = -1;
        },
        clearInput() {
            this.query = '';
            this.results = [];
            this.open = false;
            this.$refs.cieInput.focus();
        },
        moveDown() {
            if (!this.open || this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex + 1) % this.results.length;
        },
        moveUp() {
            if (!this.open || this.results.length === 0) return;
            this.selectedIndex = (this.selectedIndex - 1 + this.results.length) % this.results.length;
        },
        chooseHighlighted() {
            if (this.open && this.selectedIndex >= 0 && this.results[this.selectedIndex]) {
                this.selectItem(this.results[this.selectedIndex]);
            }
        }
    }"
    @click.away="open = false"
    class="relative w-full">

    <div class="relative flex items-center">
        <input type="text"
               x-ref="cieInput"
               id="cie10_code"
               name="cie10_code"
               x-model="query"
               :disabled="{{ $disabledExpr }}"
               @input.debounce.300ms="search()"
               @focus="if (results.length > 0) open = true"
               @keydown.arrow-down.prevent="moveDown()"
               @keydown.arrow-up.prevent="moveUp()"
               @keydown.enter.prevent="if (open && selectedIndex >= 0) { chooseHighlighted(); } else { $el.form?.requestSubmit(); }"
               @keydown.escape="open = false"
               autocomplete="off"
               placeholder="{{ $placeholder }}"
               class="{{ $inputClass }} pr-9 disabled:bg-gray-100">

        <!-- Spinner de búsqueda -->
        <div x-show="loading" x-cloak class="absolute right-2.5 text-indigo-500 pointer-events-none">
            <svg class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
        </div>

        <!-- Botón limpiar selección -->
        <button type="button"
                x-show="!loading && query.length > 0 && !({{ $disabledExpr }})"
                x-cloak
                @click="clearInput()"
                title="Limpiar diagnóstico"
                class="absolute right-2.5 text-slate-400 hover:text-slate-700 font-bold text-sm leading-none p-0.5">
            &times;
        </button>
    </div>

    <!-- Lista desplegable de coincidencias CIE-10 -->
    <div x-show="open"
         x-transition
         x-cloak
         class="absolute z-50 mt-1.5 w-full max-h-60 overflow-y-auto bg-white rounded-xl shadow-xl border border-slate-200 divide-y divide-slate-100 text-left">
        <template x-for="(item, index) in results" :key="item.id">
            <button type="button"
                    @click="selectItem(item)"
                    @mouseenter="selectedIndex = index"
                    :class="selectedIndex === index ? 'bg-indigo-50 text-indigo-900' : 'text-slate-700 hover:bg-slate-50'"
                    class="w-full px-3.5 py-2.5 text-left transition flex items-start gap-2.5">
                <span class="px-2 py-0.5 rounded-md bg-indigo-100 text-indigo-700 text-[11px] font-black font-mono shrink-0 mt-0.5"
                      x-text="item.code"></span>
                <span class="text-xs font-medium leading-snug" x-text="item.description"></span>
            </button>
        </template>
    </div>
</div>
