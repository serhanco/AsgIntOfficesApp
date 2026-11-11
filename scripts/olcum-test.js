/**
 * Measurement check: opens the main page types and clicks the main buttons, then checks what reached the
 * dataLayer (page facts and asg_track events). Nothing is sent to Google or Meta: those requests are blocked,
 * links are not followed and no form is submitted, so it is safe to run against the live site too.
 *
 *   node scripts/olcum-test.js                       (http://127.0.0.1:8080)
 *   node scripts/olcum-test.js https://acibadem.world
 *
 * Needs Playwright (npm i -D playwright, or a global install). The office "baku" must exist.
 * Run it after every change that touches buttons, pages or assets/js/track.js. Contract: docs/olcumleme-sozlesmesi.md
 */
const { chromium } = require('playwright');

const BASE = (process.argv[2] || 'http://127.0.0.1:8080').replace(/\/$/, '');
const OFFICE = 'baku';
let failed = 0;

function check(label, ok, extra) {
  if (!ok) failed++;
  console.log((ok ? '  ok   ' : '  FAIL ') + label + (ok || !extra ? '' : '\n         ' + JSON.stringify(extra)));
}

async function open(ctx, path) {
  const page = await ctx.newPage();
  // Nothing may reach the real GA4 / GTM / Meta from a test run
  await page.route(/googletagmanager\.com|google-analytics\.com|analytics\.google\.com|facebook\.(net|com)/, (r) => r.abort());
  await page.goto(BASE + path, { waitUntil: 'load' });
  // Links are measured but not followed
  await page.evaluate(() => window.addEventListener('click', (e) => { if (e.target.closest('a')) e.preventDefault(); }));
  await page.waitForTimeout(300);
  return page;
}

const pageFacts = (page) => page.evaluate(() => (window.dataLayer || []).find((x) => x && x.page_template));
const tracks = (page) => page.evaluate(() => (window.dataLayer || []).filter((x) => x && x.event === 'asg_track').map((x) => x.track));

/** Click the first element matching sel (even if hidden, e.g. inside a closed menu) and return the event it made. */
async function clickTrack(page, sel) {
  const before = (await tracks(page)).length;
  const found = await page.evaluate((s) => { const el = document.querySelector(s); if (!el) return false; el.click(); return true; }, sel);
  if (!found) return { missing: sel };
  await page.waitForTimeout(50);
  const all = await tracks(page);
  return all.length > before ? all[all.length - 1] : { none: sel };
}

function expect(label, ev, want) {
  const bad = Object.keys(want).filter((k) => ev[k] !== want[k]);
  check(label, !bad.length, { got: ev, expected: want });
}

