/* Dr. Festus Fadeyi @ 85 — progressive enhancement only; the site works without JS. */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ready(fn) {
    if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    // ---------------------------------------------------------------
    // Header: solid background once the hero is scrolled past
    // ---------------------------------------------------------------
    var header = document.querySelector('[data-header]');
    var hero = document.querySelector('.hero');
    if (header && hero) {
      var onScroll = function () {
        header.classList.toggle('is-solid', window.scrollY > 40 || document.body.classList.contains('nav-open'));
      };
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });
    }

    // ---------------------------------------------------------------
    // Mobile navigation
    // ---------------------------------------------------------------
    var toggle = document.querySelector('[data-nav-toggle]');
    var panel = document.querySelector('[data-mobile-nav]');
    if (toggle && panel) {
      var setOpen = function (open) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        if (header) header.classList.toggle('is-solid', open || window.scrollY > 40 || !hero);
      };
      toggle.addEventListener('click', function () { setOpen(toggle.getAttribute('aria-expanded') !== 'true'); });
      panel.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
      document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && panel.classList.contains('is-open')) { setOpen(false); toggle.focus(); }
      });
    }

    // ---------------------------------------------------------------
    // Active section in the desktop nav
    // ---------------------------------------------------------------
    var navLinks = Array.prototype.slice.call(document.querySelectorAll('[data-nav]'));
    if ('IntersectionObserver' in window && navLinks.length && hero) {
      var byId = {};
      navLinks.forEach(function (a) { byId[a.getAttribute('href').replace(/^.*#/, '')] = a; });
      var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          var link = byId[entry.target.id];
          if (link && entry.isIntersecting) {
            navLinks.forEach(function (l) { l.classList.remove('is-active'); });
            link.classList.add('is-active');
          }
        });
      }, { rootMargin: '-45% 0px -50% 0px' });
      Object.keys(byId).forEach(function (id) { var s = document.getElementById(id); if (s) spy.observe(s); });
    }

    // ---------------------------------------------------------------
    // Scroll reveals — only elements below the fold, only if motion is welcome
    // ---------------------------------------------------------------
    if (!reduceMotion && 'IntersectionObserver' in window) {
      var targets = Array.prototype.slice.call(document.querySelectorAll('.reveal, .reveal-img'));
      var vh = window.innerHeight;
      // A clipped image never "intersects", so image reveals watch their parent.
      var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) return;
          (entry.target.__revealTargets || []).forEach(function (el) {
            var img = el.classList.contains('reveal-img');
            el.classList.add(img ? 'reveal-img--in' : 'reveal--in');
            el.classList.remove(img ? 'reveal-img--pending' : 'reveal--pending');
          });
          io.unobserve(entry.target);
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
      targets.forEach(function (el) {
        if (el.getBoundingClientRect().top < vh * 0.92) return; // already visible: leave as is
        var img = el.classList.contains('reveal-img');
        el.classList.add(img ? 'reveal-img--pending' : 'reveal--pending');
        var watched = img ? el.parentElement : el;
        (watched.__revealTargets = watched.__revealTargets || []).push(el);
        io.observe(watched);
      });
    }

    // ---------------------------------------------------------------
    // RSVP: hide seat/dietary fields when declining
    // ---------------------------------------------------------------
    document.querySelectorAll('form').forEach(function (form) {
      var radios = form.querySelectorAll('[data-attendance]');
      if (!radios.length) return;
      var dependents = form.querySelectorAll('[data-when-attending]');
      var sync = function () {
        var checked = form.querySelector('[data-attendance]:checked');
        var declining = checked && checked.value === 'no';
        dependents.forEach(function (el) { el.hidden = !!declining; });
      };
      radios.forEach(function (r) { r.addEventListener('change', sync); });
      sync();
    });

    // ---------------------------------------------------------------
    // Money inputs: thousands separators as you type
    // ---------------------------------------------------------------
    document.querySelectorAll('[data-money]').forEach(function (input) {
      input.addEventListener('input', function () {
        var digits = input.value.replace(/[^0-9]/g, '').slice(0, 11);
        input.value = digits ? Number(digits).toLocaleString('en-NG') : '';
      });
    });

    // Suggested donation amounts
    document.querySelectorAll('[data-amount-chip]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var input = document.getElementById(chip.getAttribute('data-target'));
        if (!input) return;
        input.value = Number(chip.getAttribute('data-amount-chip')).toLocaleString('en-NG');
        document.querySelectorAll('[data-amount-chip]').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        input.focus();
      });
    });

    // Anonymous giving: hide name and public-acknowledgement options
    document.querySelectorAll('[data-anonymous]').forEach(function (box) {
      var form = box.form;
      var sync = function () {
        form.querySelectorAll('[data-when-named]').forEach(function (el) { el.hidden = box.checked; });
      };
      box.addEventListener('change', sync);
      sync();
    });

    // Show-amount consent requires acknowledgement consent
    document.querySelectorAll('[data-ack]').forEach(function (ack) {
      var amount = ack.form.querySelector('[data-ack-amount]');
      if (!amount) return;
      var sync = function () { amount.disabled = !ack.checked; if (!ack.checked) amount.checked = false; };
      ack.addEventListener('change', sync);
      sync();
    });

    // ---------------------------------------------------------------
    // Submit buttons: loading state, prevent double submits
    // ---------------------------------------------------------------
    document.querySelectorAll('[data-loading-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (form.dataset.submitting) { e.preventDefault(); return; }
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { e.preventDefault(); return; }
        form.dataset.submitting = '1';
        var btn = form.querySelector('[type="submit"]');
        if (btn) { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); }
      });
    });

    // Bring validation errors into view
    var summary = document.querySelector('[data-error-summary]');
    if (summary) {
      summary.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
      var firstInvalid = document.querySelector('[aria-invalid="true"]');
      if (firstInvalid) setTimeout(function () { firstInvalid.focus({ preventScroll: true }); }, 350);
    }

    // Close calendar menus on outside click
    document.addEventListener('click', function (e) {
      document.querySelectorAll('details.cal[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
  });
})();
