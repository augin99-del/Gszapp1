/* Benachrichtigungen auf diesem Gerät einschalten (Web Push). Ohne Fremdskripte. */
(function () {
  'use strict';
  var box = document.querySelector('[data-push]'); if (!box) return;
  var btn = box.querySelector('[data-push-on]'), msg = box.querySelector('[data-push-msg]');
  function say(t, err) { msg.textContent = t; msg.className = err ? 'error' : 'hint'; msg.hidden = false; }
  var ios = /iPhone|iPad|iPod/.test(navigator.userAgent), standalone = window.navigator.standalone === true || (window.matchMedia && matchMedia('(display-mode: standalone)').matches);
  if (!('serviceWorker' in navigator) || !('PushManager' in window) || !('Notification' in window)) {
    btn.disabled = true;
    say(ios && !standalone ? 'Am iPhone: Zentrale in Safari öffnen → Teilen → „Zum Home-Bildschirm“. Dann die Zentrale über das neue Symbol öffnen und hier erneut tippen.' : 'Dieser Browser unterstützt keine Push-Benachrichtigungen.', true);
    return;
  }
  if (Notification.permission === 'denied') say('Benachrichtigungen sind für die Zentrale blockiert. In den Einstellungen des Geräts (Mitteilungen → GSZ) erlauben.', true);
  function key(b64) { var p = '='.repeat((4 - b64.length % 4) % 4), s = atob((b64 + p).replace(/-/g, '+').replace(/_/g, '/')), a = new Uint8Array(s.length); for (var i = 0; i < s.length; i++) a[i] = s.charCodeAt(i); return a; }
  function label() { var u = navigator.userAgent; return (/iPhone/.test(u) ? 'iPhone' : /iPad/.test(u) ? 'iPad' : /Android/.test(u) ? 'Android' : /Mac/.test(u) ? 'Mac' : /Windows/.test(u) ? 'Windows-PC' : 'Gerät') + ' · ' + (/Edg\//.test(u) ? 'Edge' : /Chrome\//.test(u) ? 'Chrome' : /Firefox\//.test(u) ? 'Firefox' : 'Safari'); }
  btn.addEventListener('click', function () {
    btn.disabled = true; say('Einen Moment …');
    Notification.requestPermission().then(function (perm) {
      if (perm !== 'granted') throw new Error('Benachrichtigungen wurden nicht erlaubt.');
      return navigator.serviceWorker.register('sw.js').then(function () { return navigator.serviceWorker.ready; });
    }).then(function (reg) {
      return reg.pushManager.getSubscription().then(function (old) { return old || reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: key(box.getAttribute('data-key')) }); });
    }).then(function (sub) {
      var f = new FormData(); f.append('csrf', box.getAttribute('data-csrf')); f.append('action', 'subscribe'); f.append('sub', JSON.stringify(sub)); f.append('label', label());
      return fetch('benachrichtigungen.php', { method: 'POST', body: f, credentials: 'same-origin', headers: { 'X-Requested-With': 'fetch' } });
    }).then(function (r) { if (!r.ok) throw new Error('Speichern fehlgeschlagen.'); location.href = 'benachrichtigungen.php?aktiv=1#geraete'; })
      .catch(function (e) { btn.disabled = false; say(e && e.message ? e.message : 'Das hat nicht geklappt.', true); });
  });
})();
