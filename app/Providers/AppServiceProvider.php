<?php

// Declares the PHP namespace so Laravel autoloads this provider as App\Providers\AppServiceProvider.
namespace App\Providers;

// Imports the SystemSettings helper whose values are shared with the layout.
use App\Support\SystemSettings;
// Imports the View facade so a view composer can be registered.
use Illuminate\Support\Facades\View;
// Imports the base ServiceProvider class that Laravel loads during bootstrap.
use Illuminate\Support\ServiceProvider;

// A service provider is the place Laravel wires up application services at startup.
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    // Runs early, before the app is fully booted; used for binding into the container.
    public function register(): void
    {
        // Intentionally empty: this project has no extra container bindings to add.
        //
    }

    /**
     * Bootstrap any application services.
     */
    // Runs after all providers are registered; used for one-off setup like this.
    public function boot(): void
    {
        // The layout shows the site name and the support details on every page,
        // and hides the registration link when registration is closed.
        View::composer('layouts.app', function ($view) {
            // A view composer runs each time the named view is rendered.
            // Note: this only applies to the "layouts.app" view itself, not its children.
            $view->with([
                // The site name, from the administrator's system settings.
                'siteName' => SystemSettings::name(),
                // The support email address shared with the footer.
                'supportEmail' => SystemSettings::supportEmail(),
                // The support phone (or null) shared with the footer.
                'supportPhone' => SystemSettings::supportPhone(),
                // The support hours (or null) shared with the footer.
                'supportHours' => SystemSettings::supportHours(),
                // Whether new students may register, used to show/hide the sign-up link.
                'registrationOpen' => SystemSettings::registrationEnabled(),
            ]);
        });
    }
}
