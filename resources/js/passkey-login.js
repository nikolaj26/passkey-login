import {
    browserSupportsWebAuthn,
    startAuthentication,
    startRegistration,
} from "@simplewebauthn/browser";

document.addEventListener('alpine:init', () => {
    Alpine.data('registerPasskey', () => ({
        browserSupportsWebAuthn,
        showPasskeyForm: false,
        listeners: [],
        async init() {
            this.showPasskeyForm = this.browserSupportsWebAuthn()
            this.listeners.push(
                Livewire.on('options-received', async (options) => {

                    const publicKeyCredentialCreationOptions = {
                        optionsJSON: JSON.parse(options.data)
                    };

                    try {
                        const passkey = await startRegistration(publicKeyCredentialCreationOptions)

                        Livewire.dispatch('addPasskey', [
                            passkey
                        ])
                    } catch (e) {
                        Livewire.dispatch('close-modal', {id: 'creating-passkey'})
                        Livewire.dispatch('open-modal', {id: 'passkey-error'})

                        console.error(e)
                    }
                })
            );
        }
    }))

    Alpine.data('authenticatePasskey', () => ({
        browserSupportsWebAuthn,
        showPasskeyField: false,
        listeners: [],
        async init() {
            this.showPasskeyField = this.browserSupportsWebAuthn()
            this.listeners.push(
                Livewire.on('authenticate-attempt', async (options) => {
                    const publicKeyCredentialRequestOptions = {
                        optionsJSON: JSON.parse(options.data)
                    };

                    try {
                        const answer = await startAuthentication(publicKeyCredentialRequestOptions)

                        Livewire.dispatch('authenticatePasskey', [
                            JSON.stringify(answer)
                        ])
                    } catch (e) {
                        Livewire.dispatch('open-modal', {id: 'passkey-error'})

                        console.error(e)
                    }
                })
            );
        },
    }))
})
