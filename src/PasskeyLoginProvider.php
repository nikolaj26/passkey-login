<?php

namespace Codeartnj\PasskeyLogin;


use Codeartnj\PasskeyLogin\Pages\PasskeyLogin;
use Codeartnj\PasskeyLogin\Widgets\PasskeyManagerWidget;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PasskeyLoginProvider extends PackageServiceProvider
{
    protected array $pages = [
        PasskeyLogin::class,
    ];

    protected array $widgets = [
        PasskeyManagerWidget::class,
    ];

    public function configurePackage(Package $package): void
    {
        $package
            ->name('passkey-login')
            ->hasConfigFile()
            ->hasViews();
    }
}
