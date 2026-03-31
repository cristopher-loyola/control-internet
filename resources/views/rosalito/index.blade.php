<x-app-layout title="Inicio - Rosalito">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-white leading-tight">
            Panel Rosalito
        </h2>
    </x-slot>

    <x-zona-dashboard title="Rosalito" :zona="$zona" :stats="$stats" :chart="$chart" :payments="$payments" />
</x-app-layout>
