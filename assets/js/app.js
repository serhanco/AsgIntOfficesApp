/**
 * Acıbadem International Offices App
 * Client-side JavaScript
 */

// === Mobile Menu Toggle ===
function toggleMobileMenu() {
  const menu = document.getElementById('mobile-menu');
  const burger = document.getElementById('burger-btn');
  if (menu) {
    menu.classList.toggle('hidden');
    burger?.classList.toggle('is-open');
  }
}

// === Toast Notification System ===
function showToast(message, duration = 3000) {
  // Remove existing toast
  const existing = document.getElementById('toast');
  if (existing) existing.remove();
  
  const toast = document.createElement('div');
  toast.id = 'toast';
  toast.className = 'fixed top-24 left-1/2 transform -translate-x-1/2 bg-gray-900 text-white px-6 py-3 rounded-full text-sm font-medium shadow-2xl z-[9999] w-max max-w-[90%] text-center transition-all duration-300 opacity-0';
  toast.textContent = message;
  document.body.appendChild(toast);
  
  requestAnimationFrame(() => {
    toast.classList.replace('opacity-0', 'opacity-100');
  });
  
  setTimeout(() => {
    toast.classList.replace('opacity-100', 'opacity-0');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// === Haversine Distance Calculator ===
function calculateDistance(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = (lat2 - lat1) * Math.PI / 180;
  const dLon = (lon2 - lon1) * Math.PI / 180;
  const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon/2) * Math.sin(dLon/2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
  return R * c;
}

// === Geolocation: Find Nearest Office ===
function findNearestOffice(offices, callback) {
  if (!navigator.geolocation) {
    showToast('Geolocation is not supported by your browser.');
    callback(null);
    return;
  }
  
  navigator.geolocation.getCurrentPosition(
    (position) => {
      const userLat = position.coords.latitude;
      const userLon = position.coords.longitude;
      
      let nearest = null;
      let minDist = Infinity;
      
      offices.forEach(office => {
        const dist = calculateDistance(userLat, userLon, office.lat, office.lon);
        if (dist < minDist) {
          minDist = dist;
          nearest = { ...office, distance: dist };
        }
      });
      
      callback(nearest);
    },
    (error) => {
      console.warn('Location error:', error);
      showToast('Location access denied. Please select an office from the list.');
      callback(null);
    },
    { enableHighAccuracy: true, timeout: 8000 }
  );
}

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
    singleOffice: false
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
        html: '<div style="width:40px;height:40px;background:#0c2d74;color:white;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(12,45,116,0.4);border:3px solid white;transition:transform 0.2s;cursor:pointer"><i class="ph-fill ph-map-pin" style="font-size:20px"></i></div>',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
      });
      
      const marker = L.marker([office.lat, office.lon], { icon: customIcon }).addTo(map);
      
      if (!config.singleOffice) {
        marker.bindTooltip(
          '<div class="text-center" style="min-width:120px">' +
            '<div style="font-weight:700;font-size:14px;color:#0c2d74;margin-bottom:4px">' + office.display_name + '</div>' +
            '<div style="font-size:11px;color:#6b7280">' + office.country + '</div>' +
            '<div style="margin-top:8px;font-size:10px;font-weight:600;color:#0c2d74;background:#E6F0FA;padding:4px 8px;border-radius:6px">View details</div>' +
          '</div>',
          { direction: 'top', offset: [0, -40], className: 'custom-tooltip' }
        );
        
        marker.on('click', () => {
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
