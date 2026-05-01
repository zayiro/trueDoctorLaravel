<form action="{{ route('reviews.store') }}" method="POST">
    @csrf
    <!-- Datos Polimórficos -->
    <input type="hidden" name="reviewable_id" value="{{ $doctor->id }}">
    <input type="hidden" name="reviewable_type" value="{{ get_class($doctor) }}">

    <!-- Selector de Estrellas -->
    <select name="rating" class="rounded-xl border-gray-300">
        <option value="5">5 Estrellas</option>
        <option value="4">4 Estrellas</option>
        <option value="3">3 Estrellas</option>
        <option value="2">2 Estrellas</option>
        <option value="1">1 Estrella</option>
    </select>

    <textarea name="comment" placeholder="Cuéntanos tu experiencia..." class="w-full mt-2 rounded-xl border-gray-300"></textarea>

    <button type="submit" class="mt-2 bg-blue-600 text-white px-4 py-2 rounded-lg">Publicar Reseña</button>
</form>