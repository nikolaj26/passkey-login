<?php

namespace Codeartnj\PasskeyLogin;

use Codeartnj\PasskeyLogin\Widgets\PasskeyManagerWidget;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PasskeyLoginProvider extends PackageServiceProvider
{
    public array $widgets = [
        PasskeyManagerWidget::class
    ];

    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        return parent::boot();
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('passkey-login')
            ->hasConfigFile()
            ->hasAssets()
            ->hasViews();
    }

    public function packageBooted()
    {
        FilamentAsset::register([
            Js::make('passkey-login', __DIR__ . '/../dist/passkey-login.js'),
            Css::make('passkey-login', __DIR__.'/../resources/css/passkey-login.css'),
        ], 'codeartnj/passkey-login');
    }
}
