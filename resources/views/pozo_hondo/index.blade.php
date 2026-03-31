<x-app-layout title="Inicio - Pozo Hondo">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Panel Pozo Hondo
        </h2>
    </x-slot>

    <x-zona-dashboard title="Pozo Hondo" :zona="$zona" :stats="$stats" :chart="$chart" :payments="$payments" />
</x-app-layout>
