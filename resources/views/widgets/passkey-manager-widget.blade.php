<x-filament-widgets::widget>
    <x-filament::section>
        <h2 class="font-bold text-lg mb-4">Passkeys</h2>
        <form x-data="registerPasskey"
              wire:submit="registerOptions"
              name="createPasskey"
              class="space-x-4 flex items-end mb-6">

            <div class="w-full" x-show="showPasskeyForm">
                {{ $this->passkeyForm }}
            </div>

            <div class="flex-0" x-show="showPasskeyForm">
                <x-filament::button class="text-nowrap" type="submit">
                    Add Passkey
                </x-filament::button>
            </div>

            <div class="my-6 p-0 text-center w-full" x-show="!showPasskeyForm">
                This browser does not support Passkeys
            </div>
        </form>


        {{ $this->table }}
    </x-filament::section>

    <x-filament::modal id="passkey-error" width="md">
        <div class="flex space-x-4">
            <x-filament::icon-button
                size="lg"
                icon="heroicon-m-exclamation-triangle"
                color="danger"
            />
            <p>
                There was an error creating your passkey
            </p>
        </div>
    </x-filament::modal>

    <x-filament::modal id="creating-passkey" width="md">
        <div class="flex space-x-4">
            <x-filament::loading-indicator class="h-5 w-5"/>
            <p>
                We are creating your passkey
            </p>
        </div>
    </x-filament::modal>
</x-filament-widgets::widget>
