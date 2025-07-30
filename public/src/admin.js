console.log('admin.js loaded');
// Blokiraj pristup admin panelu bez tokena - potpuno sakrij cijeli body dok nema tokena!
try {
(function() {
    console.log('Checking admin token...');
  document.body.style.display = "none";
  const token = localStorage.getItem('admin_token');
    console.log('Token found:', token ? 'yes' : 'no');
  if (!token) {
      console.log('No token, redirecting to login...');
      window.location.href = '/admin-login.html';
    return;
  }
    console.log('Token valid, showing admin panel...');
  document.body.style.display = "";
})();
} catch (error) {
  console.error('Error in admin.js initialization:', error);
}

// FullCalendar inicijalizacija
document.addEventListener('DOMContentLoaded', function() {
  // Postavi min datum na današnji datum za sve date input polja
  const today = new Date().toISOString().split('T')[0];
  const dateInputs = ['block-slot-date', 'block-day-date', 'free-reservation_date'];
  dateInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.setAttribute('min', today);
    }
  });

  // Učitaj blokirane dane i termine
  loadBlockedDays();

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

// Funkcija za dohvat blokiranih termina za određeni datum
async function fetchBlockedSlots(date) {
  try {
    const token = localStorage.getItem('admin_token');
    const res = await fetch(`/api/blocked-slots/${date}`, {
      headers: {
        'Authorization': 'Bearer ' + token
      }
    });
    if (!res.ok) throw new Error();
    return await res.json();
  } catch (e) {
    console.log('Nema blokiranih termina za ovaj datum ili greška pri dohvatu');
    return [];
  }
}

// Funkcija za dohvat svih blokiranih dana
async function fetchAllBlockedDays() {
  try {
    const token = getToken();
    const res = await fetch('/api/blocked-days', {
      headers: {
        'Authorization': 'Bearer ' + token
      }
    });
    if (!res.ok) throw new Error();
    return await res.json();
  } catch (e) {
    console.log('Greška pri dohvatu blokiranih dana:', e);
    return [];
  }
}

// Funkcija za učitavanje i prikaz blokiranih dana
async function loadBlockedDays() {
  const blockedDaysList = document.getElementById('blocked-days-list');
  if (!blockedDaysList) return;

  try {
    // Prikaži loading state
    blockedDaysList.innerHTML = '<div style="text-align:center; color:#666;">Učitavanje blokiranih dana...</div>';
    
    console.log('Učitavanje blokiranih dana...');
    const blockedDays = await fetchAllBlockedDays();
    console.log('Dohvaćeni blokirani dani:', blockedDays);
    
    // Filtriraj prošle dane
    const todayDateInt = parseInt(new Date().toISOString().split('T')[0].replace(/-/g, '')); // npr. 20250728
    const filteredBlockedDays = blockedDays.filter(day => parseInt(day.date) >= todayDateInt);

    if (filteredBlockedDays.length === 0) {
      blockedDaysList.innerHTML = '<div style="text-align:center; color:#666;">Nema blokiranih dana</div>';
      return;
    }

    // Optimizovano generisanje HTML-a
    const htmlFragments = [];
    console.log('Generisanje HTML-a za blokirane dane...');
    
    filteredBlockedDays.forEach(day => {
      const date = day.date;
      const formattedDate = `${date.substring(6, 8)}.${date.substring(4, 6)}.${date.substring(0, 4)}`;
      const fullDate = `${date.substring(0, 4)}-${date.substring(4, 6)}-${date.substring(6, 8)}`;
      
      console.log('Obrađujem dan:', formattedDate, 'is_fully_blocked:', day.is_fully_blocked);
      
      const dayFragment = document.createElement('div');
      dayFragment.style.cssText = 'margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px;';
      
      const dateElement = document.createElement('strong');
      dateElement.style.color = '#d32f2f';
      dateElement.textContent = formattedDate;
      dayFragment.appendChild(dateElement);
      
      if (day.is_fully_blocked) {
        const blockedText = document.createElement('div');
        blockedText.style.cssText = 'color: #d32f2f; margin-top: 5px;';
        blockedText.textContent = '🛑 ČITAV DAN BLOKIRAN';
        dayFragment.appendChild(blockedText);
        
        const deblockBtn = document.createElement('button');
        deblockBtn.className = 'deblock-day-btn';
        deblockBtn.dataset.date = fullDate;
        deblockBtn.style.cssText = 'background:#4caf50;color:#fff;border:none;padding:5px 10px;border-radius:3px;margin-top:5px;cursor:pointer;';
        deblockBtn.textContent = 'Odblokiraj dan';
        dayFragment.appendChild(deblockBtn);
        
        console.log('Dodao dugme za odblokiranje dana za:', formattedDate);
      } else if (day.blocked_slots && day.blocked_slots.length > 0) {
        // Proveri da li je današnji dan
        const today = new Date().toISOString().split('T')[0];
        const isToday = fullDate === today;
        const currentTime = new Date();
        
        // Filtriraj prošle termine za današnji dan
        const validSlots = day.blocked_slots.filter(slot => {
          if (!isToday) return true; // Za ostale dane prikaži sve termine
          
          // Za današnji dan, proveri da li je termin prošao
          const slotTimeStr = slot.time_range.split(' - ')[0]; // Uzmi početak termina
          const slotTime = new Date();
          const [hours, minutes] = slotTimeStr.split(':').map(Number);
          slotTime.setHours(hours, minutes, 0, 0);
          
          // Dozvoli termin ako je trenutni ili budući (sa 5 minuta tolerancije)
          const tolerance = 5 * 60 * 1000; // 5 minuta u milisekundama
          return (slotTime.getTime() + tolerance) >= currentTime.getTime();
        });
        
        if (validSlots.length > 0) {
          const slotsContainer = document.createElement('div');
          slotsContainer.style.marginTop = '5px';
          
          validSlots.forEach(slot => {
            const slotElement = document.createElement('div');
            slotElement.style.cssText = 'color: #ff9800; margin-left: 10px;';
            slotElement.textContent = `• ${slot.time_range}`;
            slotsContainer.appendChild(slotElement);
          });
          
          dayFragment.appendChild(slotsContainer);
          
          const deblockBtn = document.createElement('button');
          deblockBtn.className = 'deblock-slots-btn';
          deblockBtn.dataset.fullDate = fullDate;
          deblockBtn.dataset.tableDate = day.date;
          deblockBtn.style.cssText = 'background:#ff9800;color:#fff;border:none;padding:5px 10px;border-radius:3px;margin-top:5px;cursor:pointer;';
          deblockBtn.textContent = 'Odblokiraj termine';
          dayFragment.appendChild(deblockBtn);
          
          console.log('Dodao dugme za odblokiranje termina za:', formattedDate);
        }
      }
      
      htmlFragments.push(dayFragment);
    });

    // Batch DOM update
    blockedDaysList.innerHTML = '';
    htmlFragments.forEach(fragment => {
      blockedDaysList.appendChild(fragment);
    });
    
    // Dodaj event listener-e za dugmad
    setTimeout(() => {
      const buttons = blockedDaysList.querySelectorAll('button');
      console.log('Pronađena dugmad:', buttons.length);
      
      // Event listener za odblokiranje dana
      const deblockDayBtns = blockedDaysList.querySelectorAll('.deblock-day-btn');
      console.log('Pronađena dugmad za odblokiranje dana:', deblockDayBtns.length);
      deblockDayBtns.forEach((btn, index) => {
        console.log(`Dodajem event listener za dugme ${index + 1}:`, btn.textContent);
        // Ukloni postojeće event listener-e
        btn.replaceWith(btn.cloneNode(true));
        const newBtn = blockedDaysList.querySelectorAll('.deblock-day-btn')[index];
        newBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const date = this.getAttribute('data-date');
          console.log('Kliknuto dugme za odblokiranje dana:', date);
          deblockDay(date);
        });
      });
      
      // Event listener za odblokiranje termina
      const deblockSlotsBtns = blockedDaysList.querySelectorAll('.deblock-slots-btn');
      console.log('Pronađena dugmad za odblokiranje termina:', deblockSlotsBtns.length);
      deblockSlotsBtns.forEach((btn, index) => {
        console.log(`Dodajem event listener za dugme ${index + 1}:`, btn.textContent);
        // Ukloni postojeće event listener-e
        btn.replaceWith(btn.cloneNode(true));
        const newBtn = blockedDaysList.querySelectorAll('.deblock-slots-btn')[index];
        newBtn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          const fullDate = this.getAttribute('data-full-date');
          const tableDate = this.getAttribute('data-table-date');
          console.log('Kliknuto dugme za odblokiranje termina:', fullDate, tableDate);
          showDeblockSlotsModal(fullDate, tableDate);
        });
      });
    }, 100);
    
  } catch (error) {
    console.error('Greška pri učitavanju blokiranih dana:', error);
    blockedDaysList.innerHTML = '<div style="text-align:center; color:#d32f2f;">Greška pri učitavanju</div>';
    showError('Greška pri učitavanju blokiranih dana');
  }
}

// Funkcija za osvežavanje liste blokiranih dana
async function refreshBlockedDays() {
  await loadBlockedDays();
}

// Funkcija za odblokiranje dana
window.deblockDay = async function(date) {
  console.log('deblockDay pozvan sa datumom:', date);
  if (!confirm('Da li ste sigurni da želite da odblokirate cijeli dan?')) {
    return;
  }

  try {
    const token = getToken();
    const res = await fetch('/api/admin/deblock_day', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ date })
    });

    if (res.ok) {
      alert('Dan je uspješno odblokiran!');
      await refreshBlockedDays();
    } else {
      const error = await res.json();
      alert('Greška prilikom odblokiranja dana: ' + (error.error || 'Nepoznata greška'));
    }
  } catch (error) {
    console.error('Greška pri odblokiranju dana:', error);
    alert('Greška pri odblokiranju dana');
  }
}

