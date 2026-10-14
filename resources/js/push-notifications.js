/**
 * Desktop (web push) notification toggle.
 *
 * Expects a button rendered with:
 *   data-push-toggle
 *   data-vapid-key   the VAPID public key
 *   data-url         the push subscription endpoint (POST to enable, DELETE to disable)
 *   data-csrf        the CSRF token
 */

const labels = {
    enable: 'Enable desktop notifications',
    disable: 'Disable desktop notifications',
    working: 'Please wait…',
    blocked: 'Notifications are blocked for this site in your browser settings',
    unsupported: 'This browser does not support desktop notifications',
    failed: 'Something went wrong. Please try again.',
};

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');

    return Uint8Array.from(atob(base64), (character) => character.charCodeAt(0));
}

function sendToServer(button, method, body) {
    return fetch(button.dataset.url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': button.dataset.csrf,
        },
        body: JSON.stringify(body),
    }).then((response) => {
        if (!response.ok) {
            throw new Error(`Push subscription request failed with status ${response.status}.`);
        }
    });
}

function saveSubscription(button, subscription) {
    return sendToServer(button, 'POST', {
        ...subscription.toJSON(),
        content_encoding: (PushManager.supportedContentEncodings || ['aes128gcm'])[0],
    });
}

function render(button, state, status) {
    button.dataset.state = state;
    button.disabled = state === 'blocked' || state === 'unsupported' || state === 'working';
    button.textContent = labels[state === 'subscribed' ? 'disable' : state === 'unsubscribed' ? 'enable' : state];

    if (status) {
        status.textContent = state === 'subscribed'
            ? 'You will get a desktop alert on this browser when your password changes.'
            : '';
    }
}

async function enable(button, registration) {
    const permission = await Notification.requestPermission();

    if (permission !== 'granted') {
        return permission === 'denied' ? 'blocked' : 'unsubscribed';
    }

    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly: true,
        applicationServerKey: urlBase64ToUint8Array(button.dataset.vapidKey),
    });

    await saveSubscription(button, subscription);

    return 'subscribed';
}

async function disable(button, registration) {
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
        await sendToServer(button, 'DELETE', { endpoint: subscription.endpoint });
        await subscription.unsubscribe();
    }

    return 'unsubscribed';
}

async function setUp(button) {
    const status = document.querySelector('[data-push-status]');

    if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
        render(button, 'unsupported', status);
        return;
    }

    if (Notification.permission === 'denied') {
        render(button, 'blocked', status);
        return;
    }

    await navigator.serviceWorker.register('/sw.js');
    const registration = await navigator.serviceWorker.ready;
    const existingSubscription = await registration.pushManager.getSubscription();

    if (existingSubscription && Notification.permission === 'granted') {
        // Re-sync, in case another account used this browser since it subscribed.
        await saveSubscription(button, existingSubscription).catch(() => {});
        render(button, 'subscribed', status);
    } else {
        render(button, 'unsubscribed', status);
    }

    button.addEventListener('click', async () => {
        const wasSubscribed = button.dataset.state === 'subscribed';
        render(button, 'working', status);

        try {
            render(button, wasSubscribed ? await disable(button, registration) : await enable(button, registration), status);
        } catch (error) {
            console.error(error);
            render(button, wasSubscribed ? 'subscribed' : 'unsubscribed', status);
            button.textContent = labels.failed;
        }
    });
}

const toggleButton = document.querySelector('[data-push-toggle]');

if (toggleButton) {
    setUp(toggleButton).catch((error) => {
        console.error(error);
        render(toggleButton, 'unsupported');
    });
}
