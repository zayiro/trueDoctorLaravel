@php
    $t = fn (string $key, array $r = []) => \App\Services\AiChat\AiChatText::t($key, $r, $locale);
    $money = fn (int $v) => '$' . number_format($v, 0, ',', '.');
    $input = 'w-full rounded-xl border-gray-200 focus:ring-blue-500';
    $startMode = old('has_account') === 'yes' ? 'exist' : 'new';
@endphp

<x-guest-layout>
    <div class="max-w-3xl mx-auto py-12 px-4 mt-6">

        <div class="flex justify-end gap-2 text-xs font-bold mb-4">
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'es']) }}" class="px-3 py-1 rounded-full {{ $locale === 'es' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $t('lang_es') }}</a>
            <a href="{{ request()->fullUrlWithQuery(['lang' => 'en']) }}" class="px-3 py-1 rounded-full {{ $locale === 'en' ? 'bg-blue-600 text-white' : 'bg-white text-slate-600 border border-slate-200' }}">{{ $t('lang_en') }}</a>
        </div>

        @if (session('error'))
            <div class="flex items-center p-4 mb-4 text-red-800 rounded-2xl bg-red-50 border border-red-100 shadow-sm" role="alert">
                <div class="text-sm font-bold">{{ session('error') }}</div>
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 mb-4 text-amber-800 rounded-2xl bg-amber-50 border border-amber-100 shadow-sm" role="alert">
                <span class="text-sm font-black uppercase tracking-wide">{{ $t('errors_title') }}</span>
                <ul class="mt-1.5 list-disc list-inside text-xs font-medium">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-xl p-8 border border-gray-100">
            <h1 class="text-2xl font-black text-gray-800">{{ $t('patient_title') }}</h1>
            <p class="mt-1 text-sm font-semibold text-blue-700">{{ $t('patient_summary', ['min' => $duration, 'total' => $money($quote['total'])]) }}</p>

            <form action="{{ route('ai-chat.process') }}" method="POST"
                  x-data="{ loading: false, mode: '{{ $startMode }}' }" x-on:submit="loading = true" class="mt-6 space-y-5">
                @csrf

                @guest
                    <div class="flex p-1 bg-gray-100 rounded-2xl">
                        <button type="button" @click="mode = 'new'" :class="mode === 'new' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'" class="flex-1 py-2 text-sm font-bold rounded-xl transition">{{ $t('tab_new') }}</button>
                        <button type="button" @click="mode = 'exist'" :class="mode === 'exist' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500'" class="flex-1 py-2 text-sm font-bold rounded-xl transition">{{ $t('tab_exist') }}</button>
                    </div>

                    <input type="hidden" name="has_account" :value="mode === 'exist' ? 'yes' : 'no'">

                    {{-- Registro --}}
                    <div x-show="mode === 'new'" class="space-y-5">
                        <div>
                            <label for="name" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_name') }}</label>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" autocomplete="name" :required="mode === 'new'" class="{{ $input }}">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="email" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_email') }}</label>
                                <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email" placeholder="correo@ejemplo.com" :required="mode === 'new'" class="{{ $input }}">
                            </div>
                            <div>
                                <label for="identification" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_id') }}</label>
                                <input type="text" name="identification" id="identification" value="{{ old('identification') }}" autocomplete="off" maxlength="30" :required="mode === 'new'"
                                       oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')" class="{{ $input }} uppercase">
                            </div>
                        </div>
                        <p class="-mt-3 text-[11px] font-medium text-gray-400 px-1 leading-relaxed">{{ $t('f_id_hint') }} {{ $t('f_id_why') }}</p>

                        <div>
                            <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_phone') }}</label>
                            <div class="flex mt-1 rounded-xl shadow-sm border border-slate-200 bg-white focus-within:ring-2 focus-within:ring-blue-500 overflow-hidden">
                                <select name="country_code" id="country_code" :required="mode === 'new'" class="bg-slate-50 text-slate-700 text-sm border-0 border-r border-slate-200 rounded-l-xl focus:ring-0 px-5 cursor-pointer">
                                    @foreach ($countryCodes as $code => $label)
                                        <option value="{{ $code }}" @selected(old('country_code', '+57') === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" maxlength="12" inputmode="numeric" placeholder="3026433874" :required="mode === 'new'"
                                       oninput="this.value = this.value.replace(/[^0-9]/g, '')" class="block w-full border-0 focus:ring-0 p-2.5 text-sm">
                            </div>
                            <p class="text-[11px] font-medium text-gray-400 mt-1.5 px-1 leading-relaxed">{{ $t('f_phone_hint') }}</p>
                        </div>
                    </div>

                    {{-- Ya tengo cuenta --}}
                    <div x-show="mode === 'exist'" x-cloak class="space-y-5">
                        <div>
                            <label for="login_email" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_login_email') }}</label>
                            <input type="email" name="login_email" id="login_email" value="{{ old('login_email') }}" autocomplete="email" :required="mode === 'exist'" class="{{ $input }}">
                        </div>
                        <div>
                            <label for="login_password" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_login_pass') }}</label>
                            <input type="password" name="login_password" id="login_password" autocomplete="current-password" :required="mode === 'exist'" class="{{ $input }}">
                        </div>
                    </div>
                @endguest

                @auth
                    <div class="bg-blue-50 p-4 rounded-2xl flex items-center gap-3 border border-blue-100">
                        <div class="bg-blue-600 text-white p-2 rounded-full font-bold w-10 h-10 flex items-center justify-center shadow-sm">{{ mb_substr($user->name, 0, 1) }}</div>
                        <p class="text-sm text-blue-800 font-bold">{{ $t('logged_as', ['name' => ucfirst($user->name)]) }}</p>
                    </div>

                    @if ($needsId)
                        <div>
                            <label for="identification" class="block text-sm font-bold text-gray-700 mb-1">{{ $t('f_id') }}</label>
                            <input type="text" name="identification" id="identification" value="{{ old('identification') }}" autocomplete="off" maxlength="30" required
                                   oninput="this.value = this.value.replace(/[^A-Za-z0-9]/g, '')" class="{{ $input }} uppercase">
                            <p class="mt-1 text-[11px] font-medium text-gray-400 px-1 leading-relaxed">{{ $t('f_id_hint') }} {{ $t('f_id_why') }}</p>
                        </div>
                    @endif
                @endauth

                {{-- Términos y consentimientos --}}
                <div class="space-y-3 border-t border-gray-100 pt-5">
                    <label class="flex gap-3 text-sm text-slate-700 leading-relaxed">
                        <input type="checkbox" name="terms" value="1" required class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('terms'))>
                        <span>{{ $t('c_terms', ['days' => $days]) }} <a href="{{ route('ai-chat.terms') }}" target="_blank" rel="noopener" class="font-semibold text-blue-600 underline hover:text-blue-700">{{ $t('c_terms_link') }}</a></span>
                    </label>
                    <label class="flex gap-3 text-sm text-slate-700 leading-relaxed">
                        <input type="checkbox" name="consent_profile" value="1" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('consent_profile'))>
                        <span>{{ $t('c_profile') }} <span class="text-slate-400">{{ $t('c_optional') }}</span></span>
                    </label>
                    <label class="flex gap-3 text-sm text-slate-700 leading-relaxed">
                        <input type="checkbox" name="email_copy" value="1" class="mt-1 rounded border-gray-300 text-blue-600 focus:ring-blue-500" @checked(old('email_copy'))>
                        <span>{{ $t('c_copy') }} <span class="text-slate-400">{{ $t('c_optional') }}</span></span>
                    </label>
                    <p class="rounded-xl bg-sky-50 border border-sky-100 px-3 py-2 text-xs leading-relaxed text-sky-800">{{ \App\Services\AiChat\AiChatReportText::retention($locale) }}</p>
                </div>

                <button type="submit" :disabled="loading"
                        class="w-full rounded-xl bg-blue-600 px-4 py-3.5 font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-60">
                    {{ $t('continue_pay') }}
                </button>

                <p class="text-center text-xs leading-relaxed text-slate-500">{{ $t('disclaimer') }}</p>
            </form>
        </div>
    </div>
</x-guest-layout>
