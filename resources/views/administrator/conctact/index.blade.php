@php
$breadcrumbs = [
    [
        'name' => 'Dashboard',
        'href' => route('administrator.dashboard'),
    ],
    [
        'name' => 'Contáctanos',
    ]
];
@endphp

<x-admin-layout :breadcrumbs="$breadcrumbs">
    @php
        $reopenId = (int) old('reply_for');
        $reopen = $reopenId ? $contactMessages->firstWhere('id', $reopenId) : null;
        $reopenItem = $reopen
            ? ['id' => $reopen->id, 'name' => $reopen->name, 'email' => $reopen->email, 'subject' => $reopen->subject, 'message' => $reopen->message]
            : ['id' => 0, 'name' => '', 'email' => '', 'subject' => '', 'message' => ''];
    @endphp
    <div class="py-10 px-4 sm:px-6" x-data="{ open: @js($reopen !== null), item: @js($reopenItem), sending: false }" @keydown.escape.window="open = false">
        <style>
            .cr-item{padding:1rem 0;display:flex;flex-direction:column;gap:.75rem}
            .cr-main{min-width:0;flex:1;word-break:break-word}
            .cr-acts{display:flex;align-items:center;gap:.5rem;flex-shrink:0;padding-top:.75rem;border-top:1px solid #f1f5f9}
            @media (min-width:640px){.cr-item{flex-direction:row;justify-content:space-between;gap:1.5rem}.cr-acts{padding-top:0;border-top:0}}
            .cr-btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem .9rem;font-size:.875rem;font-weight:600;border:0;border-radius:.75rem;cursor:pointer;transition:background-color .2s}
            .cr-btn-blue{color:#1d4ed8;background:#eff6ff}.cr-btn-blue:hover{background:#dbeafe}
            .cr-btn-red{color:#dc2626;background:#fef2f2}.cr-btn-red:hover{background:#fee2e2}
            .cr-btn-solid{color:#fff;background:#2563eb}.cr-btn-solid:hover{background:#1d4ed8}.cr-btn-solid[disabled]{opacity:.6;cursor:not-allowed}
            .cr-btn-gray{color:#475569;background:#f1f5f9}.cr-btn-gray:hover{background:#e2e8f0}
            .cr-replied{display:inline-block;margin-top:.5rem;padding:.15rem .6rem;font-size:.75rem;font-weight:600;color:#047857;background:#ecfdf5;border-radius:9999px}
            .cr-modal{position:fixed;inset:0;z-index:70;display:flex;align-items:center;justify-content:center;padding:1rem}
            .cr-backdrop{position:absolute;inset:0;background:rgba(15,23,42,.55)}
            .cr-box{position:relative;width:100%;max-width:640px;max-height:92vh;overflow:auto;background:#fff;border-radius:1rem;box-shadow:0 24px 48px -12px rgba(15,23,42,.35);padding:1.25rem}
            .cr-quote{background:#f8fafc;border:1px solid #e2e8f0;border-radius:.75rem;padding:.75rem;font-size:.85rem;color:#475569;white-space:pre-line;max-height:9rem;overflow:auto;word-break:break-word}
            .cr-textarea{width:100%;min-height:9rem;border:1px solid #cbd5e1;border-radius:.75rem;padding:.75rem;font-size:.95rem;resize:vertical;box-sizing:border-box}
            .cr-textarea:focus{outline:2px solid #2563eb;outline-offset:1px}
            .cr-error{margin:.35rem 0 0;font-size:.85rem;color:#dc2626}
            .cr-actions{display:flex;gap:.5rem;justify-content:flex-end;flex-wrap:wrap;margin-top:1rem}
        </style>
        
        <!-- Alertas de Sistema -->
        @if (session('success'))
            <div class="p-4 text-sm text-emerald-800 rounded-xl bg-emerald-50 border border-emerald-200 shadow-sm mb-3">
                <span class="font-semibold">Éxito:</span> {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="p-4 text-sm text-red-800 rounded-xl bg-red-50 border border-red-200 shadow-sm mb-3">
                <span class="font-semibold">Error:</span> {{ session('error') }}
            </div>
        @endif

        <!-- Encabezado Principal -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h2 class="text-3xl font-black text-gray-900 tracking-tight">Mensajes de Contacto</h2>
                <p class="text-sm text-gray-500 mt-1">Mantente al tanto de los mensajes de los usuarios.</p>
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <!-- Contenedor Principal de Alertas -->
                <div class="bg-white shadow-sm rounded-2xl overflow-hidden border border-gray-200/80 p-6">
                    <div class="divide-y divide-gray-100">
                        @forelse ($contactMessages as $contactRecord)
                           <div class="cr-item" data-contacto-item data-contacto-reply>
                               <div class="cr-main">
                                   <div class="font-semibold text-slate-900">{{ $contactRecord->name }}</div>
                                   <div class="text-slate-500 text-xs">{{ $contactRecord->email }}</div>
                                   <div class="font-semibold text-slate-900 mt-2">{{ $contactRecord->subject }}</div>
                                   <p class="text-slate-700 mt-1 whitespace-pre-line">{{ $contactRecord->message }}</p>
                                   <div class="font-normal text-slate-400 text-xs mt-2">
                                       {{ \Carbon\Carbon::parse($contactRecord->created_at)->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY [a las] H:mm A') }}
                                   </div>
                                   @if($contactRecord->replied_at)
                                       <div class="cr-replied">✓ Respondido el {{ $contactRecord->replied_at->locale('es')->isoFormat('D MMM YYYY, H:mm') }}</div>
                                   @endif
                               </div>

                               <!-- Acciones: en móvil van en su propia fila debajo del mensaje; en pantallas grandes, a la derecha -->
                               <div class="cr-acts">
                                   @php
                                       $replyPayload = ['id' => $contactRecord->id, 'name' => $contactRecord->name, 'email' => $contactRecord->email, 'subject' => $contactRecord->subject, 'message' => $contactRecord->message];
                                   @endphp
                                   <button type="button" class="cr-btn cr-btn-blue" @click="item = @js($replyPayload); open = true; sending = false; $nextTick(() => $refs.crText && $refs.crText.focus())">
                                       Responder
                                   </button>
                                   <form action="{{ route('administrator.contact.destroy', $contactRecord->id) }}" method="POST"
                                         onsubmit="return confirm('¿Estás seguro de que deseas eliminar este mensaje?')">
                                       @csrf
                                       @method('DELETE')
                                       <button type="submit" class="cr-btn cr-btn-red" title="Eliminar mensaje">
                                           <svg class="w-5 h-5" style="width:1.25rem;height:1.25rem" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                               <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                           </svg>
                                           Eliminar
                                       </button>
                                   </form>
                               </div>
                           </div>
                        @empty
                            <!-- Estado de bandeja vacía estilizado -->
                            <div class="p-12 text-center bg-white">
                                <div class="inline-flex p-4 bg-gray-50 text-gray-400 rounded-full mb-4">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                                    </svg>
                                </div>
                                <p class="text-base font-medium text-gray-900">Bandeja de entrada limpia</p>
                                <p class="text-sm text-gray-500 mt-1 max-w-sm mx-auto">No tienes ningún mensaje pendiente de revisar en este momento.</p>
                            </div>
                        @endforelse
                    </div>
                </div>        
            </div>
        </div>


        <!-- Paginación con soporte Query String -->
        @if($contactMessages->hasPages())
            <div class="mt-4">
                {{ $contactMessages->links() }}
            </div>
        @endif
        <!-- Popup para responder un mensaje de contacto (se envía al instante desde el correo de soporte) -->
        <div class="cr-modal" x-show="open" style="display:none" x-transition.opacity role="dialog" aria-modal="true" aria-labelledby="cr-title">
            <div class="cr-backdrop" @click="open = false"></div>
            <div class="cr-box">
                <h3 id="cr-title" class="text-lg font-black text-slate-900">Responder mensaje</h3>
                <p class="text-sm text-slate-500 mt-1">
                    Para: <strong class="text-slate-800" x-text="item.name"></strong>
                    &lt;<span x-text="item.email"></span>&gt;
                </p>
                <p class="text-xs text-slate-400 mt-1">Se enviará desde el correo de soporte de OpenDoctor.</p>

                <p class="mt-4 mb-1" style="font-size:.75rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.04em">Mensaje original</p>
                <div class="cr-quote" x-text="item.message"></div>

                <form method="POST" x-bind:action="@js(route('administrator.contact.reply', ['id' => '__ID__'])).replace('__ID__', item.id)" @submit="sending = true">
                    @csrf
                    <input type="hidden" name="reply_for" x-bind:value="item.id">
                    <label for="cr-reply" class="block mt-4 mb-1 text-sm font-semibold text-slate-700">Tu respuesta</label>
                    <textarea id="cr-reply" name="reply" x-ref="crText" class="cr-textarea" maxlength="5000" required placeholder="Escribe aquí tu respuesta…">{{ old('reply') }}</textarea>
                    @error('reply')
                        <p class="cr-error">{{ $message }}</p>
                    @enderror
                    <div class="cr-actions">
                        <button type="button" class="cr-btn cr-btn-gray" @click="open = false">Cancelar</button>
                        <button type="submit" class="cr-btn cr-btn-solid" x-bind:disabled="sending">
                            <span x-show="!sending">Enviar respuesta</span>
                            <span x-show="sending" style="display:none">Enviando…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-admin-layout>
