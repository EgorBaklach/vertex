<?php namespace App\Providers;

use Filament\Facades\Filament;
use App\Filament\Widgets\TopNavigation;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Filament::serving(fn () => Filament::registerWidgets([TopNavigation::class]));
    }

    public function register(): void
    {
        FilamentAsset::register([
            Js::make('tributejs', public_path('js/app/tributejs.js')),
            Js::make('custom', public_path('js/app/custom.js')),
            Css::make('custom', public_path('css/app/custom.css'))
        ]);
    }
}
