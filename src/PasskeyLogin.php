<?php

namespace Codeartnj\PasskeyLogin;


use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class PasskeyLogin extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('passkey-login')
            ->hasConfigFile()
            ->hasViews();
    }
}
