<x-app-layout title="CORTES">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight uppercase">
            {{ __('CORTES - ') }} {{ now()->locale('es')->monthName }} {{ now()->year }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="cortesManager()">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <!-- Buscador y Filtros -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <form action="{{ route('admin.cortes.index') }}" method="GET" class="w-full md:w-1/2 flex gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por ID, nombre o zona..."
                            class="form-input w-full rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                    </form>
                    
                    <div class="flex gap-2">
                        <button @click="openConfigModal = true" class="btn btn-secondary flex items-center gap-2">
                            ⚙️ Configurar Cortadores
                        </button>
                    </div>
                </div>

                <!-- Tabla de Cortes -->
                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID / Cliente</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Zona</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">MAC</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">¿Quién cortó?</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @foreach ($usuarios as $u)
                            <tr class="transition-colors" style="{{ $u->pagado_mes ? 'background-color: #00ff00 !important; color: #000 !important;' : '' }}">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-bold">{{ $u->numero_servicio }}</div>
                                    <div class="text-xs opacity-70">{{ $u->nombre_cliente }}</div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $u->zona ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $u->ip ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">{{ $u->mac ?? '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select @change="updateUser({{ $u->id }}, $event.target.value, 'cortador_id')"
                                        class="form-select text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 w-full"
                                        {{ $u->pagado_mes ? 'disabled' : '' }}>
                                        <option value="">Selecciona...</option>
                                        @foreach ($cortadores as $c)
                                        <option value="{{ $c->id }}" {{ $u->cortador_id == $c->id ? 'selected' : '' }}>{{ $c->nombre }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <select @change="updateUser({{ $u->id }}, $event.target.value, 'estado_corte')"
                                        class="form-select text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 w-full"
                                        {{ $u->pagado_mes ? 'disabled' : '' }}>
                                        <option value="">Selecciona...</option>
                                        <option value="Cortado" {{ $u->estado_corte === 'Cortado' ? 'selected' : '' }}>Cortado</option>
                                        <option value="Offline" {{ $u->estado_corte === 'Offline' ? 'selected' : '' }}>Offline</option>
                                        <option value="Ya cortado" {{ $u->estado_corte === 'Ya cortado' ? 'selected' : '' }}>Ya cortado</option>
                                        <option value="NO_ESTABA" {{ $u->estado_corte === 'NO_ESTABA' ? 'selected' : '' }}>NO_ESTABA</option>
                                    </select>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                <div class="mt-6">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>

        <!-- Modal de Configuración de Cortadores -->
        <div x-show="openConfigModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-6 overflow-hidden">
                <div class="flex justify-between items-center mb-4 border-b pb-2 dark:border-gray-700">
                    <h3 class="text-lg font-bold dark:text-white uppercase tracking-wide">Gestionar Cortadores</h3>
                    <button @click="openConfigModal = false" class="text-gray-500 hover:text-red-500 text-xl">&times;</button>
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-bold uppercase mb-1 dark:text-gray-300">Nuevo Cortador</label>
                    <div class="flex gap-2">
                        <input type="text" x-model="nuevoNombre" placeholder="Nombre..."
                            class="form-input w-full rounded border-gray-300 dark:bg-gray-700 dark:text-white">
                        <button @click="addCortador()" class="btn btn-primary">Añadir</button>
                    </div>
                </div>

                <div class="max-h-60 overflow-y-auto border rounded dark:border-gray-700 p-2">
                    <template x-for="c in cortadores" :key="c.id">
                        <div class="flex justify-between items-center py-2 border-b last:border-0 dark:border-gray-700">
                            <span class="text-sm dark:text-white" x-text="c.nombre"></span>
                            <button @click="removeCortador(c.id)" class="text-red-500 hover:text-red-700 text-sm font-bold uppercase">Eliminar</button>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Toast de Notificación -->
        <div x-show="showToast" x-cloak
            class="fixed bottom-5 right-5 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg z-[100]"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2">
            ✅ Cambios guardados
        </div>
    </div>

    <script>
        function cortesManager() {
            return {
                openConfigModal: false,
                nuevoNombre: '',
                cortadores: @json($cortadores),
                showToast: false,
                
                async updateUser(id, value, field) {
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const body = {};
                        body[field] = value;
                        
                        const r = await fetch(`{{ url('/admin/cortes') }}/${id}/update`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify(body)
                        });
                        
                        if(r.ok) {
                            this.triggerToast();
                        }
                    } catch(e) {
                        console.error(e);
                    }
                },

                async addCortador() {
                    if(!this.nuevoNombre) return;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const r = await fetch("{{ route('admin.cortes.cortadores.store') }}", {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({ nombre: this.nuevoNombre })
                        });
                        const j = await r.json();
                        if(j.ok) {
                            this.cortadores.push(j.cortador);
                            this.nuevoNombre = '';
                            this.triggerToast();
                        }
                    } catch(e) {
                        console.error(e);
                    }
                },

                async removeCortador(id) {
                    if(!confirm('¿Estás seguro de eliminar este cortador?')) return;
                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const r = await fetch(`{{ url('/admin/cortes/cortadores') }}/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': token
                            }
                        });
                        if(r.ok) {
                            this.cortadores = this.cortadores.filter(c => c.id !== id);
                            this.triggerToast();
                        }
                    } catch(e) {
                        console.error(e);
                    }
                },

                triggerToast() {
                    this.showToast = true;
                    setTimeout(() => this.showToast = false, 2000);
                }
            }
        }
    </script>
</x-app-layout>
