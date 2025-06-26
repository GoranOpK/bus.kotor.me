// Blokiraj pristup admin panelu bez tokena - potpuno sakrij cijeli body dok nema tokena!
(function() {
  document.body.style.display = "none";
  const token = localStorage.getItem('admin_token');
  if (!token) {
    window.location.href = '/admin-login.html'; // zamijeni po potrebi!
    return;
  }
  document.body.style.display = "";
})();

// FullCalendar inicijalizacija
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'hr',
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
      },
      // events: '/api/calendar_events', // Ovdje možeš kasnije dodati dohvat događaja iz baze
    });
    calendar.render();
  }
});

// Funkcija za dohvat slotova iz baze
async function fetchSlotTimes() {
  try {
    // Očekuje se da vraća [{id: 1, time_slot: "08:00 - 08:20"}, ...]
    const res = await fetch('/api/timeslots');
    if (!res.ok) throw new Error();
    return await res.json();
  } catch (e) {
    alert('Ne mogu da preuzmem slotove iz baze!');
    return [];
  }
}

// Prikaz slotova za odabrani dan u admin panelu
document.getElementById('block-slot-date').addEventListener('change', async function() {
  const slotsList = document.getElementById('slots-checkbox-list');
  slotsList.innerHTML = '';
  const slotTimes = await fetchSlotTimes();

  // Prikazujemo naziv, ali value je ID
  slotTimes.forEach(slot => {
    const value = slot.id;  // <-- ovdje je bitno!
    const label = document.createElement('label');
    label.className = 'slot-checkbox-label';
    const cb = document.createElement('input');
    cb.type = 'checkbox';
    cb.value = value;
    label.appendChild(cb);
    label.appendChild(document.createTextNode(' ' + slot.time_slot));
    slotsList.appendChild(label);
  });
});

// Dohvati token iz localStorage (pretpostavlja se da je login već urađen i token sačuvan)
function getToken() {
  return localStorage.getItem('admin_token'); // promijeni ime po potrebi!
}

// Blokiranje slotova za određeni dan (Bearer token!)
document.getElementById('block-slots-btn').addEventListener('click', function() {
  const date = document.getElementById('block-slot-date').value;
  const slots = Array.from(document.querySelectorAll('#slots-checkbox-list input:checked'))
    .map(cb => parseInt(cb.value, 10));

  if (!date || slots.length === 0) {
    alert('Odaberite datum i bar jedan slot!');
    return;
  }

  const token = getToken();
  fetch('/api/admin/block_slots', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({date, slots})
  }).then(res => {
    if(res.ok) {
      alert('Slotovi su uspješno blokirani!');
    } else {
      alert('Greška prilikom blokiranja slotova.');
    }
  });
});

// Blokiraj cijeli dan (Bearer token!)
document.getElementById('block-day-btn').addEventListener('click', function() {
  const date = document.getElementById('block-day-date').value;
  if (!date) {
    alert('Odaberite datum!');
    return;
  }
  const token = getToken();
  fetch('/api/admin/block_day', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({date})
  }).then(res => {
    if(res.ok) {
      alert('Dan je uspješno blokiran!');
    } else {
      alert('Greška prilikom blokiranja dana.');
    }
  });
});

// Ažuriraj broj slotova (Bearer token!)
document.getElementById('update-slots-btn').addEventListener('click', function() {
  const numSlots = document.getElementById('num-slots').value;
  if (!numSlots || numSlots < 1) {
    alert('Unesite ispravan broj slotova!');
    return;
  }
  const token = getToken();
  fetch('/api/admin/update_slots', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({numSlots})
  }).then(res => {
    if(res.ok) {
      alert('Broj slotova je ažuriran!');
    } else {
      alert('Greška pri ažuriranju broja slotova.');
    }
  });
});

// Pronađi rezervaciju za izmjenu (Bearer token!)
document.getElementById('edit-reservation-btn').addEventListener('click', function() {
  const reservationId = document.getElementById('edit-reservation-id').value.trim();
  const formDiv = document.getElementById('edit-reservation-form');
  formDiv.innerHTML = '';
  formDiv.style.display = 'none';

  if (!reservationId) {
    alert('Unesite ID rezervacije ili email!');
    return;
  }
  const token = getToken();
  fetch('/api/admin/reservation/' + encodeURIComponent(reservationId), {
    headers: {
      'Authorization': 'Bearer ' + token
    }
  })
    .then(res => res.ok ? res.json() : Promise.reject())
    .then(data => {
      // Prikaz forme za izmjenu rezervacije (prilagodi prema tvojoj strukturi)
      const form = document.createElement('form');
      form.innerHTML = `
        <label>Datum: <input type="date" id="edit-date" value="${data.date || ''}"></label><br>
        <label>Slot: <input type="text" id="edit-slot" value="${data.slot || ''}"></label><br>
        <label>Ime kompanije: <input type="text" id="edit-company" value="${data.company || ''}"></label><br>
        <label>Email: <input type="email" id="edit-email" value="${data.email || ''}"></label><br>
        <button type="button" id="save-edit-reservation">Sačuvaj izmjene</button>
      `;
      formDiv.appendChild(form);
      formDiv.style.display = 'block';

      document.getElementById('save-edit-reservation').onclick = function() {
        const newData = {
          date: document.getElementById('edit-date').value,
          slot: document.getElementById('edit-slot').value,
          company: document.getElementById('edit-company').value,
          email: document.getElementById('edit-email').value
        };
        fetch('/api/admin/update_reservation/' + encodeURIComponent(reservationId), {
          method: 'POST',
          headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(newData)
        }).then(res => {
          if(res.ok) {
            alert('Rezervacija je ažurirana!');
            formDiv.style.display = 'none';
          } else {
            alert('Greška pri ažuriranju rezervacije.');
          }
        });
      };
    })
    .catch(() => {
      alert('Rezervacija nije pronađena!');
    });
});

// Pozovi rezervaciju bez plaćanja (Bearer token!)
document.getElementById('free-reservation-btn').addEventListener('click', function() {
  const reservationId = document.getElementById('free-reservation-id').value.trim();
  if (!reservationId) {
    alert('Unesite ID rezervacije ili email!');
    return;
  }
  const token = getToken();
  fetch('/api/admin/reservation_free/' + encodeURIComponent(reservationId), {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token
    }
  }).then(res => {
    if(res.ok) {
      alert('Rezervacija je pozvana bez plaćanja!');
    } else {
      alert('Greška pri pozivanju rezervacije.');
    }
  });
});