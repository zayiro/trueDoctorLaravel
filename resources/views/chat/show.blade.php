@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Mensajes',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    <div class="max-w-6xl mx-auto px-4 py-8 mt-8">
        <div class="flex flex-col md:flex-row gap-6 h-[80vh]">

            {{-- SIDEBAR --}}
            <div class="md:w-80 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h2 class="text-lg font-black text-slate-800">Mensajes</h2>
                    <a href="{{ route('chat.index') }}" class="text-xs text-blue-600 font-bold hover:underline">Ver todos</a>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-slate-50">
                    @foreach($conversations as $conv)
                        @php
                            $other = match(Auth::user()->role) {
                                'patient' => $conv->doctor?->user ?? $conv->clinic,
                                'doctor', 'clinic' => $conv->patient?->user,
                                default => null,
                            };
                            $unread = $conv->unreadCount(Auth::id());
                            $isActive = $conv->id === $conversation->id;
                        @endphp

                        <a href="{{ route('chat.show', $conv) }}"
                            class="flex items-center gap-3 px-4 py-3 transition {{ $isActive ? 'bg-blue-50' : 'hover:bg-slate-50' }}">

                            <div class="w-10 h-10 rounded-full {{ $isActive ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center flex-shrink-0 font-black text-sm">
                                {{ substr($other?->name ?? '?', 0, 1) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between">
                                    <p class="text-sm font-bold {{ $isActive ? 'text-blue-700' : 'text-slate-800' }} truncate">
                                        {{ $other?->name ?? 'Usuario' }}
                                    </p>
                                    <span class="text-[10px] text-slate-400 flex-shrink-0 ml-1">
                                        {{ $conv->last_message_at?->diffForHumans(null, true) }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-400 truncate">
                                    {{ $conv->lastMessage?->body ?? 'Adjunto' }}
                                </p>
                            </div>

                            @if($unread > 0)
                                <span class="w-5 h-5 bg-blue-600 text-white text-[10px] font-black rounded-full flex items-center justify-center flex-shrink-0">
                                    {{ $unread }}
                                </span>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- CHAT PRINCIPAL --}}
            @php
                $other = match(Auth::user()->role) {
                    'patient' => $conversation->doctor?->user ?? $conversation->clinic,
                    'doctor', 'clinic' => $conversation->patient?->user,
                    default => null,
                };
            @endphp

            <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-100 flex flex-col overflow-hidden"
                x-data="{
                    messages: {{ Js::from($messages->map(fn($m) => [
                        'id'              => $m->id,
                        'body'            => $m->body,
                        'sender_id'       => $m->sender_id,
                        'sender_name'     => $m->sender->name,
                        'is_mine'         => $m->sender_id === Auth::id(),
                        'attachment_name' => $m->attachment_name,
                        'attachment_type' => $m->attachment_type,
                        'attachment_url'  => $m->attachment_path ? route('chat.attachment', $m->id) : null,
                        'created_at'      => $m->created_at->format('H:i'),
                    ])) }},
                    body: '',
                    sending: false,
                    lastId: {{ $messages->last()?->id ?? 0 }},
                    previewFile: null,
                    previewName: '',

                    async send() {
                        if (!this.body.trim() && !this.$refs.fileInput.files[0]) return;
                        this.sending = true;

                        const form = new FormData();
                        form.append('body', this.body);
                        form.append('_token', '{{ csrf_token() }}');
                        if (this.$refs.fileInput.files[0]) {
                            form.append('attachment', this.$refs.fileInput.files[0]);
                        }

                        const res = await fetch('{{ route('chat.send', $conversation) }}', {
                            method: 'POST',
                            body: form,
                        });

                        if (res.ok) {
                            const data = await res.json();
                            this.messages.push({ ...data.message, is_mine: true });
                            this.body = '';
                            this.previewFile = null;
                            this.previewName = '';
                            this.$refs.fileInput.value = '';
                            this.lastId = data.message.id;
                            this.$nextTick(() => this.scrollBottom());
                        }
                        this.sending = false;
                    },

                    async poll() {
                        const res = await fetch('{{ route('chat.poll', $conversation) }}?last_id=' + this.lastId);
                        if (res.ok) {
                            const data = await res.json();
                            if (data.messages.length > 0) {
                                this.messages.push(...data.messages);
                                this.lastId = data.messages[data.messages.length - 1].id;
                                this.$nextTick(() => this.scrollBottom());
                            }
                        }
                    },

                    scrollBottom() {
                        const el = this.$refs.messageContainer;
                        if (el) el.scrollTop = el.scrollHeight;
                    },

                    onFileChange(e) {
                        const file = e.target.files[0];
                        if (file) {
                            this.previewName = file.name;
                            if (file.type.startsWith('image/')) {
                                this.previewFile = URL.createObjectURL(file);
                            } else {
                                this.previewFile = null;
                            }
                        }
                    }
                }"
                x-init="
                    scrollBottom();
                    setInterval(() => poll(), 5000);
                ">

                {{-- Header del chat --}}
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-black text-sm">
                            {{ substr($other?->name ?? '?', 0, 1) }}
                        </div>
                        <div>
                            <p class="text-sm font-black text-slate-800">{{ $other?->name ?? 'Usuario' }}</p>
                            <p class="text-xs text-slate-400">
                                @if(Auth::user()->role === 'patient')
                                    {{ $conversation->doctor ? 'Especialista' : 'Clínica' }}
                                @else
                                    Paciente
                                @endif
                            </p>
                        </div>
                    </div>

                    {{-- Acciones --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click.stop="open = !open"
                            class="p-2 rounded-xl hover:bg-slate-100 transition text-slate-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z"/>
                            </svg>
                        </button>

                        <template x-teleport="body">
                            <div
                                x-show="open"
                                @click.outside="open = false"
                                x-transition
                                class="fixed z-[9999] bg-white border border-slate-100 rounded-2xl shadow-xl overflow-hidden min-w-[190px]"
                                style="top: 70px; right: 20px; display: none;">
                                @foreach(['managed' => '✅ Marcar gestionado', 'blocked' => '🚫 Bloquear', 'active' => '🔓 Activar'] as $st => $label)
                                    @if($conversation->status !== $st)
                                        <form action="{{ route('chat.status', $conversation) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="{{ $st }}">
                                            <button type="submit"
                                                class="w-full text-left px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                                                {{ $label }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Mensajes --}}
                <div class="flex-1 overflow-y-auto p-5 space-y-3" x-ref="messageContainer">
                    <template x-for="msg in messages" :key="msg.id">
                        <div :class="msg.is_mine ? 'flex justify-end' : 'flex justify-start'">
                            <div :class="msg.is_mine
                                    ? 'bg-blue-600 text-white rounded-2xl rounded-tr-sm'
                                    : 'bg-slate-100 text-slate-800 rounded-2xl rounded-tl-sm'"
                                class="max-w-xs md:max-w-md px-4 py-2.5 shadow-sm">

                                {{-- Nombre del remitente --}}
                                <p x-show="!msg.is_mine"
                                    class="text-[10px] font-black text-slate-500 mb-1"
                                    x-text="msg.sender_name"></p>

                                {{-- Texto --}}
                                <p x-show="msg.body" class="text-sm leading-relaxed" x-text="msg.body"></p>

                                {{-- Adjunto imagen --}}
                                <template x-if="msg.attachment_type && msg.attachment_type.startsWith('image/')">
                                    <a :href="msg.attachment_url" target="_blank">
                                        <img :src="msg.attachment_url" class="mt-2 rounded-xl max-w-[200px]">
                                    </a>
                                </template>

                                {{-- Adjunto archivo --}}
                                <template x-if="msg.attachment_name && msg.attachment_type && !msg.attachment_type.startsWith('image/')">
                                    <a :href="msg.attachment_url" target="_blank"
                                        class="mt-2 flex items-center gap-2 text-xs font-bold underline opacity-90">
                                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                                        </svg>
                                        <span x-text="msg.attachment_name"></span>
                                    </a>
                                </template>

                                {{-- Hora --}}
                                <p :class="msg.is_mine ? 'text-blue-200' : 'text-slate-400'"
                                    class="text-[10px] mt-1 text-right"
                                    x-text="msg.created_at"></p>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Preview archivo --}}
                <div x-show="previewName" class="px-5 py-2 bg-slate-50 border-t border-slate-100 flex items-center gap-2" style="display:none;">
                    <template x-if="previewFile">
                        <img :src="previewFile" class="w-12 h-12 rounded-lg object-cover">
                    </template>
                    <template x-if="!previewFile">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/>
                        </svg>
                    </template>
                    <span class="text-xs text-slate-600 font-medium flex-1 truncate" x-text="previewName"></span>
                    <button @click="previewFile=null; previewName=''; $refs.fileInput.value=''"
                        class="text-slate-400 hover:text-red-500 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Input de mensaje --}}
                <div class="px-4 py-3 border-t border-slate-100 flex items-end gap-2">

                    {{-- Adjuntar archivo --}}
                    <label class="cursor-pointer p-2.5 rounded-xl text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition flex-shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13"/>
                        </svg>
                        <input type="file" class="hidden" x-ref="fileInput"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                            @change="onFileChange($event)">
                    </label>

                    {{-- Textarea --}}
                    <textarea
                        x-model="body"
                        @keydown.enter.prevent="!$event.shiftKey && send()"
                        placeholder="Escribe un mensaje... (Enter para enviar)"
                        rows="1"
                        class="flex-1 resize-none rounded-2xl border border-slate-200 focus:ring-2 focus:ring-blue-300 focus:outline-none px-4 py-2.5 text-sm text-slate-700 placeholder-slate-300 max-h-32"
                        style="overflow-y: auto;"
                        @input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'">
                    </textarea>

                    {{-- Botón enviar --}}
                    <button @click="send()"
                        :disabled="sending"
                        :class="sending ? 'opacity-60 cursor-not-allowed' : 'hover:bg-blue-700'"
                        class="bg-blue-600 text-white p-2.5 rounded-xl transition flex-shrink-0">
                        <svg x-show="!sending" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5"/>
                        </svg>
                        <svg x-show="sending" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24" x-cloak>
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-admin-layout>