// StudyHub Service Worker for Web Push & PWA

self.addEventListener('install', (event) => {
    // Activate worker immediately
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

// Handle incoming push notification
self.addEventListener('push', (event) => {
    let payload = {
        title: 'StudyHub - Pengingat Tugas',
        body: 'Kamu memiliki tugas yang mendekati batas waktu!',
        icon: '/apple-touch-icon.png',
        badge: '/favicon.svg',
        data: {
            url: '/tasks'
        }
    };

    if (event.data) {
        try {
            const data = event.data.json();
            payload.title = data.title || payload.title;
            payload.body = data.body || payload.body;
            payload.icon = data.icon || payload.icon;
            payload.badge = data.badge || payload.badge;
            if (data.data) {
                payload.data = data.data;
            }
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.badge,
        vibrate: [200, 100, 200, 100, 200],
        data: payload.data,
        actions: [
            {
                action: 'open_url',
                title: 'Buka Tugas'
            }
        ],
        tag: 'studyhub-task-reminder',
        renotify: true
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

// Handle user clicking the notification
self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = (event.notification.data && event.notification.data.url) 
        ? event.notification.data.url 
        : '/tasks';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            // Check if there is already a window open with this app
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }
            // If no window open, open new window
            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }
        })
    );
});
