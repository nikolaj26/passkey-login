<?php

namespace Codeartnj\PasskeyLogin\Pages;

use Codeartnj\PasskeyLogin\Models\Passkey;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Login as BaseAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\HtmlString;
use Livewire\Attributes\On;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSource;

class PasskeyLogin extends BaseAuth
{
    protected static string $view = 'passkey-login::pages.auth.login';
    public int $step = 1;
    public string $email;

    public function form(Form $form): Form
    {
        return $form
            ->schema(config('passkey-login.multi_step') ? $this->getMultiStepForm() : $this->getSingleStepForm())
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (config('passkey-login.multi_step')) {
            $data['email'] = $this->email;
        }

        if (!Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (!$user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            $this->throwFailureValidationException();
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    #[On('authenticatePasskey')]
    public function authenticatePasskey($answer): void
    {
        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($answer, PublicKeyCredential::class, 'json');

        $requestCSM = (new CeremonyStepManagerFactory)->requestCeremony();

        if (!$publicKeyCredential->response instanceof AuthenticatorAssertionResponse) {
            Notification::make()
                ->danger()
                ->title('Invalid passkey')
                ->send();
        }

        $passkey = Passkey::where('credential_id', base64_encode($publicKeyCredential->rawId))
            ->first();

        if (!$passkey) {
            Notification::make()
                ->danger()
                ->title('Passkey expired')
                ->send();

            return;
        }

        if (config('passkey-login.matching_email')) {
            if (config('passkey-login.multi_step') && $passkey->user->email !== $this->email) {
                Notification::make()
                    ->danger()
                    ->title('Passkey doesn\'t belongs to you')
                    ->send();

                return;
            }

            $data = $this->form->getState();

            if (!config('passkey-login.multi_step') && $passkey->user->email !== $data['email']) {
                Notification::make()
                    ->danger()
                    ->title('Passkey doesn\'t belongs to you')
                    ->send();

                return;
            }
        }

        try {
            $publicKeyCredentialSource = AuthenticatorAssertionResponseValidator::create($requestCSM)->check(
                publicKeyCredentialSource: $passkey->data,
                authenticatorAssertionResponse: $publicKeyCredential->response,
                publicKeyCredentialRequestOptions: Session::get('passkey-authentication-options'),
                host: request()->getHost(),
                userHandle: null
            );
        } catch (\Throwable $throwable) {
            Notification::make()
                ->danger()
                ->title('Passkey not valid')
                ->send();

            return;
        }

        $passkey->update(['data' => $publicKeyCredentialSource]);

        Auth::loginUsingId($passkey->user_id);
        request()->session()->regenerate();

        $this->redirect(config('passkey-login.successful_login_route'));
    }

    public function authenticateOptions()
    {
        $allowedCredentials = Passkey::all()
            ->map(fn(Passkey $passkey) => $passkey->data)
            ->map(fn(PublicKeyCredentialSource $publicKeyCredentialSource) => $publicKeyCredentialSource->getPublicKeyCredentialDescriptor())
            ->all();

        $options = new PublicKeyCredentialRequestOptions(
            challenge: config('passkey-login.authenticate_options.challenge'),
            rpId: config('passkey-login.rp_id'),
            allowCredentials: $allowedCredentials
        );

        Session::put('passkey-authentication-options', $options);

        $this->dispatch('authenticate-attempt', data: (new WebauthnSerializerFactory(
            AttestationStatementSupportManager::create()
        ))->create()->serialize(data: $options, format: 'json'));
    }

    public function continue()
    {
        $data = $this->form->getState();

        if (isset($data['email'])) {
            $this->step++;
            $this->email = $data['email'];
        }
    }

    private function getSingleStepForm()
    {
        return [
            TextInput::make('email')
                ->label(__('filament-panels::pages/auth/login.form.email.label'))
                ->email()
                ->required()
                ->autocomplete()
                ->autofocus(),

            TextInput::make('password')
                ->label(__('filament-panels::pages/auth/login.form.password.label'))
                ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->autocomplete('current-password')
                ->required(),
        ];
    }

    private function getMultiStepForm()
    {
        return [
            TextInput::make('email')
                ->label(__('filament-panels::pages/auth/login.form.email.label'))
                ->visible(fn() => $this->step === 1)
                ->email()
                ->required()
                ->autocomplete()
                ->autofocus(),

            TextInput::make('password')
                ->visible(fn() => $this->step == 2)
                ->label(__('filament-panels::pages/auth/login.form.password.label'))
                ->hint(filament()->hasPasswordReset() ? new HtmlString(Blade::render('<x-filament::link :href="filament()->getRequestPasswordResetUrl()"> {{ __(\'filament-panels::pages/auth/login.actions.request_password_reset.label\') }}</x-filament::link>')) : null)
                ->password()
                ->revealable(filament()->arePasswordsRevealable())
                ->autocomplete('current-password')
                ->required(),
        ];
    }
}
