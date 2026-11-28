/* GSZ-Zentrale: Service Worker nur für Push-Benachrichtigungen (kein Offline-Speicher, keine Daten im Cache). */
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (e) { e.waitUntil(self.clients.claim()); });
self.addEventListener('push', function (e) {
  var d = {};
  try { d = e.data ? e.data.json() : {}; } catch (x) { d = { body: e.data ? e.data.text() : '' }; }
  e.waitUntil(self.registration.showNotification(d.title || 'GSZ-Zentrale', {
    body: d.body || '', tag: d.tag || undefined, renotify: !!d.tag, data: { url: d.url || 'index.php' },
    icon: 'assets/apple-touch-icon.png', badge: 'assets/favicon-32.png'
  }));
});
self.addEventListener('notificationclick', function (e) {
  e.notification.close();
  var url = new URL((e.notification.data && e.notification.data.url) || 'index.php', self.registration.scope).href;
  e.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (ws) {
    for (var i = 0; i < ws.length; i++) { if ('focus' in ws[i]) { if (ws[i].navigate) ws[i].navigate(url); return ws[i].focus(); } }
    return self.clients.openWindow(url);
  }));
});
