<?php

namespace Codeartnj\PasskeyLogin;


use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PasskeyLoginProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('passkey-login')
            ->hasConfigFile()
            ->hasViews();
    }

    public function packageBooted()
    {
        FilamentAsset::register([
            Js::make('passkey-login', __DIR__.'/../dist/passkey-login.js'),
        ], 'codeartnj/passkey-login');
    }
}