// Funkcija za prikaz modala za odblokiranje termina
window.showDeblockSlotsModal = async function(fullDate, tableDate) {
  console.log('showDeblockSlotsModal pozvan sa:', fullDate, tableDate);
  try {
    const blockedSlots = await fetchBlockedSlots(fullDate);
    const slotTimes = await fetchSlotTimes();
    
    console.log('Dohvaćeni blokirani termini:', blockedSlots);
    console.log('Dohvaćeni slotovi:', slotTimes);

    // Kreiraj modal
    const modal = document.createElement('div');
    modal.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      z-index: 1000;
    `;
    
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
      background: white;
      padding: 20px;
      border-radius: 10px;
      max-width: 500px;
      max-height: 80vh;
      overflow-y: auto;
    `;
    
    modalContent.innerHTML = `
      <h3>Odblokiraj termine za ${fullDate.split('-').reverse().join('.')}</h3>
      <div id="deblock-slots-list" style="margin: 15px 0;"></div>
      <div style="text-align: right;">
        <button id="cancel-deblock-btn" style="background:#666;color:#fff;border:none;padding:8px 15px;border-radius:3px;margin-right:10px;">Otkaži</button>
        <button id="confirm-deblock-btn" data-full-date="${fullDate}" data-table-date="${tableDate}" style="background:#4caf50;color:#fff;border:none;padding:8px 15px;border-radius:3px;">Odblokiraj izabrane</button>
      </div>
    `;
    
    modal.appendChild(modalContent);
    document.body.appendChild(modal);
    
    // Popuni listu termina
    const slotsList = modalContent.querySelector('#deblock-slots-list');
    let slotsHtml = '';
    
    // Proveri da li ima blokiranih termina
    const blockedSlotIds = blockedSlots.map(blocked => blocked.time_slot_id);
    console.log('ID-jevi blokiranih termina:', blockedSlotIds);
    
    if (blockedSlotIds.length === 0) {
      slotsHtml = '<p style="color: #666; text-align: center;">Nema blokiranih termina za ovaj datum.</p>';
    } else {
      slotTimes.forEach(slot => {
        const isBlocked = blockedSlotIds.includes(slot.id);
        if (isBlocked) {
          slotsHtml += `
            <label style="display: block; margin: 5px 0; padding: 8px; background: #ffe6e6; border-radius: 3px; border: 1px solid #ffcdd2;">
              <input type="checkbox" value="${slot.id}" style="margin-right: 8px; transform: scale(1.2);">
              <span style="font-weight: bold; color: #d32f2f;">${slot.time_slot}</span>
            </label>
          `;
        }
      });
    }
    
    slotsList.innerHTML = slotsHtml;
    
    // Dodaj event listener-e za dugmad u modalu
    const cancelBtn = modalContent.querySelector('#cancel-deblock-btn');
    const confirmBtn = modalContent.querySelector('#confirm-deblock-btn');
    
    cancelBtn.addEventListener('click', function() {
      console.log('Kliknuto Otkaži dugme');
      closeDeblockModal();
    });
    
    confirmBtn.addEventListener('click', function() {
      console.log('Kliknuto Odblokiraj izabrane dugme');
      const fullDate = this.getAttribute('data-full-date');
      const tableDate = this.getAttribute('data-table-date');
      deblockSelectedSlots(fullDate, tableDate);
    });
    
  } catch (error) {
    console.error('Greška pri prikazu modala:', error);
    alert('Greška pri prikazu modala');
  }
}

// Funkcija za zatvaranje modala
window.closeDeblockModal = function() {
  const modal = document.querySelector('div[style*="position: fixed"]');
  if (modal) {
    modal.remove();
  }
}

// Funkcija za odblokiranje izabranih termina
window.deblockSelectedSlots = async function(fullDate, tableDate) {
  const selectedSlots = Array.from(document.querySelectorAll('#deblock-slots-list input:checked'))
    .map(cb => parseInt(cb.value, 10));
  
  if (selectedSlots.length === 0) {
    alert('Izaberite bar jedan termin za odblokiranje!');
    return;
  }

  try {
    const token = getToken();
    const res = await fetch('/api/admin/deblock_slots', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ 
        date: fullDate,
        slots: selectedSlots 
      })
    });

    if (res.ok) {
      alert('Termini su uspješno odblokirani!');
      closeDeblockModal();
      await refreshBlockedDays();
    } else {
      const error = await res.json();
      alert('Greška prilikom odblokiranja termina: ' + (error.error || 'Nepoznata greška'));
    }
  } catch (error) {
    console.error('Greška pri odblokiranju termina:', error);
    alert('Greška pri odblokiranju termina');
  }
}

// Prikaz slotova za odabrani dan u admin panelu
const blockSlotDate = document.getElementById('block-slot-date');
if (blockSlotDate) blockSlotDate.addEventListener('change', async function() {
  const slotsList = document.getElementById('slots-checkbox-list');
  slotsList.innerHTML = '';
  const slotTimes = await fetchSlotTimes();
  
  // Dohvati blokirane termine za odabrani datum
  const date = this.value;
  const blockedSlots = await fetchBlockedSlots(date);
  
  // Osveži listu blokiranih dana kada se promeni datum
  setTimeout(async () => {
    await refreshBlockedDays();
  }, 500);
  
  // Proveri da li je dan potpuno blokiran
  const allBlocked = slotTimes.every(slot => 
    blockedSlots.some(blocked => blocked.time_slot_id === slot.id && blocked.available === 0)
  );
  
  if (allBlocked && blockedSlots.length > 0) {
    slotsList.innerHTML = '<div style="color:#d32f2f; padding:10px; background:#ffe6e6; border:1px solid #d32f2f; border-radius:5px;"><strong>⚠️ DAN JE POTPUNO BLOKIRAN</strong><br>Nema dostupnih termina za blokiranje.</div>';
    return;
  }

  // Kreiraj tabelu umesto checkbox lista
  const table = document.createElement('table');
  table.style.width = '100%';
  table.style.borderCollapse = 'collapse';
  table.style.marginTop = '10px';
  table.style.border = '1px solid #ddd';
  
  // Dodaj header
  const thead = document.createElement('thead');
  const headerRow = document.createElement('tr');
  headerRow.style.backgroundColor = '#f5f5f5';
  
  const headerCell1 = document.createElement('th');
  headerCell1.textContent = 'Termin';
  headerCell1.style.padding = '8px';
  headerCell1.style.border = '1px solid #ddd';
  headerCell1.style.textAlign = 'left';
  
  const headerCell2 = document.createElement('th');
  headerCell2.textContent = 'Status';
  headerCell2.style.padding = '8px';
  headerCell2.style.border = '1px solid #ddd';
  headerCell2.style.textAlign = 'center';
  headerCell2.style.width = '100px';
  
  headerRow.appendChild(headerCell1);
  headerRow.appendChild(headerCell2);
  thead.appendChild(headerRow);
  table.appendChild(thead);
  
  // Dodaj body
  const tbody = document.createElement('tbody');

  slotTimes.forEach(slot => {
    // Proveri da li je termin već blokiran
    const isAlreadyBlocked = blockedSlots.some(blocked => 
      blocked.time_slot_id === slot.id && blocked.available === 0
    );
    
    // Proveri da li je termin prošao (za današnji datum)
    let isPastTime = false;
    if (date === new Date().toISOString().split('T')[0]) {
      const now = new Date();
      const currentTime = now.getHours() * 60 + now.getMinutes(); // trenutno vreme u minutima
      
      // Parsiraj vreme termina (npr. "11:00 - 11:20" -> 11:00)
      const timeMatch = slot.time_slot.match(/(\d{1,2}):(\d{2})/);
      if (timeMatch) {
        const slotHour = parseInt(timeMatch[1]);
        const slotMinute = parseInt(timeMatch[2]);
        const slotTime = slotHour * 60 + slotMinute; // vreme termina u minutima
        
        // Ako je termin prošao (sa 20 minuta tolerancije)
        if (slotTime + 20 < currentTime) {
          isPastTime = true;
        }
      }
    }
    
    // Ako je termin blokiran ILI prošao, preskoči ga
    if (isAlreadyBlocked || isPastTime) {
      return;
    }
    
    const row = document.createElement('tr');
    row.style.cursor = 'pointer';
    row.style.transition = 'background-color 0.2s';
    
    // Termin cell
    const timeCell = document.createElement('td');
    timeCell.textContent = slot.time_slot;
    timeCell.style.padding = '8px';
    timeCell.style.border = '1px solid #ddd';
    
    // Status cell
    const statusCell = document.createElement('td');
    statusCell.style.padding = '8px';
    statusCell.style.border = '1px solid #ddd';
    statusCell.style.textAlign = 'center';
    
    const checkbox = document.createElement('input');
    checkbox.type = 'checkbox';
    checkbox.value = slot.id;
    checkbox.style.margin = '0';
    checkbox.style.transform = 'scale(1.2)';
    
    // Dodaj event listener za checkbox
    checkbox.addEventListener('change', function() {
      if (this.checked) {
        row.classList.add('blocked');
        row.style.backgroundColor = '#ffe6e6';
        timeCell.style.color = '#d32f2f';
        timeCell.style.fontWeight = 'bold';
        timeCell.style.textDecoration = 'line-through';
      } else {
        row.classList.remove('blocked');
        row.style.backgroundColor = '';
        timeCell.style.color = '';
        timeCell.style.fontWeight = '';
        timeCell.style.textDecoration = '';
      }
    });
    
    statusCell.appendChild(checkbox);
    
    row.appendChild(timeCell);
    row.appendChild(statusCell);
    tbody.appendChild(row);
  });
  
  table.appendChild(tbody);
  slotsList.appendChild(table);
});

// Dohvati token iz localStorage
function getToken() {
  return localStorage.getItem('admin_token');
}

