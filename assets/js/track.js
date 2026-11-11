/**
 * Measurement: turns clicks and other visitor actions into events with fixed names and fields.
 * Contract (names, fields, how to mark new buttons): docs/olcumleme-sozlesmesi.md
 *
 * Every event goes to the dataLayer as {event: 'asg_track', track: {name, ...fields}} (read by GTM).
 * In direct GA4 mode it also goes to GA4 with gtag('event'), and to the Meta Pixel when that is loaded here.
 *
 * Clicks are recognised in two ways:
 *  1. data-track="click_whatsapp" (and data-track-owner / data-track-id) on the link or button: exact.
 *  2. Its address: tel:, wa.me, mailto:, Google Maps, .ics, other sites, /office/x, /event/x. A button nobody
 *     marked is still counted, with cta_position "unknown" when no data-track-area is around it.
 * Context comes from the nearest element around the link that carries it:
 *  data-track-area (where on the page), data-track-owner (office / hq / event_location / event_contact),
 *  data-office-id / -slug / -country, data-event-id / -slug / -type / -audience.
 */
(function () {
  'use strict';
  var CFG = window.ASG_TRACK || { mode: '', hq: {} };
  var FIELDS = ['cta_type', 'cta_owner', 'cta_position', 'cta_id', 'office_id', 'office_slug', 'office_country',
    'event_id', 'event_slug', 'event_type', 'event_audience', 'link_domain', 'method'];
  // Meta standard events for our key events (used only when the Pixel is loaded directly, not through GTM)
  var META = {
    click_whatsapp: 'Contact', click_phone: 'Contact', click_email: 'Contact', click_directions: 'Contact',
    event_request_submit: 'Lead', click_consultation: 'Lead', event_registration: 'CompleteRegistration'
  };

  function clean(params) {
    var out = {};
    FIELDS.forEach(function (k) {
      var v = params[k];
      out[k] = v === undefined || v === '' ? null : (typeof v === 'number' ? v : String(v).slice(0, 100));
    });
    return out;
  }

  function send(name, params) {
    if (!/^[a-z][a-z0-9_]{1,39}$/.test(name)) return;
    var p = clean(params || {});
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ track: null }); // fields of the previous event must not leak into this one
    window.dataLayer.push({ event: 'asg_track', track: Object.assign({ name: name }, p) });
    if (CFG.mode === 'ga4' && typeof window.gtag === 'function') {
      var g = {};
      Object.keys(p).forEach(function (k) { if (p[k] !== null) g[k] = p[k]; });
      window.gtag('event', name, g);
    }
    if (CFG.pixel && typeof window.fbq === 'function' && META[name]) {
      window.fbq('track', META[name], { content_name: name, content_category: p.cta_owner || '' });
    }
  }

  // ---------- recognising a click ----------
  var digits = function (s) { return String(s || '').replace(/\D/g, ''); };
  var attr = function (el, sel, name) { var n = el.closest(sel); return n ? n.getAttribute(name) : null; };

  function isHq(type, url) {
    var hq = CFG.hq || {};
    if (type === 'phone') return !!hq.phone && digits(url.pathname || url.href) === hq.phone;
    if (type === 'whatsapp') {
      var num = /wa\.me$/i.test(url.hostname) ? url.pathname : (url.searchParams.get('phone') || '');
      return !!hq.whatsapp && digits(num) === hq.whatsapp;
    }
    if (type === 'email') return !!hq.email && decodeURIComponent(url.pathname).toLowerCase() === hq.email;
    return false;
  }

  /** {name, cta_type, ...} for a link/button, or null when the click is not measured. */
  function classify(el) {
    var href = el.getAttribute('data-track-href') || el.getAttribute('href') || '';
    var url = null;
    try { url = href ? new URL(href, location.href) : null; } catch (e) { url = null; }
    var r = { name: el.getAttribute('data-track') || '', cta_id: el.getAttribute('data-track-id') || '' };
    var proto = url ? url.protocol : '';
    var host = url ? url.hostname.replace(/^www\./, '') : '';
    var path = url ? url.pathname : '';

    if (el.hasAttribute('data-share')) { r.name = r.name || 'share'; r.cta_type = 'share'; r.method = navigator.share ? 'native' : 'copy'; }
    else if (proto === 'tel:') { r.cta_type = 'phone'; r.name = r.name || 'click_phone'; }
    else if (proto === 'mailto:') { r.cta_type = 'email'; r.name = r.name || 'click_email'; }
    else if (proto === 'whatsapp:' || /(^|\.)wa\.me$|whatsapp\.com$/i.test(host)) { r.cta_type = 'whatsapp'; r.name = r.name || 'click_whatsapp'; }
    else if (/(^|\.)google\.[a-z.]+$/i.test(host) && /^\/maps/.test(path) || /^maps\.google\./i.test(host) || /^maps\.app\.goo\.gl$/i.test(host)) { r.cta_type = 'directions'; r.name = r.name || 'click_directions'; }
    else if (/\.ics$/i.test(path)) { r.cta_type = 'calendar'; r.name = r.name || 'calendar_add'; }
    else if (url && host && url.host !== location.host && /^https?:$/.test(proto)) {
      r.link_domain = host;
      if (/^partner\.acibademinternational\.com$/i.test(host)) { r.cta_type = 'partner'; r.name = r.name || 'click_partner'; }
      else if (/^acibademinternational\.com$/i.test(host) && /^\/(second-opinion|online-consultation|contact)(\/|$)/i.test(path)) {
        r.cta_type = 'consultation'; r.name = r.name || 'click_consultation'; r.cta_id = r.cta_id || path.split('/')[1];
      } else { r.cta_type = 'outbound'; r.name = r.name || 'click_outbound'; }
    } else if (url && url.host === location.host) {
      var m;
      if (el.closest('[data-lang-menu]') && url.searchParams.get('lang')) {
        r.cta_type = 'language'; r.name = r.name || 'language_change'; r.method = url.searchParams.get('lang');
      } else if ((m = path.match(/\/office\/([a-z0-9-]+)\/?$/))) {
        r.cta_type = 'office_link'; r.name = r.name || 'office_select'; r.link_office = m[1];
      } else if ((m = path.match(/\/event\/([a-z0-9-]+)\/?$/))) {
        r.cta_type = 'event_link'; r.name = r.name || 'event_select'; r.link_event = m[1];
      }
    }
    if (!r.name) return null;
    if (!r.cta_type) r.cta_type = el.getAttribute('data-track-type') || 'other';

    r.cta_position = attr(el, '[data-track-area]', 'data-track-area') || 'unknown';
    r.cta_owner = attr(el, '[data-track-owner]', 'data-track-owner') || (url && isHq(r.cta_type, url) ? 'hq' : '');

    // The office / event the button belongs to. A link to another office or event page is about that one.
    var o = el.closest('[data-office-slug]');
    if (r.link_office && (!o || o.getAttribute('data-office-slug') !== r.link_office)) {
      r.office_slug = r.link_office;
    } else if (o) {
      r.office_id = +o.getAttribute('data-office-id') || null;
      r.office_slug = o.getAttribute('data-office-slug');
      r.office_country = o.getAttribute('data-office-country');
    }
    var ev = el.closest('[data-event-slug]');
    if (r.link_event && (!ev || ev.getAttribute('data-event-slug') !== r.link_event)) {
      r.event_slug = r.link_event;
    } else if (ev) {
      r.event_id = +ev.getAttribute('data-event-id') || null;
      r.event_slug = ev.getAttribute('data-event-slug');
      r.event_type = ev.getAttribute('data-event-type');
      r.event_audience = ev.getAttribute('data-event-audience');
    }
    return r;
  }

  // Capture phase: runs before the page's own handlers and before the browser follows the link
  document.addEventListener('click', function (e) {
    if (!e.target || !e.target.closest) return;
    var el = e.target.closest('a[href], [data-track], [data-share]');
    if (!el || el.getAttribute('data-track') === 'off') return;
    var r;
    try { r = classify(el); } catch (err) { return; }
    if (r) send(r.name, r);
  }, true);

  // Events from page scripts (finder, forms, filters): asgTrack('finder_result', {method: 'gps', ...})
  var queued = window.asgTrackQ || [];
  window.asgTrack = function (name, params) { try { send(name, params || {}); } catch (e) { /* never break the page */ } };
  queued.forEach(function (q) { window.asgTrack(q[0], q[1]); });
  window.asgTrackQ = [];
})();
