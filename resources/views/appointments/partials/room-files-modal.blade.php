<script>
document.addEventListener('alpine:init', () => {
    Alpine.store('roomFiles', {
        me: {{ auth()->id() }},
        indexUrl: @json(route('appointments.files.index', $appointment)),
        storeUrl: @json(route('appointments.files.store', $appointment)),
        csrf: @json(csrf_token()),
        files: [], newIds: [], loaded: false,
        modalOpen: false, uploading: false, error: '', selected: null,

        init() {
            this.fetchFiles();
            setInterval(() => { if (!document.hidden) this.fetchFiles(); }, 8000);
        },

        get newCount() { return this.newIds.length; },
        isNew(id) { return this.newIds.includes(id); },
        markSeen() { this.newIds = []; },

        async fetchFiles() {
            try {
                const r = await fetch(this.indexUrl, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const { files } = await r.json();
                const known = new Set(this.files.map(f => f.id));
                if (this.loaded) {
                    files.forEach(f => {
                        if (!known.has(f.id) && f.uploaded_by !== this.me) this.newIds.push(f.id);
                    });
                }
                this.files = files;
                this.loaded = true;
            } catch (e) { /* silencioso: se reintenta en el siguiente ciclo */ }
        },

        open() { this.error = ''; this.selected = null; this.modalOpen = true; },

        pick(e) {
            const f = e.target.files[0];
            this.error = '';
            this.selected = null;
            if (!f) return;
            if (!['application/pdf', 'image/jpeg', 'image/png'].includes(f.type)) {
                this.error = 'Solo se permiten PDF, JPG o PNG.'; return;
            }
            if (f.size > 10 * 1024 * 1024) {
                this.error = 'El archivo no puede superar 10 MB.'; return;
            }
            this.selected = f;
        },

        async upload() {
            if (!this.selected || this.uploading) return;
            this.uploading = true;
            this.error = '';
            const fd = new FormData();
            fd.append('file', this.selected);
            try {
                const r = await fetch(this.storeUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': this.csrf, 'Accept': 'application/json' },
                    body: fd,
                });
                const data = await r.json().catch(() => ({}));
                if (!r.ok) {
                    this.error = data.errors?.file?.[0] || data.message || 'No se pudo subir el archivo.';
                    return;
                }
                this.modalOpen = false;
                this.selected = null;
                await this.fetchFiles();
            } catch (e) {
                this.error = 'Error de conexión. Intenta de nuevo.';
            } finally {
                this.uploading = false;
            }
        },
    });
});
</script>

<div x-data x-cloak x-show="$store.roomFiles.modalOpen"
     x-transition.opacity
     @keydown.escape.window="$store.roomFiles.modalOpen = false"
     class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
    <div @click.outside="$store.roomFiles.modalOpen = false"
         class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
        <h3 class="text-lg font-semibold text-gray-900">Subir archivo a la reunión</h3>
        <p class="mt-1 text-sm text-gray-500">PDF, JPG o PNG. Máximo 10 MB.</p>

        <label class="mt-4 flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-gray-300 p-6 text-center hover:border-blue-400">
            <span class="text-sm text-gray-600"
                  x-text="$store.roomFiles.selected ? $store.roomFiles.selected.name : 'Toca para elegir un archivo'"></span>
            <input type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                   @change="$store.roomFiles.pick($event)">
        </label>

        <p x-show="$store.roomFiles.error" x-text="$store.roomFiles.error" class="mt-2 text-sm text-red-600"></p>

        <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="$store.roomFiles.modalOpen = false"
                    class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Cancelar</button>
            <button type="button" @click="$store.roomFiles.upload()"
                    :disabled="!$store.roomFiles.selected || $store.roomFiles.uploading"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                <span x-show="!$store.roomFiles.uploading">Subir</span>
                <span x-show="$store.roomFiles.uploading">Subiendo...</span>
            </button>
        </div>
    </div>
</div>