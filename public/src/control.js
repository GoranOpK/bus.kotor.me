// API endpoint za dohvat vremenskih slotova za readonly admin panel
const apiEndpoint = "/api/readonly-admin/timeslots/reserved-today";

// Helper funkcija: pretvara vrijeme "HH:MM" u minute
function parseTimeToMinutes(timeStr) {
  if (!timeStr) return 0;
  const [h, m] = timeStr.trim().split(":").map(Number);
  return h * 60 + (isNaN(m) ? 0 : m);
}

// Funkcija: vraća trenutno aktivni slot i naredna dva na osnovu vremena servera
function getCurrentAndNextTwoSlots(intervals, nowMinutes) {
  // Svaki interval ima polje .interval u formatu "hh:mm - hh:mm"
  const slots = intervals.map((it, idx) => {
    let intervalStr = it.interval || it.interval_name || it.name || "";
    let [from, to] = intervalStr.split(" - ");
    return {
      ...it,
      intervalStr,
      fromMins: parseTimeToMinutes(from),
      toMins: parseTimeToMinutes(to),
    };
  });
  let firstIdx = slots.findIndex(slot => slot.toMins > nowMinutes);
  if (firstIdx === -1) return [];
  let visibleSlots = slots.slice(firstIdx, firstIdx + 3)
    .filter(slot => slot.fromMins <= nowMinutes + 40);
  return visibleSlots;
}

// Prikazuje vremenske intervale i rezervacije u slotovima
function renderIntervals(intervals, nowMinutes) {
  const container = document.getElementById('intervals');
  const slotsToShow = getCurrentAndNextTwoSlots(intervals, nowMinutes);
  if (!slotsToShow.length) {
    container.innerHTML = `<div class="interval-block"><div class="interval-title">Nema rezervisanih vremenskih slotova za prikaz</div></div>`;
    return;
  }
  container.innerHTML = slotsToShow.map(interval => `
    <div class="interval-block">
      <div class="interval-title">${interval.intervalStr}</div>
      <table class="time-slots-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Tip vozila</th>
            <th>Registarska oznaka</th>
          </tr>
        </thead>
        <tbody>
          ${
            (interval.reservations && interval.reservations.length)
            ? interval.reservations.map((res, i) => `
                <tr>
                  <td>${i + 1}</td>
                  <td>${res.vehicle_type || res.vehicle_type_id || ""}</td>
                  <td>${res.license_plate || ""}</td>
                </tr>
              `).join('')
            : `<tr><td colspan="3" class="empty">Nema rezervacija</td></tr>`
          }
        </tbody>
      </table>
    </div>
  `).join('');
}

// Dohvata podatke sa API-a i prikazuje slotove
function fetchDataAndRender() {
  const token = localStorage.getItem('readonly_token') || localStorage.getItem('admin_token');
  fetch(apiEndpoint, {
    headers: token ? { 
      'Authorization': 'Bearer ' + token,
      'Accept': 'application/json'
    } : {
      'Accept': 'application/json'
    }
  })
    .then(r => {
      if (!r.ok) throw new Error("API status: " + r.status);
      return r.json();
    })
    .then(data => {
      let serverTimeRaw = data.server_time;
      let serverTime;
      // Podržava oba formata vremena: "2025-06-20 11:09:24" i "11:09:24"
      if (/^\d{4}-\d{2}-\d{2}/.test(serverTimeRaw)) {
        serverTime = serverTimeRaw.split(" ")[1]?.substring(0,5) || "--:--";
      } else {
        serverTime = serverTimeRaw.substring(0,5);
      }
      document.getElementById('serverTime').textContent = serverTime;

      const [hh, mm] = serverTime.split(":").map(Number);
      const nowMinutes = hh * 60 + mm;
      renderIntervals(data.data || data.intervals || [], nowMinutes);
    })
    .catch(err => {
      document.getElementById('intervals').innerHTML = `
        <div class="interval-block">
          <div class="interval-title" style="color:#d9534f;">Greška u učitavanju podataka!</div>
          <div style="color:#888;font-size:0.95em;word-break:break-all">${err && err.toString ? err.toString() : err}</div>
        </div>
      `;
      console.error("Fetch error:", err);
    });
}

// Automatski refresh podataka svakih 5 minuta
fetchDataAndRender();
setInterval(fetchDataAndRender, 5 * 60 * 1000);

