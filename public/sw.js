/**
 * Service worker for desktop (web push) notifications.
 *
 * The browser runs this file in the background, so it can show a
 * notification even when none of the site's tabs are open.
 */

self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch {
        payload = { body: event.data?.text() };
    }

    event.waitUntil(
        self.registration.showNotification(payload.title ?? 'Notification', {
            body: payload.body,
            icon: payload.icon,
            badge: payload.badge,
            tag: payload.tag,
            requireInteraction: payload.requireInteraction ?? false,
            data: payload.data ?? {},
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = new URL(event.notification.data?.url ?? '/', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const existingWindow = windows.find((client) => client.url === url);

            return existingWindow ? existingWindow.focus() : self.clients.openWindow(url);
        }),
    );
});
