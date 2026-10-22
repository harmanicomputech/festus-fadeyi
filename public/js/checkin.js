/* Welcome-desk check-in: camera QR scanning (jsQR), manual code entry and name search.
   The QR holds only an opaque token; every code is validated server-side. */
(function () {
  'use strict';

  var root = document.querySelector('[data-checkin]');
  if (!root) return;

  var video = root.querySelector('[data-video]');
  var idle = root.querySelector('[data-idle]');
  var cameraMsg = root.querySelector('[data-camera-msg]');
  var result = root.querySelector('[data-result]');
  var titleEl = root.querySelector('[data-result-title]');
  var nameEl = root.querySelector('[data-result-name]');
  var detailEl = root.querySelector('[data-result-detail]');
  var admitBtn = root.querySelector('[data-admit]');
  var nextBtn = root.querySelector('[data-next]');
  var countEl = root.querySelector('[data-count]');
  var totalEl = root.querySelector('[data-total]');
  var csrf = root.dataset.csrf;

  var canvas = document.createElement('canvas');
  var ctx = canvas.getContext('2d', { willReadFrequently: true });
  var stream = null;
  var scanning = false;
  var busy = false;
  var lastCode = '';
  var lastAt = 0;
  var current = null;

  var TITLES = {
    valid: 'Valid invitation',
    admitted: 'Welcome — checked in',
    already: 'Already checked in',
    repeat: 'Already checked in',
    not_confirmed: 'Not a confirmed guest',
    invalid: 'Code not recognised'
  };

  function post(url, body) {
    return fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
      credentials: 'same-origin',
      body: JSON.stringify(body || {})
    }).then(handle);
  }

  function handle(res) {
    if (res.status === 419 || res.status === 401) { throw new Error('Your session has expired. Please refresh the page and sign in again.'); }
    if (res.status === 429) { throw new Error('Too many requests — please wait a moment.'); }
    if (!res.ok) { throw new Error('Server error (' + res.status + '). Please try again.'); }
    return res.json();
  }

  function beep(ok) {
    try {
      var ac = new (window.AudioContext || window.webkitAudioContext)();
      var o = ac.createOscillator(); var g = ac.createGain();
      o.frequency.value = ok ? 880 : 220; g.gain.value = 0.08;
      o.connect(g); g.connect(ac.destination); o.start(); o.stop(ac.currentTime + (ok ? 0.12 : 0.3));
    } catch (e) { /* audio optional */ }
    if (navigator.vibrate) navigator.vibrate(ok ? 60 : [80, 60, 80]);
  }

  function show(state, data) {
    current = data && data.guest ? data.guest : null;
    result.className = 'result is-shown result--' + state;
    titleEl.textContent = TITLES[state] || state;
    nameEl.textContent = current ? current.name : '';
    var bits = [];
    if (current) {
      bits.push(current.seats + (current.seats === 1 ? ' seat' : ' seats'));
      if (current.organisation) bits.push(current.organisation);
      if (current.dietary) bits.push('Needs: ' + current.dietary);
      if (state === 'not_confirmed') bits.push('RSVP status: ' + current.status + ' — please refer to the event manager.');
      if ((state === 'already' || state === 'repeat') && current.checked_in_at) bits.push('Arrived at ' + current.checked_in_at + (current.checked_in_by ? ' (by ' + current.checked_in_by + ')' : ''));
    } else if (data && data.message) {
      bits.push(data.message);
    }
    detailEl.textContent = bits.join(' · ');
    admitBtn.hidden = state !== 'valid';
    if (state === 'valid') admitBtn.focus(); else nextBtn.focus();
    beep(state === 'valid' || state === 'admitted');
  }

  function showError(msg) {
    result.className = 'result is-shown result--invalid';
    titleEl.textContent = 'Something went wrong';
    nameEl.textContent = '';
    detailEl.textContent = msg;
    admitBtn.hidden = true;
  }

  function lookup(code) {
    if (busy) return;
    busy = true;
    post(root.dataset.lookupUrl, { code: code })
      .then(function (data) { show(data.result, data); })
      .catch(function (e) { showError(e.message); })
      .finally(function () { busy = false; });
  }

  admitBtn.addEventListener('click', function () {
    if (!current || busy) return;
    busy = true;
    admitBtn.disabled = true;
    post(current.admit_url)
      .then(function (data) {
        show(data.admit === 'admitted' ? 'admitted' : data.admit, data);
        if (data.totals) { countEl.textContent = data.totals.checked_in; totalEl.textContent = data.totals.confirmed; }
      })
      .catch(function (e) { showError(e.message); })
      .finally(function () { busy = false; admitBtn.disabled = false; });
  });

  nextBtn.addEventListener('click', function () {
    result.className = 'result';
    current = null;
    lastCode = '';
  });

  // ---------------------------------------------------------------
  // Camera scanning
  // ---------------------------------------------------------------
  function tick() {
    if (!scanning) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA && !busy && typeof window.jsQR === 'function') {
      var w = video.videoWidth, h = video.videoHeight;
      var scale = Math.min(1, 640 / w);
      canvas.width = Math.round(w * scale); canvas.height = Math.round(h * scale);
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var code = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });
      if (code && code.data) {
        var now = Date.now();
        // Ignore the same code held in front of the camera
        if (code.data !== lastCode || now - lastAt > 4000) {
          lastCode = code.data; lastAt = now;
          lookup(code.data);
        }
      }
    }
    setTimeout(function () { requestAnimationFrame(tick); }, 180);
  }

  root.querySelector('[data-start]').addEventListener('click', function () {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      cameraMsg.textContent = 'Camera not available in this browser. Use manual entry or search below (HTTPS is required for the camera).';
      return;
    }
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false })
      .then(function (s) {
        stream = s;
        video.srcObject = s;
        return video.play();
      })
      .then(function () { idle.hidden = true; idle.style.display = 'none'; scanning = true; tick(); })
      .catch(function () { cameraMsg.textContent = 'Camera permission was denied or no camera was found. Use manual entry or search below.'; });
  });

  document.addEventListener('visibilitychange', function () {
    if (document.hidden && stream) { scanning = false; }
    else if (!document.hidden && stream) { scanning = true; tick(); }
  });

  // ---------------------------------------------------------------
  // Manual code and search
  // ---------------------------------------------------------------
  root.querySelector('[data-manual]').addEventListener('submit', function (e) {
    e.preventDefault();
    var v = e.target.code.value.trim();
    if (v) lookup(v);
    e.target.code.value = '';
  });

  var list = root.querySelector('[data-search-results]');
  var timer = null;
  var searchForm = root.querySelector('[data-search]');
  searchForm.addEventListener('submit', function (e) { e.preventDefault(); });
  searchForm.q.addEventListener('input', function () {
    clearTimeout(timer);
    var q = searchForm.q.value.trim();
    timer = setTimeout(function () {
      if (q.length < 2) { list.innerHTML = ''; return; }
      fetch(root.dataset.searchUrl + '?q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
        .then(handle)
        .then(function (data) {
          list.innerHTML = '';
          if (!data.guests.length) { var li = document.createElement('li'); li.textContent = 'No matching confirmed, waitlisted or maybe guests.'; list.appendChild(li); return; }
          data.guests.forEach(function (g) {
            var li = document.createElement('li');
            var label = document.createElement('span');
            label.textContent = g.guest.name + ' · ' + g.guest.seats + ' seat(s) · ' + g.guest.status + (g.guest.checked_in_at ? ' · arrived ' + g.guest.checked_in_at : '');
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'btn btn--sm'; btn.textContent = 'Select';
            btn.addEventListener('click', function () { show(g.result, g); window.scrollTo({ top: 0, behavior: 'smooth' }); });
            li.appendChild(label); li.appendChild(btn); list.appendChild(li);
          });
        })
        .catch(function (e) { list.innerHTML = ''; var li = document.createElement('li'); li.textContent = e.message; list.appendChild(li); });
    }, 250);
  });
})();
