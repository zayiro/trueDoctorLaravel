<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Motivo de la consulta</label>
        <input type="text" name="reason_for_consultation" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Síntomas</label>
        <textarea name="symptoms" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Diagnóstico</label>
        <textarea name="diagnosis" rows="3" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Plan de Tratamiento / Medicación</label>
        <textarea name="treatment_plan" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Escribe aquí las indicaciones o medicamentos..."></textarea>
    </div>
</div>
<input type="hidden" name="appointment_id" value="{{ $appointmentId }}">