// Blokiranje slotova za određeni dan
const blockSlotsBtn = document.getElementById('block-slots-btn');
if (blockSlotsBtn) blockSlotsBtn.addEventListener('click', async function() {
  console.log('Kliknuto dugme "Blokiraj izabrane termine"');
  
  const date = document.getElementById('block-slot-date').value;
  const slots = Array.from(document.querySelectorAll('#slots-checkbox-list input:checked'))
    .map(cb => parseInt(cb.value, 10));

  console.log('Odabrani datum:', date);
  console.log('Odabrani termini:', slots);

  if (!date || slots.length === 0) {
    alert('Odaberite datum i bar jedan termin!');
    return;
  }

  const token = getToken();
  console.log('Token:', token ? 'postoji' : 'ne postoji');
  
  // Prvo proveri postojeće rezervacije
  console.log('Provjeravam postojeće rezervacije za datum:', date, 'i termine:', slots);
  try {
    console.log('Šaljem zahtjev na /api/admin/check-existing-reservations');
    const checkRes = await fetch('/api/admin/check-existing-reservations', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({date})
    });
    
    console.log('checkRes.status:', checkRes.status);
    console.log('checkRes.ok:', checkRes.ok);
    
    if (checkRes.ok) {
      const checkData = await checkRes.json();
      console.log('checkData:', checkData);
      
      // Proveri da li je checkData array ili objekat sa reservations svojstvom
      const reservations = Array.isArray(checkData) ? checkData : (checkData.reservations || []);
      console.log('reservations:', reservations);
      
      // Loguj rezervacije u Laravel log
      fetch('/api/admin/log-reservations', {
        method: 'POST',
        headers: {
          'Authorization': 'Bearer ' + token,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          date: date,
          slots: slots,
          reservations: reservations
        })
      }).catch(e => console.log('Greška pri logovanju:', e));
      
              // Filtriraj rezervacije koje se poklapaju sa odabranim terminima
        const conflictingReservations = reservations.filter(reservation => {
          // Konvertuj vremena u slot ID-jeve
          const dropOffTime = reservation.drop_off_time; // "12:20 - 12:40"
          const pickUpTime = reservation.pick_up_time;   // "18:00 - 18:20"
          
          // Izvuci slot ID-jeve iz vremena
          const dropOffSlotId = getSlotIdFromTime(dropOffTime);
          const pickUpSlotId = getSlotIdFromTime(pickUpTime);
          
          console.log(`Rezervacija ${reservation.id}: drop_off=${dropOffTime} (slot ${dropOffSlotId}), pick_up=${pickUpTime} (slot ${pickUpSlotId})`);
          
          // Proveri da li se bilo koji od odabranih termina poklapa sa rezervacijom
          return slots.some(slotId => 
            (slotId >= dropOffSlotId && slotId <= pickUpSlotId) ||
            (dropOffSlotId >= Math.min(...slots) && pickUpSlotId <= Math.max(...slots))
          );
        });
      
      console.log('conflictingReservations:', conflictingReservations);
      
      if (conflictingReservations.length > 0) {
        console.log('Pronađeni konflikti, prikazujem modal');
        // Prikaži modal sa rezervacijama
        showReservationsModal(conflictingReservations, date);
        return;
      } else {
        console.log('Nema konflikta, nastavljam sa blokiranjem');
      }
    } else {
      console.log('checkRes nije ok, nastavljam sa blokiranjem');
    }
  } catch (error) {
    console.error('Greška pri provjeri rezervacija:', error);
  }

  // Ako nema konflikta, nastavi sa blokiranjem
  fetch('/api/admin/block_slots', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({date, slots})
  }).then(async res => {
    if(res.ok) {
      alert('Termini su uspješno blokirani!');
      
      try {
        // Mali delay da se server ažurira
        await new Promise(resolve => setTimeout(resolve, 1000));
        
        console.log('Osvježavam listu blokiranih dana...');
        
        // Osveži listu blokiranih dana
        await refreshBlockedDays();
        
        console.log('Lista osvježena, zatvaram modal...');
        
        // Zatvori modal sa terminima - očisti listu
        const slotsList = document.getElementById('slots-checkbox-list');
        if (slotsList) {
          slotsList.innerHTML = '';
          console.log('Modal zatvoren - lista očišćena');
        }
        
        // Resetuj datum na prazan
        const blockSlotDate = document.getElementById('block-slot-date');
        if (blockSlotDate) {
          blockSlotDate.value = '';
          console.log('Datum resetovan');
        }
        
        console.log('Sve završeno!');
        
        // Osveži stranicu da se sve ažurira
        setTimeout(() => {
          window.location.reload();
        }, 2000);
        
      } catch (error) {
        console.error('Greška pri osvježavanju:', error);
        alert('Termini su blokirani, ali greška pri osvježavanju liste.');
      }
      
    } else {
      alert('Greška prilikom blokiranja termina.');
    }
  });
});

// Blokiraj ceo dan
const blockDayBtn = document.getElementById('block-day-btn');
const blockDayDate = document.getElementById('block-day-date');
const dayStatus = document.getElementById('day-status');

// Event listener za prikaz statusa dana
if (blockDayDate) blockDayDate.addEventListener('change', async function() {
  const date = this.value;
  if (!date) {
    dayStatus.style.display = 'none';
    return;
  }

  try {
    // Formatiraj datum u YYYYMMDD format za API poziv
    const formattedDate = date.replace(/-/g, '');
    const blockedSlots = await fetchBlockedSlots(formattedDate);
    const slotTimes = await fetchSlotTimes();
    
    // Proveri da li su svi termini blokirani
    const allBlocked = slotTimes.every(slot => 
      blockedSlots.some(blocked => blocked.time_slot_id === slot.id && blocked.available === 0)
    );
    
    if (allBlocked && blockedSlots.length > 0) {
      dayStatus.innerHTML = '<strong style="color:#d32f2f;">⚠️ DAN JE BLOKIRAN</strong><br>Svi termini za ovaj dan su blokirani.';
      dayStatus.style.backgroundColor = '#ffe6e6';
      dayStatus.style.border = '1px solid #d32f2f';
      dayStatus.style.display = 'block';
    } else if (blockedSlots.length > 0) {
      const blockedCount = blockedSlots.length;
      const totalCount = slotTimes.length;
      dayStatus.innerHTML = `<strong style="color:#ff9800;">⚠️ DJELIMIČNO BLOKIRANO</strong><br>${blockedCount} od ${totalCount} termina je blokirano.`;
      dayStatus.style.backgroundColor = '#fff3e0';
      dayStatus.style.border = '1px solid #ff9800';
      dayStatus.style.display = 'block';
    } else {
      dayStatus.innerHTML = '<strong style="color:#4caf50;">✓ DAN JE SLOBODAN</strong><br>Nijedan termin nije blokiran.';
      dayStatus.style.backgroundColor = '#e8f5e8';
      dayStatus.style.border = '1px solid #4caf50';
      dayStatus.style.display = 'block';
    }
  } catch (error) {
    console.error('Greška pri provjeri statusa dana:', error);
    dayStatus.innerHTML = '<strong style="color:#ff9800;">⚠️ GREŠKA</strong><br>Ne mogu da provjerim status dana.';
    dayStatus.style.backgroundColor = '#fff3e0';
    dayStatus.style.border = '1px solid #ff9800';
    dayStatus.style.display = 'block';
  }
});

