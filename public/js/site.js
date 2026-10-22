/* Dr. Festus Fadeyi @ 85 — interactions and motion.
   Motion uses GSAP + ScrollTrigger + Lenis (public/vendor). Everything degrades to a
   static, fully readable page when motion is reduced or the libraries are unavailable. */
(function () {
  'use strict';

  var root = document.documentElement;
  var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var finePointer = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  function ready(fn) { if (document.readyState !== 'loading') fn(); else document.addEventListener('DOMContentLoaded', fn); }
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); }

  ready(function () {
    var gsap = window.gsap;
    var ST = window.ScrollTrigger;
    var motion = !reduceMotion && gsap && ST;
    var lenis = null;

    if (motion) {
      gsap.registerPlugin(ST);
    } else {
      root.classList.remove('js-motion');
    }

    // ---------------------------------------------------------------
    // Smooth scrolling (Lenis) and anchor links
    // ---------------------------------------------------------------
    var header = $('[data-header]');
    var headerOffset = function () { return -((header && header.offsetHeight) || 0) + 1; };

    if (motion && window.Lenis) {
      lenis = new window.Lenis({ lerp: 0.1, smoothWheel: true, wheelMultiplier: 1 });
      lenis.on('scroll', ST.update);
      gsap.ticker.add(function (t) { lenis.raf(t * 1000); });
      gsap.ticker.lagSmoothing(0);
    }

    document.addEventListener('click', function (e) {
      var a = e.target.closest('a[href*="#"]');
      if (!a) return;
      var url = new URL(a.href, location.href);
      if (url.pathname !== location.pathname || !url.hash) return;
      var target = url.hash === '#top' ? document.body : document.getElementById(url.hash.slice(1));
      if (!target) return;
      e.preventDefault();
      if (lenis) lenis.scrollTo(target, { offset: headerOffset(), duration: 1.4 });
      else target.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth' });
      history.replaceState(null, '', url.hash);
    });

    // ---------------------------------------------------------------
    // Header: solid after the hero starts scrolling, hides on scroll down
    // ---------------------------------------------------------------
    var hero = $('[data-hero]');
    var progress = $('[data-progress]');
    var lastY = window.scrollY;
    function onScroll() {
      var y = window.scrollY;
      if (header) {
        var open = document.body.classList.contains('nav-open');
        header.classList.toggle('is-solid', !hero || y > 40 || open);
        header.classList.toggle('is-hidden', !open && y > 400 && y > lastY + 2);
        if (y < lastY - 2) header.classList.remove('is-hidden');
      }
      if (progress) {
        var max = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, y / max) : 0) + ')';
      }
      lastY = y;
    }
    onScroll();
    if (lenis) lenis.on('scroll', onScroll); else window.addEventListener('scroll', onScroll, { passive: true });

    // ---------------------------------------------------------------
    // Mobile navigation
    // ---------------------------------------------------------------
    var toggle = $('[data-nav-toggle]');
    var panel = $('[data-mobile-nav]');
    if (toggle && panel) {
      var setOpen = function (open) {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        panel.classList.toggle('is-open', open);
        document.body.classList.toggle('nav-open', open);
        if (lenis) { if (open) lenis.stop(); else lenis.start(); }
        onScroll();
      };
      toggle.addEventListener('click', function () { setOpen(toggle.getAttribute('aria-expanded') !== 'true'); });
      panel.addEventListener('click', function (e) { if (e.target.closest('a')) setOpen(false); });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) { setOpen(false); toggle.focus(); } });
    }

    // Active section in the desktop nav
    var navLinks = $$('[data-nav]');
    if ('IntersectionObserver' in window && navLinks.length && hero) {
      var byId = {};
      navLinks.forEach(function (a) { byId[a.getAttribute('href').replace(/^.*#/, '')] = a; });
      var spy = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting || !byId[entry.target.id]) return;
          navLinks.forEach(function (l) { l.classList.remove('is-active'); });
          byId[entry.target.id].classList.add('is-active');
        });
      }, { rootMargin: '-45% 0px -50% 0px' });
      Object.keys(byId).forEach(function (id) { var s = document.getElementById(id); if (s) spy.observe(s); });
    }

    // ---------------------------------------------------------------
    // Countdown to the event (works without motion)
    // ---------------------------------------------------------------
    var countdown = $('[data-countdown]');
    if (countdown) {
      var target = new Date(countdown.getAttribute('data-countdown')).getTime();
      var units = {};
      $$('[data-unit]', countdown).forEach(function (el) { units[el.getAttribute('data-unit')] = el; });
      var pad = function (n) { return (n < 10 ? '0' : '') + n; };
      var tick = function () {
        var diff = Math.max(0, target - Date.now());
        var s = Math.floor(diff / 1000);
        units.days.textContent = Math.floor(s / 86400);
        units.hours.textContent = pad(Math.floor(s % 86400 / 3600));
        units.minutes.textContent = pad(Math.floor(s % 3600 / 60));
        units.seconds.textContent = pad(s % 60);
        if (diff <= 0) { countdown.hidden = true; clearInterval(timer); }
      };
      var timer = setInterval(tick, 1000);
      tick();
    }

    // Copy bank account number
    $$('[data-copy]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var text = btn.getAttribute('data-copy');
        var done = function () { var t = btn.textContent; btn.textContent = 'Copied'; setTimeout(function () { btn.textContent = t; }, 1600); };
        if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () {}); else done();
      });
    });

    formBehaviour();

    if (!motion) { root.classList.add('motion-ready'); return; }

    // ===============================================================
    // MOTION
    // ===============================================================

    // Split text into masked words, preserving inline elements (<em>, <b>, .gold)
    function splitWords(el) {
      var words = [];
      (function walk(node) {
        Array.prototype.slice.call(node.childNodes).forEach(function (child) {
          if (child.nodeType === 3) {
            var parts = child.textContent.split(/(\s+)/);
            var frag = document.createDocumentFragment();
            parts.forEach(function (part) {
              if (!part) return;
              if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
              var outer = document.createElement('span'); outer.className = 'split-word';
              var inner = document.createElement('span'); inner.textContent = part;
              outer.appendChild(inner); frag.appendChild(outer); words.push(inner);
            });
            child.parentNode.replaceChild(frag, child);
          } else if (child.nodeType === 1) {
            walk(child);
          }
        });
      })(el);
      return words;
    }

    // ---- Hero intro -------------------------------------------------
    if (hero) {
      var intro = gsap.timeline({ defaults: { ease: 'expo.out' }, delay: 0.15 });
      intro
        .fromTo('[data-hero-visual]', { opacity: 0, clipPath: 'inset(100% 0% 0% 0% round 999px 999px 28px 28px)', scale: 1.06 },
          { opacity: 1, clipPath: 'inset(0% 0% 0% 0% round 0px 0px 0px 0px)', scale: 1, duration: 1.8, clearProps: 'clipPath' }, 0)
        .fromTo('[data-hero-img]', { scale: 1.35 }, { scale: 1, duration: 2.4 }, 0)
        .fromTo('.hero__title .line > span', { yPercent: 115, opacity: 1 }, { yPercent: 0, duration: 1.4, stagger: 0.12 }, 0.25)
        .fromTo('[data-hero-item]', { y: 28, opacity: 0 }, { y: 0, opacity: 1, duration: 1.2, stagger: 0.09 }, 0.55);

      // Scroll-linked parallax as the hero leaves the screen
      gsap.to('[data-hero-img]', { yPercent: 12, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
      gsap.to('[data-hero-copy]', { y: -90, opacity: 0.15, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });

      // Pointer parallax layers + glow + tilt
      if (finePointer) {
        var layers = $$('[data-depth]', hero).map(function (el) {
          var d = parseFloat(el.getAttribute('data-depth')) || 0.3;
          return { x: gsap.quickTo(el, 'x', { duration: 1.2, ease: 'power3' }), y: gsap.quickTo(el, 'y', { duration: 1.2, ease: 'power3' }), d: d };
        });
        var glow = $('[data-glow]');
        var gx = glow && gsap.quickTo(glow, 'x', { duration: 1.6, ease: 'power3' });
        var gy = glow && gsap.quickTo(glow, 'y', { duration: 1.6, ease: 'power3' });
        var tilt = $('[data-tilt]');
        hero.addEventListener('pointermove', function (e) {
          var r = hero.getBoundingClientRect();
          var nx = (e.clientX - r.left) / r.width - 0.5;
          var ny = (e.clientY - r.top) / r.height - 0.5;
          layers.forEach(function (l) { l.x(nx * 36 * l.d); l.y(ny * 30 * l.d); });
          if (glow) { gx(e.clientX - r.left - glow.offsetWidth / 2); gy(e.clientY - r.top - glow.offsetHeight / 2); }
          if (tilt) gsap.to(tilt, { rotateY: nx * 8, rotateX: -ny * 6, transformPerspective: 1100, duration: 1.2, ease: 'power3.out' });
        });
        hero.addEventListener('pointerleave', function () {
          layers.forEach(function (l) { l.x(0); l.y(0); });
          if (tilt) gsap.to(tilt, { rotateY: 0, rotateX: 0, duration: 1.4, ease: 'power3.out' });
        });
      }

      particles($('[data-particles]'), hero);
    }

    // ---- Split headings --------------------------------------------
    $$('[data-split]').forEach(function (el) {
      var words = splitWords(el);
      gsap.fromTo(words, { yPercent: 110 }, {
        yPercent: 0, duration: 1.2, ease: 'expo.out', stagger: 0.06,
        scrollTrigger: { trigger: el, start: 'top 88%', once: true }
      });
    });

    // ---- Fade-up reveals -------------------------------------------
    ST.batch('[data-reveal]', {
      start: 'top 90%', once: true,
      onEnter: function (batch) { gsap.fromTo(batch, { y: 40, opacity: 0 }, { y: 0, opacity: 1, duration: 1.2, ease: 'expo.out', stagger: 0.1, overwrite: true }); }
    });
    ST.batch('[data-reveal-stagger]', {
      start: 'top 92%', once: true,
      onEnter: function (batch) { gsap.fromTo(batch, { y: 50, opacity: 0 }, { y: 0, opacity: 1, duration: 1, ease: 'expo.out', stagger: 0.08 }); }
    });

    // ---- Welcome: words light up as you scroll ---------------------
    $$('[data-scrub-words]').forEach(function (el) {
      var words = [];
      $$('.welcome__text', el).forEach(function (p) {
        p.innerHTML = p.textContent.split(/(\s+)/).map(function (w) { return /^\s+$/.test(w) ? w : '<span class="w">' + w.replace(/[&<>]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;' }[c]; }) + '</span>'; }).join('');
        words = words.concat($$('.w', p));
      });
      gsap.to(words, { opacity: 1, stagger: 0.1, ease: 'none', scrollTrigger: { trigger: el, start: 'top 80%', end: 'bottom 45%', scrub: true } });
    });

    // ---- Image parallax --------------------------------------------
    $$('[data-parallax-img]').forEach(function (img) {
      gsap.fromTo(img, { yPercent: -8 }, { yPercent: 8, ease: 'none', scrollTrigger: { trigger: img.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } });
    });

    // ---- Milestones: pinned horizontal scroll on desktop -------------
    var mm = gsap.matchMedia();
    mm.add('(min-width: 900px)', function () {
      var wrap = $('[data-milestones]');
      var track = $('[data-milestones-track]');
      if (!wrap || !track) return;
      var distance = function () { return Math.max(0, track.scrollWidth - window.innerWidth); };
      if (distance() < 40) return;
      gsap.to(track, {
        x: function () { return -distance(); }, ease: 'none',
        scrollTrigger: { trigger: wrap, start: 'center center', end: function () { return '+=' + distance(); }, pin: true, scrub: 0.6, invalidateOnRefresh: true, anticipatePin: 1 }
      });
    });
    gsap.fromTo('.milestone', { y: 60, opacity: 0 }, { y: 0, opacity: 1, duration: 1, ease: 'expo.out', stagger: 0.08, scrollTrigger: { trigger: '[data-milestones]', start: 'top 85%', once: true } });

    // ---- Book: floating and tilting --------------------------------
    var book = $('[data-book-tilt]');
    if (book) {
      gsap.to(book, { y: -14, duration: 3, ease: 'sine.inOut', yoyo: true, repeat: -1 });
      gsap.fromTo(book, { rotateY: -24, rotateX: 6 }, { rotateY: -6, rotateX: 0, ease: 'none', transformPerspective: 1200, scrollTrigger: { trigger: book, start: 'top bottom', end: 'bottom top', scrub: true } });
      if (finePointer) {
        var stage = book.parentElement;
        stage.addEventListener('pointermove', function (e) {
          var r = stage.getBoundingClientRect();
          gsap.to(book.firstElementChild, { rotateY: ((e.clientX - r.left) / r.width - 0.5) * 22, rotateX: -((e.clientY - r.top) / r.height - 0.5) * 16, transformPerspective: 1000, duration: 0.8, ease: 'power3.out' });
        });
        stage.addEventListener('pointerleave', function () { gsap.to(book.firstElementChild, { rotateY: 0, rotateX: 0, duration: 1.2, ease: 'power3.out' }); });
      }
    }

    // ---- Programme: light follows the pointer -----------------------
    $$('.prog li').forEach(function (li) {
      li.addEventListener('pointermove', function (e) {
        var r = li.getBoundingClientRect();
        li.style.setProperty('--mx', (e.clientX - r.left) + 'px');
        li.style.setProperty('--my', (e.clientY - r.top) + 'px');
      });
    });

    // ---- Footer headline parallax -----------------------------------
    var big = $('.footer__big');
    if (big) gsap.fromTo(big, { xPercent: -6 }, { xPercent: 0, ease: 'none', scrollTrigger: { trigger: big, start: 'top bottom', end: 'bottom 60%', scrub: true } });

    // ---- Magnetic buttons -----------------------------------------
    if (finePointer) {
      $$('[data-magnetic]').forEach(function (btn) {
        var mx = gsap.quickTo(btn, 'x', { duration: 0.6, ease: 'power3' });
        var my = gsap.quickTo(btn, 'y', { duration: 0.6, ease: 'power3' });
        btn.addEventListener('pointermove', function (e) {
          var r = btn.getBoundingClientRect();
          mx((e.clientX - r.left - r.width / 2) * 0.28);
          my((e.clientY - r.top - r.height / 2) * 0.4);
        });
        btn.addEventListener('pointerleave', function () { mx(0); my(0); });
      });
    }

    root.classList.add('motion-ready');
    window.addEventListener('load', function () { ST.refresh(); });

    // Jump to an anchor present in the URL once layout has settled
    if (location.hash && lenis) {
      var initial = document.getElementById(location.hash.slice(1));
      if (initial) setTimeout(function () { lenis.scrollTo(initial, { offset: headerOffset(), immediate: true }); }, 60);
    }
  });

  // -----------------------------------------------------------------
  // Gold particles drifting upward in the hero (paused off-screen)
  // -----------------------------------------------------------------
  function particles(canvas, host) {
    if (!canvas || !canvas.getContext) return;
    var ctx = canvas.getContext('2d');
    var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    var w, h, dots = [], running = false, raf;

    function resize() {
      w = host.offsetWidth; h = host.offsetHeight;
      canvas.width = w * dpr; canvas.height = h * dpr;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      var count = w < 700 ? 26 : 60;
      dots = [];
      for (var i = 0; i < count; i++) dots.push(spawn(true));
    }
    function spawn(anywhere) {
      return {
        x: Math.random() * w, y: anywhere ? Math.random() * h : h + 10,
        r: Math.random() * 1.8 + 0.4, vy: -(Math.random() * 0.35 + 0.08), vx: (Math.random() - 0.5) * 0.15,
        a: Math.random() * 0.5 + 0.15, t: Math.random() * Math.PI * 2
      };
    }
    function frame() {
      ctx.clearRect(0, 0, w, h);
      for (var i = 0; i < dots.length; i++) {
        var p = dots[i];
        p.x += p.vx; p.y += p.vy; p.t += 0.02;
        if (p.y < -10) dots[i] = p = spawn(false);
        var alpha = p.a * (0.6 + 0.4 * Math.sin(p.t));
        var g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 4);
        g.addColorStop(0, 'rgba(226,201,143,' + alpha + ')');
        g.addColorStop(1, 'rgba(199,164,91,0)');
        ctx.fillStyle = g;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 4, 0, Math.PI * 2); ctx.fill();
      }
      if (running) raf = requestAnimationFrame(frame);
    }
    resize();
    window.addEventListener('resize', resize);
    new IntersectionObserver(function (entries) {
      running = entries[0].isIntersecting;
      cancelAnimationFrame(raf);
      if (running) raf = requestAnimationFrame(frame);
    }).observe(host);
  }

  // -----------------------------------------------------------------
  // Form behaviour (independent of motion)
  // -----------------------------------------------------------------
  function formBehaviour() {
    // RSVP: hide seat/dietary fields when declining
    $$('form').forEach(function (form) {
      var radios = $$('[data-attendance]', form);
      if (!radios.length) return;
      var dependents = $$('[data-when-attending]', form);
      var sync = function () {
        var checked = $('[data-attendance]:checked', form);
        dependents.forEach(function (el) { el.hidden = !!(checked && checked.value === 'no'); });
      };
      radios.forEach(function (r) { r.addEventListener('change', sync); });
      sync();
    });

    // Money inputs: thousands separators as you type
    $$('[data-money]').forEach(function (input) {
      input.addEventListener('input', function () {
        var digits = input.value.replace(/[^0-9]/g, '').slice(0, 11);
        input.value = digits ? Number(digits).toLocaleString('en-NG') : '';
      });
    });

    // Suggested donation amounts
    $$('[data-amount-chip]').forEach(function (chip) {
      chip.addEventListener('click', function () {
        var input = document.getElementById(chip.getAttribute('data-target'));
        if (!input) return;
        input.value = Number(chip.getAttribute('data-amount-chip')).toLocaleString('en-NG');
        $$('[data-amount-chip]').forEach(function (c) { c.setAttribute('aria-pressed', c === chip ? 'true' : 'false'); });
        input.focus();
      });
    });

    // Anonymous giving hides name and public-acknowledgement options
    $$('[data-anonymous]').forEach(function (box) {
      var sync = function () { $$('[data-when-named]', box.form).forEach(function (el) { el.hidden = box.checked; }); };
      box.addEventListener('change', sync);
      sync();
    });

    // Showing the amount requires consent to be acknowledged
    $$('[data-ack]').forEach(function (ack) {
      var amount = $('[data-ack-amount]', ack.form);
      if (!amount) return;
      var sync = function () { amount.disabled = !ack.checked; if (!ack.checked) amount.checked = false; };
      ack.addEventListener('change', sync);
      sync();
    });

    // Submit buttons: loading state, no double submits
    $$('[data-loading-form]').forEach(function (form) {
      form.addEventListener('submit', function (e) {
        if (form.dataset.submitting) { e.preventDefault(); return; }
        if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) { e.preventDefault(); return; }
        form.dataset.submitting = '1';
        var btn = $('[type="submit"]', form);
        if (btn) { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); }
      });
    });

    // Bring validation errors into view
    var summary = $('[data-error-summary]');
    if (summary) {
      setTimeout(function () {
        summary.scrollIntoView({ block: 'center' });
        var firstInvalid = $('[aria-invalid="true"]');
        if (firstInvalid) firstInvalid.focus({ preventScroll: true });
      }, 50);
    }

    // Close calendar menus on outside click
    document.addEventListener('click', function (e) {
      $$('details.cal[open]').forEach(function (d) { if (!d.contains(e.target)) d.removeAttribute('open'); });
    });
  }
})();
