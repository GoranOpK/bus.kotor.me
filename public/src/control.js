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
    container.innerHTML = `<div class="interval-block"><div class="interval-title">Nema rezervisanih slotova </div></div>`;
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
    headers: token ? { 'Authorization': 'Bearer ' + token } : {}
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