if (blockDayBtn) blockDayBtn.addEventListener('click', async function() {
  const date = document.getElementById('block-day-date').value;
  if (!date) {
    alert('Odaberite datum!');
    return;
  }
  
  // Proveri da li je dan već potpuno blokiran
  try {
    const formattedDate = date.replace(/-/g, '');
    const blockedSlots = await fetchBlockedSlots(formattedDate);
    const slotTimes = await fetchSlotTimes();
    
    const allBlocked = slotTimes.every(slot => 
      blockedSlots.some(blocked => blocked.time_slot_id === slot.id && blocked.available === 0)
    );
    
    if (allBlocked && blockedSlots.length > 0) {
      alert('Dan je već potpuno blokiran!');
      return;
    }
  } catch (error) {
    console.error('Greška pri provjeri statusa dana:', error);
  }
  
  // PROVERA POSTOJEĆIH REZERVACIJA
  try {
  const token = getToken();
    const checkResponse = await fetch('/api/admin/check-existing-reservations', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer ' + token,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({date})
    });
    
    if (checkResponse.ok) {
      const checkData = await checkResponse.json();
      
      if (checkData.count > 0) {
        // Postoje rezervacije - prikaži upozorenje
        const confirmed = confirm(
          `⚠️ UPOZORENJE!\n\n` +
          `U odabranom danu (${date}) postoje ${checkData.count} rezervacija.\n\n` +
          `Želite li da:\n` +
          `1. Prikažete detalje rezervacija u novom tabu\n` +
          `2. Preuzmete TXT fajl sa podacima rezervacija\n` +
          `3. Nastavite sa blokiranjem samo slobodnih termina\n\n` +
          `Kliknite OK za nastavak ili Cancel za otkazivanje.`
        );
        
                 if (confirmed) {
           // Prikaži detalje rezervacija u modal-u umesto novog taba
           showReservationsModal(checkData, date);
          
          // Blokiraj samo slobodne termine
          const blockResponse = await fetch('/api/admin/block-only-available-slots', {
            method: 'POST',
            headers: {
              'Authorization': 'Bearer ' + token,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({date})
          });
          
                     if (blockResponse.ok) {
             alert(`✅ Uspješno blokirani samo slobodni termini!\n\nPostojeće rezervacije (${checkData.count}) su ostavljene nedirnute.\n\nDetalji rezervacija su prikazani u modal-u.`);
                         // Osveži status dana
            blockDayDate.dispatchEvent(new Event('change'));
            await refreshBlockedDays(); // Osveži listu blokiranih dana
           } else {
             alert('Greška prilikom blokiranja slobodnih termina.');
           }
        }
      } else {
        // Nema rezervacija - blokiraj ceo dan
        const confirmed = confirm(
          `Nema rezervacija za datum ${date}.\n\nŽelite li da blokirate cijeli dan?`
        );
        
        if (confirmed) {
          const blockResponse = await fetch('/api/admin/block_day', {
            method: 'POST',
            headers: {
              'Authorization': 'Bearer ' + token,
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({date})
          });
          
          if (blockResponse.ok) {
      alert('Dan je uspješno blokiran!');
            // Osveži status dana
            blockDayDate.dispatchEvent(new Event('change'));
            await refreshBlockedDays(); // Osveži listu blokiranih dana
    } else {
      alert('Greška prilikom blokiranja dana.');
          }
        }
      }
    } else {
      alert('Greška pri provjeri rezervacija.');
    }
  } catch (error) {
    console.error('Greška pri provjeri rezervacija:', error);
    alert('Greška pri provjeri rezervacija. Pokušajte ponovo.');
  }
});

// Učitaj tipove vozila za pretragu
async function loadVehicleTypes() {
  try {
    const token = getToken();
    console.log('Loading vehicle types with token:', token ? 'present' : 'missing');
    
    const res = await fetch('/api/admin/vehicle-types', {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    
    console.log('Vehicle types response status:', res.status);
    
    if (!res.ok) {
      throw new Error(`HTTP ${res.status}: ${res.statusText}`);
    }
    
    const types = await res.json();
    console.log('Vehicle types loaded:', types);
    
    const select = document.getElementById('search-vehicle_type_id');
    if (select) {
      // Očisti postojeće opcije
      select.innerHTML = '<option value="">Izaberi tip vozila</option>';
      
      types.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.description_vehicle;
        select.appendChild(option);
      });
      
      console.log('Vehicle types added to select, total options:', select.options.length);
    } else {
      console.error('Select element not found');
    }
  } catch (e) {
    console.error('Greška pri učitavanju tipova vozila:', e);
  }
}

// Pretraga rezervacija
const searchReservationsBtn = document.getElementById('search-reservations-btn');
let currentReservationId = null;

// Debounced search function
const debouncedSearch = debounce(async function() {
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

  const token = getToken();
  const params = new URLSearchParams();
  if (date) params.append('date', date);
  if (user_name) params.append('user_name', user_name);
  if (email) params.append('email', email);
  if (vehicle_type_id) params.append('vehicle_type_id', vehicle_type_id);
  if (license_plate) params.append('license_plate', license_plate);

  console.log('Searching with params:', params.toString());
  
  try {
    const res = await fetch('/api/admin/search-reservations?' + params.toString(), {
      headers: { 'Authorization': 'Bearer ' + token }
    });
    
    console.log('Search response status:', res.status);
    const data = await res.json();
    console.log('Search results:', data);
    
    const resultsDiv = document.getElementById('search-results');
    const formDiv = document.getElementById('edit-reservation-form');
    
    formDiv.innerHTML = '';
    formDiv.style.display = 'none';

    if (!data || data.length === 0) {
      resultsDiv.innerHTML = '<p>Nema rezervacija koje odgovaraju kriterijumu.</p>';
      resultsDiv.style.display = 'block';
      return;
    }

    if (data.length === 1) {
      // Ako je samo jedna rezervacija, prikaži je direktno
      showReservationForm(data[0]);
      resultsDiv.style.display = 'none';
    } else {
      // Ako ima više rezervacija, prikaži listu
      showReservationsList(data);
    }
  } catch (error) {
    console.error('Search error:', error);
    showError('Greška pri pretraživanju rezervacija!');
  }
}, 300); // 300ms debounce

if (searchReservationsBtn) {
  searchReservationsBtn.addEventListener('click', function() {
    LoadingManager.show(this, 'Pretražujem...');
    debouncedSearch().finally(() => {
      LoadingManager.hide(this);
    });
  });
}

// Prikaži listu rezervacija
function showReservationsList(reservations) {
  const resultsDiv = document.getElementById('search-results');
  let html = '<h4>Pronađene rezervacije:</h4><div style="max-height:300px; overflow-y:auto;">';
  
  reservations.forEach(reservation => {
    const statusColor = reservation.status === 'storno' ? 'red' : 
                       reservation.status === 'paid' ? 'green' : 'black';
    
    html += `
      <div style="border:1px solid #ccc; padding:10px; margin:5px 0; cursor:pointer;" 
           onclick="selectReservation(${reservation.id})">
        <strong>ID: ${reservation.id}</strong> | 
        <span style="color:${statusColor}">Status: ${reservation.status || 'N/A'}</span><br>
        Datum: ${reservation.reservation_date} | 
        Ime: ${reservation.user_name}<br>
        Email: ${reservation.email} | 
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
  const token = getToken();
  console.log('Loading reservation ID:', reservationId);
  fetch('/api/reservation/' + reservationId, {
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
      showReservationForm(reservation);
      document.getElementById('search-results').style.display = 'none';
      
      // Resetuj polja za pretragu da pokazuju podatke odabrane rezervacije
      const searchDateInput = document.getElementById('search-date');
      if (searchDateInput && reservation.reservation_date) {
        searchDateInput.value = reservation.reservation_date;
      }
      
      // Resetuj ostala polja za pretragu
      const searchNameInput = document.getElementById('search-user_name');
      if (searchNameInput) searchNameInput.value = reservation.user_name || '';
      
      const searchEmailInput = document.getElementById('search-email');
      if (searchEmailInput) searchEmailInput.value = reservation.email || '';
      
      const searchLicenseInput = document.getElementById('search-license_plate');
      if (searchLicenseInput) searchLicenseInput.value = reservation.license_plate || '';
      
      const searchVehicleTypeInput = document.getElementById('search-vehicle_type_id');
      if (searchVehicleTypeInput) searchVehicleTypeInput.value = reservation.vehicle_type_id || '';
    })
    .catch((error) => {
      console.error('Error loading reservation:', error);
      alert('Greška pri učitavanju rezervacije!');
    });
}

// Prikaži formu za izmenu rezervacije
function showReservationForm(reservation) {
      if (!reservation || !reservation.id) {
        alert('Rezervacija nije pronađena!');
        return;
      }
  
      currentReservationId = reservation.id;
  const formDiv = document.getElementById('edit-reservation-form');

  // Proveri da li je datum rezervacije prošao
  const reservationDate = new Date(reservation.reservation_date);
  const today = new Date();
  today.setHours(0, 0, 0, 0); // Resetuj vreme na početak dana
  const isPastDate = reservationDate < today;

      const form = document.createElement('form');
      form.innerHTML = `
    <h4>Rezervacija ID: ${reservation.id}</h4>
    <label>Status:</label>
    <input type="text" id="status" value="${reservation.status || ''}" readonly style="color:${reservation.status === 'storno' ? 'red' : 'black'};"><br>
        <label>Merchant Transaction ID:</label>
        <input type="text" id="merchant_transaction_id" value="${reservation.merchant_transaction_id || ''}" readonly><br>
    <label>Datum rezervacije:</label>
    <input type="date" id="reservation_date" value="${reservation.reservation_date}" ${isPastDate ? 'readonly' : ''}><br>
        <label>Ime i prezime:</label>
        <input type="text" id="user_name" value="${reservation.user_name || ''}" readonly><br>
        <label>Država:</label>
    <select id="country" ${isPastDate ? 'disabled' : ''}>
      <option value="">Izaberi državu</option>
      <option value="ME" ${reservation.country === 'ME' ? 'selected' : ''}>Crna Gora</option>
      <option value="HR" ${reservation.country === 'HR' ? 'selected' : ''}>Hrvatska</option>
      <option value="RS" ${reservation.country === 'RS' ? 'selected' : ''}>Srbija</option>
      <option value="BA" ${reservation.country === 'BA' ? 'selected' : ''}>Bosna i Hercegovina</option>
      <option value="MK" ${reservation.country === 'MK' ? 'selected' : ''}>Sjeverna Makedonija</option>
      <option value="SI" ${reservation.country === 'SI' ? 'selected' : ''}>Slovenija</option>
      <option value="AL" ${reservation.country === 'AL' ? 'selected' : ''}>Albanija</option>
      <option value="AD" ${reservation.country === 'AD' ? 'selected' : ''}>Andora</option>
      <option value="AT" ${reservation.country === 'AT' ? 'selected' : ''}>Austrija</option>
      <option value="BY" ${reservation.country === 'BY' ? 'selected' : ''}>Belorusija</option>
      <option value="BE" ${reservation.country === 'BE' ? 'selected' : ''}>Belgija</option>
      <option value="BG" ${reservation.country === 'BG' ? 'selected' : ''}>Bugarska</option>
      <option value="CZ" ${reservation.country === 'CZ' ? 'selected' : ''}>Češka</option>
      <option value="DK" ${reservation.country === 'DK' ? 'selected' : ''}>Danska</option>
      <option value="EE" ${reservation.country === 'EE' ? 'selected' : ''}>Estonija</option>
      <option value="FI" ${reservation.country === 'FI' ? 'selected' : ''}>Finska</option>
      <option value="FR" ${reservation.country === 'FR' ? 'selected' : ''}>Francuska</option>
      <option value="DE" ${reservation.country === 'DE' ? 'selected' : ''}>Njemačka</option>
      <option value="GR" ${reservation.country === 'GR' ? 'selected' : ''}>Grčka</option>
      <option value="HU" ${reservation.country === 'HU' ? 'selected' : ''}>Mađarska</option>
      <option value="IS" ${reservation.country === 'IS' ? 'selected' : ''}>Island</option>
      <option value="IE" ${reservation.country === 'IE' ? 'selected' : ''}>Irska</option>
      <option value="IT" ${reservation.country === 'IT' ? 'selected' : ''}>Italija</option>
      <option value="XK" ${reservation.country === 'XK' ? 'selected' : ''}>Kosovo</option>
      <option value="LV" ${reservation.country === 'LV' ? 'selected' : ''}>Letonija</option>
      <option value="LI" ${reservation.country === 'LI' ? 'selected' : ''}>Lihtenštajn</option>
      <option value="LT" ${reservation.country === 'LT' ? 'selected' : ''}>Litvanija</option>
      <option value="LU" ${reservation.country === 'LU' ? 'selected' : ''}>Luksemburg</option>
      <option value="MT" ${reservation.country === 'MT' ? 'selected' : ''}>Malta</option>
      <option value="MD" ${reservation.country === 'MD' ? 'selected' : ''}>Moldavija</option>
      <option value="MC" ${reservation.country === 'MC' ? 'selected' : ''}>Monako</option>
      <option value="NL" ${reservation.country === 'NL' ? 'selected' : ''}>Holandija</option>
      <option value="NO" ${reservation.country === 'NO' ? 'selected' : ''}>Norveška</option>
      <option value="PL" ${reservation.country === 'PL' ? 'selected' : ''}>Poljska</option>
      <option value="PT" ${reservation.country === 'PT' ? 'selected' : ''}>Portugalija</option>
      <option value="RO" ${reservation.country === 'RO' ? 'selected' : ''}>Rumunija</option>
      <option value="RU" ${reservation.country === 'RU' ? 'selected' : ''}>Rusija</option>
      <option value="SM" ${reservation.country === 'SM' ? 'selected' : ''}>San Marino</option>
      <option value="SK" ${reservation.country === 'SK' ? 'selected' : ''}>Slovačka</option>
      <option value="ES" ${reservation.country === 'ES' ? 'selected' : ''}>Španija</option>
      <option value="SE" ${reservation.country === 'SE' ? 'selected' : ''}>Švedska</option>
      <option value="CH" ${reservation.country === 'CH' ? 'selected' : ''}>Švajcarska</option>
      <option value="UA" ${reservation.country === 'UA' ? 'selected' : ''}>Ukrajina</option>
      <option value="GB" ${reservation.country === 'GB' ? 'selected' : ''}>Velika Britanija</option>
      <option value="VA" ${reservation.country === 'VA' ? 'selected' : ''}>Vatikan</option>
      <option value="TR" ${reservation.country === 'TR' ? 'selected' : ''}>Turska</option>
      <option value="IL" ${reservation.country === 'IL' ? 'selected' : ''}>Izrael</option>
      <option value="OTHER" ${reservation.country === 'OTHER' ? 'selected' : ''}>Ostalo</option>
    </select><br>
        <label>Registrarska oznaka:</label>
    <input type="text" id="license_plate" value="${reservation.license_plate || ''}" ${isPastDate ? 'readonly' : ''} style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');"><br>
        <label>Email:</label>
    <input type="email" id="email" value="${reservation.email || ''}" ${isPastDate ? 'readonly' : ''}><br>
        <label>ID tipa vozila:</label>
    <select id="vehicle_type_id" ${isPastDate ? 'disabled' : ''}>
      <option value="">Izaberi tip vozila</option>
    </select><br>
        <label>ID drop-off termina:</label>
    <select id="drop_off_time_slot_id" ${isPastDate ? 'disabled' : ''}></select><br>
        <label>ID pick-up termina:</label>
    <select id="pick_up_time_slot_id" ${isPastDate ? 'disabled' : ''}></select><br>
    <button type="button" id="save-edit-reservation" ${isPastDate ? 'disabled' : ''}>Sačuvaj izmjene</button>
    ${isPastDate ? '<p style="color: red; font-weight: bold;">⚠️ Datum rezervacije je prošao - izmjena nije dozvoljena</p>' : ''}
      `;
  
  formDiv.innerHTML = '';
      formDiv.appendChild(form);
      formDiv.style.display = 'block';

      const saveEditBtn = document.getElementById('save-edit-reservation');
      if (saveEditBtn) saveEditBtn.onclick = function() {
    // Dodatna provera datuma pre slanja
    const reservationDate = new Date(reservation.reservation_date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    if (reservationDate < today) {
      alert('Ne možete izmjeniti rezervaciju čiji je datum prošao!');
      return;
    }
    
    const email = document.getElementById('email').value.trim();
    if (!validateEmailWithFeedback(document.getElementById('email'), true)) {
      return;
    }
    
        const newData = {
      reservation_date: document.getElementById('reservation_date').value,
          license_plate: document.getElementById('license_plate').value,
      email: email,
      country: document.getElementById('country').value,
          vehicle_type_id: document.getElementById('vehicle_type_id').value,
          drop_off_time_slot_id: document.getElementById('drop_off_time_slot_id').value,
          pick_up_time_slot_id: document.getElementById('pick_up_time_slot_id').value
        };
    const token = getToken();
        fetch('/api/reservation/' + currentReservationId, {
          method: 'PUT',
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

  const stornirajBtn = document.createElement('button');
  stornirajBtn.textContent = 'Storniraj fiskalni račun';
  stornirajBtn.style.background = '#c00';
  stornirajBtn.style.color = '#fff';
  stornirajBtn.type = 'button'; // <--- OVO JE KLJUČNO!
  stornirajBtn.onclick = function(event) {
    event.preventDefault(); // <--- OVO JE KLJUČNO!
    if (!confirm('Da li ste sigurni da želite da stornirate fiskalni račun za ovu rezervaciju?')) return;
    const token = getToken();
    fetch('/api/reservation/' + currentReservationId + '/storno', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        }
    }).then(res => res.json())
      .then(data => {
        if (data.success) {
            let msg = 'Račun je uspešno storniran!';
            if (data.data && data.data.Url && data.data.Url.Value) {
                msg += '\nFiskalni QR: ' + data.data.Url.Value;
            }
            if (data.data && data.data.UIDRequest) {
                msg += '\nIKOF: ' + data.data.UIDRequest;
            }
            if (data.data && data.data.ResponseCode) {
                msg += '\nJIKR: ' + data.data.ResponseCode;
            }
            alert(msg);
            selectReservation(currentReservationId);
        } else {
            alert('Greška: ' + (data.message || 'Storniranje nije uspjelo.'));
        }
      });
  };
  form.appendChild(stornirajBtn);

  // Izgled računa dugme
  const testPdfBtn = document.createElement('button');
  testPdfBtn.textContent = 'Izgled računa';
  testPdfBtn.style.background = '#9c1420';
  testPdfBtn.style.color = '#fff';
  testPdfBtn.onclick = function() {
      window.open('/test-pdf/' + currentReservationId, '_blank');
  };
  form.appendChild(testPdfBtn);

  // Popuni drop-downove
  populateVehicleTypesSelect('vehicle_type_id', reservation.vehicle_type_id);
  populateTimeSlotSelects(reservation.reservation_date, 'drop_off_time_slot_id', 'pick_up_time_slot_id', reservation.drop_off_time_slot_id, reservation.pick_up_time_slot_id);
  setupSlotFilters('drop_off_time_slot_id', 'pick_up_time_slot_id');
  
  // Dodaj event listener za promenu datuma
  if (!isPastDate) {
    document.getElementById('reservation_date').addEventListener('change', function() {
      const newDate = this.value;
      populateTimeSlotSelects(newDate, 'drop_off_time_slot_id', 'pick_up_time_slot_id');
      setupSlotFilters('drop_off_time_slot_id', 'pick_up_time_slot_id');
    });
  }
}

// Funkcija za konverziju vremena u slot ID
function getSlotIdFromTime(timeString) {
  // timeString format: "18:00 - 18:20"
  const timeMap = {
    "00:00 - 07:00": 1,
    "07:00 - 07:20": 2,
    "07:20 - 07:40": 3,
    "07:40 - 08:00": 4,
    "08:00 - 08:20": 5,
    "08:20 - 08:40": 6,
    "08:40 - 09:00": 7,
    "09:00 - 09:20": 8,
    "09:20 - 09:40": 9,
    "09:40 - 10:00": 10,
    "10:00 - 10:20": 11,
    "10:20 - 10:40": 12,
    "10:40 - 11:00": 13,
    "11:00 - 11:20": 14,
    "11:20 - 11:40": 15,
    "11:40 - 12:00": 16,
    "12:00 - 12:20": 17,
    "12:20 - 12:40": 18,
    "12:40 - 13:00": 19,
    "13:00 - 13:20": 20,
    "13:20 - 13:40": 21,
    "13:40 - 14:00": 22,
    "14:00 - 14:20": 23,
    "14:20 - 14:40": 24,
    "14:40 - 15:00": 25,
    "15:00 - 15:20": 26,
    "15:20 - 15:40": 27,
    "15:40 - 16:00": 28,
    "16:00 - 16:20": 29,
    "16:20 - 16:40": 30,
    "16:40 - 17:00": 31,
    "17:00 - 17:20": 32,
    "17:20 - 17:40": 33,
    "17:40 - 18:00": 34,
    "18:00 - 18:20": 35,
    "18:20 - 18:40": 36,
    "18:40 - 19:00": 37,
    "19:00 - 19:20": 38,
    "19:20 - 19:40": 39,
    "19:40 - 20:00": 40,
    "20:00 - 24:00": 41
  };
  
  return timeMap[timeString] || 0;
}

// Funkcija za proveru email sintakse
function validateEmailSyntax(email) {
  if (!email || email.trim() === '') return false;
  
  // Proveri da li sadrži @
  if (!email.includes('@')) return false;
  
  // Podeli na lokalni deo i domen
  const parts = email.split('@');
  if (parts.length !== 2) return false;
  
  const localPart = parts[0];
  const domain = parts[1];
  
  // Proveri da li lokalni deo nije prazan
  if (!localPart || localPart.trim() === '') return false;
  
  // Proveri da li domen nije prazan i da li sadrži tačku
  if (!domain || domain.trim() === '' || !domain.includes('.')) return false;
  
  // Proveri da li domen ima bar 2 karaktera nakon tačke
  const domainParts = domain.split('.');
  if (domainParts.length < 2 || domainParts[domainParts.length - 1].length < 2) return false;
  
  return true;
}

// Centralizovana funkcija za validaciju email-a sa vizuelnim feedback-om
function validateEmailWithFeedback(emailInput, showAlert = false) {
  const email = emailInput.value.trim();
  
  if (!email) return true; // Prazan email je OK (nije obavezan)
  
  if (!validateEmailSyntax(email)) {
    // Vizuelni feedback
    emailInput.style.borderColor = 'red';
    emailInput.style.backgroundColor = '#ffe6e6';
    
    // Dodaj poruku o grešci ako ne postoji
    if (!emailInput.nextElementSibling || !emailInput.nextElementSibling.classList.contains('email-error')) {
      const errorMsg = document.createElement('div');
      errorMsg.className = 'email-error';
      errorMsg.style.color = 'red';
      errorMsg.style.fontSize = '12px';
      errorMsg.style.marginTop = '2px';
      errorMsg.textContent = 'Neispravna email adresa. Mora sadržati @ i validan domen.';
      emailInput.parentNode.insertBefore(errorMsg, emailInput.nextSibling);
    }
    
    // Alert ako je potreban
    if (showAlert) {
      alert('Neispravna email adresa. Mora sadržati @ i validan domen.');
      emailInput.focus();
    }
    
    return false;
  } else {
    // Ukloni vizuelni feedback ako je email ispravan
    emailInput.style.borderColor = '';
    emailInput.style.backgroundColor = '';
    
    // Ukloni poruku o grešci
    const errorMsg = emailInput.nextElementSibling;
    if (errorMsg && errorMsg.classList.contains('email-error')) {
      errorMsg.remove();
    }
    
    return true;
  }
}

async function populateVehicleTypesSelect(selectId, selectedValue = '') {
  const token = getToken && typeof getToken === 'function' ? getToken() : null;
  const res = await fetch('/api/admin/vehicle-types', {
    headers: token ? { 'Authorization': 'Bearer ' + token } : {}
  });
  const types = await res.json();
  const select = document.getElementById(selectId);
  if (!select) return;
  select.innerHTML = '<option value="">Izaberi tip vozila</option>';
  types.forEach(type => {
    const option = document.createElement('option');
    option.value = type.id;
    option.textContent = type.description_vehicle;
    if (selectedValue && selectedValue == type.id) option.selected = true;
    select.appendChild(option);
  });
}

async function populateTimeSlotSelects(date, dropOffSelectId, pickUpSelectId, selectedDropOff = null, selectedPickUp = null) {
  if (!date) return;
  
  try {
    const token = getToken();
    const res = await fetch('/api/timeslots/available?date=' + encodeURIComponent(date), {
      headers: token ? { 'Authorization': 'Bearer ' + token } : {}
    });
    
    if (!res.ok) {
      console.error('Greška pri učitavanju slotova:', res.statusText);
      return;
    }
    
    const slots = await res.json();

    const dropOffSelect = document.getElementById(dropOffSelectId);
    const pickUpSelect = document.getElementById(pickUpSelectId);

    if (!dropOffSelect || !pickUpSelect) return;

    // Resetuj opcije
    dropOffSelect.innerHTML = '<option value="">Izaberi drop-off termin</option>';
    pickUpSelect.innerHTML = '<option value="">Izaberi pick-up termin</option>';

    // Proveri da li je današnji datum
    const today = new Date().toISOString().split('T')[0];
    const isToday = date === today;
    const currentTime = new Date();
    
    // Dodaj slotove sa filtriranjem
    slots.forEach(slot => {
      // Za drop-off: ako je današnji dan, dozvoli samo trenutni i budući termini
      let isDropOffAvailable = true;
      if (isToday) {
        // Parsiraj vreme iz slot-a (npr. "08:00 - 08:20" -> "08:00")
        const slotTimeStr = slot.time_slot.split(' - ')[0];
        const slotTime = new Date();
        const [hours, minutes] = slotTimeStr.split(':').map(Number);
        slotTime.setHours(hours, minutes, 0, 0);
        
        // Dozvoli termin ako je trenutni ili budući (sa 5 minuta tolerancije)
        const tolerance = 5 * 60 * 1000; // 5 minuta u milisekundama
        isDropOffAvailable = (slotTime.getTime() + tolerance) >= currentTime.getTime();
      }

      const dropOffOption = document.createElement('option');
      dropOffOption.value = slot.id;
      dropOffOption.textContent = slot.time_slot;
      dropOffOption.disabled = !isDropOffAvailable;
      if (selectedDropOff && selectedDropOff == slot.id) dropOffOption.selected = true;
      dropOffSelect.appendChild(dropOffOption);

      const pickUpOption = document.createElement('option');
      pickUpOption.value = slot.id;
      pickUpOption.textContent = slot.time_slot;
      if (selectedPickUp && selectedPickUp == slot.id) pickUpOption.selected = true;
      pickUpSelect.appendChild(pickUpOption);
    });
    
  } catch (error) {
    console.error('Greška pri učitavanju slotova:', error);
  }
}

function setupSlotFilters(dropOffSelectId, pickUpSelectId) {
  const dropOffSelect = document.getElementById(dropOffSelectId);
  const pickUpSelect = document.getElementById(pickUpSelectId);

  if (!dropOffSelect || !pickUpSelect) return;

  // Ukloni postojeće event listenere da izbegnemo duplikate
  const newDropOffSelect = dropOffSelect.cloneNode(true);
  const newPickUpSelect = pickUpSelect.cloneNode(true);
  dropOffSelect.parentNode.replaceChild(newDropOffSelect, dropOffSelect);
  pickUpSelect.parentNode.replaceChild(newPickUpSelect, pickUpSelect);

  newDropOffSelect.addEventListener('change', function() {
    const selectedDropOff = parseInt(this.value, 10);
    
    Array.from(newPickUpSelect.options).forEach(option => {
      if (option.value === '') return; // Preskoči "Izaberi" opciju
      
      const slotId = parseInt(option.value, 10);
      // Pick-up mora biti veći od drop-off-a
      option.disabled = selectedDropOff && slotId <= selectedDropOff;
      
      // Ako je trenutno izabran pick-up koji je sada onemogućen, resetuj ga
      if (option.selected && option.disabled) {
        newPickUpSelect.value = '';
      }
    });
  });

  newPickUpSelect.addEventListener('change', function() {
    const selectedPickUp = parseInt(this.value, 10);
    
    Array.from(newDropOffSelect.options).forEach(option => {
      if (option.value === '') return; // Preskoči "Izaberi" opciju
      
      const slotId = parseInt(option.value, 10);
      // Drop-off mora biti manji od pick-up-a
      option.disabled = selectedPickUp && slotId >= selectedPickUp;
      
      // Ako je trenutno izabran drop-off koji je sada onemogućen, resetuj ga
      if (option.selected && option.disabled) {
        newDropOffSelect.value = '';
      }
    });
  });
}

// Učitaj tipove vozila kada se stranica učita
window.addEventListener('DOMContentLoaded', function() {
  // Inicijalizuj lazy loading
  initializeLazyLoading();
  
  // Postavi velika slova i filtriraj karaktere za sva polja registarske oznake
  const licensePlateInputs = document.querySelectorAll('input[id*="license_plate"], input[id*="license-plate"]');
  licensePlateInputs.forEach(input => {
    input.style.textTransform = 'uppercase';
    input.addEventListener('input', function() {
      // Filtriraj samo velika slova i brojeve
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
    });
});

  // Dodaj proveru email sintakse za sva email polja
  const emailInputs = document.querySelectorAll('input[type="email"], input[id*="email"]');
  emailInputs.forEach(input => {
    input.addEventListener('blur', function() {
      validateEmailWithFeedback(this, false);
    });
  });

  // Inicijalno popunjavanje slotova za besplatnu rezervaciju ako je datum već postavljen
  const freeReservationDate = document.getElementById('free-reservation_date');
  if (freeReservationDate && freeReservationDate.value) {
    populateTimeSlotSelects(freeReservationDate.value, 'free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
    setupSlotFilters('free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
  }

  freeReservationDate.addEventListener('change', function() {
    const newDate = this.value;
    populateTimeSlotSelects(newDate, 'free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
    setupSlotFilters('free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
  });
  
  // Ažuriraj filtriranje za današnji dan svakih 5 minuta
  setInterval(function() {
    const today = new Date().toISOString().split('T')[0];
    
    // Ažuriraj besplatnu rezervaciju ako je današnji datum
    const freeDate = document.getElementById('free-reservation_date');
    if (freeDate && freeDate.value === today) {
      populateTimeSlotSelects(freeDate.value, 'free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
      setupSlotFilters('free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
    }
    
    // Ažuriraj formu za izmenu ako je današnji datum
    const editDate = document.getElementById('reservation_date');
    if (editDate && editDate.value === today) {
      populateTimeSlotSelects(editDate.value, 'drop_off_time_slot_id', 'pick_up_time_slot_id');
      setupSlotFilters('drop_off_time_slot_id', 'pick_up_time_slot_id');
    }
  }, 5 * 60 * 1000); // 5 minuta
  
  // Inicijalizuj analitiku
  initializeAnalytics();
  
  // Učitaj osnovne komponente odmah
  loadReportEmails();
  loadNumSlots();
});

// Kreiraj besplatnu rezervaciju (status: 'free') - ne prolazi kroz payment flow
const freeReservationBtn = document.getElementById('free-reservation-btn');
if (freeReservationBtn) freeReservationBtn.addEventListener('click', function() {
  const emailInput = document.getElementById('free-email');
  
  // Provera email sintakse
  if (!validateEmailWithFeedback(emailInput, true)) {
    return;
  }
  
  const dropOffSlotId = document.getElementById('free-drop_off_time_slot_id').value.trim();
  const pickUpSlotId = document.getElementById('free-pick_up_time_slot_id').value.trim();
  
  // Provera da li je dozvoljena besplatna rezervacija
  const isAllowedFreeReservation = (
    // Slučaj 1: Isti slot za posebne slotove (1 ili 41)
    (dropOffSlotId === pickUpSlotId && (dropOffSlotId === '1' || dropOffSlotId === '41')) ||
    // Slučaj 2: Posebna kombinacija - dolazak slot 1 i odlazak slot 41
    (dropOffSlotId === '1' && pickUpSlotId === '41')
  );
  
  if (!isAllowedFreeReservation) {
    alert('Besplatne rezervacije su dozvoljene samo za:\n1. Iste termine (00:00-07:00 ili 20:00-24:00)\n2. Kombinaciju dolazak 00:00-07:00 i odlazak 20:00-24:00');
    return;
  }
  
  const data = {
    user_name: document.getElementById('free-user_name').value.trim(),
    country: document.getElementById('free-country').value.trim(),
    license_plate: document.getElementById('free-license_plate').value.trim(),
    email: emailInput.value.trim(),
    vehicle_type_id: document.getElementById('free-vehicle_type_id').value.trim(),
    reservation_date: document.getElementById('free-reservation_date').value.trim(),
    drop_off_time_slot_id: dropOffSlotId,
    pick_up_time_slot_id: pickUpSlotId,
    status: 'free' // Besplatna rezervacija - ne prolazi kroz payment i fiskalizaciju
  };

  // Prosta validacija
  for (const key in data) {
    if (!data[key]) {
      alert('Popunite sva polja!');
      return;
    }
  }

  fetch('/api/reservations/reserve', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify(data)
  }).then(res => {
    if(res.ok) {
      return res.json(); // Vraćamo JSON da dobijemo ID rezervacije
    } else {
      throw new Error('Greška pri upisu besplatne rezervacije.');
    }
  }).then(data => {
    // Šaljemo email potvrdu
    return fetch('/api/reservations/send-free-confirmation', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        reservation_id: data.id,
        email: data.email
      })
    });
  }).then(res => {
    if(res.ok) {
      alert('Besplatna rezervacija je uspješno upisana i potvrda je poslata na email!');
      // Po želji: resetuj polja
    } else {
      alert('Besplatna rezervacija je upisana, ali greška pri slanju email-a.');
    }
  }).catch(error => {
    alert(error.message || 'Greška pri upisu besplatne rezervacije.');
  });
});

// --- EMAIL MANAGEMENT ---
async function loadReportEmails() {
    const res = await fetch('/api/report-emails');
    const emails = await res.json();
    const list = document.getElementById('report-emails-list');
    if (!list) return;
    list.innerHTML = '';
    emails.forEach(email => {
        const li = document.createElement('li');
        li.textContent = email + ' ';
        const delBtn = document.createElement('button');
        delBtn.textContent = 'Obriši';
        delBtn.onclick = () => deleteReportEmail(email);
        li.appendChild(delBtn);
        list.appendChild(li);
    });
}
async function addReportEmail() {
    const emailInput = document.getElementById('new-report-email');
    if (!emailInput) return;
    
    // Provera email sintakse
    if (!validateEmailWithFeedback(emailInput, true)) {
        return;
    }
    
    const email = emailInput.value.trim();
    if (!email) return;
    
    try {
        const response = await fetch('/api/report-emails', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({email})
        });
        
        if (response.ok) {
            emailInput.value = '';
            loadReportEmails();
            showSuccess('Email uspješno dodat!');
        } else {
            showError('Greška pri dodavanju email-a');
        }
    } catch (error) {
        console.error('Error adding report email:', error);
        showError('Greška pri dodavanju email-a');
    }
}
async function deleteReportEmail(email) {
    try {
        const response = await fetch('/api/report-emails/' + encodeURIComponent(email), {
            method: 'DELETE'
        });
        
        if (response.ok) {
            loadReportEmails();
            showSuccess('Email uspješno obrisan!');
        } else {
            showError('Greška pri brisanju email-a');
        }
    } catch (error) {
        console.error('Error deleting report email:', error);
        showError('Greška pri brisanju email-a');
    }
}
const addReportEmailBtn = document.getElementById('add-report-email-btn');
if (addReportEmailBtn) addReportEmailBtn.onclick = addReportEmail;
window.addEventListener('DOMContentLoaded', loadReportEmails);

// Prikaz trenutnog broja slotova
async function loadNumSlots() {
    const res = await fetch('/api/num-slots');
    const data = await res.json();
    const el = document.getElementById('current-num-slots');
    if (el) el.textContent = data.num_slots;
}
window.addEventListener('DOMContentLoaded', loadNumSlots);

// Ažuriranje broja slotova
const updateNumSlotsBtn = document.getElementById('update-num-slots-btn');
if (updateNumSlotsBtn) updateNumSlotsBtn.addEventListener('click', updateNumSlots);

async function updateNumSlots() {
    const input = document.getElementById('new-num-slots');
    const value = input.value;
    if (!value || isNaN(value) || value < 1) {
        showError('Unesite ispravan broj!');
        return;
    }
    
    try {
        const res = await fetch('/api/num-slots', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({num_slots: value})
        });
        
        if (res.ok) {
            showSuccess('Broj slotova je ažuriran!');
            loadNumSlots();
            input.value = '';
        } else {
            showError('Greška pri ažuriranju broja slotova.');
        }
    } catch (error) {
        console.error('Error updating num slots:', error);
        showError('Greška pri ažuriranju broja slotova.');
    }
}

// ===========================
// IZVEŠTAJI - OTVARANJE U NOVIM TABOVIMA
// ===========================

// Inicijalizacija izveštaja
function initializeReports() {
  // Postavi maksimalne datume za dnevne izveštaje (samo prošli period)
  const today = new Date().toISOString().split('T')[0];
  
  const dailyFinanceDate = document.getElementById('daily-finance-date');
  const dailyVehicleDate = document.getElementById('daily-vehicle-date');
  
  if (dailyFinanceDate) {
    dailyFinanceDate.max = today;
    dailyFinanceDate.value = today;
  }
  
  if (dailyVehicleDate) {
    dailyVehicleDate.max = today;
    dailyVehicleDate.value = today;
  }
  
  // Popuni godine za mesečne i godišnje izveštaje
  const currentYear = new Date().getFullYear();
  const yearSelects = [
    'monthly-finance-year', 'yearly-finance-year',
    'monthly-vehicle-year', 'yearly-vehicle-year'
  ];
  
  yearSelects.forEach(selectId => {
    const select = document.getElementById(selectId);
    if (select) {
      select.innerHTML = '<option value="">Godina</option>';
      // Dodaj godine od 2024 do trenutne godine
      for (let year = 2024; year <= currentYear; year++) {
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) option.selected = true;
        select.appendChild(option);
      }
    }
  });
  
  // Postavi trenutni mesec za mesečne izveštaje
  const currentMonth = String(new Date().getMonth() + 1).padStart(2, '0');
  const monthSelects = ['monthly-finance-month', 'monthly-vehicle-month'];
  
  monthSelects.forEach(selectId => {
    const select = document.getElementById(selectId);
    if (select) {
      select.value = currentMonth;
    }
  });
}

// Pozovi inicijalizaciju
initializeReports();

// Finansijski izveštaji
const dailyFinanceReportBtn = document.getElementById('daily-finance-report-btn');
if (dailyFinanceReportBtn) dailyFinanceReportBtn.addEventListener('click', function() {
    const date = document.getElementById('daily-finance-date').value;
    if (!date) {
        alert('Molimo izaberite datum za dnevni finansijski izvještaj.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/daily-finance?date=${date}&token=${token}`, '_blank');
});

const monthlyFinanceReportBtn = document.getElementById('monthly-finance-report-btn');
if (monthlyFinanceReportBtn) monthlyFinanceReportBtn.addEventListener('click', function() {
    const month = document.getElementById('monthly-finance-month').value;
    const year = document.getElementById('monthly-finance-year').value;
    if (!month || !year) {
        alert('Molimo izaberite mjesec i godinu za mjesečni finansijski izvještaj.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/monthly-finance?month=${month}&year=${year}&token=${token}`, '_blank');
});

const yearlyFinanceReportBtn = document.getElementById('yearly-finance-report-btn');
if (yearlyFinanceReportBtn) yearlyFinanceReportBtn.addEventListener('click', function() {
    const year = document.getElementById('yearly-finance-year').value;
    if (!year) {
        alert('Molimo izaberite godinu za godišnji finansijski izvještaj.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/yearly-finance?year=${year}&token=${token}`, '_blank');
});

// Izveštaji po tipu vozila
const dailyVehicleReportBtn = document.getElementById('daily-vehicle-report-btn');
if (dailyVehicleReportBtn) dailyVehicleReportBtn.addEventListener('click', function() {
    const date = document.getElementById('daily-vehicle-date').value;
    if (!date) {
        alert('Molimo izaberite datum za dnevni izvještaj po tipu vozila.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/daily-vehicle-reservations?date=${date}&token=${token}`, '_blank');
});

const monthlyVehicleReportBtn = document.getElementById('monthly-vehicle-report-btn');
if (monthlyVehicleReportBtn) monthlyVehicleReportBtn.addEventListener('click', function() {
    const month = document.getElementById('monthly-vehicle-month').value;
    const year = document.getElementById('monthly-vehicle-year').value;
    if (!month || !year) {
        alert('Molimo izaberite mjesec i godinu za mjesečni izvještaj po tipu vozila.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/monthly-vehicle-reservations?month=${month}&year=${year}&token=${token}`, '_blank');
});

const yearlyVehicleReportBtn = document.getElementById('yearly-vehicle-report-btn');
if (yearlyVehicleReportBtn) yearlyVehicleReportBtn.addEventListener('click', function() {
    const year = document.getElementById('yearly-vehicle-year').value;
    if (!year) {
        alert('Molimo izaberite godinu za godišnji izvještaj po tipu vozila.');
        return;
    }
    const token = getToken();
    window.open(`/admin/reports/yearly-vehicle-reservations?year=${year}&token=${token}`, '_blank');
});

// Funkcija za prikaz modal-a sa rezervacijama
function showReservationsModal(checkData, date) {
    // Kreiraj modal overlay
    const modalOverlay = document.createElement('div');
    modalOverlay.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
    `;
    
    // Kreiraj modal sadržaj
    const modalContent = document.createElement('div');
    modalContent.style.cssText = `
        background: white;
        padding: 20px;
        border-radius: 10px;
        max-width: 90%;
        max-height: 90%;
        overflow-y: auto;
        position: relative;
    `;
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #333;">📋 Rezervacije za datum: ${date}</h2>
            <button onclick="this.closest('.modal-overlay').remove()" style="
                background: #f44336; 
                color: white; 
                border: none; 
                padding: 8px 12px; 
                border-radius: 5px; 
                cursor: pointer;
                font-size: 16px;
            ">✕</button>
        </div>
        
        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <p style="margin: 0;"><strong>Ukupno rezervacija: ${checkData.length}</strong></p>
            <div style="margin-top: 10px;">
                <button onclick="downloadReservationsTxt('${date}')" style="
                    background: #4CAF50; 
                    color: white; 
                    padding: 10px 20px; 
                    border: none; 
                    border-radius: 5px; 
                    cursor: pointer; 
                    margin-right: 10px;
                ">📥 Preuzmi TXT fajl</button>
                <button onclick="blockOnlyAvailableSlots('${date}')" style="
                    background: #ff9800; 
                    color: white; 
                    padding: 10px 20px; 
                    border: none; 
                    border-radius: 5px; 
                    cursor: pointer;
                ">🔒 Blokiraj samo dostupne termine</button>
            </div>
        </div>
        
        <div style="overflow-x: auto;">
            <table style="border-collapse: collapse; width: 100%; font-size: 14px;">
                <thead>
                    <tr style="background-color: #f2f2f2;">
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">#</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Ime i prezime</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Email</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Država</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Registarska oznaka</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Tip vozila</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Status</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Drop-off termin</th>
                        <th style="border: 1px solid #ddd; padding: 8px; text-align: left;">Pick-up termin</th>
                    </tr>
                </thead>
                <tbody>
                    ${checkData.map((res, index) => `
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px;">${index + 1}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.user_name || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.email || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.country || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.license_plate || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.vehicle_type || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px; color: ${res.status === 'paid' ? 'green' : res.status === 'storno' ? 'red' : 'black'}">${res.status || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.drop_off_time || 'N/A'}</td>
                            <td style="border: 1px solid #ddd; padding: 8px;">${res.pick_up_time || 'N/A'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
    `;
    
    modalOverlay.appendChild(modalContent);
    modalOverlay.className = 'modal-overlay';
    document.body.appendChild(modalOverlay);
}

// Funkcija za blokiranje samo dostupnih termina
async function blockOnlyAvailableSlots(date) {
    const token = getToken();
    
    try {
        const response = await fetch('/api/admin/block-only-available-slots', {
            method: 'POST',
            headers: {
                'Authorization': 'Bearer ' + token,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({date})
        });
        
        if (response.ok) {
            alert('Dostupni termini su uspješno blokirani!');
            
            // Zatvori modal
            const modal = document.querySelector('.modal-overlay');
            if (modal) modal.remove();
            
            // Osveži listu blokiranih dana
            await loadBlockedDays();
            
            // Zatvori modal sa terminima
            const slotsList = document.getElementById('slots-checkbox-list');
            if (slotsList) {
                slotsList.innerHTML = '';
            }
            
            // Resetuj datum
            const blockSlotDate = document.getElementById('block-slot-date');
            if (blockSlotDate) {
                blockSlotDate.value = '';
            }
            
        } else {
            alert('Greška prilikom blokiranja dostupnih termina.');
        }
    } catch (error) {
        console.error('Greška:', error);
        alert('Greška prilikom blokiranja dostupnih termina.');
    }
}

// Dodaj funkciju za preuzimanje TXT fajla
window.downloadReservationsTxt = function(date) {
    const token = getToken();
    
    fetch('/api/admin/generate-reservations-txt', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + token,
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({date: date})
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.blob();
    })
    .then(blob => {
        // Kreiraj link za download
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `rezervacije_${date}.txt`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    })
    .catch(error => {
        console.error('Error downloading file:', error);
        alert('Greška pri preuzimanju TXT fajla. Pokušajte ponovo.');
    });
};

// ===========================
// ANALITIKA REZERVACIJA
// ===========================

// Globalna varijabla za chart
let analyticsChart = null;

// Inicijalizacija analitike
function initializeAnalytics() {
  // Postavi default datume (poslednjih 30 dana)
  const endDate = new Date();
  const startDate = new Date();
  startDate.setDate(startDate.getDate() - 30);
  
  const startDateInput = document.getElementById('analytics-start-date');
  const endDateInput = document.getElementById('analytics-end-date');
  
  if (startDateInput) {
    startDateInput.value = startDate.toISOString().split('T')[0];
  }
  if (endDateInput) {
    endDateInput.value = endDate.toISOString().split('T')[0];
  }
  
  // Dodaj event listener za automatsko postavljanje maksimalnog datuma
  if (startDateInput && endDateInput) {
    // Postavi inicijalni max za start date
    startDateInput.max = endDateInput.value;
    
    // Event listener za promenu end date
    endDateInput.addEventListener('change', function() {
      const endDateValue = this.value;
      if (endDateValue) {
        startDateInput.max = endDateValue;
        
        // Ako je trenutni start date veći od novog end date, resetuj ga
        if (startDateInput.value && startDateInput.value > endDateValue) {
          startDateInput.value = endDateValue;
        }
      }
    });
    
    // Event listener za promenu start date
    startDateInput.addEventListener('change', function() {
      const startDateValue = this.value;
      const endDateValue = endDateInput.value;
      
      if (startDateValue && endDateValue && startDateValue > endDateValue) {
        alert('Početni datum ne može biti nakon krajnjeg datuma');
        this.value = endDateValue;
      }
    });
  }
  
  // Event listeneri za dugmad
  const timeSlotBtn = document.getElementById('time-slot-analytics-btn');
  const vehicleTypeBtn = document.getElementById('vehicle-type-analytics-btn');
  const countryBtn = document.getElementById('country-analytics-btn');
  
  if (timeSlotBtn) {
    timeSlotBtn.addEventListener('click', () => loadTimeSlotAnalytics());
  }
  if (vehicleTypeBtn) {
    vehicleTypeBtn.addEventListener('click', () => loadVehicleTypeAnalytics());
  }
  if (countryBtn) {
    countryBtn.addEventListener('click', () => loadCountryAnalytics());
  }
}

// Funkcija za dohvat analitike
async function fetchAnalytics(type, startDate, endDate, includeFree) {
  try {
    const token = getToken();
    const params = new URLSearchParams({
      type: type,
      start_date: startDate,
      end_date: endDate,
      include_free: includeFree ? '1' : '0'
    });
    
    // Dodaj timeout za API poziv
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 sekundi timeout
    
    const response = await fetch(`/api/admin/analytics?${params}`, {
      headers: {
        'Authorization': 'Bearer ' + token
      },
      signal: controller.signal
    });
    
    clearTimeout(timeoutId);
    
    if (!response.ok) {
      if (response.status === 404) {
        throw new Error('API endpoint nije pronađen. Proverite da li je backend implementiran.');
      } else if (response.status === 401) {
        throw new Error('Niste autorizovani. Molimo da se ulogujte ponovo.');
      } else if (response.status === 500) {
        throw new Error('Greška na serveru. Pokušajte ponovo kasnije.');
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
    }
    
    const data = await response.json();
    
    // Validacija podataka
    if (!Array.isArray(data)) {
      throw new Error('Neispravan format podataka od servera');
    }
    
    return data;
  } catch (error) {
    console.error('Greška pri dohvatu analitike:', error);
    
    if (error.name === 'AbortError') {
      showError('Zahtjev je prekinut zbog prekoračenja vremena. Pokušajte ponovo.');
    } else {
      showError(`Greška pri dohvatu podataka: ${error.message}`);
    }
    
    return null;
  }
}

// Funkcija za prikaz grafikona
function displayChart(data, title, chartType = 'bar') {
  const resultsDiv = document.getElementById('analytics-results');
  const titleDiv = document.getElementById('analytics-title');
  const summaryDiv = document.getElementById('analytics-summary');
  
  if (!resultsDiv || !titleDiv || !summaryDiv) return;
  
  // Prikaži rezultate
  resultsDiv.style.display = 'block';
  titleDiv.textContent = title;
  
  // Uništi postojeći chart ako postoji
  if (analyticsChart) {
    analyticsChart.destroy();
  }
  
  // Kreiraj novi chart
  const ctx = document.getElementById('analytics-chart');
  if (!ctx) return;
  
  const chartData = {
    labels: data.labels,
    datasets: [{
      label: data.datasetLabel || 'Broj rezervacija',
      data: data.values,
      backgroundColor: data.backgroundColor || 'rgba(54, 162, 235, 0.8)',
      borderColor: data.borderColor || 'rgba(54, 162, 235, 1)',
      borderWidth: 1
    }]
  };
  
  analyticsChart = new Chart(ctx, {
    type: chartType,
    data: chartData,
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: true,
          position: 'top'
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              return `${context.label}: ${context.parsed.y} rezervacija`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  });
  
  // Prikaži sažetak
  const total = data.values.reduce((sum, val) => sum + val, 0);
  const max = Math.max(...data.values);
  const maxLabel = data.labels[data.values.indexOf(max)];
  
  summaryDiv.innerHTML = `
    <strong>Sažetak:</strong><br>
    • Ukupno rezervacija: <strong>${total}</strong><br>
    • Najviše rezervacija: <strong>${max}</strong> (${maxLabel})<br>
    • Prosječno po kategoriji: <strong>${(total / data.values.length).toFixed(1)}</strong>
  `;
}

// Analiza zauzetosti po terminima
async function loadTimeSlotAnalytics() {
  const startDate = document.getElementById('analytics-start-date').value;
  const endDate = document.getElementById('analytics-end-date').value;
  const includeFree = document.getElementById('include-free-reservations').checked;
  const btn = document.getElementById('time-slot-analytics-btn');
  
  if (!startDate || !endDate) {
    showError('Molimo izaberite početni i krajnji datum');
    return;
  }
  
  if (new Date(startDate) > new Date(endDate)) {
    showError('Početni datum ne može biti nakon krajnjeg datuma');
    return;
  }
  
  try {
    LoadingManager.show(btn, 'Učitavam analitiku...');
    const data = await fetchAnalytics('time_slots', startDate, endDate, includeFree);
    
    if (!data) {
      showError('Nema podataka za prikaz');
      return;
    }
    
    // Pripremi podatke za chart
    const chartData = {
      labels: data.map(item => item.time_slot),
      values: data.map(item => item.count),
      datasetLabel: 'Broj rezervacija po terminu',
      backgroundColor: 'rgba(54, 162, 235, 0.8)',
      borderColor: 'rgba(54, 162, 235, 1)'
    };
    
    displayChart(chartData, `Analiza zauzetosti po terminima (${startDate} - ${endDate})`);
    showSuccess('Analitika uspješno učitana');
    
  } catch (error) {
    console.error('Analytics error:', error);
    showError('Greška pri učitavanju analitike');
  } finally {
    LoadingManager.hide(btn);
  }
}

// Analiza po tipovima vozila
async function loadVehicleTypeAnalytics() {
  const startDate = document.getElementById('analytics-start-date').value;
  const endDate = document.getElementById('analytics-end-date').value;
  const includeFree = document.getElementById('include-free-reservations').checked;
  const btn = document.getElementById('vehicle-type-analytics-btn');
  
  if (!startDate || !endDate) {
    showError('Molimo izaberite početni i krajnji datum');
    return;
  }
  
  if (new Date(startDate) > new Date(endDate)) {
    showError('Početni datum ne može biti nakon krajnjeg datuma');
    return;
  }
  
  try {
    LoadingManager.show(btn, 'Učitavam analitiku...');
    const data = await fetchAnalytics('vehicle_types', startDate, endDate, includeFree);
    
    if (!data) {
      showError('Nema podataka za prikaz');
      return;
    }
    
    // Pripremi podatke za chart
    const chartData = {
      labels: data.map(item => item.vehicle_type),
      values: data.map(item => item.count),
      datasetLabel: 'Broj rezervacija po tipu vozila',
      backgroundColor: 'rgba(76, 175, 80, 0.8)',
      borderColor: 'rgba(76, 175, 80, 1)'
    };
    
    displayChart(chartData, `Analiza po tipovima vozila (${startDate} - ${endDate})`);
    showSuccess('Analitika uspješno učitana');
    
  } catch (error) {
    console.error('Analytics error:', error);
    showError('Greška pri učitavanju analitike');
  } finally {
    LoadingManager.hide(btn);
  }
}

// Analiza po državama
async function loadCountryAnalytics() {
  const startDate = document.getElementById('analytics-start-date').value;
  const endDate = document.getElementById('analytics-end-date').value;
  const includeFree = document.getElementById('include-free-reservations').checked;
  const btn = document.getElementById('country-analytics-btn');
  
  if (!startDate || !endDate) {
    showError('Molimo izaberite početni i krajnji datum');
    return;
  }
  
  if (new Date(startDate) > new Date(endDate)) {
    showError('Početni datum ne može biti nakon krajnjeg datuma');
    return;
  }
  
  try {
    LoadingManager.show(btn, 'Učitavam analitiku...');
    const data = await fetchAnalytics('countries', startDate, endDate, includeFree);
    
    if (!data) {
      showError('Nema podataka za prikaz');
      return;
    }
    
    // Pripremi podatke za chart
    const chartData = {
      labels: data.map(item => item.country),
      values: data.map(item => item.count),
      datasetLabel: 'Broj rezervacija po državi',
      backgroundColor: 'rgba(255, 152, 0, 0.8)',
      borderColor: 'rgba(255, 152, 0, 1)'
    };
    
    displayChart(chartData, `Analiza po državama (${startDate} - ${endDate})`);
    showSuccess('Analitika uspješno učitana');
    
  } catch (error) {
    console.error('Analytics error:', error);
    showError('Greška pri učitavanju analitike');
  } finally {
    LoadingManager.hide(btn);
  }
}

// ===========================
// UTILITY FUNCTIONS
// ===========================

// Debounce funkcija za optimizaciju pretrage
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

// Loading state manager
const LoadingManager = {
  show(element, text = 'Učitavanje...') {
    if (!element) return;
    
    const originalText = element.textContent;
    const originalDisabled = element.disabled;
    
    element.disabled = true;
    element.textContent = text;
    element.dataset.originalText = originalText;
    element.dataset.originalDisabled = originalDisabled;
  },
  
  hide(element) {
    if (!element) return;
    
    element.disabled = element.dataset.originalDisabled === 'true';
    element.textContent = element.dataset.originalText || '';
    delete element.dataset.originalText;
    delete element.dataset.originalDisabled;
  }
};

// Error handler
function showError(message, duration = 5000) {
  const errorDiv = document.createElement('div');
  errorDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #f44336;
    color: white;
    padding: 12px 20px;
    border-radius: 4px;
    z-index: 10000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
  `;
  errorDiv.textContent = message;
  
  // Dodaj CSS animaciju
  if (!document.getElementById('error-animations')) {
    const style = document.createElement('style');
    style.id = 'error-animations';
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
    `;
    document.head.appendChild(style);
  }
  
  document.body.appendChild(errorDiv);
  
  setTimeout(() => {
    errorDiv.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => errorDiv.remove(), 300);
  }, duration);
}

// Success handler
function showSuccess(message, duration = 3000) {
  const successDiv = document.createElement('div');
  successDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #4caf50;
    color: white;
    padding: 12px 20px;
    border-radius: 4px;
    z-index: 10000;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    animation: slideIn 0.3s ease;
  `;
  successDiv.textContent = message;
  
  document.body.appendChild(successDiv);
  
  setTimeout(() => {
    successDiv.style.animation = 'slideOut 0.3s ease';
    setTimeout(() => successDiv.remove(), 300);
  }, duration);
}

// ===========================
// LAZY LOADING MANAGER
// ===========================

const LazyLoader = {
  loadedComponents: new Set(),
  
  // Lazy load komponente na osnovu vidljivosti
  async loadComponent(componentName, loadFunction) {
    if (this.loadedComponents.has(componentName)) {
      return;
    }
    
    try {
      await loadFunction();
      this.loadedComponents.add(componentName);
      console.log(`Component ${componentName} loaded successfully`);
    } catch (error) {
      console.error(`Failed to load component ${componentName}:`, error);
      showError(`Greška pri učitavanju ${componentName}`);
    }
  },
  
  // Intersection Observer za lazy loading
  observeElement(element, callback, threshold = 0.1) {
    if (!element) return;
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          callback();
          observer.unobserve(entry.target);
        }
      });
    }, { threshold });
    
    observer.observe(element);
  }
};

// Lazy load blokirane dane kada postanu vidljivi
function initializeLazyLoading() {
  const blockedDaysSection = document.querySelector('.admin-panel-block');
  if (blockedDaysSection) {
    LazyLoader.observeElement(blockedDaysSection, () => {
      LazyLoader.loadComponent('blockedDays', loadBlockedDays);
    });
  }
  
  // Lazy load vehicle types
  const vehicleTypeSelects = document.querySelectorAll('select[id*="vehicle_type"]');
  if (vehicleTypeSelects.length > 0) {
    LazyLoader.observeElement(vehicleTypeSelects[0], () => {
      LazyLoader.loadComponent('vehicleTypes', loadVehicleTypes);
    });
  }
}