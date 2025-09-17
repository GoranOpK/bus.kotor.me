// API endpoint za dohvat slotova
const apiEndpoint = "/api/readonly-admin/timeslots/reserved-today";

// Helper za parsiranje "HH:MM"
function parseTimeToMinutes(timeStr) {
  if (!timeStr) return 0;
  const [h, m] = timeStr.trim().split(":").map(Number);
  return h * 60 + (isNaN(m) ? 0 : m);
}

// Na osnovu server_time, izaberi aktivni slot i sledeća dva
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
      // Podrška i za "2025-06-20 11:09:24" i za "11:09:24"
      if (/^\d{4}-\d{2}-\d{2}/.test(serverTimeRaw)) {
        // Format "2025-06-20 11:09:24"
        serverTime = serverTimeRaw.split(" ")[1]?.substring(0,5) || "--:--";
      } else {
        // Format "11:09:24"
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

fetchDataAndRender();
setInterval(fetchDataAndRender, 5 * 60 * 1000);

// Funkcija za debounce
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

// Funkcija za prikaz greške
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

// Funkcija za učitavanje tipova vozila
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

// Pretraga rezervacija
const searchReservationsBtn = document.getElementById('search-reservations-btn');

// Search function
async function performSearch() {
  const date = document.getElementById('search-date').value;
  const user_name = document.getElementById('search-user_name').value.trim();
  const email = document.getElementById('search-email').value.trim();
  const vehicle_type_id = document.getElementById('search-vehicle_type_id').value;
  const license_plate = document.getElementById('search-license_plate').value.trim();

  // Proveri da li je unet bar jedan kriterijum
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

  console.log('Searching with params:', params.toString());
  
  try {
    const res = await fetch('/api/readonly-admin/search-reservations?' + params.toString(), {
      headers: { 
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
      }
    });
    
    console.log('Search response status:', res.status);
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const data = await res.json();
    console.log('Search results:', data);
    
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
      // Ako je samo jedna rezervacija, prikaži je direktno
      showReservationDetails(data[0]);
      resultsDiv.style.display = 'none';
    } else {
      // Ako ima više rezervacija, prikaži listu
      showReservationsList(data);
    }
  } catch (error) {
    console.error('Search error:', error);
    showError('Greška pri pretraživanju rezervacija!');
  }
}

// Debounced search function
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

// Prikaži listu rezervacija
function showReservationsList(reservations) {
  const resultsDiv = document.getElementById('search-results');
  let html = `<h4 style="color: #9e1321; margin-bottom: 15px;">Pronađene rezervacije: ${reservations.length}</h4><div style="max-height:300px; overflow-y:auto;">`;
  
  reservations.forEach(reservation => {
    const statusColor = reservation.status === 'storno' ? '#d9534f' : 
                       reservation.status === 'paid' ? '#5cb85c' : '#333';
    
    // Formatiraj datum u lokalnom formatu
    const reservationDate = new Date(reservation.reservation_date);
    const formattedDate = reservationDate.toLocaleDateString('sr-RS', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
    
    html += `
      <div style="border:1px solid #ddd; padding:12px; margin:8px 0; border-radius:4px; background:#f9f9f9;">
        <strong>ID: ${reservation.id}</strong> | 
        <span style="color:${statusColor}">Status: ${reservation.status || 'N/A'}</span><br>
        Datum: ${formattedDate} | 
        Ime: ${reservation.user_name}<br>
        Email: ${reservation.email} | 
        Tip vozila: ${reservation.vehicle_type ? reservation.vehicle_type.description_vehicle : 'N/A'} | 
        Registarska oznaka: ${reservation.license_plate}<br>
        Merchant ID: ${reservation.merchant_transaction_id || 'N/A'}
      </div>
    `;
  });
  
  html += '</div>';
  resultsDiv.innerHTML = html;
  resultsDiv.style.display = 'block';
}

// Izaberi rezervaciju iz liste
function selectReservation(reservationId) {
  const token = localStorage.getItem('readonly_token') || localStorage.getItem('admin_token');
  console.log('Loading reservation ID:', reservationId);
  fetch('/api/readonly-admin/reservation/' + reservationId, {
    headers: { 
      'Authorization': 'Bearer ' + token,
      'Accept': 'application/json'
    }
  })
    .then(res => {
      console.log('Reservation response status:', res.status);
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      return res.json();
    })
    .then(reservation => {
      console.log('Reservation loaded:', reservation);
      showReservationDetails(reservation);
      document.getElementById('search-results').style.display = 'none';
    })
    .catch(error => {
      console.error('Error loading reservation:', error);
      showError('Greška pri učitavanju rezervacije!');
    });
}

// Prikaži detalje rezervacije (samo uvid, bez mogućnosti izmene)
function showReservationDetails(reservation) {
  const detailsDiv = document.getElementById('reservation-details');
  
  // Formatiraj datum
  const reservationDate = new Date(reservation.reservation_date);
  const formattedDate = reservationDate.toLocaleDateString('sr-RS', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
  
  // Formatiraj vreme
  const dropOffTime = reservation.drop_off_time_slot ? reservation.drop_off_time_slot.time_slot : 'N/A';
  const pickUpTime = reservation.pick_up_time_slot ? reservation.pick_up_time_slot.time_slot : 'N/A';
  
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
          <strong>Drop-off termin:</strong> ${dropOffTime}<br>
          <strong>Pick-up termin:</strong> ${pickUpTime}<br>
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

// Inicijalizacija - učitaj tipove vozila kada se stranica učita
document.addEventListener('DOMContentLoaded', function() {
  loadVehicleTypes();
});