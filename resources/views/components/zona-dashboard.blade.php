@props([
    'title',
    'zona',
    'stats' => [],
    'chart' => ['labels' => [], 'values' => []],
    'payments' => [],
])

<div class="min-h-[calc(100vh-4rem)] bg-black text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="text-sm text-gray-400">Zona</div>
                <div class="text-2xl font-semibold tracking-tight">{{ $title }}</div>
            </div>
            <div class="flex flex-col sm:flex-row gap-2">
                <form method="GET" class="flex items-center gap-2 w-full sm:w-auto">
                    <input name="q" value="{{ request('q') }}" placeholder="Buscar cliente"
                           class="w-full sm:w-72 rounded-lg bg-zinc-900 border border-zinc-700 px-3 py-2 text-sm text-white placeholder:text-zinc-500 focus:outline-none focus:ring-2 focus:ring-zinc-500">
                    <button class="px-4 py-2 rounded-lg bg-zinc-800 hover:bg-zinc-700 text-sm font-medium">Buscar</button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mt-6">
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="text-xs text-zinc-400">Clientes</div>
                <div class="text-2xl font-semibold mt-1">{{ (int) ($stats['total'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="text-xs text-zinc-400">Activos</div>
                <div class="text-2xl font-semibold mt-1">{{ (int) ($stats['activos'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="text-xs text-zinc-400">Pendientes</div>
                <div class="text-2xl font-semibold mt-1">{{ (int) ($stats['pendientes'] ?? 0) }}</div>
            </div>
            <div class="rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="text-xs text-zinc-400">Desactivados</div>
                <div class="text-2xl font-semibold mt-1">{{ (int) ($stats['desactivados'] ?? 0) }}</div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-4 mt-6">
            <div class="lg:col-span-2 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold">Clientes nuevos (7 días)</div>
                </div>
                <div class="h-52">
                    <canvas id="zonaClientesChart"></canvas>
                </div>
            </div>
            <div class="lg:col-span-3 rounded-xl border border-zinc-800 bg-zinc-950 p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="text-sm font-semibold">Últimos pagos</div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-zinc-400 border-b border-zinc-800">
                                <th class="py-2 font-medium">Folio</th>
                                <th class="py-2 font-medium">Número</th>
                                <th class="py-2 font-medium">Cliente</th>
                                <th class="py-2 font-medium text-right">Total</th>
                                <th class="py-2 font-medium">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-900">
                            @forelse($payments as $p)
                                <tr class="hover:bg-zinc-900/60">
                                    <td class="py-2">{{ $p['folio'] ?? '—' }}</td>
                                    <td class="py-2 font-semibold">{{ $p['numero'] ?? '—' }}</td>
                                    <td class="py-2">{{ $p['nombre'] ?? '—' }}</td>
                                    <td class="py-2 text-right">${{ number_format((float) ($p['total'] ?? 0), 2) }}</td>
                                    <td class="py-2 text-zinc-400">{{ $p['fecha'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-10 text-center text-zinc-500 italic">Sin pagos recientes</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        (() => {
            const el = document.getElementById('zonaClientesChart');
            if(!el) return;
            const labels = @json($chart['labels'] ?? []);
            const values = @json($chart['values'] ?? []);
            new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Nuevos',
                        data: values,
                        backgroundColor: '#e11d48',
                        borderRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { enabled: true }
                    },
                    scales: {
                        x: { ticks: { color: '#a1a1aa' }, grid: { color: 'rgba(63,63,70,0.4)' } },
                        y: { ticks: { color: '#a1a1aa', precision: 0 }, grid: { color: 'rgba(63,63,70,0.4)' }, beginAtZero: true }
                    }
                }
            });
        })();
    </script>
</div>