// Funkcija za debounce (odlaganje poziva funkcije)
function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// Prikazuje grešku korisniku (toast poruka gore desno)
function showError(message, duration = 5000) {
  const errorDiv = document.createElement('div');
  errorDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #d9534f;
    color: white;
    padding: 15px 20px;
    border-radius: 5px;
    z-index: 1000;
    max-width: 300px;
    word-wrap: break-word;
  `;
  errorDiv.textContent = message;
  document.body.appendChild(errorDiv);

  setTimeout(() => {
    if (errorDiv.parentNode) {
      errorDiv.parentNode.removeChild(errorDiv);
    }
  }, duration);
}

// Učitava tipove vozila za select iz API-a
async function loadVehicleTypes() {
  try {
    const res = await fetch('/api/vehicle-types');
    if (!res.ok) throw new Error();
    const vehicleTypes = await res.json();

    const select = document.getElementById('search-vehicle_type_id');
    if (select) {
      select.innerHTML = '<option value="">Izaberi tip vozila</option>';
      vehicleTypes.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.description_vehicle;
        select.appendChild(option);
      });
    }
  } catch (error) {
    console.error('Greška pri učitavanju tipova vozila:', error);
  }
}

// Dugme za pretragu rezervacija
const searchReservationsBtn = document.getElementById('search-reservations-btn');

// Funkcija za pokretanje pretrage rezervacija
async function performSearch() {
  const date = document.getElementById('search-date').value;
  const user_name = document.getElementById('search-user_name').value.trim();
  const email = document.getElementById('search-email').value.trim();
  const vehicle_type_id = document.getElementById('search-vehicle_type_id').value;
  const license_plate = document.getElementById('search-license_plate').value.trim();

  // Bar jedan kriterijum mora biti unet
  if (!date && !user_name && !email && !vehicle_type_id && !license_plate) {
    showError('Unesite bar jedan kriterijum za pretragu!');
    return;
  }

  const token = localStorage.getItem('readonly_token') || localStorage.getItem('admin_token');
  const params = new URLSearchParams();
  if (date) params.append('date', date);
  if (user_name) params.append('user_name', user_name);
  if (email) params.append('email', email);
  if (vehicle_type_id) params.append('vehicle_type_id', vehicle_type_id);
  if (license_plate) params.append('license_plate', license_plate);

  try {
    const res = await fetch('/api/readonly-admin/search-reservations?' + params.toString(), {
      headers: { 
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
      }
    });

    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }

    const data = await res.json();

    const resultsDiv = document.getElementById('search-results');
    const detailsDiv = document.getElementById('reservation-details');

    detailsDiv.innerHTML = '';
    detailsDiv.style.display = 'none';

    if (!data || data.length === 0) {
      resultsDiv.innerHTML = '<p style="color: #666; text-align: center;">Nema rezervacija koje odgovaraju kriterijumu.</p>';
      resultsDiv.style.display = 'block';
      return;
    }

    if (data.length === 1) {
      // Ako je jedna rezervacija, prikazuje detalje
      showReservationDetails(data[0]);
      resultsDiv.style.display = 'none';
    } else {
      // Prikazuje listu rezervacija
      showReservationsList(data);
    }
  } catch (error) {
    console.error('Search error:', error);
    showError('Greška pri pretraživanju rezervacija!');
  }
}

// Debounce pretraga
const debouncedSearch = debounce(performSearch, 300);

if (searchReservationsBtn) {
  searchReservationsBtn.addEventListener('click', async function() {
    this.textContent = 'Pretražujem...';
    this.disabled = true;
    try {
      await performSearch();
    } finally {
      this.textContent = 'Pretraži';
      this.disabled = false;
    }
  });
}

// Prikazuje listu rezervacija (sa terminima ispod Merchant ID)
function showReservationsList(reservations) {
  const resultsDiv = document.getElementById('search-results');
  let html = `<h4 style="color: #9e1321; margin-bottom: 15px;">Pronađene rezervacije: ${reservations.length}</h4><div style="max-height:300px; overflow-y:auto;">`;

  reservations.forEach(reservation => {
    const statusColor = reservation.status === 'storno' ? '#d9534f' : 
                       reservation.status === 'paid' ? '#5cb85c' : '#333';

    const reservationDate = new Date(reservation.reservation_date);
    const formattedDate = reservationDate.toLocaleDateString('sr-RS', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });

    // Prikaz termina ispod Merchant ID
    let terminiHtml = '';
    if (reservation.pick_up_time_slot && reservation.pick_up_time_slot.time_slot) {
      terminiHtml += `<div><b>Pick-up termin:</b> ${reservation.pick_up_time_slot.time_slot}</div>`;
    }
    if (reservation.drop_off_time_slot && reservation.drop_off_time_slot.time_slot) {
      terminiHtml += `<div><b>Drop-off termin:</b> ${reservation.drop_off_time_slot.time_slot}</div>`;
    }
    if (reservation.terms && Array.isArray(reservation.terms) && reservation.terms.length) {
      terminiHtml += `<div><b>Termini rezervacije:</b> ${reservation.terms.map(t => t.time_slot || t).join(', ')}</div>`;
    }

    html += `
      <div style="border:1px solid #ddd; padding:12px; margin:8px 0; border-radius:4px; background:#f9f9f9;">
        <strong>ID: ${reservation.id}</strong> | 
        <span style="color:${statusColor}">Status: ${reservation.status || 'N/A'}</span><br>
        Datum: ${formattedDate} | 
        Ime: ${reservation.user_name}<br>
        Email: ${reservation.email} | 
        Tip vozila: ${reservation.vehicle_type ? reservation.vehicle_type.description_vehicle : 'N/A'} | 
        Registarska oznaka: ${reservation.license_plate}<br>
        Merchant ID: ${reservation.merchant_transaction_id || 'N/A'}<br>
        ${terminiHtml}
      </div>
    `;
  });

  html += '</div>';
  resultsDiv.innerHTML = html;
  resultsDiv.style.display = 'block';
}

// Dohvati rezervaciju po ID i prikaži detalje
function selectReservation(reservationId) {
  const token = localStorage.getItem('readonly_token') || localStorage.getItem('admin_token');
  fetch('/api/readonly-admin/reservation/' + reservationId, {
    headers: { 
      'Authorization': 'Bearer ' + token,
      'Accept': 'application/json'
    }
  })
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      return res.json();
    })
    .then(reservation => {
      showReservationDetails(reservation);
      document.getElementById('search-results').style.display = 'none';
    })
    .catch(error => {
      showError('Greška pri učitavanju rezervacije!');
    });
}

// Prikazuje detalje rezervacije (sa terminima)
function showReservationDetails(reservation) {
  const detailsDiv = document.getElementById('reservation-details');

  const reservationDate = new Date(reservation.reservation_date);
  const formattedDate = reservationDate.toLocaleDateString('sr-RS', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });

  const dropOffTime = reservation.drop_off_time_slot ? reservation.drop_off_time_slot.time_slot : 'N/A';
  const pickUpTime = reservation.pick_up_time_slot ? reservation.pick_up_time_slot.time_slot : 'N/A';

  let terminiHtml = '';
  if (reservation.pick_up_time_slot && reservation.pick_up_time_slot.time_slot) {
    terminiHtml += `<strong>Pick-up termin:</strong> ${reservation.pick_up_time_slot.time_slot}<br>`;
  }
  if (reservation.drop_off_time_slot && reservation.drop_off_time_slot.time_slot) {
    terminiHtml += `<strong>Drop-off termin:</strong> ${reservation.drop_off_time_slot.time_slot}<br>`;
  }
  if (reservation.terms && Array.isArray(reservation.terms) && reservation.terms.length) {
    terminiHtml += `<strong>Termini rezervacije:</strong> ${reservation.terms.map(t => t.time_slot || t).join(', ')}<br>`;
  }

  const statusColor = reservation.status === 'storno' ? '#d9534f' : 
                     reservation.status === 'paid' ? '#5cb85c' : '#333';

  const html = `
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6;">
      <h3 style="color: #9e1321; margin-top: 0;">Detalji rezervacije #${reservation.id}</h3>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
        <div>
          <strong>Status:</strong> <span style="color: ${statusColor};">${reservation.status || 'N/A'}</span><br>
          <strong>Datum:</strong> ${formattedDate}<br>
          <strong>Ime i prezime:</strong> ${reservation.user_name}<br>
          <strong>Email:</strong> ${reservation.email}<br>
          <strong>Telefon:</strong> ${reservation.phone || 'N/A'}<br>
          <strong>Država:</strong> ${reservation.country || 'N/A'}
        </div>
        <div>
          <strong>Tip vozila:</strong> ${reservation.vehicle_type ? reservation.vehicle_type.description_vehicle : 'N/A'}<br>
          <strong>Registarska oznaka:</strong> ${reservation.license_plate || 'N/A'}<br>
          ${terminiHtml}
          <strong>Merchant ID:</strong> ${reservation.merchant_transaction_id || 'N/A'}<br>
          <strong>Kreirano:</strong> ${new Date(reservation.created_at).toLocaleString('sr-RS')}
        </div>
      </div>
      <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
        <button onclick="document.getElementById('reservation-details').style.display='none'; document.getElementById('search-results').style.display='block';" 
                style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">
          Nazad na listu
        </button>
      </div>
    </div>
  `;

  detailsDiv.innerHTML = html;
  detailsDiv.style.display = 'block';
}

// Kada se stranica učita, učitaj tipove vozila
document.addEventListener('DOMContentLoaded', function() {
  loadVehicleTypes();
});