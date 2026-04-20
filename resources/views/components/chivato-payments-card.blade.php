{{-- Pagos de Chivato Card Component --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-md p-5 flex flex-col gap-4">
    <div class="flex items-center justify-between">
        <div>
            <div class="text-xs font-semibold uppercase tracking-widest text-gray-400">Pagos</div>
            <div class="text-lg font-bold text-gray-800 dark:text-white mt-0.5">Pagos de Chivato</div>
        </div>
        <div class="w-9 h-9 rounded-full flex items-center justify-center" style="background:rgba(245,158,11,0.12);">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
            </svg>
        </div>
    </div>
    <div class="flex flex-col gap-4">
        <div class="text-center">
            <div class="text-3xl font-bold text-gray-800 dark:text-white" x-text="money(metrics.chivato_pagos ?? 0)"></div>
            <div class="text-sm text-gray-500 mt-1">Total de pagos</div>
        </div>
        <div class="grid grid-cols-2 gap-4 text-center">
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <div class="text-xl font-bold text-gray-800 dark:text-white" x-text="metrics.chivato_count ?? 0"></div>
                <div class="text-xs text-gray-500">Pagos</div>
            </div>
            <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3">
                <div class="text-xl font-bold text-gray-800 dark:text-white" x-text="money(metrics.chivato_promedio ?? 0)"></div>
                <div class="text-xs text-gray-500">Promedio</div>
            </div>
        </div>
    </div>
</div>
