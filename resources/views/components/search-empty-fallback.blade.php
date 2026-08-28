<!-- resources/views/components/search-empty-fallback.blade.php -->

<!-- Sección: Por qué elegir OpenDoctor -->
<section class="py-16 px-4 bg-gradient-to-br from-slate-50 to-slate-100">
  <div class="max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-gray-900 mb-4">Por qué elegir OpenDoctorOnline</h2>
    <p class="text-gray-600 mb-12 text-lg">Mientras configuramos tu especialista favorito, conoce nuestros servicios disponibles ahora.</p>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Tarjeta 1: Medicina General -->
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm hover:shadow-md transition">
        <div class="mb-4">
          <svg class="w-12 h-12 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Medicina General</h3>
        <p class="text-gray-600 mb-4">Consulta inmediata con médicos generales disponibles. Evaluación inicial, asesoramiento y derivación a especialistas si es necesario.</p>
        <a href="{{ url('/search') }}?specialty=medicina-general" class="text-blue-600 font-semibold hover:text-blue-700 inline-flex items-center gap-2">
          Ver disponibilidad
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Tarjeta 2: Análisis IA -->
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm hover:shadow-md transition">
        <div class="mb-4">
          <svg class="w-12 h-12 text-purple-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="1"></circle>
            <path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m2.12-2.12l4.24-4.24"></path>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Análisis Médico con IA</h3>
        <p class="text-gray-600 mb-4">Carga fotos, síntomas o documentos médicos. Nuestro análisis impulsado por IA te proporciona un primer diagnóstico y recomendaciones.</p>
        <a href="/medical-analysis" class="text-purple-600 font-semibold hover:text-purple-700 inline-flex items-center gap-2">
          Probar análisis gratis
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>

      <!-- Tarjeta 3: Reservas Virtuales -->
      <div class="bg-white rounded-xl p-8 border border-gray-200 shadow-sm hover:shadow-md transition">
        <div class="mb-4">
          <svg class="w-12 h-12 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
            <line x1="16" y1="2" x2="16" y2="6"></line>
            <line x1="8" y1="2" x2="8" y2="6"></line>
            <line x1="3" y1="10" x2="21" y2="10"></line>
          </svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-3">Reservas Virtuales</h3>
        <p class="text-gray-600 mb-4">Elige hora, especialista y plataforma. Telemedicina segura, flexible y sin desplazamientos. Paga solo cuando confirmes.</p>
        <a href="{{ url('/search') }}?specialty=medicina-general" class="text-green-600 font-semibold hover:text-green-700 inline-flex items-center gap-2">
          Agendar cita
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
          </svg>
        </a>
      </div>
    </div>
  </div>
</section>

<!-- Sección: Cómo funciona -->
<section class="py-16 px-4 bg-white">
  <div class="max-w-5xl mx-auto">
    <h2 class="text-3xl font-bold text-gray-900 mb-12 text-center">Cómo funciona OpenDoctorOnline</h2>
    
    <div class="space-y-8">
      <!-- Paso 1 -->
      <div class="flex gap-6 items-start">
        <div class="flex-shrink-0">
          <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white font-bold">1</div>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Describe tus síntomas o necesidad</h3>
          <p class="text-gray-600">Cuéntanos qué especialidad necesitas, síntomas, o carga documentos médicos. Nuestro sistema te mostrará opciones disponibles o te ofrecerá medicina general inmediata.</p>
        </div>
      </div>

      <!-- Paso 2 -->
      <div class="flex gap-6 items-start">
        <div class="flex-shrink-0">
          <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white font-bold">2</div>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Elige médico, hora y plataforma</h3>
          <p class="text-gray-600">Selecciona entre doctores disponibles, elige la hora que se ajuste a tu agenda y decide si prefieres telemedicina o cita presencial.</p>
        </div>
      </div>

      <!-- Paso 3 -->
      <div class="flex gap-6 items-start">
        <div class="flex-shrink-0">
          <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white font-bold">3</div>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Paga de forma segura</h3>
          <p class="text-gray-600">Realiza el pago con tarjeta de crédito, débito o billetera digital (NEQUI, Davivienda). Tu cita se confirma instantáneamente. Solo pagas si completas la reserva.</p>
        </div>
      </div>

      <!-- Paso 4 -->
      <div class="flex gap-6 items-start">
        <div class="flex-shrink-0">
          <div class="flex items-center justify-center h-12 w-12 rounded-md bg-blue-600 text-white font-bold">4</div>
        </div>
        <div>
          <h3 class="text-xl font-bold text-gray-900 mb-2">Consulta y seguimiento</h3>
          <p class="text-gray-600">Accede a tu cita por videoconferencia segura o presencial. Recibe tu diagnóstico, recetas digitales y seguimiento en tu perfil.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Sección: Exámenes Médicos con IA -->
