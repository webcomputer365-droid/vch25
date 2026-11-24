(() => {
  const container = document.getElementById('daytrail-map');
  if (!container || !window.L) return;

  let places = [];
  try {
    places = JSON.parse(container.dataset.places || '[]');
  } catch {
    container.textContent = 'The map could not load its place list.';
    return;
  }

  const map = window.L.map(container, { scrollWheelZoom: false }).setView([7.9167, 81.5333], 11);
  window.L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap contributors</a>',
  }).addTo(map);

  const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (c) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[c]);

  const points = [];
  places.forEach((place) => {
    if (!Number.isFinite(place.latitude) || !Number.isFinite(place.longitude)) return;
    const point = [place.latitude, place.longitude];
    points.push(point);
    const marker = window.L.circleMarker(point, {
      radius: 8,
      color: '#fffef9',
      weight: 3,
      fillColor: '#c8312b',
      fillOpacity: 1,
    }).addTo(map);
    marker.bindPopup(
      `<strong>${escapeHtml(place.name)}</strong><br><span>${escapeHtml(place.category)}</span><br><a href="${escapeHtml(place.url)}">View place details</a>`
    );
  });

    if (points.length > 1) {
    map.fitBounds(window.L.latLngBounds(points).pad(0.12));
  } else if (points.length === 1) {
    map.setView(points[0], 14);
  }
})();