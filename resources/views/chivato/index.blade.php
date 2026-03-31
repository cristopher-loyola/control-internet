<x-app-layout title="Inicio - Chivato">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Panel Chivato
        </h2>
    </x-slot>

    <x-zona-dashboard title="Chivato" :zona="$zona" :stats="$stats" :chart="$chart" :payments="$payments" />
</x-app-layout>
