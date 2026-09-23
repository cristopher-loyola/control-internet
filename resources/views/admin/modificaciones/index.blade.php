<x-app-layout title="Historial de modificaciones">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">Historial de modificaciones de adeudo</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg p-6 text-gray-900 dark:text-gray-100">
                <div class="flex flex-wrap items-end gap-3 mb-4">
                    <a href="{{ route('admin.index') }}" class="btn btn-primary">Volver al dashboard</a>
                    <form method="GET" action="{{ route('admin.dashboard.modificaciones') }}" class="flex flex-wrap items-end gap-3">
                        <div>
                            <label for="mes-modificaciones" class="block text-sm">Mes de modificación</label>
                            <input id="mes-modificaciones" type="month" name="mes" value="{{ $mes }}" required class="form-input mt-1">
                        </div>
                        <button type="submit" class="btn btn-primary">Consultar</button>
                        <button type="submit" name="format" value="excel" class="btn btn-success">Exportar Excel</button>
                    </form>
                </div>
                @error('mes')
                    <p class="text-red-600 mb-4">{{ $message }}</p>
                @enderror
                <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                    {{ ucfirst($mesLabel) }} · {{ $cantidad }} modificaciones.
                    Cambios desde “Modificar total” en Pagos y el botón azul de Clientes. Incluye recibos cancelados.
                    Las filas azules son cambios de Clientes: importes sin recargo y sin cobro.
                </p>
                <div class="overflow-x-auto">
                    @include('admin.modificaciones.tabla')
                </div>
                <div class="mt-4">{{ $paginator->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
