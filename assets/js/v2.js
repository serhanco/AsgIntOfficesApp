/**
 * New design (includes/v2): header, mobile menu, scroll reveal, number count-up,
 * office action bar. Language menu, maps and search stay in assets/js/app.js.
 */
(function () {
  'use strict';
  const root = document.documentElement;
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  ready(function () {
    // Header: deeper glass once the page scrolls
    const header = document.querySelector('[data-header]');
    if (header) {
      // --hb: where the header ends on screen (the preview ribbon above it scrolls away);
      // the mobile menu and language list open just below it
      const onScroll = () => {
        header.classList.toggle('is-scrolled', window.scrollY > 8);
        root.style.setProperty('--hb', Math.max(0, header.getBoundingClientRect().bottom) + 'px');
      };
      window.addEventListener('resize', onScroll);
      onScroll();
      window.addEventListener('scroll', onScroll, { passive: true });

      // Long menu labels (some languages) + count badges can crowd the logo: drop the menu icons first,
      // then the logo's side text, then shrink the logo, only as far as needed
      const logo = header.querySelector('.site-header__bar > a');
      const steps = ['fit-1', 'fit-2', 'fit-3'];
      const fit = () => {
        if (!logo) return;
        header.classList.remove(...steps);
        for (const step of steps) {
          if (logo.scrollWidth <= logo.clientWidth + 1) break;
          header.classList.add(step);
        }
      };
      fit();
      window.addEventListener('resize', fit);
      if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
    }

    // Sticky search bar under the header: its height is added to the scroll offset of in-page jumps
    // (country list), so the card title stays visible below both bars
    const bar = document.querySelector('[data-sticky-bar]');
    if (bar) {
      const setBar = () => root.style.setProperty('--bar-h', bar.offsetHeight + 'px');
      setBar();
      window.addEventListener('resize', setBar);
      if ('ResizeObserver' in window) new ResizeObserver(setBar).observe(bar);
    }

    // Mobile menu
    const burger = document.querySelector('[data-burger]');
    const drawer = document.querySelector('[data-drawer]');
    function setDrawer(open) {
      if (!burger || !drawer) return;
      drawer.classList.toggle('is-open', open);
      drawer.setAttribute('aria-hidden', String(!open));
      burger.setAttribute('aria-expanded', String(open));
      root.classList.toggle('drawer-open', open);
    }
    if (burger && drawer) {
      burger.addEventListener('click', () => setDrawer(!drawer.classList.contains('is-open')));
      drawer.addEventListener('click', (e) => { if (e.target === drawer) setDrawer(false); });
      document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setDrawer(false); });
      window.addEventListener('resize', () => { if (window.innerWidth >= 1024) setDrawer(false); });
    }

    // Reveal on scroll
    const items = document.querySelectorAll('.reveal');
    if ('IntersectionObserver' in window && root.classList.contains('js-anim')) {
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
        });
      }, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
      items.forEach((el) => io.observe(el));
    } else {
      items.forEach((el) => el.classList.add('in'));
    }

    // Count-up for numbers like "50+" / "28" (text stays correct without JS)
    const counters = document.querySelectorAll('[data-count]');
    if (!reduced && 'IntersectionObserver' in window) {
      const run = (el) => {
        const target = parseInt(el.dataset.count, 10);
        const suffix = el.dataset.suffix || '';
        const t0 = performance.now(), dur = 1400;
        const step = (t) => {
          const p = Math.min(1, (t - t0) / dur);
          el.textContent = Math.round(target * (1 - Math.pow(1 - p, 3))) + suffix;
          if (p < 1) requestAnimationFrame(step);
        };
        requestAnimationFrame(step);
      };
      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => { if (en.isIntersecting) { run(en.target); io.unobserve(en.target); } });
      }, { threshold: 0.6 });
      counters.forEach((el) => io.observe(el));
    }

    // Office page: floating Call / WhatsApp / Email / Route bar once the hero buttons scroll away
    const dock = document.querySelector('[data-dock]');
    const anchor = document.querySelector('[data-dock-anchor]');
    if (dock && anchor && 'IntersectionObserver' in window) {
      new IntersectionObserver(([en]) => {
        dock.classList.toggle('is-visible', !en.isIntersecting && en.boundingClientRect.top < 0);
      }).observe(anchor);
    }
    // Event dates: printed as numbers by PHP, written out here in the page language
    const dateEls = document.querySelectorAll('[data-date], [data-month]');
    if (dateEls.length && window.Intl && Intl.DateTimeFormat) {
      const lang = root.lang || 'en';
      const toDate = (s) => { const p = s.split('-'); return new Date(Date.UTC(+p[0], +p[1] - 1, +p[2])); };
      const thisYear = new Date().getFullYear();
      try {
        dateEls.forEach((el) => {
          if (el.dataset.month) {
            const m = new Intl.DateTimeFormat(lang, { month: 'short', timeZone: 'UTC' }).format(toDate(el.dataset.month));
            el.textContent = m.replace(/\.$/, '');
            return;
          }
          const a = toDate(el.dataset.date);
          const b = el.dataset.dateEnd ? toDate(el.dataset.dateEnd) : null;
          const opts = { day: 'numeric', month: 'long', timeZone: 'UTC' };
          if (a.getUTCFullYear() !== thisYear || (b && b.getUTCFullYear() !== thisYear)) opts.year = 'numeric';
          if (!b) opts.weekday = 'short';
          const fmt = new Intl.DateTimeFormat(lang, opts);
          el.textContent = b ? (fmt.formatRange ? fmt.formatRange(a, b) : fmt.format(a) + ' – ' + fmt.format(b)) : fmt.format(a);
        });
      } catch (e) { /* keep the numeric dates */ }
    }

    // "Nearest office" buttons on the map and the office list: the location is only asked for on click.
    // Offices come from window.NEAR_OFFICES; the three closest are written into [data-near-result] and the page
    // gets 'v2:nearest' ({ lat, lon, list sorted by distance }) for anything else (map marker, list distances). ?at=lat,lon / ?at=deny fake it for tests.
    const nearBtns = document.querySelectorAll('[data-near-me]');
    if (nearBtns.length) {
      const T = window.APP_I18N || {};
      const setBusy = (on) => nearBtns.forEach((b) => {
        b.disabled = on;
        b.classList.toggle('is-locating', on);
        const label = b.querySelector('[data-near-me-label]');
        if (!label) return;
        if (!label.dataset.idle) label.dataset.idle = label.textContent;
        label.textContent = on && T.locating ? T.locating : label.dataset.idle;
      });
      const esc = (v) => String(v == null ? '' : v).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
      const km = (d) => (d < 1 ? '<1' : Math.round(d).toLocaleString('en-US')) + ' km';
      const row = (o, main) => `<a href="${esc(o.url)}" class="near-row${main ? ' near-row--main' : ''}">
          <span class="near-row__icon"><i class="ph-fill ph-buildings" aria-hidden="true"></i></span>
          <span class="near-row__text"><b><bdi>${esc(o.name)}</bdi></b><small><bdi>${esc(o.country)}</bdi></small></span>
          <span class="near-row__km" dir="ltr">${km(o.distance)}</span>
          <i class="ph ph-arrow-right arrow" aria-hidden="true"></i></a>`;
      const found = (lat, lon) => {
        setBusy(false);
        const list = (window.NEAR_OFFICES || [])
          .filter((o) => o.lat != null && o.lon != null)
          .map((o) => Object.assign({}, o, { distance: calculateDistance(lat, lon, o.lat, o.lon) }))
          .sort((a, b) => a.distance - b.distance);
        document.querySelectorAll('[data-near-result]').forEach((box) => {
          if (!list.length) return;
          box.innerHTML = `<p class="near-result__h"><i class="ph-fill ph-crosshair" aria-hidden="true"></i>${esc(T.nearest || '')}</p>`
            + list.slice(0, 3).map((o, i) => row(o, i === 0)).join('');
          box.hidden = false;
        });
        document.dispatchEvent(new CustomEvent('v2:nearest', { detail: { lat, lon, list } }));
      };
      const failed = (unsupported) => {
        setBusy(false);
        const msg = unsupported ? T.geoUnsupported : T.geoDenied;
        if (msg && typeof showToast === 'function') showToast(msg, 4000);
      };
      const test = (new URLSearchParams(location.search).get('at') || '').split(',');
      const locate = () => {
        setBusy(true);
        if (test[0] === 'deny') { setTimeout(() => failed(false), 600); return; }
        if (test.length === 2 && !isNaN(parseFloat(test[0])) && !isNaN(parseFloat(test[1]))) { setTimeout(() => found(parseFloat(test[0]), parseFloat(test[1])), 600); return; }
        if (!navigator.geolocation) { failed(true); return; }
        navigator.geolocation.getCurrentPosition(
          (p) => found(p.coords.latitude, p.coords.longitude),
          () => failed(false),
          // City-level accuracy is enough to pick an office; a recent cached fix answers instantly
          { enableHighAccuracy: false, timeout: 10000, maximumAge: 10 * 60 * 1000 }
        );
      };
      nearBtns.forEach((b) => b.addEventListener('click', locate));
    }

    // Share: native share sheet on phones, copy the link elsewhere
    document.querySelectorAll('[data-share]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const url = btn.dataset.url || location.href;
        if (navigator.share) {
          navigator.share({ title: btn.dataset.title || document.title, url }).catch(() => {});
          return;
        }
        if (navigator.clipboard) {
          navigator.clipboard.writeText(url).then(() => {
            const label = btn.querySelector('span');
            if (!label) return;
            const old = label.textContent;
            label.textContent = btn.dataset.copied || 'OK';
            setTimeout(() => { label.textContent = old; }, 2000);
          }).catch(() => {});
        }
      });
    });
  });
})();
