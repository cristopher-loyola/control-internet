<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Route;

class RouteHelper
{
    /**
     * Genera la ruta de historial dinámicamente según el perfil del usuario
     */
    public static function historyRoute(string $location): string
    {
        $role = auth()->user()->role ?? 'admin';
        
        // Casos especiales donde la ruta no sigue el patrón estándar
        if (in_array($location, ['rosalito', 'chivato', 'pozo_hondo', 'pozo-hondo'])) {
            $routeName = $location === 'pozo_hondo' || $location === 'pozo-hondo' ? 'pagos.pozo-hondo.history' : "pagos.{$location}.history";
            return route($routeName);
        }
        
        // Para perfiles específicos, usar su propia ruta de historial
        if (in_array($role, ['rosalito', 'chivato', 'pozo_hondo']) && $location === $role) {
            return route("{$role}.historial");
        }
        
        // Para cualquier otro caso, usar la ruta estándar
        return route("{$role}.{$location}.history");
    }

    /**
     * Genera la ruta del dashboard dinámicamente según el perfil del usuario
     */
    public static function dashboardRoute(): string
    {
        $role = auth()->user()->role ?? 'admin';
        return route("{$role}.index");
    }

    /**
     * Verifica si una ruta está disponible para el perfil actual
     */
    public static function hasRoute(string $routeName): bool
    {
        $role = auth()->user()->role ?? 'admin';
        $fullRouteName = "{$role}.{$routeName}";
        
        return Route::has($fullRouteName);
    }
}