(async () => {
  const browser = await chromium.launch();
  const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
  // Accept cookies so visitors in consent countries get the same page
  await ctx.addCookies([{ name: 'asg_consent', value: 'yes', url: BASE }]);

  console.log('Page facts');
  for (const [path, tpl] of [['/', 'home'], ['/offices', 'offices'], ['/office/' + OFFICE, 'office'], ['/events', 'events'],
    ['/map', 'map'], ['/nearest', 'nearest'], ['/contracted-institutions', 'institutions'], ['/sitemap', 'sitemap'], ['/olcum-test-404', 'not_found']]) {
    const page = await open(ctx, path);
    const f = await pageFacts(page);
    check(path + ' page_template=' + tpl, f && f.page_template === tpl && !!f.page_lang && f.contract_version, f);
    const loads = await page.evaluate(() => document.querySelectorAll('script[src*="googletagmanager.com"]').length);
    check(path + ' at most one Google tag', loads <= 1, { loads });
    await page.close();
  }
  {
    const page = await open(ctx, '/office/' + OFFICE + '?lang=ar');
    const f = await pageFacts(page);
    check('arabic page: page_lang=ar, page_dir=rtl, office_slug', f.page_lang === 'ar' && f.page_dir === 'rtl' && f.office_slug === OFFICE, f);
    await page.close();
  }

  console.log('Home page (finder for Azerbaijan via ?cc=az)');
  {
    const page = await open(ctx, '/?cc=az');
    const all = await tracks(page);
    const res = all.find((t) => t.name === 'finder_result');
    check('finder_result with an office', res && res.office_country === 'AZ' && !!res.office_slug && res.method === 'test', res);
    expect('finder phone', await clickTrack(page, '#nf-card .nf-actions a[href^="tel:"]'), { name: 'click_phone', cta_owner: 'office', cta_position: 'finder_card', office_country: 'AZ' });
    expect('finder directions', await clickTrack(page, '#nf-card .nf-actions a[href*="google.com/maps"]'), { name: 'click_directions', cta_position: 'finder_card' });
    expect('finder office details', await clickTrack(page, '#nf-card a.nf-details'), { name: 'office_select', cta_position: 'finder_card' });
    expect('footer HQ WhatsApp', await clickTrack(page, 'footer a[href*="wa.me"]'), { name: 'click_whatsapp', cta_owner: 'hq', cta_position: 'footer' });
    expect('footer HQ e-mail', await clickTrack(page, 'footer a[href^="mailto:"]'), { name: 'click_email', cta_owner: 'hq', cta_position: 'footer' });
    expect('mobile menu HQ phone', await clickTrack(page, '[data-drawer] a[href^="tel:"]'), { name: 'click_phone', cta_owner: 'hq', cta_position: 'mobile_menu' });
    expect('language menu', await clickTrack(page, '[data-lang-menu] a[hreflang="ar"]'), { name: 'language_change', method: 'ar', cta_position: 'header' });
    await page.close();
  }
  {
    const page = await open(ctx, '/?cc=us');
    const res = (await tracks(page)).find((t) => t.name === 'finder_result');
    check('finder_result no office (US)', res && res.cta_id === 'no_office', res);
    expect('no-office card HQ phone', await clickTrack(page, '#nf-card .nf-panel--hq a[href^="tel:"]'), { name: 'click_phone', cta_owner: 'hq' });
    await page.close();
  }

  console.log('Office page');
  {
    const page = await open(ctx, '/office/' + OFFICE);
    const office = { office_slug: OFFICE, cta_owner: 'office' };
    expect('hero phone', await clickTrack(page, '[data-track-area="office_hero"] a[href^="tel:"]'), { name: 'click_phone', cta_position: 'office_hero', ...office });
    expect('hero directions', await clickTrack(page, '[data-track-area="office_hero"] a[href*="google.com/maps"]'), { name: 'click_directions', cta_position: 'office_hero', ...office });
    expect('contact card e-mail', await clickTrack(page, '[data-track-area="office_contact"] a[href^="mailto:"]'), { name: 'click_email', cta_position: 'office_contact', ...office });
    expect('dock phone', await clickTrack(page, '[data-dock] a[href^="tel:"]'), { name: 'click_phone', cta_position: 'office_dock', ...office });
    expect('second opinion card', await clickTrack(page, 'a[href*="acibademinternational.com/second-opinion"]'), { name: 'click_consultation', cta_id: 'second-opinion', office_slug: OFFICE });
    expect('partner card', await clickTrack(page, '[data-track-area="office_cards"] a[href*="partner.acibademinternational.com"]'), { name: 'click_partner', office_slug: OFFICE });
    const other = await clickTrack(page, '[data-track-area="office_related"] a[href*="/office/"]');
    if (!other.missing) check('related office link points to the other office', other.name === 'office_select' && other.office_slug && other.office_slug !== OFFICE, other);
    await page.close();
  }

  console.log('Events');
  {
    const page = await open(ctx, '/events');
    const ev = await clickTrack(page, '#view-list a[data-event-slug]');
    if (ev.missing) console.log('  skip  no event card on /events');
    else check('event card -> event_select with type and audience', ev.name === 'event_select' || ev.name === 'click_outbound' ? !!(ev.event_slug && ev.event_type && ev.event_audience) : false, ev);
    expect('type filter', await clickTrack(page, '[data-type-filter]:not([data-type-filter=""])'), { name: 'filter_use', method: 'type' });
    expect('request form WhatsApp', await clickTrack(page, '[data-req-wa]'), { name: 'click_whatsapp', cta_owner: 'hq', cta_position: 'event_request', cta_id: 'event_request' });
    const slug = await page.evaluate(() => { const a = document.querySelector('#view-list a[data-event-slug]:not([data-ev-external])'); return a ? a.getAttribute('data-event-slug') : ''; });
    await page.close();
    if (slug) {
      const p2 = await open(ctx, '/event/' + slug);
      const f = await pageFacts(p2);
      check('event page facts', f.page_template === 'event' && f.event_slug === slug && !!f.event_audience, f);
      expect('share button', await clickTrack(p2, '[data-share]'), { name: 'share', event_slug: slug });
      const cal = await clickTrack(p2, 'a[href$=".ics"]');
      if (!cal.missing) expect('add to calendar', cal, { name: 'calendar_add', event_slug: slug });
      await p2.close();
    }
  }

  await browser.close();
  console.log(failed ? `\n${failed} check(s) failed` : '\nAll checks passed');
  process.exit(failed ? 1 : 0);
})();
