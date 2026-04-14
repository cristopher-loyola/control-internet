<x-app-sidebar title="Inicio - Rosalito" header-title="Panel Rosalito">
    <x-zona-dashboard title="Rosalito" :zona="$zona" :stats="$stats" :chart="$chart" :payments="$payments" zonaRoute="rosalito" :corteActivo="$corteActivo" />
</x-app-sidebar>
