/* Öffentliche Seiten: Fotovorschau und Schutz vor doppeltem Absenden. Ohne Cookies, ohne Fremdskripte. */
(function () {
  'use strict';
  document.querySelectorAll('[data-photo]').forEach(function (inp) {
    var box = inp.closest('.pub-photo'), label = box.querySelector('[data-photo-label]'), img = box.querySelector('[data-photo-preview]'), url = null;
    inp.addEventListener('change', function () {
      var f = inp.files && inp.files[0];
      if (url) { URL.revokeObjectURL(url); url = null; }
      if (!f) { img.hidden = true; box.classList.remove('has-photo'); label.lastChild.textContent = ' Foto aufnehmen oder auswählen'; return; }
      if (f.size > 8 * 1024 * 1024) { alert('Das Foto ist größer als 8 MB. Bitte ein kleineres wählen.'); inp.value = ''; return; }
      try { url = URL.createObjectURL(f); img.src = url; img.hidden = false; } catch (e) {}
      box.classList.add('has-photo'); label.lastChild.textContent = ' Foto ändern';
    });
  });
  document.querySelectorAll('[data-report-form]').forEach(function (form) {
    form.addEventListener('submit', function () {
      var b = form.querySelector('[data-send]'); if (!b) return;
      setTimeout(function () { b.disabled = true; b.textContent = 'Wird gesendet …'; }, 0);
    });
  });
})();
