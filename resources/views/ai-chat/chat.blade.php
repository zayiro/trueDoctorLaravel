@php $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale); @endphp
<x-guest-layout>
    <div class="max-w-2xl mx-auto px-3 py-4 mt-2" x-data="aiChatApp(@js($config))" @pagehide.window="leave()" @visibilitychange.window="visible()">
        {{-- Encabezado: título, idioma y reloj --}}
        <div class="flex items-center justify-between gap-3 mb-2">
            <div>
                <h1 class="text-lg font-black text-gray-800">{{ $t('chat_title') }}</h1>
                <p class="text-xs text-slate-500" x-show="focus" x-text="focusLabel" x-cloak></p>
            </div>
            <div class="flex items-center gap-3">
                <select class="rounded-lg border-gray-300 text-xs py-1" aria-label="{{ $t('lang_label') }}" @change="setLocale($event.target.value)">
                    <option value="es" @selected($locale === 'es')>ES</option>
                    <option value="en" @selected($locale === 'en')>EN</option>
                </select>
                <div class="text-right">
                    <div class="text-[10px] uppercase tracking-wide text-slate-400">{{ $t('timer_label') }}</div>
                    <div class="font-mono text-xl font-black" :class="secondsLeft <= 60 ? 'text-red-600' : (secondsLeft <= 300 ? 'text-amber-600' : 'text-gray-800')" x-text="clock"></div>
                </div>
            </div>
        </div>

        <div x-show="secondsLeft <= 300 && secondsLeft > 60 && !ended" x-cloak class="mb-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-800">{{ $t('five_min') }}</div>
        <div x-show="secondsLeft <= 60 && !ended" x-cloak class="mb-2 rounded-xl bg-red-50 border border-red-200 px-3 py-2 text-sm text-red-700 font-semibold">{{ $t('one_min') }}</div>

        {{-- Mensajes --}}
        <div x-ref="box" class="bg-white rounded-2xl border border-gray-200 shadow-sm p-3 h-[55vh] overflow-y-auto space-y-3">
            <template x-for="m in messages" :key="m.key">
                <div class="flex" :class="m.role === 'user' ? 'justify-end' : 'justify-start'">
                    <div class="max-w-[85%] rounded-2xl px-3 py-2 text-sm whitespace-pre-line leading-relaxed"
                         :class="m.role === 'user' ? 'bg-blue-600 text-white' : (m.kind === 'notice' ? 'bg-amber-50 text-amber-900 border border-amber-200' : 'bg-gray-100 text-gray-800')"
                         x-text="m.content"></div>
                </div>
            </template>
            <div x-show="typing" x-cloak class="text-xs text-slate-400">{{ $t('typing') }}</div>
        </div>

        {{-- Arranque rápido --}}
        <div x-show="!quickUsed && !ended && !otherDevice" x-cloak class="mt-3">
            <p class="text-xs text-slate-500 mb-1">{{ $t('qs_title') }}</p>
            <div class="flex flex-wrap gap-2">
                @for ($i = 1; $i <= 6; $i++)
                    <button type="button" @click="quick({{ $i }})" :disabled="sending"
                            class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100 disabled:opacity-50">{{ $t('qs_' . $i) }}</button>
                @endfor
            </div>
        </div>

        <p x-show="error" x-text="error" x-cloak class="mt-2 text-sm font-semibold text-amber-700"></p>

        {{-- Escribir --}}
        <form class="mt-3" @submit.prevent="send()">
            <div class="flex items-end gap-2">
                <textarea x-model="draft" rows="2" :maxlength="maxChars" :disabled="ended || otherDevice || remaining <= 0"
                          placeholder="{{ $t('input_placeholder') }}" @keydown.enter.prevent="if (!$event.shiftKey) send()"
                          class="flex-1 rounded-xl border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                <button type="submit" :disabled="sending || ended || otherDevice || remaining <= 0 || draft.trim() === ''"
                        class="rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">{{ $t('send') }}</button>
            </div>
            <div class="mt-1 flex justify-between text-xs text-slate-400">
                <span x-text="remaining + ' ' + texts.msgs_left"></span>
                <span x-text="draft.length + ' / ' + maxChars"></span>
            </div>
        </form>

        {{-- Sesión abierta en otro lugar --}}
        <div x-show="otherDevice && !ended" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl p-6 max-w-sm text-center space-y-3">
                <h2 class="text-lg font-black text-gray-800">{{ $t('other_title') }}</h2>
                <p class="text-sm text-slate-600">{{ $t('other_text') }}</p>
                <button type="button" @click="reloadHere()" class="rounded-xl bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">{{ $t('use_here') }}</button>
            </div>
        </div>

        {{-- Fin --}}
        <div x-show="ended" x-cloak class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl p-6 max-w-sm text-center"><p class="text-sm text-slate-700">{{ $t('ended_overlay') }}</p></div>
        </div>
    </div>

    <script>
        function aiChatApp(cfg) {
            return {
                urls: cfg.urls, csrf: cfg.csrf, instance: cfg.instance, texts: cfg.texts,
                secondsLeft: cfg.secondsLeft, remaining: cfg.remaining, maxChars: cfg.maxChars,
                quickUsed: cfg.quickUsed, focus: cfg.focus || null,
                messages: cfg.messages.map(m => ({ ...m, key: 'm' + m.id })),
                draft: '', sending: false, typing: false, error: '', ended: false, otherDevice: false,
                nudged: false, timers: [], lastTick: Date.now(), seq: 0,
                get clock() {
                    const s = Math.max(0, this.secondsLeft);
                    return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
                },
                get focusLabel() { return this.focus || ''; },
                init() {
                    this.scroll();
                    this.timers.push(setInterval(() => this.tick(), 1000));
                    this.timers.push(setInterval(() => this.sync(), 30000));
                    // Mensaje de ánimo local si no ha escrito nada
                    if (!this.quickUsed && cfg.nudgeSeconds > 0) {
                        setTimeout(() => {
                            if (!this.nudged && !this.quickUsed && !this.messages.some(m => m.role === 'user')) {
                                this.nudged = true;
                                this.push({ role: 'assistant', kind: 'notice', content: this.texts.nudge });
                            }
                        }, cfg.nudgeSeconds * 1000);
                    }
                },
                tick() {
                    if (this.ended) return;
                    this.secondsLeft = Math.max(0, this.secondsLeft - 1);
                    if (this.secondsLeft <= 0) this.sync();
                },
                push(m) { this.messages.push({ ...m, key: 'l' + (++this.seq) }); this.scroll(); },
                scroll() { this.$nextTick(() => { const b = this.$refs.box; if (b) b.scrollTop = b.scrollHeight; }); },
                async sync() {
                    if (this.ended) return;
                    try {
                        const r = await fetch(this.urls.state + '?instance=' + encodeURIComponent(this.instance), { headers: { 'Accept': 'application/json' } });
                        const d = await r.json();
                        if (d.status && d.status !== 'activa') { this.finish(); return; }
                        if (d.active === false) { this.otherDevice = true; return; }
                        this.secondsLeft = d.seconds_left;
                        this.remaining = d.remaining;
                        this.focus = d.focus || this.focus;
                    } catch (e) { /* sin red: el reloj local sigue */ }
                },
                visible() { if (document.visibilityState === 'visible') this.sync(); },
                finish() {
                    this.ended = true;
                    setTimeout(() => window.location.reload(), 2500);
                },
                async post(url, body) {
                    return fetch(url, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': this.csrf },
                        body: JSON.stringify({ ...body, instance: this.instance }),
                    });
                },
                async deliver(body) {
                    if (this.sending) return;
                    this.sending = true; this.error = ''; this.typing = true;
                    try {
                        const r = await this.post(this.urls.message, body);
                        const d = await r.json().catch(() => ({}));
                        if (d.ok) {
                            d.messages.forEach(m => this.messages.push({ ...m, key: 'm' + m.id }));
                            this.remaining = d.remaining; this.secondsLeft = d.seconds_left; this.focus = d.focus || this.focus;
                            if (body.quick) this.quickUsed = true;
                            if (body.text) this.draft = '';
                            this.scroll();
                        } else if (d.code === 'ended') { this.finish(); }
                        else if (d.code === 'other_device') { this.otherDevice = true; }
                        else { this.error = d.message || this.texts.err_generic; }
                    } catch (e) { this.error = this.texts.err_generic; }
                    this.typing = false; this.sending = false;
                },
                send() { const t = this.draft.trim(); if (t) this.deliver({ text: t }); },
                quick(i) { this.deliver({ quick: i }); },
                async setLocale(l) {
                    try { await this.post(this.urls.locale, { locale: l }); } catch (e) {}
                    this.release();
                    window.location.reload();
                },
                reloadHere() { window.location.reload(); },
                release() { try { navigator.sendBeacon(this.urls.release, new Blob([JSON.stringify({ instance: this.instance, _token: this.csrf })], { type: 'application/json' })); } catch (e) {} },
                leave() { this.release(); },
            };
        }
    </script>
</x-guest-layout>
