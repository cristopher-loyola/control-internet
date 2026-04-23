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
