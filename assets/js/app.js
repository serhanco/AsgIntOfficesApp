/**
 * Acıbadem International Offices App
 * Client-side JavaScript
 */

// Translated UI strings (set in includes/v2/header.php)
const I18N = Object.assign({
  viewDetails: 'View details'
}, window.APP_I18N || {});

// === Language Switcher Dropdown (desktop) ===
document.addEventListener('click', function(e) {
  const toggle = e.target.closest('[data-lang-toggle]');
  document.querySelectorAll('[data-lang-menu]').forEach(menu => {
    const btn = menu.querySelector('[data-lang-toggle]');
    const panel = menu.querySelector('[data-lang-panel]');
    const open = toggle === btn ? panel.classList.contains('hidden') : (menu.contains(e.target) && !panel.classList.contains('hidden'));
    panel.classList.toggle('hidden', !open);
    btn.setAttribute('aria-expanded', open);
  });
});
document.addEventListener('keydown', function(e) {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('[data-lang-menu]').forEach(menu => {
    menu.querySelector('[data-lang-panel]').classList.add('hidden');
    menu.querySelector('[data-lang-toggle]').setAttribute('aria-expanded', false);
  });
});

// === Country Jump (Offices List Page) ===
function scrollToCountry(countryId) {
  if (!countryId) return;
  const element = document.getElementById('country-' + countryId);
  
  // Remove existing highlights
  document.querySelectorAll('.country-group').forEach(el => {
    el.classList.remove('country-highlight');
  });
  
  if (element) {
    element.scrollIntoView({ behavior: 'smooth', block: 'start' });
    element.classList.add('country-highlight');
    
    // Remove highlight after 3 seconds
    setTimeout(() => {
      element.classList.remove('country-highlight');
    }, 3000);
  }
}

// === Office Search Filter (Offices List Page) ===
function filterOffices(query) {
  const searchTerm = query.toLowerCase().trim();
  const groups = document.querySelectorAll('.country-group');
  let visibleCount = 0;
  
  groups.forEach(group => {
    const cards = group.querySelectorAll('.office-card');
    let groupVisible = false;
    
    cards.forEach(card => {
      const name = (card.dataset.name || '').toLowerCase();
      const country = (card.dataset.country || '').toLowerCase();
      const address = (card.dataset.address || '').toLowerCase();
      const match = !searchTerm || name.includes(searchTerm) || country.includes(searchTerm) || address.includes(searchTerm);
      
      card.style.display = match ? '' : 'none';
      if (match) { groupVisible = true; visibleCount++; }
    });
    
    group.style.display = groupVisible ? '' : 'none';
  });
  
  // Show/hide no results message
  const noResults = document.getElementById('no-results');
  if (noResults) {
    noResults.style.display = visibleCount === 0 ? 'block' : 'none';
  }
}

// === Initialize Leaflet Map ===
function initMap(containerId, offices, options = {}) {
  const defaults = {
    center: [41.0082, 28.9784],
    zoom: 4,
    singleOffice: false,
    markerHtml: ''   // custom pin markup
  };
  const config = { ...defaults, ...options };
  
  const map = L.map(containerId, {
    zoomControl: false,
    scrollWheelZoom: !config.singleOffice
  }).setView(config.center, config.zoom);
  
  map.attributionControl.setPrefix(false);
  
  L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap',
    maxZoom: 19
  }).addTo(map);
  
  L.control.zoom({ position: 'bottomright' }).addTo(map);
  
  // Add markers
  offices.forEach(office => {
    if (office.lat && office.lon) {
      const customIcon = L.divIcon({
        className: 'bg-transparent',
        html: config.markerHtml || '<div style="width:40px;height:40px;background:#0c2d74;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(12,45,116,0.4);border:3px solid white;transition:transform 0.2s;cursor:pointer"><i class="ph-fill ph-map-pin" style="font-size:20px"></i></div>',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
      });
      
      const marker = L.marker([office.lat, office.lon], { icon: customIcon }).addTo(map);
      
      if (!config.singleOffice) {
        marker.bindTooltip(
          '<div class="text-center" style="min-width:120px">' +
            '<div style="font-weight:700;font-size:14px;color:#0c2d74;margin-bottom:4px">' + office.display_name + '</div>' +
            '<div style="font-size:11px;color:#6b7280">' + office.country + '</div>' +
            '<div style="margin-top:8px;font-size:10px;font-weight:600;color:#0c2d74;background:#E6F0FA;padding:4px 8px;border-radius:6px">' + I18N.viewDetails + '</div>' +
          '</div>',
          { direction: 'top', offset: [0, -40], className: 'custom-tooltip' }
        );
        
        marker.on('click', () => {
          const slug = (String(office.url || '').match(/\/office\/([a-z0-9-]+)/) || [])[1];
          if (window.asgTrack) window.asgTrack('office_select', { cta_type: 'office_link', cta_position: 'map_marker', office_slug: slug, office_country: office.country_code });
          window.location.href = office.url;
        });
      }
    }
  });
  
  // Fix rendering in hidden containers
  setTimeout(() => map.invalidateSize(), 300);
  
  return map;
}

// === Document Ready ===
document.addEventListener('DOMContentLoaded', function() {
  // Search input debounce
  const searchInput = document.getElementById('office-search');
  if (searchInput) {
    let debounceTimer;
    searchInput.addEventListener('input', function() {
      clearTimeout(debounceTimer);
      debounceTimer = setTimeout(() => filterOffices(this.value), 200);
    });
  }
});
