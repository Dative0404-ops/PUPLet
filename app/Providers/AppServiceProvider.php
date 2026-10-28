<?php

namespace App\Providers;

use App\Support\SystemSettings;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

// Wires up application services at startup.
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Empty by design: this project has no extra container bindings.
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Share the site name, support details, and registration flag
        // with the layout on every page.
        View::composer('layouts.app', function ($view) {
            // Runs each time the "layouts.app" view is rendered
            // (not for its child views).
            $view->with([
                // The site name from the administrator's system settings.
                'siteName' => SystemSettings::name(),
                // The support details shared with the footer.
                'supportEmail' => SystemSettings::supportEmail(),
                'supportPhone' => SystemSettings::supportPhone(),
                'supportHours' => SystemSettings::supportHours(),
                // Whether new students may register, used to show/hide the sign-up link.
                'registrationOpen' => SystemSettings::registrationEnabled(),
            ]);
        });
    }
}
