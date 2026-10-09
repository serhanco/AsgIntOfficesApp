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
