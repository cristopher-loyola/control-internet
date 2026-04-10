<x-app-layout title="USUARIOS">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight uppercase">
            {{ __('Usuarios del Sistema') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="{
        baseUrl: @js(url('/admin/usuarios')),
        roles: @js($roles ?? []),
        edit: { id: null, name: '', email: '', role: '', password: '', password_confirmation: '' },
        deleting: { id: null, name: '' },
        openCreate() {
            this.$dispatch('open-modal', 'admin-user-create');
        },
        openEdit(u) {
            this.edit = {
                id: u.id,
                name: u.name ?? '',
                email: u.email ?? '',
                role: u.role ?? '',
                password: '',
                password_confirmation: '',
            };
            this.$dispatch('open-modal', 'admin-user-edit');
        },
        openDelete(u) {
            this.deleting = { id: u.id, name: u.name ?? '' };
            this.$dispatch('open-modal', 'admin-user-delete');
        },
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="alert alert-success mb-4" role="alert">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div class="alert alert-danger mb-4" role="alert">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-danger mb-4" role="alert">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
                    <form action="{{ route('admin.usuarios.index') }}" method="GET" class="flex gap-2">
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Buscar por nombre"
                            class="form-input w-64 rounded-lg border-gray-300 dark:border-gray-600 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <button type="submit" class="btn btn-primary">Buscar</button>
                        @if(request('q'))
                            <a href="{{ route('admin.usuarios.index') }}" class="btn btn-secondary">Limpiar</a>
                        @endif
                    </form>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-sm text-gray-500 dark:text-gray-400">
                            Total: {{ $usuarios->total() }} usuarios
                        </span>
                        <button type="button" class="btn btn-success" @click="openCreate()">Nuevo usuario</button>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Nombre</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Rol</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha de Registro</th>
                                <th class="px-4 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            @forelse ($usuarios as $u)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $u->id }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $u->name }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-300">
                                    {{ $u->email }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if($u->role === 'admin') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                        @elseif($u->role === 'pagos') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                        @elseif($u->role === 'tecnico') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                        @elseif($u->role === 'contrataciones') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200
                                        @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                        @endif">
                                        {{ $u->role_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $u->created_at ? $u->created_at->format('d/m/Y H:i') : '-' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-end">
                                    <div class="d-inline-flex gap-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm"
                                            @click="openEdit(@js(['id' => $u->id, 'name' => $u->name, 'email' => $u->email, 'role' => $u->role]))">
                                            Editar
                                        </button>
                                        <button type="button" class="btn btn-outline-danger btn-sm"
                                            @click="openDelete(@js(['id' => $u->id, 'name' => $u->name]))">
                                            Eliminar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                    No hay usuarios registrados en el sistema.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $usuarios->links() }}
                </div>
            </div>
        </div>
    </div>

    <x-modal name="admin-user-create" focusable>
        <form method="POST" action="{{ route('admin.usuarios.store') }}" class="p-6 space-y-4">
            @csrf

            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Nuevo usuario
            </h2>

            <div>
                <x-input-label for="create_name" value="Nombre" />
                <x-text-input id="create_name" name="name" type="text" class="mt-1 block w-full" required />
            </div>

            <div>
                <x-input-label for="create_email" value="Email" />
                <x-text-input id="create_email" name="email" type="email" class="mt-1 block w-full" required />
            </div>

            <div>
                <x-input-label for="create_role" value="Rol" />
                <select id="create_role" name="role" class="form-select mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Sin rol</option>
                    @foreach (($roles ?? []) as $r)
                        <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="create_password" value="Contraseña" />
                    <x-text-input id="create_password" name="password" type="password" class="mt-1 block w-full" required />
                </div>
                <div>
                    <x-input-label for="create_password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="create_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="btn btn-primary">Guardar</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="admin-user-edit" focusable>
        <form method="POST" x-bind:action="`${baseUrl}/${edit.id}`" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Editar usuario
            </h2>

            <div>
                <x-input-label for="edit_name" value="Nombre" />
                <x-text-input id="edit_name" name="name" type="text" class="mt-1 block w-full" x-model="edit.name" required />
            </div>

            <div>
                <x-input-label for="edit_email" value="Email" />
                <x-text-input id="edit_email" name="email" type="email" class="mt-1 block w-full" x-model="edit.email" required />
            </div>

            <div>
                <x-input-label for="edit_role" value="Rol" />
                <select id="edit_role" name="role" x-model="edit.role" class="form-select mt-1 block w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <option value="">Sin rol</option>
                    @foreach (($roles ?? []) as $r)
                        <option value="{{ $r }}">{{ ucfirst(str_replace('_', ' ', $r)) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="edit_password" value="Nueva contraseña (opcional)" />
                    <x-text-input id="edit_password" name="password" type="password" class="mt-1 block w-full" x-model="edit.password" />
                </div>
                <div>
                    <x-input-label for="edit_password_confirmation" value="Confirmar contraseña" />
                    <x-text-input id="edit_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" x-model="edit.password_confirmation" />
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-primary-button class="btn btn-primary">Guardar cambios</x-primary-button>
            </div>
        </form>
    </x-modal>

    <x-modal name="admin-user-delete" focusable>
        <form method="POST" x-bind:action="`${baseUrl}/${deleting.id}`" class="p-6 space-y-4">
            @csrf
            @method('DELETE')

            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Eliminar usuario
            </h2>

            <p class="text-sm text-gray-700 dark:text-gray-300">
                ¿Seguro que deseas eliminar a <span class="font-semibold" x-text="deleting.name"></span>?
            </p>

            <div class="flex justify-end gap-2">
                <x-secondary-button type="button" x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                <x-danger-button>Eliminar</x-danger-button>
            </div>
        </form>
    </x-modal>

    <style>
        @media (max-width: 640px) {
            .max-w-7xl { padding-left: 0.75rem; padding-right: 0.75rem; }
            .max-w-7xl .p-6 { padding: 0.75rem; }
            /* Buscador y botón */
            .max-w-7xl .flex.flex-col.md\:flex-row { flex-direction: column; align-items: stretch; gap: 0.75rem; }
            .max-w-7xl form.flex.gap-2 { flex-direction: column; }
            .max-w-7xl form.flex.gap-2 .form-input { width: 100%; max-width: none; }
            .max-w-7xl .d-flex.align-items-center { flex-direction: row; justify-content: space-between; }
            .max-w-7xl .btn { padding: 0.5rem 0.75rem; font-size: 0.875rem; }
            /* Tabla */
            .max-w-7xl .overflow-x-auto { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .max-w-7xl table { font-size: 0.8125rem; }
            .max-w-7xl th, .max-w-7xl td { padding: 0.5rem 0.375rem; white-space: nowrap; }
            .max-w-7xl .inline-flex.items-center.px-2\.5 { font-size: 0.625rem; padding: 0.125rem 0.5rem; }
            /* Botones de acción */
            .max-w-7xl .d-inline-flex.gap-2 { display: flex; flex-direction: column; gap: 0.25rem; }
            .max-w-7xl .btn-sm { padding: 0.25rem 0.5rem; font-size: 0.75rem; }
        }
    </style>
</x-app-layout>
