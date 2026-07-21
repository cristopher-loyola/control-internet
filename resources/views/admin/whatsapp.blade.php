<x-app-layout title="WHATSAPP">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight uppercase">
            {{ __('Notificaciones de WhatsApp') }}
        </h2>
    </x-slot>

    <div class="py-6" x-data="{
        connected: @js($estado['connected'] ?? false),
        number: @js($estado['number'] ?? null),
        hasQr: @js($estado['has_qr'] ?? false),
        destinatario: @js($destinatario ?? ''),
        savingDestinatario: false,
        relinking: false,
        qrSrc: '',
        poll: null,
        init() {
            this.refreshQr();
            this.poll = setInterval(() => this.checkStatus(), 4000);
        },
        async checkStatus() {
            const r = await fetch('{{ route('admin.whatsapp.status') }}', { headers: { 'Accept': 'application/json' } });
            const j = await r.json();
            this.connected = !!j.connected;
            this.number = j.number || null;
            this.hasQr = !!j.has_qr;
            if (!this.connected) this.refreshQr();
        },
        refreshQr() {
            this.qrSrc = '{{ route('admin.whatsapp.qr') }}?t=' + Date.now();
        },
        async relink() {
            if (!confirm('Esto cerrará la sesión actual y pedirá vincular un nuevo número por QR. ¿Continuar?')) return;
            this.relinking = true;
            try {
                const r = await fetch('{{ route('admin.whatsapp.relink') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                });
                const j = await r.json();
                if (j.ok) {
                    this.connected = false;
                    this.number = null;
                    setTimeout(() => this.refreshQr(), 1500);
                } else {
                    alert(j.message || 'No se pudo reiniciar la sesión');
                }
            } finally {
                this.relinking = false;
            }
        },
        async guardarDestinatario() {
            this.savingDestinatario = true;
            try {
                const r = await fetch('{{ route('admin.whatsapp.destinatario.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ numero: this.destinatario }),
                });
                const j = await r.json();
                if (j.ok) {
                    alert('Número destinatario guardado.');
                } else {
                    alert(j.message || 'No se pudo guardar');
                }
            } finally {
                this.savingDestinatario = false;
            }
        },
    }">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Estado del emisor -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Número emisor</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    Es el número de WhatsApp vinculado que envía las notificaciones. Requiere escanear un código QR desde el celular con ese número.
                </p>

                <template x-if="connected">
                    <div class="flex items-center gap-3 p-4 bg-green-50 dark:bg-green-900/30 rounded-lg">
                        <span class="inline-block w-3 h-3 rounded-full bg-green-500"></span>
                        <div>
                            <p class="text-sm font-semibold text-green-800 dark:text-green-300">Conectado</p>
                            <p class="text-sm text-green-700 dark:text-green-400" x-text="'Número: ' + number"></p>
                        </div>
                    </div>
                </template>

                <template x-if="!connected">
                    <div class="p-4 bg-yellow-50 dark:bg-yellow-900/30 rounded-lg">
                        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 mb-3">
                            Sin vincular. Escanea el código con WhatsApp → Dispositivos vinculados → Vincular un dispositivo.
                        </p>
                        <img :src="qrSrc" alt="Código QR de WhatsApp" class="w-64 h-64 border rounded-lg bg-white p-2" x-on:error="setTimeout(() => refreshQr(), 2000)">
                    </div>
                </template>

                <button type="button" @click="relink()" :disabled="relinking"
                    class="btn btn-outline-danger btn-sm mt-4">
                    <span x-show="!relinking">Cambiar número (cerrar sesión actual)</span>
                    <span x-show="relinking">Reiniciando...</span>
                </button>
            </div>

            <!-- Destinatario -->
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-1">Número destinatario (Soporte Técnico)</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                    A este número se le avisa "Activar &lt;número de servicio&gt;" cuando un cliente que debía estar cortado por adeudo paga en Chivato, Pozo Hondo o Rosalito.
                </p>

                <div class="flex gap-2 max-w-md">
                    <input type="text" x-model="destinatario" placeholder="Ej. 5214681057656 (52 + 10 dígitos)"
                        class="form-input flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                    <button type="button" @click="guardarDestinatario()" :disabled="savingDestinatario" class="btn btn-primary">
                        <span x-show="!savingDestinatario">Guardar</span>
                        <span x-show="savingDestinatario">Guardando...</span>
                    </button>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
