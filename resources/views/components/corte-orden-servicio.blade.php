@props(['activo' => false])

<div class="w-full flex flex-wrap items-center gap-3">
    <button type="submit" name="orden" value="numero_servicio"
        aria-pressed="{{ $activo ? 'true' : 'false' }}"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
        <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4v16m0 0l-2-2m2 2l2-2M9 4h4M9 10h7M9 16h11" />
        </svg>
        Ordenar por número de servicio
    </button>
    @if($activo)
        <span class="text-sm text-gray-600">Ordenado de menor a mayor</span>
    @endif
</div>
