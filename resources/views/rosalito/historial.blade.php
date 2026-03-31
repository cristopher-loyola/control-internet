<x-app-sidebar>
    <x-slot name="headerTitle">Historial de Pagos - Rosalito</x-slot>

    <div class="min-h-screen bg-gray-100 py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-2">Historial de Pagos</h2>
                        <p class="text-sm text-gray-600">Lista de todos los pagos registrados en el perfil Rosalito</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Total de registros</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $pagos->count() }}</p>
                    </div>
                </div>
            </div>

            <!-- Tabla de Pagos -->
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                            <tr>
                                <th class="px-4 py-3">Folio</th>
                                <th class="px-4 py-3">No. Servicio</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Periodo</th>
                                <th class="px-4 py-3">Total</th>
                                <th class="px-4 py-3">Método</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pagos as $pago)
                                <tr class="bg-white border-b hover:bg-gray-50">
                                    <td class="px-4 py-3 font-medium text-gray-900">
                                        {{ str_pad($pago['reference_number'], 8, '0', STR_PAD_LEFT) }}
                                    </td>
                                    <td class="px-4 py-3">{{ $pago['numero_servicio'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['nombre'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['periodo'] }}</td>
                                    <td class="px-4 py-3">${{ number_format($pago['total'], 2) }}</td>
                                    <td class="px-4 py-3">{{ $pago['metodo'] }}</td>
                                    <td class="px-4 py-3">{{ $pago['fecha_formateada'] }}</td>
                                    <td class="px-4 py-3">
                                        @if($pago['cancelado'])
                                            <span class="px-2 py-1 text-xs rounded-full bg-red-100 text-red-800">
                                                CANCELADO
                                            </span>
                                        @else
                                            <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">
                                                PAGADO
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if(!$pago['cancelado'])
                                            <form id="delete-form-{{ $pago['id'] }}" method="POST" action="{{ route('rosalito.pagos.eliminar', $pago['id']) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" onclick="confirmDelete('delete-form-{{ $pago['id'] }}')" class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-gray-400 text-sm">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                        No se encontraron pagos registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Resumen -->
            @if($pagos->count() > 0)
                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <p class="text-sm text-gray-600">Pagos Exitosos</p>
                        <p class="text-xl font-bold text-green-600">
                            {{ $pagos->where('cancelado', false)->count() }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <p class="text-sm text-gray-600">Pagos Cancelados</p>
                        <p class="text-xl font-bold text-red-600">
                            {{ $pagos->where('cancelado', true)->count() }}
                        </p>
                    </div>
                    <div class="bg-white rounded-lg shadow-sm p-4">
                        <p class="text-sm text-gray-600">Monto Total Pagado</p>
                        <p class="text-xl font-bold text-indigo-600">
                            ${{ number_format($pagos->where('cancelado', false)->sum('total'), 2) }}
                        </p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script>
    function confirmDelete(formId) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'No podrás revertir esta acción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }

    @if(session('success'))
        Swal.fire({
            title: '¡Eliminado!',
            text: '{{ session('success') }}',
            icon: 'success',
            confirmButtonColor: '#3085d6',
            timer: 3000,
            timerProgressBar: true
        });
    @endif

    @if(session('error'))
        Swal.fire({
            title: 'Error',
            text: '{{ session('error') }}',
            icon: 'error',
            confirmButtonColor: '#3085d6'
        });
    @endif
    </script>
</x-app-sidebar>
