<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Helpers\RouteHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Registrar helper para usar en Blade
        \Blade::directive('historyRoute', function ($expression) {
            return "<?php echo \\App\\Helpers\\RouteHelper::historyRoute({$expression}); ?>";
        });

        \Blade::directive('dashboardRoute', function () {
            return "<?php echo \\App\\Helpers\\RouteHelper::dashboardRoute(); ?>";
        });
    }
}
