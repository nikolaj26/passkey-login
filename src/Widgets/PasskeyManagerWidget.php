<?php

namespace Codeartnj\PasskeyLogin\Widgets;

use Codeartnj\PasskeyLogin\Models\Passkey;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

class PasskeyManagerWidget extends Widget implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string $view = 'passkey-login::widgets.passkey-manager-widget';

    public array $passkeyData;

    protected function getForms(): array
    {
        return [
            'passkeyForm'
        ];
    }

    public function passkeyForm($passkeyForm): Form
    {
        return $passkeyForm
            ->statePath('passkeyData')
            ->schema([
                TextInput::make('name')
                    ->required()
            ]);
    }

    #[On('addPasskey')]
    public function addPasskey($passkey): void
    {
        if (!isset($this->passkeyData['name'])) {
            return;
        }

        try {
            /** @var PublicKeyCredential $publicKeyCredential */
            $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
                ->create()
                ->deserialize(json_encode($passkey), PublicKeyCredential::class, 'json');

            $creationCSM = (new CeremonyStepManagerFactory)->creationCeremony();

            if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
                throw new \Exception('The public key credential is not an instance of authenticator attestation response');
            }

            try {
                $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCSM)->check(
                    authenticatorAttestationResponse: $publicKeyCredential->response,
                    publicKeyCredentialCreationOptions: Session::get('passkey-registration-options'),
                    host: request()->getHost()
                );
            } catch (\Throwable $throwable) {
                throw ValidationException::withMessages([
                    'name' => 'The given passkey is invalid'
                ])->errorBag('createPasskey');
            }

            Passkey::create([
                'user_id' => auth()->user()->id,
                'name' => $this->passkeyData['name'],
                'credential_id' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId),
                'data' => $publicKeyCredentialSource
            ]);
        } catch (\Throwable $throwable) {
            Log::error('Issue with registering a passkey', [
                'user_id' => auth()->user()->id,
                'message' => $throwable->getMessage(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile()
            ]);

            $this->dispatch('close-modal', id: 'creating-passkey');
            $this->dispatch('open-modal', id: 'passkey-error');

            return;
        }

        $this->dispatch('close-modal', id: 'creating-passkey');
        $this->passkeyData['name'] = '';
        Session::forget('passkey-registration-options');

        Notification::make()
            ->title('Saved successfully')
            ->success()
            ->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Passkey::where('user_id', auth()->user()->id))
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('created_at'),
            ])->actions([
                \Filament\Tables\Actions\DeleteAction::make()
            ]);
    }

    public function registerOptions()
    {
        $this->passkeyForm->getState();

        if (!isset($this->passkeyForm->getState()['name'])) {
            Notification::make()
                ->danger()
                ->title('Passkey field is required')
                ->send();

            $this->passkeyData['name'] = '';

            return;
        }

        if (Passkey::where('user_id', auth()->user()->id)->where('name', $this->passkeyForm->getState()['name'])->exists()) {
            Notification::make()
                ->danger()
                ->title('Passkey with this name already exists')
                ->send();

            return;
        }

        $this->dispatch('open-modal', id: 'creating-passkey');

        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('passkey-login.register_options.rp.name'),
                id: config('passkey-login.rp_id')
            ),
            user: new PublicKeyCredentialUserEntity(
                name: auth()->user()->{config('passkey-login.register_options.user.name')},
                id: auth()->user()->{config('passkey-login.register_options.user.id')},
                displayName: auth()->user()->{config('passkey-login.register_options.user.displayName')},
            ),
            challenge: config('passkey-login.register_options.challenge'),
            authenticatorSelection: new AuthenticatorSelectionCriteria(
                authenticatorAttachment: config('passkey-login.register_options.authenticator_selection.authenticator_attachment'),
                residentKey: config('passkey-login.register_options.authenticator_selection.resident_key')
            )
        );

        Session::put('passkey-registration-options', $options);

        $this->dispatch('options-received', data: (new WebauthnSerializerFactory(
            AttestationStatementSupportManager::create()
        ))->create()->serialize(data: $options, format: 'json'));
    }
}
