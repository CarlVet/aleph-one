import Webpass from '@laragear/webpass';

/**
 * Wires up passkey (WebAuthn) registration and login buttons using Webpass,
 * the maintained client that pairs with laragear/webauthn.
 *
 *  - [data-passkey-register]  on the settings page: registers a new passkey
 *  - [data-passkey-login]     on the login page: signs in with a passkey
 *  - [data-passkey-status]    optional element used to show feedback messages
 */
function setStatus(message, isError = false) {
    const el = document.querySelector('[data-passkey-status]');
    if (!el) {
        if (isError) {
            console.error(message);
        }
        return;
    }
    el.textContent = message;
    el.classList.toggle('text-red-500', isError);
    el.classList.toggle('text-gray-500', !isError);
}

function hidePasskeyUiIfUnsupported() {
    if (Webpass.isSupported()) {
        return;
    }
    document.querySelectorAll('[data-passkey-register], [data-passkey-login]').forEach((el) => {
        el.closest('[data-passkey-section]')?.remove() ?? el.remove();
    });
}

function initRegister() {
    const button = document.querySelector('[data-passkey-register]');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        button.disabled = true;
        setStatus('Follow your device prompt to create a passkey…');

        const { success, error } = await Webpass.attest(
            { path: '/webauthn/register/options', findCsrfToken: true },
            { path: '/webauthn/register', findCsrfToken: true },
        );

        if (success) {
            setStatus('Passkey registered.');
            window.location.reload();
            return;
        }

        console.error('Passkey registration failed:', error);
        setStatus(`Could not register the passkey — ${error?.message ?? error}`, true);
        button.disabled = false;
    });
}

function initLogin() {
    const button = document.querySelector('[data-passkey-login]');
    if (!button) {
        return;
    }

    button.addEventListener('click', async () => {
        button.disabled = true;
        setStatus('Choose your passkey to sign in…');

        const { success, error } = await Webpass.assert(
            { path: '/webauthn/login/options', findCsrfToken: true },
            { path: '/webauthn/login', findCsrfToken: true },
        );

        if (success) {
            window.location.assign('/');
            return;
        }

        console.error('Passkey sign-in failed:', error);
        setStatus(`Passkey sign-in failed — ${error?.message ?? error}`, true);
        button.disabled = false;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    hidePasskeyUiIfUnsupported();
    initRegister();
    initLogin();
});
