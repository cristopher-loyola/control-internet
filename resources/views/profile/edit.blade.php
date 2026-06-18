@php
    $role = auth()->user()->role;
    $layout = match($role) {
        'tecnico' => 'app-tecnico-layout',
        'pagos', 'admin' => 'app-layout',
        default => 'app-sidebar',
    };
@endphp
<x-dynamic-component :component="$layout">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Perfil
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            @if(auth()->user()->role === 'admin')
            <div id="cobradores" class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg"
                 x-data="cobradoresAdmin()" x-init="cargar()">

                {{-- Header --}}
                <div class="flex items-center gap-2 mb-4">
                    <h2 class="text-base font-bold text-gray-900 dark:text-white">Gestionar Cobradores</h2>
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-300"
                          x-text="cobradores.length"></span>
                    <p class="text-xs text-gray-400 dark:text-gray-500 ml-1 hidden sm:block">Solo los activos aparecen en pagos</p>
                </div>

                {{-- Lista compacta --}}
                <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden mb-3">
                    <template x-for="(c, idx) in cobradores" :key="c.id">
                        <div class="flex items-center gap-2.5 px-3 py-2 border-b border-gray-100 dark:border-gray-700/60 last:border-0 transition-colors"
                             :class="c.activo ? 'bg-white dark:bg-gray-800' : 'bg-gray-50/80 dark:bg-gray-900/40'">

                            {{-- Avatar --}}
                            <div class="relative flex-shrink-0">
                                <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold text-white"
                                      :style="'background:' + colorFor(c.nombre)">
                                    <span x-text="c.nombre.charAt(0).toUpperCase()"></span>
                                </span>
                                <span class="absolute -bottom-px -right-px w-2 h-2 rounded-full border border-white dark:border-gray-800"
                                      :class="c.activo ? 'bg-green-400' : 'bg-gray-300'"></span>
                            </div>

                            {{-- Nombre --}}
                            <span class="flex-1 text-sm font-medium truncate"
                                  :class="c.activo ? 'text-gray-800 dark:text-gray-100' : 'text-gray-400 dark:text-gray-500'"
                                  x-text="c.nombre"></span>

                            {{-- Toggle --}}
                            <button type="button" @click="toggleActivo(c)"
                                class="flex-shrink-0 relative inline-flex h-4 w-7 items-center rounded-full transition-colors focus:outline-none"
                                :style="c.activo ? 'background:#6366f1' : 'background:#d1d5db'">
                                <span class="inline-block h-3 w-3 rounded-full shadow transition-all"
                                      :style="c.activo ? 'transform:translateX(14px);background:#fff' : 'transform:translateX(2px);background:#6b7280'"></span>
                            </button>

                            {{-- Eliminar --}}
                            <button type="button" @click="pedirEliminar(c)"
                                class="flex-shrink-0 p-1 rounded text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </template>

                    <template x-if="cobradores.length === 0">
                        <div class="py-5 text-center text-xs text-gray-400">Sin cobradores registrados</div>
                    </template>
                </div>

                {{-- Agregar --}}
                <form @submit.prevent="agregar" class="flex gap-2">
                    <input type="text" x-model="nuevoNombre" placeholder="Nombre del nuevo cobrador"
                        class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm shadow-sm focus:border-indigo-400 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                        maxlength="60">
                    <button type="submit"
                        class="px-3 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 text-white text-xs font-semibold rounded-lg transition flex items-center gap-1"
                        :disabled="!nuevoNombre.trim()">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                        </svg>
                        Agregar
                    </button>
                </form>
                <p x-show="error" x-text="error" class="mt-1.5 text-xs text-red-500"></p>

                {{-- Modal de confirmación --}}
                <div x-show="confirmarEliminar" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm"
                     @click.self="confirmarEliminar = false">
                    <div x-show="confirmarEliminar" x-cloak
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                         x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                         class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-80 overflow-hidden">
                        <div class="p-5">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-9 h-9 rounded-full bg-red-100 dark:bg-red-900/30 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4.5 h-4.5 text-red-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:1.125rem;height:1.125rem">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm text-gray-900 dark:text-white">Eliminar cobrador</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">Esta acción no se puede deshacer</p>
                                </div>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                                ¿Eliminar a <span class="font-semibold text-gray-900 dark:text-white" x-text="cobPendiente?.nombre"></span>?
                            </p>
                            <div class="flex gap-2">
                                <button type="button" @click="confirmarEliminar = false"
                                    class="flex-1 px-3 py-2 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 transition">
                                    Cancelar
                                </button>
                                <button type="button" @click="eliminar()"
                                    class="flex-1 px-3 py-2 rounded-lg text-sm font-semibold text-white transition"
                                    style="background:#ef4444"
                                    @mouseover="$el.style.background='#dc2626'"
                                    @mouseleave="$el.style.background='#ef4444'">
                                    Sí, eliminar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
            @endif

            <div class="p-4 sm:p-8 bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>

@if(auth()->user()->role === 'admin')
<script>
function cobradoresAdmin() {
    const COLORS = ['#6366f1','#ec4899','#3b82f6','#8b5cf6','#10b981','#f59e0b','#ef4444','#14b8a6'];
    const colorMap = {};
    let colorIdx = 0;
    return {
        cobradores: @json($cobradores ?? []),
        nuevoNombre: '',
        error: '',
        confirmarEliminar: false,
        cobPendiente: null,
        colorFor(nombre) {
            if (!colorMap[nombre]) colorMap[nombre] = COLORS[colorIdx++ % COLORS.length];
            return colorMap[nombre];
        },
        cargar() {},
        async agregar() {
            this.error = '';
            const nombre = this.nuevoNombre.trim();
            if (!nombre) return;
            const r = await fetch('{{ route('admin.cobradores.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                body: JSON.stringify({ nombre })
            });
            const j = await r.json();
            if (!r.ok) { this.error = j.errors?.nombre?.[0] || 'Error al agregar.'; return; }
            this.cobradores.push(j);
            this.nuevoNombre = '';
        },
        async toggleActivo(c) {
            const r = await fetch(`/admin/cobradores/${c.id}/toggle`, {
                method: 'PATCH',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            const j = await r.json();
            Object.assign(c, j);
        },
        pedirEliminar(c) {
            this.cobPendiente = c;
            this.confirmarEliminar = true;
        },
        async eliminar() {
            const c = this.cobPendiente;
            if (!c) return;
            this.confirmarEliminar = false;
            await fetch(`/admin/cobradores/${c.id}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
            });
            this.cobradores = this.cobradores.filter(x => x.id !== c.id);
            this.cobPendiente = null;
        }
    };
}
</script>
@endif
