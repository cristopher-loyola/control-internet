<x-app-layout title="Clientes con pagos pendientes">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Clientes con pagos pendientes ({{ $items->total() }})
        </h2>
    </x-slot>

    @php
        $isPagos = request()->is('pagos/*');
        $routePrefix = $isPagos ? 'pagos' : 'admin';
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 rounded shadow p-4">
                <form method="get" class="flex items-end gap-3 mb-3">
                    <div>
                        <label class="block text-xs text-gray-600">Mes</label>
                        <input type="month" name="month" value="{{ $month }}" class="rounded border-gray-300 text-sm" />
                    </div>
                    <button class="btn btn-primary">Filtrar</button>
                    <div class="ml-auto flex gap-2">
                        <a href="{{ route($routePrefix . '.index') }}" class="btn btn-primary">Volver a inicio</a>
                    </div>
                </form>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                        <tr class="text-left text-gray-600">
                            <th class="py-2">#</th>
                            <th class="py-2">Nombre</th>
                            <th class="py-2">Mensualidad</th>
                            <th class="py-2">Recargo</th>
                            <th class="py-2">Pendiente</th>
                            <th class="py-2">Meses</th>
                            <th class="py-2">Desde</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($items as $r)
                            <tr class="border-t border-gray-200">
                                <td class="py-2">
                                    <span class="inline-flex items-center gap-1">
                                        @if(($r['recargo'] ?? 0) > 0)
                                            <span title="Con recargo" class="inline-block w-2.5 h-2.5 rounded-full bg-red-600"></span>
                                        @endif
                                        {{ $r['numero'] }}
                                    </span>
                                </td>
                                <td class="py-2">{{ $r['nombre'] }}</td>
                                <td class="py-2">${{ number_format((float)$r['mensualidad'], 2) }}</td>
                                <td class="py-2">${{ number_format((float)$r['recargo'], 2) }}</td>
                                <td class="py-2 font-semibold">${{ number_format((float)$r['pendiente'], 2) }}</td>
                                <td class="py-2">{{ $r['meses_adeudo'] }}</td>
                                <td class="py-2">{{ $r['desde_periodo'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="py-3 text-gray-500">Sin clientes con pagos pendientes.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $items->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
