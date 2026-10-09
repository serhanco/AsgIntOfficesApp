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
  });
})();