<section class="py-16 px-4 bg-slate-50">
  <div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-2xl p-8 md:p-12 border border-gray-200 shadow-lg">
      <div class="grid md:grid-cols-2 gap-8 items-center">
        <div>
          <h2 class="text-3xl font-bold text-gray-900 mb-6">Análisis de Exámenes Médicos con IA</h2>
          <p class="text-gray-600 mb-6">Sube tus exámenes médicos (análisis de sangre, radiografías, resonancias) y nuestro sistema de inteligencia artificial te proporciona:</p>
          
          <ul class="space-y-4">
            <li class="flex gap-3 items-start">
              <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700"><strong>Resumen ejecutivo:</strong> Valores normales, anormales y rango de referencia</span>
            </li>
            <li class="flex gap-3 items-start">
              <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700"><strong>Análisis de hallazgos:</strong> Lo que significan tus resultados</span>
            </li>
            <li class="flex gap-3 items-start">
              <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700"><strong>Recomendaciones personalizadas:</strong> Próximos pasos y especialistas</span>
            </li>
            <li class="flex gap-3 items-start">
              <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
              </svg>
              <span class="text-gray-700"><strong>Plantilla descargable:</strong> PDF listo para consultas médicas</span>
            </li>
          </ul>

          <div class="mt-8">
            <a href="/medical-analysis" class="inline-block w-full md:w-auto text-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-lg transition shadow-lg">
              Analizar mis exámenes ahora
            </a>
          </div>
        </div>

        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-8 border border-blue-200">
          <div class="space-y-6">
            <div class="bg-white rounded-lg p-4 border border-blue-200">
              <p class="text-sm text-gray-600 mb-2">Ejemplo: Hemograma completo</p>
              <div class="h-32 bg-gradient-to-br from-emerald-100 to-blue-100 rounded flex items-center justify-center">
                <svg class="w-16 h-16 text-blue-600 opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M9 11l3 3L22 4"></path>
                  <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"></path>
                </svg>
              </div>
            </div>
            <div class="text-center">
              <p class="text-sm text-gray-600">Soporta JPG, PNG, PDF</p>
              <p class="text-xs text-gray-500 mt-1">Máximo 10 MB por archivo</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Sección: FAQ -->
<!-- Sección: FAQ - CON CSS ARREGLADO -->
<section class="w-full py-16 px-4 bg-white">
  <div class="max-w-5xl mx-auto">
    <h2 class="text-5xl font-bold text-gray-900 mb-12 text-center">Preguntas Frecuentes</h2>
    
    <div class="space-y-3" style="display: block; width: 100%;">
      <!-- FAQ Item 1 -->
      <details class="block w-full border border-gray-200 rounded-lg hover:bg-gray-50 transition" style="width: 100%;">
        <summary class="flex justify-between items-center font-bold text-gray-900 select-none cursor-pointer px-6 py-4 hover:text-blue-600" style="width: 100%; display: flex;">
          <span style="flex: 1;">¿Cómo hago una reserva en OpenDoctorOnline?</span>
          <svg class="w-5 h-5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </summary>
        <div class="px-6 pb-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600">Selecciona la especialidad o síntoma, elige un médico disponible, escoge la hora que mejor te convenga y realiza el pago. Recibirás un correo de confirmación con los detalles de acceso.</p>
        </div>
      </details>

      <!-- FAQ Item 2 -->
      <details class="block w-full border border-gray-200 rounded-lg hover:bg-gray-50 transition" style="width: 100%;">
        <summary class="flex justify-between items-center font-bold text-gray-900 select-none cursor-pointer px-6 py-4 hover:text-blue-600" style="width: 100%; display: flex;">
          <span style="flex: 1;">¿Cuáles son los métodos de pago?</span>
          <svg class="w-5 h-5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </summary>
        <div class="px-6 pb-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600">Aceptamos tarjetas de crédito y débito (Visa, Mastercard, American Express), billeteras digitales y transferencias bancarias. Todos los pagos son procesados de forma segura.</p>
        </div>
      </details>

      <!-- FAQ Item 3 -->
      <details class="block w-full border border-gray-200 rounded-lg hover:bg-gray-50 transition" style="width: 100%;">
        <summary class="flex justify-between items-center font-bold text-gray-900 select-none cursor-pointer px-6 py-4 hover:text-blue-600" style="width: 100%; display: flex;">
          <span style="flex: 1;">¿Qué tan confiable es el análisis de IA?</span>
          <svg class="w-5 h-5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </summary>
        <div class="px-6 pb-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600">Nuestro análisis con IA es una herramienta de apoyo educativo y no reemplaza el diagnóstico médico profesional. Siempre recomendamos consultar con un especialista para confirmación y tratamiento.</p>
        </div>
      </details>

      <!-- FAQ Item 4 -->
      <details class="block w-full border border-gray-200 rounded-lg hover:bg-gray-50 transition" style="width: 100%;">
        <summary class="flex justify-between items-center font-bold text-gray-900 select-none cursor-pointer px-6 py-4 hover:text-blue-600" style="width: 100%; display: flex;">
          <span style="flex: 1;">¿Puedo cancelar mi cita?</span>
          <svg class="w-5 h-5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </summary>
        <div class="px-6 pb-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600">Sí. Las cancelaciones realizadas con 24 horas de anticipación reciben reembolso completo. Cancelaciones con menos de 24 horas se retienen como crédito en tu cuenta.</p>
        </div>
      </details>

      <!-- FAQ Item 5 -->
      <details class="block w-full border border-gray-200 rounded-lg hover:bg-gray-50 transition" style="width: 100%;">
        <summary class="flex justify-between items-center font-bold text-gray-900 select-none cursor-pointer px-6 py-4 hover:text-blue-600" style="width: 100%; display: flex;">
          <span style="flex: 1;">¿La consulta es confidencial?</span>
          <svg class="w-5 h-5 text-gray-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
          </svg>
        </summary>
        <div class="px-6 pb-4 pt-4 border-t border-gray-200">
          <p class="text-gray-600">Absolutamente. Cumplimos con normativas de privacidad y protección de datos (GDPR, HIPAA compatible). Todas las consultas son encriptadas y solo accesibles por ti y tu médico tratante.</p>
        </div>
      </details>
    </div>
  </div>
</section>
