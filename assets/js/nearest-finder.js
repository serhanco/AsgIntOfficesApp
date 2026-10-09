/**
 * Nearest-office finder (home page: index.php)
 *
 * 1. On load, guess the visitor's country without asking for location
 *    (test ?cc= > Cloudflare country > device time zone > browser language region).
 * 2. Country with offices  -> its office (nearest one once location is shared).
 *    Türkiye               -> head office.
 *    No office there       -> "we are one click away" card with head office contacts.
 * 3. "Find nearest" asks for location; offices in the visitor's country come first.
 *    Refused or failed     -> short message, countdown to the world map (cancellable).
 * 4. Search box: offices by city or country name, any language.
 */
(function () {
  'use strict';

  const C = window.NF_CONFIG;
  if (!C) return;
  const T = C.t;
  const card = document.getElementById('nf-card');
  const offices = C.offices;
  const HQ_COUNTRY = C.hq.country_code;
  const FAR_KM = 2500;
  const COUNTDOWN_S = 5;

  // Time zones shared by more than one country we serve. The browser language picks
  // the country when it can; otherwise the card names no country and shows the region.
  const SHARED_ZONES = { 'Europe/Belgrade': { codes: ['rs', 'xk'], byLang: { sr: 'rs', sq: 'xk' } } };

  const state = {
    country: null,      // ISO code, lowercase
    region: null,       // [codes] when the time zone is shared
    position: null,     // {lat, lon} once location is shared
    selected: null,     // office picked from search
    locating: false,
    countdown: null,
  };

  // ---------- helpers ----------
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = (tpl, ...args) => { let i = 0; return tpl.replace(/%(\d+\$)?[sd]|%\.0f/g, (m, pos) => esc(pos ? args[parseInt(pos, 10) - 1] : args[i++])); };
  // Put trusted HTML (flag + bold country) where the translation has %s
  const fill = (tpl, html) => { const i = tpl.indexOf('%s'); return i < 0 ? esc(tpl) + ' ' + html : esc(tpl.slice(0, i)) + html + esc(tpl.slice(i + 2)); };
  const flag = (cc) => cc ? `<img src="https://flagcdn.com/w40/${esc(cc)}.png" alt="" class="nf-flag" width="20" height="15">` : '';

  let regionNames = null;
  try { regionNames = new Intl.DisplayNames([C.lang, 'en'], { type: 'region' }); } catch (e) { /* old browser */ }
  function countryName(cc) {
    if (!cc) return '';
    if (cc === 'xk') { const o = offices.find((x) => x.cc === cc); if (o) return o.country; }
    try { if (regionNames) { const n = regionNames.of(cc.toUpperCase()); if (n && n.toLowerCase() !== cc) return n; } } catch (e) { /* unknown code */ }
    const o = offices.find((x) => x.cc === cc);
    return o ? o.country : cc.toUpperCase();
  }

  function distanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371, rad = Math.PI / 180;
    const dLat = (lat2 - lat1) * rad, dLon = (lon2 - lon1) * rad;
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(lat1 * rad) * Math.cos(lat2 * rad) * Math.sin(dLon / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  const kmText = (d) => T.kmAway.replace('%.0f', Math.round(d).toLocaleString('en-US'));

  const officesIn = (codes) => offices.filter((o) => codes.includes(o.cc));
  function byDistance(list) {
    if (!state.position) return list.slice();
    return list
      .map((o) => ({ ...o, distance: distanceKm(state.position.lat, state.position.lon, o.lat, o.lon) }))
      .sort((a, b) => a.distance - b.distance);
  }

  // Rough position of a visitor whose country is only guessed: the time zone's city when
  // the zone belongs to that country, else the country's approximate centre
  function approxPoint() {
    if (!state.country) return null;
    let tz = '';
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { /* ignore */ }
    const zone = (window.TZ_COORDS || {})[tz];
    if (zone && (window.TZ_COUNTRIES || {})[tz] === state.country) return { lat: zone[0], lon: zone[1] };
    const c = (window.CC_CENTRES || {})[state.country];
    return c ? { lat: c[0], lon: c[1] } : null;
  }
  function nearestOffice() {
    const pt = state.position || approxPoint();
    if (!pt) return null;
    let best = null;
    offices.forEach((o) => {
      const d = distanceKm(pt.lat, pt.lon, o.lat, o.lon);
      if (!best || d < best.distance) best = { ...o, distance: d };
    });
    if (best) best.approx = !state.position;
    return best;
  }

  // ---------- country guess ----------
  function guessCountry() {
    const known = (cc) => cc && /^[a-z]{2}$/.test(cc);
    if (known(C.testCountry)) return { country: C.testCountry };
    if (known(C.serverCountry)) return { country: C.serverCountry };

    let tz = '';
    try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { /* ignore */ }
    const langs = navigator.languages || [navigator.language || ''];
    const langRegions = langs
      .map((l) => (l.split('-').find((p, i) => i > 0 && /^[A-Za-z]{2}$/.test(p)) || '').toLowerCase())
      .filter(Boolean);

    const shared = SHARED_ZONES[tz];
    if (shared) {
      const byRegion = langRegions.find((r) => shared.codes.includes(r));
      const byLang = langs.map((l) => shared.byLang[l.split('-')[0].toLowerCase()]).find(Boolean);
      const hit = byRegion || byLang;
      return hit ? { country: hit } : { region: shared.codes };
    }
    const fromTz = (window.TZ_COUNTRIES || {})[tz];
    if (fromTz) return { country: fromTz };
    if (langRegions.length) return { country: langRegions[0] };
    return {};
  }

  // ---------- rendering ----------
  function actionsHtml(o) {
    const tel = o.phone ? `tel:${o.phone.replace(/[^0-9+]/g, '')}` : '';
    const wa = o.whatsapp ? `https://wa.me/${o.whatsapp.replace(/[^0-9]/g, '')}` : '';
    const mail = o.email ? `mailto:${o.email}` : '';
    const route = o.lat != null
      ? `https://www.google.com/maps/dir/?api=1&destination=${o.lat},${o.lon}`
      : `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent('Acıbadem ' + o.address)}`;
    const btn = (href, icon, label, extra = '') => href
      ? `<a href="${esc(href)}" class="nf-action ${extra}"${href.startsWith('http') ? ' target="_blank" rel="noopener"' : ''}><i class="ph-fill ${icon}"></i><span>${esc(label)}</span></a>`
      : '';
    return `<div class="nf-actions">
      ${btn(tel, 'ph-phone', T.call)}
      ${btn(wa, 'ph-whatsapp-logo', T.whatsapp, 'nf-action--wa')}
      ${btn(mail, 'ph-envelope-simple', T.email)}
      ${btn(route, 'ph-navigation-arrow', T.route)}
    </div>`;
  }

  function contextLine() {
    if (state.selected) return '';
    if (state.position) return `<p class="nf-context"><i class="ph-fill ph-crosshair"></i>${esc(T.byLocation)}${state.country ? ` · ${flag(state.country)} ${esc(countryName(state.country))}` : ''}</p>`;
    if (state.country) return `<p class="nf-context"><i class="ph-fill ph-map-pin"></i><span>${fill(T.seemsIn, `<b class="nf-context__country">${flag(state.country)} ${esc(countryName(state.country))}</b>`)}</span></p>`;
    return '';
  }

  function refineBtn() {
    if (state.position || state.selected) return '';
    return `<button type="button" class="nf-refine" data-nf-locate>
      <span class="nf-radar nf-radar--dark" aria-hidden="true"><i class="ph-fill ph-crosshair"></i></span>
      <span data-nf-locate-label>${esc(T.refine)}</span></button>`;
  }

  function officeCard(main, others, label) {
    const dist = main.distance != null ? `<span class="nf-distance">${esc(kmText(main.distance))}</span>` : '';
    const chips = others.length ? `<div class="nf-others">
        <p class="nf-others__h">${esc(T.otherInCountry)}</p>
        <div class="nf-chips">${others.map((o) => `<button type="button" class="nf-chip" data-nf-pick="${esc(o.url)}">${esc(o.name)}${o.distance != null ? ` <small>${esc(Math.round(o.distance).toLocaleString('en-US'))} km</small>` : ''}</button>`).join('')}</div>
      </div>` : '';
    return `${contextLine()}
      <div class="nf-main">
        <div class="nf-main__info">
          <p class="nf-label">${esc(label)}</p>
          <p class="nf-country">${flag(main.cc)}<span>${esc(countryName(main.cc))}</span></p>
          <h2 class="nf-name">${esc(main.name)}</h2>
          <p class="nf-address"><i class="ph-fill ph-map-pin"></i><span dir="auto">${esc(main.address)}</span></p>
          ${dist}
        </div>
        ${actionsHtml(main)}
      </div>
      ${chips}
      <div class="nf-footer">
        ${refineBtn()}
        <a href="${esc(main.url)}" class="nf-details">${esc(T.details)} <i class="ph ph-arrow-right nf-arrow"></i></a>
      </div>`;
  }

  function hqOffice() {
    return { name: T.hqName, cc: HQ_COUNTRY, address: C.hq.address, phone: C.hq.phone, whatsapp: C.hq.whatsapp, email: C.hq.email };
  }

  function hqCard() {
    const hq = hqOffice();
    return `${contextLine()}
      <div class="nf-main">
        <div class="nf-main__info">
          <p class="nf-label">${esc(T.hqLabel)}</p>
          <p class="nf-country">${flag(hq.cc)}<span>${esc(countryName(hq.cc))}</span></p>
          <h2 class="nf-name">${esc(hq.name)}</h2>
          <p class="nf-address"><i class="ph-fill ph-map-pin"></i><span dir="auto">${esc(hq.address)}</span></p>
        </div>
        ${actionsHtml(hq)}
      </div>
      <div class="nf-footer">
        <a href="${esc(C.mapUrl)}" class="nf-details"><i class="ph-fill ph-globe-hemisphere-west"></i> ${esc(T.seeWorld)} <i class="ph ph-arrow-right nf-arrow"></i></a>
      </div>`;
  }

  function noOfficeCard() {
    const hq = hqOffice();
    const h = state.country ? fill(T.noOfficeH, `<span class="nf-nowrap">${esc(countryName(state.country))}</span>`) : esc(T.noOfficeRegionH);
    const near = nearestOffice();
    const physical = near ? `<div class="nf-near">
        <p class="nf-label">${esc(T.nearestLabel)}</p>
        <div class="nf-main">
          <div class="nf-main__info">
            <p class="nf-country">${flag(near.cc)}<span>${esc(countryName(near.cc))}</span></p>
            <h3 class="nf-name nf-name--sm">${esc(near.name)}</h3>
            <p class="nf-address"><i class="ph-fill ph-map-pin"></i><span dir="auto">${esc(near.address)}</span></p>
            <span class="nf-distance">${near.approx ? '≈ ' : ''}${esc(kmText(near.distance))}</span>
          </div>
          ${actionsHtml(near)}
        </div>
        <div class="nf-footer"><a href="${esc(near.url)}" class="nf-details">${esc(T.details)} <i class="ph ph-arrow-right nf-arrow"></i></a></div>
      </div>` : '';
    return `${contextLine()}
      <div class="nf-empty">
        <div class="nf-empty__icon" aria-hidden="true"><i class="ph-fill ph-globe-hemisphere-east"></i></div>
        <h2 class="nf-empty__h">${h}</h2>
        <p class="nf-empty__p">${esc(T.noOfficeP)}</p>
      </div>
      <div class="nf-hq">
        <div class="nf-hq__info">
          <p class="nf-hq__name">${flag(hq.cc)} ${esc(hq.name)}</p>
          <p class="nf-address"><i class="ph-fill ph-map-pin"></i><span dir="auto">${esc(hq.address)}</span></p>
        </div>
        ${actionsHtml(hq)}
      </div>
      ${physical}
      <div class="nf-links">
        <a href="${esc(C.mapUrl)}" class="nf-link nf-link--primary"><i class="ph-fill ph-globe-hemisphere-west"></i>${esc(T.seeWorld)}</a>
        <a href="${esc(C.listUrl)}" class="nf-link"><i class="ph-fill ph-list-dashes"></i>${esc(T.seeList)}</a>
      </div>
      <div class="nf-footer nf-footer--center">${refineBtn()}</div>`;
  }

  function unknownCard() {
    return `<div class="nf-empty">
        <div class="nf-empty__icon" aria-hidden="true"><i class="ph-fill ph-compass"></i></div>
        <h2 class="nf-empty__h">${esc(T.unknownH)}</h2>
        <p class="nf-empty__p">${esc(T.unknownP)}</p>
      </div>
      <div class="nf-footer nf-footer--center">${refineBtn()}</div>`;
  }

  // Tells other parts of the page (the home page events list) which country the visitor is in
  let lastCc = null;
  function announceCountry() {
    let cc = state.selected ? state.selected.cc : state.country;
    if (!cc && state.position) { const near = byDistance(offices)[0]; cc = near && near.distance <= FAR_KM ? near.cc : null; }
    if (cc && cc !== lastCc) {
      lastCc = cc;
      document.dispatchEvent(new CustomEvent('nf:country', { detail: { country: cc } }));
    }
  }

  function render() {
    announceCountry();
    let html;
    if (state.selected) {
      html = officeCard(state.selected, [], T.selectedLabel);
    } else if (state.country === HQ_COUNTRY && !officesIn([HQ_COUNTRY]).length) {
      html = hqCard();
    } else if (state.country && officesIn([state.country]).length) {
      const list = byDistance(officesIn([state.country]));
      html = officeCard(list[0], list.slice(1), T.nearestLabel);
    } else if (state.country) {
      html = noOfficeCard();
    } else if (state.position) {
      const near = byDistance(offices)[0];
      html = near && near.distance <= FAR_KM
        ? officeCard(near, byDistance(officesIn([near.cc])).filter((o) => o.url !== near.url), T.nearestLabel)
        : noOfficeCard();
    } else if (state.region && officesIn(state.region).length) {
      // Country with the most offices first
      const count = (cc) => officesIn([cc]).length;
      const list = officesIn(state.region).sort((a, b) => count(b.cc) - count(a.cc));
      html = officeCard(list[0], list.slice(1), T.regionLabel);
    } else {
      html = unknownCard();
    }
    card.innerHTML = html;
    card.classList.remove('nf-card--in');
    void card.offsetWidth; // restart the entrance animation
    card.classList.add('nf-card--in');
    setLocating(state.locating);
  }

  // ---------- location ----------
  function setLocating(on) {
    state.locating = on;
    document.querySelectorAll('[data-nf-locate]').forEach((b) => {
      b.classList.toggle('is-locating', on);
      b.disabled = on;
      const label = b.querySelector('[data-nf-locate-label]');
      if (label) {
        if (!label.dataset.idle) label.dataset.idle = label.textContent;
        label.textContent = on ? T.locating : label.dataset.idle;
      }
    });
  }

  function onPosition(lat, lon) {
    setLocating(false);
    state.position = { lat, lon };
    state.selected = null;
    render();
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function onLocationError() {
    setLocating(false);
    startCountdown();
  }

  function locate() {
    if (state.locating) return;
    stopCountdown();
    setLocating(true);
    if (C.testAt === 'deny') { setTimeout(onLocationError, 700); return; }
    if (Array.isArray(C.testAt)) { setTimeout(() => onPosition(C.testAt[0], C.testAt[1]), 900); return; }
    if (!navigator.geolocation) { onLocationError(); return; }
    // Permission already refused: don't ask again, go straight to the fallback
    if (navigator.permissions && navigator.permissions.query) {
      navigator.permissions.query({ name: 'geolocation' }).then(
        (st) => (st.state === 'denied' ? onLocationError() : requestPosition()),
        requestPosition
      );
      return;
    }
    requestPosition();
  }

  function requestPosition() {
    navigator.geolocation.getCurrentPosition(
      (p) => onPosition(p.coords.latitude, p.coords.longitude),
      onLocationError,
      // City-level accuracy is enough to pick an office; a recent cached fix answers instantly
      { enableHighAccuracy: false, timeout: 10000, maximumAge: 10 * 60 * 1000 }
    );
  }

  // ---------- countdown to the world map ----------
  function startCountdown() {
    stopCountdown();
    let left = COUNTDOWN_S;
    const box = document.createElement('div');
    box.className = 'nf-countdown';
    box.setAttribute('role', 'alert');
    box.innerHTML = `
      <div class="nf-countdown__ring" aria-hidden="true">
        <svg viewBox="0 0 36 36"><circle cx="18" cy="18" r="16" class="nf-countdown__track"/><circle cx="18" cy="18" r="16" class="nf-countdown__bar" style="animation-duration:${COUNTDOWN_S}s"/></svg>
        <span data-nf-count>${left}</span>
      </div>
      <div class="nf-countdown__text">
        <p class="nf-countdown__h">${esc(T.deniedH)}</p>
        <p class="nf-countdown__p" data-nf-count-text>${fmt(T.deniedP, left)}</p>
        <div class="nf-countdown__btns">
          <button type="button" class="nf-link" data-nf-stay>${esc(T.stay)}</button>
          <a href="${esc(C.mapUrl)}" class="nf-link nf-link--primary">${esc(T.goNow)}</a>
        </div>
      </div>`;
    card.prepend(box);
    const timer = setInterval(() => {
      left -= 1;
      if (left <= 0) { clearInterval(timer); window.location.href = C.mapUrl; return; }
      box.querySelector('[data-nf-count]').textContent = left;
      box.querySelector('[data-nf-count-text]').innerHTML = fmt(T.deniedP, left);
    }, 1000);
    state.countdown = { timer, box };
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function stopCountdown() {
    if (!state.countdown) return;
    clearInterval(state.countdown.timer);
    state.countdown.box.remove();
    state.countdown = null;
  }

  // ---------- search ----------
  const norm = (s) => String(s || '').toLocaleLowerCase('en-US')
    .replace(/ı/g, 'i').replace(/ə/g, 'e').replace(/ß/g, 'ss')
    .normalize('NFD').replace(/[̀-ͯ]/g, '').trim();

  const index = offices.map((o) => {
    const names = [o.name, o.country, countryName(o.cc), o.address];
    try { names.push(new Intl.DisplayNames(['en'], { type: 'region' }).of(o.cc.toUpperCase())); } catch (e) { /* ignore */ }
    return { office: o, hay: names.map(norm).join(' | '), name: norm(o.name) };
  });

  const input = document.getElementById('nf-search-input');
  const list = document.getElementById('nf-search-list');
  let results = [], active = -1;

  function showResults(q) {
    const n = norm(q);
    if (n.length < 2) { closeList(); return; }
    results = index
      .filter((x) => x.hay.includes(n))
      .sort((a, b) => (b.name.startsWith(n) - a.name.startsWith(n)) || a.office.name.localeCompare(b.office.name))
      .slice(0, 6)
      .map((x) => x.office);
    active = results.length ? 0 : -1;
    list.innerHTML = results.length
      ? results.map((o, i) => `<li role="option" id="nf-opt-${i}" class="nf-opt" data-i="${i}" aria-selected="${i === active}">${flag(o.cc)}<span class="nf-opt__name">${esc(o.name)}</span><span class="nf-opt__country">${esc(countryName(o.cc))}</span></li>`).join('')
      : `<li class="nf-opt nf-opt--none">${esc(T.searchNone)} <a href="${esc(C.mapUrl)}">${esc(T.seeWorld)}</a></li>`;
    list.hidden = false;
    input.setAttribute('aria-expanded', 'true');
    input.setAttribute('aria-activedescendant', active >= 0 ? `nf-opt-${active}` : '');
  }

  function closeList() {
    list.hidden = true;
    input.setAttribute('aria-expanded', 'false');
    input.removeAttribute('aria-activedescendant');
  }

  function choose(o) {
    if (!o) return;
    stopCountdown();
    state.selected = state.position ? { ...o, distance: distanceKm(state.position.lat, state.position.lon, o.lat, o.lon) } : o;
    input.value = o.name;
    closeList();
    render();
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function moveActive(step) {
    if (!results.length) return;
    active = (active + step + results.length) % results.length;
    list.querySelectorAll('.nf-opt').forEach((li, i) => li.setAttribute('aria-selected', String(i === active)));
    input.setAttribute('aria-activedescendant', `nf-opt-${active}`);
  }

  if (input) {
    input.addEventListener('focus', stopCountdown);
    input.addEventListener('input', () => { stopCountdown(); showResults(input.value); });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { e.preventDefault(); moveActive(1); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); moveActive(-1); }
      else if (e.key === 'Enter') { e.preventDefault(); choose(results[active]); }
      else if (e.key === 'Escape') closeList();
    });
    list.addEventListener('mousedown', (e) => {
      const li = e.target.closest('[data-i]');
      if (li) { e.preventDefault(); choose(results[+li.dataset.i]); }
    });
    document.addEventListener('click', (e) => { if (!e.target.closest('#nf-search')) closeList(); });
  }

  // ---------- wiring ----------
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-nf-locate]')) { locate(); return; }
    if (e.target.closest('[data-nf-stay]')) { stopCountdown(); input && input.focus(); return; }
    const pick = e.target.closest('[data-nf-pick]');
    if (pick) {
      const o = offices.find((x) => x.url === pick.dataset.nfPick);
      if (o) choose(o);
    }
  });

  Object.assign(state, guessCountry());
  render();
  // The menu link "En Yakın Ofis" opens the home page with ?find=1
  if (/[?&]find=1(&|$)/.test(window.location.search)) locate();
})();
