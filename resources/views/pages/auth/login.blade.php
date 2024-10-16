<x-filament-panels::page.simple>
    @vite(['resources/css/app.css'])

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <form id="login-form"
          x-data="authenticatePasskey"
          wire:submit="authenticate"
          class="fi-form grid gap-y-6">

        {{ $this->form }}

        <x-filament::button type="submit" wire:target="" class="w-full">
            Log In
        </x-filament::button>

        <div x-show="showPasskeyField" x-cloak class="flex flex-col items-center space-y-5">
            <div class="w-full flex items-center mb-6">
                <div class="line"></div>
                <div class="px-3">OR</div>
                <div class="line"></div>
            </div>

            <x-filament::button
                class="w-full"
                wire:click="authenticateOptions"
                icon="heroicon-m-key"
                color="gray"
                icon-position="after"
            >
                Log In with Passkey
            </x-filament::button>
        </div>
    </form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}

    @vite(['resources/js/app.js'])
</x-filament-panels::page.simple>
