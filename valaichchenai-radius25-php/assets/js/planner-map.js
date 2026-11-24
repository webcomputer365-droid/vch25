(() => {
  const container = document.getElementById('planner-map');
  if (!container || !window.L) return;

  let places = [];
  let home = { lat: 7.926135, lng: 81.527874, label: 'Valaichchenai town centre' };
  try {
    places = JSON.parse(container.dataset.places || '[]');
    home = JSON.parse(container.dataset.home || '{}');
  } catch {
    container.textContent = 'The map could not load its stop list.';
    return;
  }

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[c]);

  const map = window.L.map(container, { scrollWheelZoom: false }).setView([home.lat || 7.926135, home.lng || 81.527874], 12);
  window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
  }).addTo(map);

  const points = [];

  if (Number.isFinite(home.lat) && Number.isFinite(home.lng)) {
    window.L.circleMarker([home.lat, home.lng], {
      radius: 9, color: '#fffef9', weight: 3,
      fillColor: '#d99a4c', fillOpacity: 1,
    }).addTo(map).bindPopup(`<strong>Start</strong><br>${escapeHtml(home.label || 'Home')}`);
    points.push([home.lat, home.lng]);
  }

  places.forEach((place, index) => {
    if (!Number.isFinite(place.latitude) || !Number.isFinite(place.longitude)) return;
    const point = [place.latitude, place.longitude];
    points.push(point);
    const marker = window.L.circleMarker(point, {
      radius: 8, color: '#fffef9', weight: 3,
      fillColor: '#345943', fillOpacity: 1,
    }).addTo(map);
    marker.bindPopup(
      `<strong>Stop ${index + 1}: ${escapeHtml(place.name)}</strong><br><span>${escapeHtml(place.category)}</span>`
    );
  });

    if (points.length > 1) {
    map.fitBounds(window.L.latLngBounds(points).pad(0.12));
  } else if (points.length === 1) {
    map.setView(points[0], 14);
  }
})();