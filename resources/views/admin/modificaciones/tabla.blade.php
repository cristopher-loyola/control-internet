<table class="w-full text-sm divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            @foreach(['Folio', 'Fecha', 'Número', 'Cliente', 'Total anterior', 'Total modificado', 'Motivo', 'Modificado por', 'Quién cobró'] as $titulo)
                <th class="px-3 py-2 text-left whitespace-nowrap">{{ $titulo }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($rows as $r)
            <tr class="{{ $r->estado === 'Cancelado' ? 'bg-rose-50 dark:bg-rose-900/20' : '' }}">
                <td class="px-3 py-2 whitespace-nowrap texto">{{ $r->folio }}</td>
                <td class="px-3 py-2 whitespace-nowrap texto">{{ $r->fecha }}</td>
                <td class="px-3 py-2 texto">{{ $r->numero }}</td>
                <td class="px-3 py-2 texto">{{ $r->cliente }}</td>
                <td class="px-3 py-2 whitespace-nowrap">{{ $r->total_anterior !== null ? '$'.number_format((float)$r->total_anterior, 2) : 'No registrado' }}</td>
                <td class="px-3 py-2 whitespace-nowrap">${{ number_format((float)$r->total, 2) }}</td>
                <td class="px-3 py-2 texto">{{ $r->motivo }}</td>
                <td class="px-3 py-2 texto">{{ $r->usuario }}</td>
                <td class="px-3 py-2 texto">{{ $r->cobro }}</td>
            </tr>
        @empty
            <tr><td colspan="9" class="px-3 py-6 text-center text-gray-500">No hay modificaciones registradas en este mes.</td></tr>
        @endforelse
    </tbody>
</table>
