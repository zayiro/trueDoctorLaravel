<script>
    // Helper de IndexedDB para el AI Scribe.
    const AIScribeStorage = (() => {
        const DB_NAME = 'opendoctor_ai_scribe';
        const STORE_NAME = 'pending_recordings';
        const DB_VERSION = 1;

        function openDb() {
            return new Promise((resolve, reject) => {
                const req = indexedDB.open(DB_NAME, DB_VERSION);

                req.onupgradeneeded = (event) => {
                    const db = event.target.result;
                    if (!db.objectStoreNames.contains(STORE_NAME)) {
                        const store = db.createObjectStore(STORE_NAME, { keyPath: 'id' });
                        store.createIndex('patientId', 'patientId', { unique: false });
                    }
                };

                req.onsuccess = () => resolve(req.result);
                req.onerror = () => reject(req.error);
            });
        }

        async function save(record) {
            const db = await openDb();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).put(record);
                tx.oncomplete = () => resolve(record);
                tx.onerror = () => reject(tx.error);
            });
        }

        async function remove(id) {
            const db = await openDb();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readwrite');
                tx.objectStore(STORE_NAME).delete(id);
                tx.oncomplete = () => resolve();
                tx.onerror = () => reject(tx.error);
            });
        }

        async function getAll() {
            const db = await openDb();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const req = tx.objectStore(STORE_NAME).getAll();
                req.onsuccess = () => resolve(req.result || []);
                req.onerror = () => reject(req.error);
            });
        }

        async function get(id) {
            const db = await openDb();
            return new Promise((resolve, reject) => {
                const tx = db.transaction(STORE_NAME, 'readonly');
                const req = tx.objectStore(STORE_NAME).get(id);
                req.onsuccess = () => resolve(req.result || null);
                req.onerror = () => reject(req.error);
            });
        }

        return { save, remove, getAll, get };
    })();

    document.addEventListener('alpine:init', () => {
        Alpine.data('consultationScribe', ({ patientId, appointmentId, uploadUrl, statusUrlBase, notifyPendingUrl, recordingId }) => ({
            state: 'idle',
            mediaRecorder: null,
            chunks: [],
            startedAt: null,
            elapsedLabel: '00:00',
            timerInterval: null,
            pollInterval: null,
            errorMessage: '',
            uploadAttempts: 0,
            maxAutoRetries: 3,
            recordingId: recordingId,

            get statusLabel() {
                return {
                    uploading: 'Subiendo audio...',
                    transcribing: 'Transcribiendo...',
                    structuring: 'Redactando nota...',
                }[this.state] || 'Procesando...';
            },

            async init() {
                const pending = await AIScribeStorage.get(this.recordingId);
                if (pending && pending.status === 'pending_upload') {
                    this.chunks = (pending.chunkBuffers || []).map(
                        buf => new Blob([buf], { type: 'audio/webm' })
                    );
                    this.uploadAttempts = pending.attempts || 0;
                    this.state = 'upload_failed';
                    this.errorMessage = 'Grabación anterior pendiente. Haz clic en reintentar.';
                }
            },

            async startRecording() {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    this.chunks = [];
                    this.uploadAttempts = 0;
                    this.mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });

                    this.mediaRecorder.ondataavailable = (e) => {
                        if (e.data.size > 0) this.chunks.push(e.data);
                    };

                    this.mediaRecorder.onstop = () => {
                        stream.getTracks().forEach(track => track.stop());
                        this.uploadAudio();
                    };

                    this.mediaRecorder.start(30000);
                    this.startedAt = Date.now();
                    this.state = 'recording';
                    this.timerInterval = setInterval(() => this.updateElapsed(), 1000);
                } catch (err) {
                    this.errorMessage = 'No se pudo acceder al micrófono. Revisa los permisos.';
                    this.state = 'error';
                }
            },

            stopRecording() {
                clearInterval(this.timerInterval);
                this.state = 'uploading';
                this.mediaRecorder.stop();
            },

            updateElapsed() {
                const seconds = Math.floor((Date.now() - this.startedAt) / 1000);
                const mm = String(Math.floor(seconds / 60)).padStart(2, '0');
                const ss = String(seconds % 60).padStart(2, '0');
                this.elapsedLabel = `${mm}:${ss}`;
            },

            async uploadAudio() {
                this.state = 'uploading';
                this.uploadAttempts++;

                const blob = new Blob(this.chunks, { type: 'audio/webm' });
                const formData = new FormData();
                formData.append('audio', blob, 'consultation.webm');

                try {
                    const res = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                        body: formData,
                    });

                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'No se pudo subir el audio.');
                    }

                    const data = await res.json();
                    await AIScribeStorage.remove(this.recordingId);

                    this.state = 'transcribing';
                    this.pollStatus(data.job_token);

                } catch (err) {
                    await this.handleUploadFailure();
                }
            },

            async handleUploadFailure() {
                const buffers = await Promise.all(
                    this.chunks.map(chunk => chunk.arrayBuffer())
                );

                await AIScribeStorage.save({
                    id: this.recordingId,
                    patientId,
                    appointmentId,
                    uploadUrl,
                    notifyPendingUrl,
                    chunkBuffers: buffers,
                    attempts: this.uploadAttempts,
                    status: 'pending_upload',
                    savedAt: Date.now(),
                });

                if (this.uploadAttempts < this.maxAutoRetries) {
                    this.state = 'uploading';
                    setTimeout(() => this.uploadAudio(), 20000);
                } else {
                    this.state = 'upload_failed';
                    this.errorMessage = 'No se pudo subir. Tu grabación se guardó — reintentar cuando tengas conexión.';
                    this.notifyPendingIfOnline();
                }
            },

            async notifyPendingIfOnline() {
                if (!navigator.onLine) return;
                try {
                    await fetch(notifyPendingUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ appointment_id: appointmentId }),
                    });
                } catch (e) {}
            },

            retryUpload() {
                this.uploadAttempts = 0;
                this.uploadAudio();
            },

            pollStatus(jobToken) {
                this.pollInterval = setInterval(async () => {
                    try {
                        const res = await fetch(`${statusUrlBase}/${jobToken}/status`);
                        const data = await res.json();

                        if (data.status === 'transcribing' || data.status === 'structuring' || data.status === 'queued') {
                            this.state = data.status === 'queued' ? 'transcribing' : data.status;
                            return;
                        }

                        if (data.status === 'ready') {
                            clearInterval(this.pollInterval);
                            this.fillForm(data);
                            this.state = 'ready';
                            return;
                        }

                        if (data.status === 'failed' || data.status === 'not_found') {
                            clearInterval(this.pollInterval);
                            this.errorMessage = data.error || 'No se pudo generar la nota.';
                            this.state = 'error';
                        }
                    } catch (err) {
                        clearInterval(this.pollInterval);
                        this.errorMessage = 'Se perdió la conexión durante el procesamiento.';
                        this.state = 'error';
                    }
                }, 3000);
            },

            fillForm(data) {
                const form = document.getElementById('evolution-note-form');
                if (!form) return;

                const set = (name, value) => {
                    const field = form.querySelector(`[name="${name}"]`);
                    if (field && value) field.value = value;
                };

                set('entry_type',      data.soap?.entry_type ?? 'consultation');
                set('cie10_code',      data.soap?.cie10_code ?? '');
                set('soap_subjective', data.soap?.subjective  ?? '');
                set('soap_objective',  data.soap?.objective   ?? '');
                set('soap_assessment', data.soap?.assessment  ?? '');
                set('soap_plan',       data.soap?.plan        ?? '');
            },

            reset() {
                this.state = 'idle';
                this.chunks = [];
                this.errorMessage = '';
                this.uploadAttempts = 0;
            },
        }));
    });

    window.addEventListener('online', async () => {
        try {
            const pending = await AIScribeStorage.getAll();
            for (const rec of pending) {
                if (rec.status !== 'pending_upload') continue;
                try {
                    await fetch(rec.notifyPendingUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ appointment_id: rec.appointmentId }),
                    });
                } catch (e) {}
            }
        } catch (e) {}
    });
</script>