console.log('admin.js loaded');

// Cache za available_parking_slots
let availableParkingSlotsCache = null;
let availableParkingSlotsCacheTime = 0;
const AVAILABLE_PARKING_SLOTS_CACHE_DURATION = 5 * 60 * 1000; // 5 minuta

// Funkcija za dohvatanje available_parking_slots iz API-ja
async function getAvailableParkingSlots() {
  const now = Date.now();
  
  // Proveri cache
  if (availableParkingSlotsCache && (now - availableParkingSlotsCacheTime) < AVAILABLE_PARKING_SLOTS_CACHE_DURATION) {
    return availableParkingSlotsCache;
  }
  
  try {
    const response = await fetch('/api/system-config/available-parking-slots');
    const data = await response.json();
<<<<<<< HEAD
    const value = data.value || 9; // fallback na 9 ako API ne radi
=======
    const value = data.value || 8; // fallback na 8 ako API ne radi
>>>>>>> edd871dd4444f817be418d934462960767b66424
    
    // Sačuvaj u cache
    availableParkingSlotsCache = value;
    availableParkingSlotsCacheTime = now;
    
    return value;
  } catch (error) {
    console.error('Error fetching available parking slots:', error);
<<<<<<< HEAD
    return availableParkingSlotsCache || 9; // koristi cache ili fallback na 9
=======
    return availableParkingSlotsCache || 8; // koristi cache ili fallback na 8
>>>>>>> edd871dd4444f817be418d934462960767b66424
  }
}

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
  // Postavi min datum na današnji datum za sve date input polja - koristi lokalno vreme
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, '0');
  const day = String(today.getDate()).padStart(2, '0');
  const todayStr = `${year}-${month}-${day}`;
  
  const dateInputs = ['block-slot-date', 'block-day-date', 'free-reservation_date', 'paid-reservation-date'];
  dateInputs.forEach(id => {
    const input = document.getElementById(id);
    if (input) {
      input.setAttribute('min', todayStr);
      // Postavi današnji datum kao podrazumevanu vrednost
      input.value = todayStr;
    }
  });

  // Učitaj nedostupne i blokirane dane
  loadUnavailableDays();
  loadBlockedDays();
  


  // Inicijalizuj formu za neuspešna plaćanja
  console.log('Pozivam initializeFailedPaymentsForm...');
  initializeFailedPaymentsForm();
  console.log('initializeFailedPaymentsForm pozvana');

  // Inicijalizuj formu za uspešna plaćanja
  console.log('Pozivam initializeSuccessfulPaymentsForm...');
  initializeSuccessfulPaymentsForm();
  console.log('initializeSuccessfulPaymentsForm pozvana');

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

// Funkcija za dohvat svih nedostupnih dana (popunjeni rezervacijama)
async function fetchUnavailableDays() {
  try {
    const token = getToken();
    const res = await fetch('/api/unavailable-days', {
      headers: {
        'Authorization': 'Bearer ' + token
      }
    });
    if (!res.ok) {
      const errorData = await res.json().catch(() => ({}));
      throw new Error(errorData.error || `HTTP ${res.status}: ${res.statusText}`);
    }
    return await res.json();
  } catch (e) {
    console.log('Greška pri dohvatu nedostupnih dana:', e);
    throw e; // Re-throw the error so loadUnavailableDays can handle it
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
    if (!res.ok) {
      const errorData = await res.json().catch(() => ({}));
      throw new Error(errorData.error || `HTTP ${res.status}: ${res.statusText}`);
    }
    return await res.json();
  } catch (e) {
    console.log('Greška pri dohvatu blokiranih dana:', e);
    throw e; // Re-throw the error so loadBlockedDays can handle it
  }
}

// Funkcija za učitavanje i prikaz nedostupnih dana (popunjeni rezervacijama)
async function loadUnavailableDays() {
  const unavailableDaysList = document.getElementById('unavailable-days-list');
  if (!unavailableDaysList) return;

  try {
    // Prikaži loading state
    unavailableDaysList.innerHTML = '<div style="text-align:center; color:#666;">Učitavanje nedostupnih dana...</div>';
    
    console.log('Učitavanje nedostupnih dana...');
    const unavailableDays = await fetchUnavailableDays();
    console.log('Dohvaćeni nedostupni dani:', unavailableDays);
    
    // Filtriraj prošle dane - koristi lokalno vreme
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const dayOfMonth = String(today.getDate()).padStart(2, '0');
    const todayDateInt = parseInt(`${year}${month}${dayOfMonth}`); // npr. 20250803
    const filteredUnavailableDays = unavailableDays.filter(day => parseInt(day.date) >= todayDateInt);

    if (filteredUnavailableDays.length === 0) {
      unavailableDaysList.innerHTML = '<div style="text-align:center; color:#666;">Nema nedostupnih termina</div>';
      return;
    }

    // Optimizovano generisanje HTML-a
    const htmlFragments = [];
    console.log('Generisanje HTML-a za nedostupne dane...');
    
    filteredUnavailableDays.forEach(day => {
      const date = day.date;
      const formattedDate = `${date.substring(6, 8)}.${date.substring(4, 6)}.${date.substring(0, 4)}`;
      
      console.log('Obrađujem nedostupan dan:', formattedDate, 'is_fully_unavailable:', day.is_fully_unavailable, 'unavailable_slots:', day.unavailable_slots);
      
      const dayFragment = document.createElement('div');
      dayFragment.style.cssText = 'margin-bottom: 15px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;';
      
      const dateElement = document.createElement('strong');
      dateElement.style.color = '#666';
      dateElement.textContent = formattedDate;
      dayFragment.appendChild(dateElement);
      
      if (day.is_fully_unavailable) {
        const unavailableText = document.createElement('div');
        unavailableText.style.cssText = 'color: #666; margin-top: 5px;';
        unavailableText.textContent = '📋 ČITAV DAN POPUNJEN REZERVACIJAMA';
        dayFragment.appendChild(unavailableText);
      } else if (day.unavailable_slots && Array.isArray(day.unavailable_slots) && day.unavailable_slots.length > 0) {
        const slotsContainer = document.createElement('div');
        slotsContainer.style.marginTop = '5px';
        
        day.unavailable_slots.forEach(slot => {
          const slotElement = document.createElement('div');
          slotElement.style.cssText = 'color: #666; margin-left: 10px;';
          slotElement.textContent = `• ${slot.time_range} (popunjeno rezervacijama)`;
          slotsContainer.appendChild(slotElement);
        });
        
        dayFragment.appendChild(slotsContainer);
      }
      
      htmlFragments.push(dayFragment);
    });

    // Batch DOM update
    unavailableDaysList.innerHTML = '';
    htmlFragments.forEach(fragment => {
      unavailableDaysList.appendChild(fragment);
    });
    
  } catch (error) {
    console.error('Greška pri učitavanju nedostupnih dana:', error);
    unavailableDaysList.innerHTML = '<div style="text-align:center; color:#d32f2f;">Greška pri učitavanju nedostupnih dana</div>';
    showError('Greška pri učitavanju nedostupnih dana');
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
    
    // Filtriraj prošle dane - koristi lokalno vreme
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    const todayDateInt = parseInt(`${year}${month}${day}`); // npr. 20250803
    const filteredBlockedDays = blockedDays.filter(day => parseInt(day.date) >= todayDateInt);

    if (filteredBlockedDays.length === 0) {
      blockedDaysList.innerHTML = '<div style="text-align:center; color:#666;">Nema blokiranih dana ni termina</div>';
      return;
    }
    // Optimizovano generisanje HTML-a
    const htmlFragments = [];
    console.log('Generisanje HTML-a za blokirane dane...');
    
    filteredBlockedDays.forEach(day => {
      const date = day.date;
      const formattedDate = `${date.substring(6, 8)}.${date.substring(4, 6)}.${date.substring(0, 4)}`;
      const fullDate = `${date.substring(0, 4)}-${date.substring(4, 6)}-${date.substring(6, 8)}`;
      
      console.log('Obrađujem dan:', formattedDate, 'is_fully_blocked:', day.is_fully_blocked, 'blocked_slots:', day.blocked_slots);
      
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
      } else if (day.blocked_slots && Array.isArray(day.blocked_slots) && day.blocked_slots.length > 0) {
        // Proveri da li je današnji dan - koristi lokalno vreme
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const dayOfMonth = String(today.getDate()).padStart(2, '0');
        const todayStr = `${year}-${month}-${dayOfMonth}`;
        const isToday = fullDate === todayStr;
        const currentTime = new Date();
        
        // Filtriraj prošle termine za današnji dan
        const validSlots = day.blocked_slots.filter(slot => {
          if (!isToday) return true; // Za ostale dane prikaži sve termine
          
          // Za današnji dan, proveri da li je termin prošao
          const slotTimeStr = slot.time_range.split(' - ')[1]; // Uzmi KRAJ termina (ne početak)
          const slotTime = new Date();
          const [hours, minutes] = slotTimeStr.split(':').map(Number);
          slotTime.setHours(hours, minutes, 0, 0);
          
          // Dozvoli termin ako je KRAJ termina u budućnosti (sa 5 minuta tolerancije)
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
    blockedDaysList.innerHTML = '<div style="text-align:center; color:#d32f2f;">Greška pri učitavanju blokiranih dana</div>';
    showError('Greška pri učitavanju blokiranih dana');
  }
}

// Funkcija za osvežavanje liste blokiranih dana
async function refreshBlockedDays() {
  await loadUnavailableDays();
  await loadBlockedDays();
}

// Funkcija za odblokiranje dana
window.deblockDay = async function(date) {
  console.log('deblockDay pozvan sa datumom:', date);
  if (!confirm('Da li ste sigurni da želite da odblokirate ceo dan?')) {
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
  console.log('Proveravam postojeće rezervacije za datum:', date, 'i termine:', slots);
  try {
    console.log('Šaljem zahtev na /api/admin/check-existing-reservations');
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
      }).catch(e => console.log('Greška pri logiranju:', e));
      
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
    console.error('Greška pri proveri rezervacija:', error);
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
        
        console.log('Osvežavam listu blokiranih dana...');
        
        // Osveži listu blokiranih dana
        await refreshBlockedDays();
        
        console.log('Lista osvežena, zatvaram modal...');
        
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
        console.error('Greška pri osvežavanju:', error);
        alert('Termini su blokirani, ali greška pri osvežavanju liste.');
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
      dayStatus.innerHTML = `<strong style="color:#ff9800;">⚠️ DELIMIČNO BLOKIRANO</strong><br>${blockedCount} od ${totalCount} termina je blokirano.`;
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
    console.error('Greška pri proveri statusa dana:', error);
    dayStatus.innerHTML = '<strong style="color:#ff9800;">⚠️ GREŠKA</strong><br>Ne mogu da proverim status dana.';
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
    console.error('Greška pri proveri statusa dana:', error);
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
          `Nema rezervacija za datum ${date}.\n\nŽelite li da blokirate ceo dan?`
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
      alert('Greška pri proveri rezervacija.');
    }
  } catch (error) {
    console.error('Greška pri proveri rezervacija:', error);
    alert('Greška pri proveri rezervacija. Pokušajte ponovo.');
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
  const merchant_transaction_id = document.getElementById('search-merchant_transaction_id').value.trim();
  const date = document.getElementById('search-date').value;
  const user_name = document.getElementById('search-user_name').value.trim();
  const email = document.getElementById('search-email').value.trim();
  const vehicle_type_id = document.getElementById('search-vehicle_type_id').value;
  const license_plate = document.getElementById('search-license_plate').value.trim();
  const status = document.getElementById('search-status').value;

  // Proveri da li je unet bar jedan kriterijum
  if (!merchant_transaction_id && !date && !user_name && !email && !vehicle_type_id && !license_plate && !status) {
    showError('Unesite bar jedan kriterijum za pretragu!');
    return;
  }

  const token = getToken();
  const params = new URLSearchParams();
  if (merchant_transaction_id) params.append('merchant_transaction_id', merchant_transaction_id);
  if (date) params.append('date', date);
  if (user_name) params.append('user_name', user_name);
  if (email) params.append('email', email);
  if (vehicle_type_id) params.append('vehicle_type_id', vehicle_type_id);
  if (license_plate) params.append('license_plate', license_plate);
  if (status) params.append('status', status);

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
    debouncedSearch();
    // Sakrij loading nakon 1 sekunde (debouncedSearch ne vraća Promise)
    setTimeout(() => {
      LoadingManager.hide(this);
    }, 1000);
  });
}

// Prikaži listu rezervacija
function showReservationsList(reservations) {
  const resultsDiv = document.getElementById('search-results');
  let html = `<h4>Pronađene rezervacije: ${reservations.length}</h4><div style="max-height:300px; overflow-y:auto;">`;
  
  reservations.forEach(reservation => {
    const statusColor = reservation.status === 'storno' ? 'red' : 
                       reservation.status === 'paid' ? 'green' : 'black';
    
    // Formatiraj datum u lokalnom formatu
    const reservationDate = new Date(reservation.reservation_date);
    const formattedDate = reservationDate.toLocaleDateString('sr-RS', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
    
    html += `
      <div style="border:1px solid #ccc; padding:10px; margin:5px 0; cursor:pointer;" 
           onclick="selectReservation(${reservation.id})">
        <strong>ID: ${reservation.id}</strong> | 
        <span style="color:${statusColor}">Status: ${reservation.status || 'N/A'}</span><br>
        Datum: ${formattedDate} | 
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
        // Formatiraj datum za HTML date input (YYYY-MM-DD) - koristi lokalno vreme
        const reservationDate = new Date(reservation.reservation_date);
        const year = reservationDate.getFullYear();
        const month = String(reservationDate.getMonth() + 1).padStart(2, '0');
        const day = String(reservationDate.getDate()).padStart(2, '0');
        const formattedDateForInput = `${year}-${month}-${day}`;
        searchDateInput.value = formattedDateForInput;
      }
      
      // Resetuj ostala polja za pretragu
      const searchMerchantInput = document.getElementById('search-merchant_transaction_id');
      if (searchMerchantInput) searchMerchantInput.value = reservation.merchant_transaction_id || '';
      
      const searchNameInput = document.getElementById('search-user_name');
      if (searchNameInput) searchNameInput.value = reservation.user_name || '';
      
      const searchEmailInput = document.getElementById('search-email');
      if (searchEmailInput) searchEmailInput.value = reservation.email || '';
      
      const searchLicenseInput = document.getElementById('search-license_plate');
      if (searchLicenseInput) searchLicenseInput.value = reservation.license_plate || '';
      
      const searchVehicleTypeInput = document.getElementById('search-vehicle_type_id');
      if (searchVehicleTypeInput) searchVehicleTypeInput.value = reservation.vehicle_type_id || '';
      
      const searchStatusInput = document.getElementById('search-status');
      if (searchStatusInput) searchStatusInput.value = reservation.status || '';
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

  // Formatiraj datum za HTML date input (YYYY-MM-DD) - koristi lokalno vreme
  const year = reservationDate.getFullYear();
  const month = String(reservationDate.getMonth() + 1).padStart(2, '0');
  const day = String(reservationDate.getDate()).padStart(2, '0');
  const formattedDateForInput = `${year}-${month}-${day}`;

      const form = document.createElement('form');
      form.innerHTML = `
    <h4>Rezervacija ID: ${reservation.id}</h4>
    <label>Status:</label>
    <input type="text" id="status" value="${reservation.status || ''}" readonly style="color:${reservation.status === 'storno' ? 'red' : 'black'};"><br>
        <label>Merchant Transaction ID:</label>
        <input type="text" id="merchant_transaction_id" value="${reservation.merchant_transaction_id || ''}" readonly><br>
    <label>Datum rezervacije:</label>
    <input type="date" id="reservation_date" value="${formattedDateForInput}" readonly style="background-color:#f5f5f5;"><br>
        <label>Ime i prezime:</label>
        <input type="text" id="user_name" value="${reservation.user_name || ''}" readonly><br>
        <label>Država:</label>
    <select id="country" ${isPastDate ? 'disabled' : ''}>
      <option value="">Izaberi državu</option>
      <option value="ME" ${reservation.country === 'ME' ? 'selected' : ''}>Crna Gora</option>
      <option value="HR" ${reservation.country === 'HR' ? 'selected' : ''}>Hrvatska</option>
      <option value="RS" ${reservation.country === 'RS' ? 'selected' : ''}>Srbija</option>
      <option value="BA" ${reservation.country === 'BA' ? 'selected' : ''}>Bosna i Hercegovina</option>
      <option value="MK" ${reservation.country === 'MK' ? 'selected' : ''}>Severna Makedonija</option>
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
      <option value="DE" ${reservation.country === 'DE' ? 'selected' : ''}>Nemačka</option>
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
<<<<<<< HEAD
    <input type="text" id="license_plate" value="${reservation.license_plate || ''}" ${isPastDate ? 'readonly' : ''} style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-ZŠĐŽČĆ0-9]/g, '');"><br>
=======
    <input type="text" id="license_plate" value="${reservation.license_plate || ''}" ${isPastDate ? 'readonly' : ''} style="text-transform: uppercase;" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');"><br>
>>>>>>> edd871dd4444f817be418d934462960767b66424
        <label>Email:</label>
    <input type="email" id="email" value="${reservation.email || ''}" ${isPastDate ? 'readonly' : ''}><br>
        <label>ID tipa vozila:</label>
    <select id="vehicle_type_id" ${isPastDate ? 'disabled' : ''}>
      <option value="">Izaberi tip vozila</option>
    </select><br>
        <label>ID drop-off termina:</label>
    <input type="text" id="drop_off_time_slot_id_display" value="${reservation.drop_off_time_slot_id || ''}" readonly style="background-color:#f5f5f5;"><br>
        <label>ID pick-up termina:</label>
    <input type="text" id="pick_up_time_slot_id_display" value="${reservation.pick_up_time_slot_id || ''}" readonly style="background-color:#f5f5f5;"><br>
    <button type="button" id="save-edit-reservation" style="background:#9c1420;color:#fff;border:none;padding:8px 15px;border-radius:3px;" ${isPastDate ? 'disabled' : ''}>Sačuvaj izmene</button>
    <button type="button" id="change-date-time-btn" style="background:#ff9800;color:#fff;border:none;padding:8px 15px;border-radius:3px;">Promena datuma i termina rezervacije</button>
    ${isPastDate ? '<p style="color: red; font-weight: bold;">⚠️ Datum rezervacije je prošao - izmena nije dozvoljena</p>' : ''}
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
      alert('Ne možete izmeniti rezervaciju čiji je datum prošao!');
      return;
    }
    
    const email = document.getElementById('email').value.trim();
    if (!validateEmailWithFeedback(document.getElementById('email'), true)) {
      return;
    }
    
        const newData = {
          license_plate: document.getElementById('license_plate').value,
      email: email,
      country: document.getElementById('country').value,
          vehicle_type_id: document.getElementById('vehicle_type_id').value
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

  // Dodaj event listener za dugme promene datuma i termina
  const changeDateTimeBtn = document.getElementById('change-date-time-btn');
  if (changeDateTimeBtn) {
    changeDateTimeBtn.onclick = function() {
      showChangeDateTimeModal(reservation);
    };
  }

  // Proveri da li je rezervacija besplatna
  const isFreeReservation = reservation.status === 'free';
  
  console.log('DEBUG: Status rezervacije:', {
    id: reservation.id,
    status: reservation.status,
    isFree: isFreeReservation
  });

  // Dodaj dugme "Storniraj fiskalni račun" samo za plaćene rezervacije
  if (!isFreeReservation) {
    const stornirajBtn = document.createElement('button');
    stornirajBtn.textContent = 'Storniraj fiskalni račun';
    stornirajBtn.style.background = '#c00';
    stornirajBtn.style.color = '#fff';
    stornirajBtn.style.border = 'none';
    stornirajBtn.style.padding = '8px 15px';
    stornirajBtn.style.borderRadius = '3px';
    stornirajBtn.type = 'button';
    stornirajBtn.onclick = function(event) {
      event.preventDefault();
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
              let msg = 'Račun je uspješno storniran!';
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
              alert('Greška: ' + (data.message || 'Storniranje nije uspelo.'));
          }
        });
    };
    form.appendChild(stornirajBtn);
  }

  // Izgled računa/potvrde dugme
  const testPdfBtn = document.createElement('button');
  const buttonText = isFreeReservation ? 'Izgled potvrde' : 'Izgled računa';
  testPdfBtn.textContent = buttonText;
  testPdfBtn.style.background = '#9c1420';
  testPdfBtn.style.color = '#fff';
  testPdfBtn.style.border = 'none';
  testPdfBtn.style.padding = '8px 15px';
  testPdfBtn.style.borderRadius = '3px';
  testPdfBtn.onclick = function() {
      if (isFreeReservation) {
          window.open('/free-reservation-confirmation/' + currentReservationId, '_blank');
      } else {
          window.open('/test-pdf/' + currentReservationId, '_blank');
      }
  };
  form.appendChild(testPdfBtn);



  // Popuni drop-downove
  populateVehicleTypesSelect('vehicle_type_id', reservation.vehicle_type_id);
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
<<<<<<< HEAD
      // Filtriraj velika slova (uključujući dijakritičke simbole) i brojeve
      this.value = this.value.toUpperCase().replace(/[^A-ZŠĐŽČĆ0-9]/g, '');
=======
      // Filtriraj samo velika slova i brojeve
      this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
>>>>>>> edd871dd4444f817be418d934462960767b66424
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
    // Admin panel ne koristi setupSlotFilters za besplatne rezervacije
  }

  freeReservationDate.addEventListener('change', function() {
    const newDate = this.value;
    populateTimeSlotSelects(newDate, 'free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
    // Admin panel ne koristi setupSlotFilters za besplatne rezervacije
  });
  
  // Ažuriraj filtriranje za današnji dan svakih 5 minuta
  setInterval(function() {
    const today = new Date().toISOString().split('T')[0];
    
    // Ažuriraj besplatnu rezervaciju ako je današnji datum
    const freeDate = document.getElementById('free-reservation_date');
    if (freeDate && freeDate.value === today) {
      populateTimeSlotSelects(freeDate.value, 'free-drop_off_time_slot_id', 'free-pick_up_time_slot_id');
      // Admin panel ne koristi setupSlotFilters za besplatne rezervacije
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
  
  // Učitaj tipove vozila za besplatne rezervacije, search i edit
  populateVehicleTypesSelect('free-vehicle_type_id');
  populateVehicleTypesSelect('search-vehicle_type_id');
  populateVehicleTypesSelect('vehicle_type_id');
  loadVehicleTypes();
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
  
  // Admin može da pravi besplatne rezervacije za sve termine
  // (validacija se vrši samo na frontend-u za korisnike)
  
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
    if (data.success) {
      let message = 'Besplatna rezervacija je uspješno upisana i potvrda je poslata na email!';
      
      // Dodaj upozorenje ako je bio poslednji slot
      if (data.warning) {
        message += '\n\n⚠️ UPOZORENJE: Rezervisali ste poslednji dostupan slot za ovaj termin!';
      }
      
      alert(message);
      
      // Resetuj polja
      document.getElementById('free-user_name').value = '';
      document.getElementById('free-country').value = '';
      document.getElementById('free-license_plate').value = '';
      document.getElementById('free-email').value = '';
      document.getElementById('free-vehicle_type_id').value = '';
      document.getElementById('free-reservation_date').value = '';
      document.getElementById('free-drop_off_time_slot_id').value = '';
      document.getElementById('free-pick_up_time_slot_id').value = '';
    } else {
      alert(data.message || 'Greška pri upisu besplatne rezervacije.');
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
        alert('Molimo izaberite datum za dnevni finansijski izveštaj.');
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
        alert('Molimo izaberite godinu za godišnji finansijski izveštaj.');
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
        alert('Molimo izaberite datum za dnevni izveštaj po tipu vozila.');
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
        alert('Molimo izaberite godinu za godišnji izveštaj po tipu vozila.');
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
    
    // Formatiraj datum za prikaz
    const formattedDate = new Date(date).toLocaleDateString('sr-RS', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit'
    });
    
    modalContent.innerHTML = `
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0; color: #333;">📋 Rezervacije za datum: ${formattedDate}</h2>
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
    }
  );
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
    console.log('fetchAnalytics called with:', { type, startDate, endDate, includeFree });
    
    const token = getToken();
    const params = new URLSearchParams({
      type: type,
      start_date: startDate,
      end_date: endDate,
      include_free: includeFree ? '1' : '0'
    });
    
    console.log('Request URL:', `/api/admin/analytics?${params}`);
    console.log('Request headers:', { 'Authorization': 'Bearer ' + token });
    
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
        throw new Error('Niste autorizovani. Molimo se ulogujte ponovo.');
      } else if (response.status === 500) {
        throw new Error('Greška na serveru. Pokušajte ponovo kasnije.');
      } else {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }
    }
    
    const data = await response.json();
    
    console.log('Raw response data:', data);
    
    // Validacija podataka - podržava i stari format (niz) i novi format (objekat sa slots)
    if (!data || (Array.isArray(data) && data.length === 0) || 
        (!Array.isArray(data) && (!data.slots || !Array.isArray(data.slots) || data.slots.length === 0))) {
      throw new Error('Nema podataka za prikaz');
    }
    
    return data;
  } catch (error) {
    console.error('Greška pri dohvatu analitike:', error);
    
    if (error.name === 'AbortError') {
      showError('Zahtev je prekinut zbog prekoračenja vremena. Pokušajte ponovo.');
    } else {
      showError(`Greška pri dohvatu podataka: ${error.message}`);
    }
    
    return null;
  }
}

// Funkcija za prikaz grafikona
async function displayChart(data, title, chartType = 'bar', limitYAxis = true) {
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
  
  // Dohvati maksimalnu kapacitet za Y osu samo ako je potrebno
  let maxCapacity = null;
  if (limitYAxis) {
    maxCapacity = await getAvailableParkingSlots();
  }
  
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
              return `${context.label}: ${Math.round(context.parsed.y)} rezervacija`;
            }
          }
        }
      },
      scales: {
        y: {
          beginAtZero: true,
          ...(maxCapacity && { max: maxCapacity }),
          ticks: {
            stepSize: 1
          }
        }
      }
    }
  }
  );
  
      // Prikaži sažetak
    const total = data.values.reduce((sum, val) => sum + val, 0);
    const max = Math.max(...data.values);
    const maxLabel = data.labels[data.values.indexOf(max)];
    
    // Za analizu termina, prikaži da se radi o zauzetosti slotova, ne jedinstvenim rezervacijama
    const isTimeSlotAnalysis = title.includes('zauzetosti po terminima');
    console.log('DisplayChart Debug:', {
      title: title,
      isTimeSlotAnalysis: isTimeSlotAnalysis,
      total: total,
      max: max,
      maxLabel: maxLabel,
      codeVersion: 'DISPLAY_CHART_V2'
    });
    
    // Ako imamo summary podatke, koristi ih
    let summaryText;
    if (data.summary && isTimeSlotAnalysis) {
      // Prikaži intervale koji su dostigli maksimum
      let maxIntervalsText = '';
      if (data.summary.max_occupancy_intervals && data.summary.max_occupancy_intervals.length > 0) {
        const maxCapacity = await getAvailableParkingSlots();
        maxIntervalsText = `<br>• Intervali sa maksimalnom popunjenošću (${maxCapacity}): <strong>${data.summary.max_occupancy_intervals.join(', ')}</strong>`;
      }
      
      summaryText = `• Ukupno jedinstvenih rezervacija: <strong>${data.summary.total_unique_reservations}</strong><br>• Termin sa najvećom popunjenošću: <strong>${data.summary.max_occupancy}</strong> (${data.summary.max_occupancy_slot})${maxIntervalsText}<br>• Prosečna popunjenost po terminu: <strong>${data.summary.avg_occupancy}</strong>`;
    } else {
      summaryText = isTimeSlotAnalysis 
        ? `• Ukupna zauzetost slotova: <strong>${total}</strong><br>• Najviše zauzetih slotova: <strong>${max}</strong> (${maxLabel})<br>• Prosečno po terminu: <strong>${(total / data.values.length).toFixed(1)}</strong>`
        : `• Ukupno rezervacija: <strong>${total}</strong><br>• Najviše rezervacija: <strong>${max}</strong> (${maxLabel})<br>• Prosečno po kategoriji: <strong>${(total / data.values.length).toFixed(1)}</strong>`;
    }
    
    console.log('Summary Debug:', {
      isTimeSlotAnalysis: isTimeSlotAnalysis,
      summaryText: summaryText,
      finalHTML: `<strong>Sažetak:</strong><br>${summaryText}`
    });
    
    // Forsirano ažuriranje HTML-a
    summaryDiv.innerHTML = '';
    summaryDiv.innerHTML = `
      <strong>Sažetak:</strong><br>
      ${summaryText}
    `;
    
    // Dodatna provera
    console.log('HTML after update:', summaryDiv.innerHTML);
    console.log('Element exists:', !!summaryDiv);
    console.log('Element visible:', summaryDiv.style.display !== 'none');
    
    // Proveri da li se HTML zaista prikazuje u DOM-u
    setTimeout(() => {
      console.log('DOM check after 1 second:', summaryDiv.innerHTML);
      console.log('Element text content:', summaryDiv.textContent);
    }, 1000);
}

// Funkcija za formatiranje datuma u dd.mm.yyyy format
function formatDateForDisplay(dateString) {
  const date = new Date(dateString);
  const day = String(date.getDate()).padStart(2, '0');
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const year = date.getFullYear();
  return `${day}.${month}.${year}`;
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
    console.log('Analytics data received:', data);
    
    const slots = data.slots || data; // Podržava i stari i novi format
    const summary = data.summary || null;
    
    console.log('Processed slots:', slots);
    console.log('Summary data:', summary);
    
    if (!slots || !Array.isArray(slots) || slots.length === 0) {
      showError('Nema podataka za prikaz');
      return;
    }
    
    const chartData = {
      labels: slots.map(item => item.time_slot),
      values: slots.map(item => item.count),
      datasetLabel: 'Broj rezervacija po terminu',
      backgroundColor: 'rgba(54, 162, 235, 0.8)',
      borderColor: 'rgba(54, 162, 235, 1)',
      summary: summary // Dodaj summary podatke
    };
    
    const formattedStartDate = formatDateForDisplay(startDate);
    const formattedEndDate = formatDateForDisplay(endDate);
    await displayChart(chartData, `Analiza zauzetosti po terminima (${formattedStartDate} - ${formattedEndDate})`, 'bar', true);
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
    
    const formattedStartDate = formatDateForDisplay(startDate);
    const formattedEndDate = formatDateForDisplay(endDate);
    await displayChart(chartData, `Analiza po tipovima vozila (${formattedStartDate} - ${formattedEndDate})`, 'bar', false);
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
    
    const formattedStartDate = formatDateForDisplay(startDate);
    const formattedEndDate = formatDateForDisplay(endDate);
    await displayChart(chartData, `Analiza po državama (${formattedStartDate} - ${formattedEndDate})`, 'bar', false);
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
  const unavailableDaysSection = document.querySelector('.admin-panel-block');
  if (unavailableDaysSection) {
    LazyLoader.observeElement(unavailableDaysSection, () => {
      LazyLoader.loadComponent('unavailableDays', loadUnavailableDays);
    });
  }
  
  const blockedDaysSection = document.querySelectorAll('.admin-panel-block')[1]; // Drugi admin-panel-block
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

// ===========================
// NEUSPEŠNA PLAĆANJA
// ===========================

// Inicijalizacija forme za neuspešna plaćanja
function initializeFailedPaymentsForm() {
  console.log('=== POČETAK initializeFailedPaymentsForm ===');
  
  const csvInput = document.getElementById('failed-payments-csv');
  const processBtn = document.getElementById('process-failed-payments-btn');
  const previewDiv = document.getElementById('csv-preview');
  
  console.log('Elementi pronađeni:', {
    csvInput: csvInput,
    processBtn: processBtn,
    previewDiv: previewDiv
  });
  
  if (csvInput && processBtn) {
    csvInput.addEventListener('change', handleCsvFileSelect);
    processBtn.addEventListener('click', handleFailedPaymentsSubmit);
    console.log('Event listeners dodani za neuspešna plaćanja');
    
    // Dodaj direktan test klik
    processBtn.addEventListener('click', function() {
      console.log('DIRECT TEST: Dugme za neuspešna plaćanja kliknuto!');
    });
  } else {
    console.error('Elementi za neuspešna plaćanja nisu pronađeni!');
    console.error('csvInput:', csvInput);
    console.error('processBtn:', processBtn);
  }
}

// Obrada izbora CSV fajla
function handleCsvFileSelect(event) {
  const file = event.target.files[0];
  const previewDiv = document.getElementById('csv-preview');
  const processBtn = document.getElementById('process-failed-payments-btn');
  
  if (!file) {
    previewDiv.style.display = 'none';
    processBtn.disabled = true;
    return;
  }
  
  if (!file.name.toLowerCase().endsWith('.csv')) {
    showError('❌ Molimo izaberite CSV fajl!');
    event.target.value = '';
    return;
  }
  
  const reader = new FileReader();
  reader.onload = function(e) {
    try {
      const csvContent = e.target.result;
      const lines = csvContent.split('\n');
      const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
      
      // Proveri da li postoji merchant_transaction_id kolona (merchantTxId)
      const merchantIdIndex = headers.findIndex(h => 
        h.toLowerCase() === 'merchanttxid' ||
        (h.toLowerCase().includes('merchant') && 
         h.toLowerCase().includes('transaction') && 
         h.toLowerCase().includes('id'))
      );
      
      if (merchantIdIndex === -1) {
        showError('❌ CSV fajl ne sadrži kolonu merchantTxId ili merchant_transaction_id!');
        event.target.value = '';
        previewDiv.style.display = 'none';
        processBtn.disabled = true;
        return;
      }
      
      // Prikaži preview prvih 10 redova
      const previewLines = lines.slice(0, 11); // headers + 10 redova
      previewDiv.innerHTML = previewLines.map(line => 
        line.replace(/,/g, ' | ').replace(/"/g, '')
      ).join('<br>');
      previewDiv.style.display = 'block';
      
      // Omogući dugme
      processBtn.disabled = false;
      
      showSuccess('✅ CSV fajl uspješno učitan!');
      
    } catch (error) {
      console.error('Error parsing CSV:', error);
      showError('❌ Greška pri čitanju CSV fajla!');
      event.target.value = '';
      previewDiv.style.display = 'none';
      processBtn.disabled = true;
    }
  };
  reader.readAsText(file);
}

// Obrada submit-a forme za neuspešna plaćanja
async function handleFailedPaymentsSubmit() {
  console.log('=== POČETAK handleFailedPaymentsSubmit ===');
  
  const fileInput = document.getElementById('failed-payments-csv');
  const submitBtn = document.getElementById('process-failed-payments-btn');
  const resultDiv = document.getElementById('failed-payments-result');
  
  console.log('Elementi u handleFailedPaymentsSubmit:', {
    fileInput: fileInput,
    submitBtn: submitBtn,
    resultDiv: resultDiv,
    hasFile: fileInput ? fileInput.files[0] : 'no fileInput'
  });
  
  if (!fileInput.files[0]) {
    showError('❌ Molimo izaberite CSV fajl!');
    return;
  }
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Obrađujem...';
  
  try {
    const file = fileInput.files[0];
    const csvContent = await readFileAsText(file);
    const lines = csvContent.split('\n');
    const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
    
    // Pronađi indeks merchant_transaction_id kolone (merchantTxId)
    const merchantIdIndex = headers.findIndex(h => 
      h.toLowerCase() === 'merchanttxid' ||
      (h.toLowerCase().includes('merchant') && 
       h.toLowerCase().includes('transaction') && 
       h.toLowerCase().includes('id'))
    );
    
    if (merchantIdIndex === -1) {
      throw new Error('CSV fajl ne sadrži kolonu merchantTxId ili merchant_transaction_id');
    }
    
    // Izvuci merchant_transaction_id iz svih redova
    const merchantIds = [];
    for (let i = 1; i < lines.length; i++) {
      if (lines[i].trim()) {
        const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
        if (values[merchantIdIndex]) {
          merchantIds.push(values[merchantIdIndex]);
        }
      }
    }
    
    if (merchantIds.length === 0) {
      throw new Error('Nema validnih merchant_transaction_id u CSV fajlu');
    }
    
    console.log('Pronađeno merchant IDs:', merchantIds.length);
    
    // Pošalji na server
    const token = getToken();
    const response = await fetch('/api/delete-failed-payments', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({ merchant_transaction_ids: merchantIds })
    });
    
    const responseData = await response.json();
    
    if (response.ok && responseData.success) {
      resultDiv.innerHTML = `
        <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px;">
          <h4 style="margin: 0 0 10px 0; color: #155724;">✅ USPJEŠNO OBRADJENO!</h4>
          <p style="margin: 5px 0;"><strong>Status:</strong> Neuspješna plaćanja su uspješno obradjena.</p>
          <p style="margin: 5px 0;"><strong>Obrisano iz temp_data:</strong> ${responseData.deleted_count} zapisa</p>
          <p style="margin: 5px 0;"><strong>Nije pronađeno:</strong> ${responseData.not_found_count} zapisa</p>
          <hr style="margin: 10px 0; border: none; border-top: 1px solid #c3e6cb;">
          <h5 style="margin: 10px 0 5px 0; color: #155724;">Detalji:</h5>
          <p style="margin: 5px 0; font-size: 12px;">• Ukupno merchant IDs u CSV: ${merchantIds.length}</p>
          <p style="margin: 5px 0; font-size: 12px;">• Obrisano iz temp_data: ${responseData.deleted_count}</p>
          <p style="margin: 5px 0; font-size: 12px;">• Nije pronađeno u temp_data: ${responseData.not_found_count}</p>
        </div>
      `;
      resultDiv.style.display = 'block';
      
      // Resetuj formu
      fileInput.value = '';
      document.getElementById('csv-preview').style.display = 'none';
      
      showSuccess(`✅ Uspješno obrisano ${responseData.deleted_count} neuspješnih plaćanja!`);
    } else {
      resultDiv.innerHTML = `
        <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
          <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ GREŠKA!</h4>
          <p style="margin: 5px 0;"><strong>Status:</strong> Greška pri obradi neuspješnih plaćanja.</p>
          <p style="margin: 5px 0;"><strong>Razlog:</strong> ${responseData.error || responseData.message || 'Nepoznata greška'}</p>
        </div>
      `;
      resultDiv.style.display = 'block';
      showError('❌ Greška pri obradi neuspješnih plaćanja!');
    }
  } catch (error) {
    console.error('Error in handleFailedPaymentsSubmit:', error);
    resultDiv.innerHTML = `
      <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
        <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ GREŠKA!</h4>
        <p style="margin: 5px 0;"><strong>Status:</strong> Došlo je do greške pri obradi CSV fajla.</p>
        <p style="margin: 5px 0;"><strong>Detalji:</strong> ${error.message}</p>
      </div>
    `;
    resultDiv.style.display = 'block';
    showError('❌ Greška pri obradi CSV fajla!');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Obradi neuspješna plaćanja';
  }
}

// Helper funkcija za čitanje fajla kao tekst
function readFileAsText(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = e => resolve(e.target.result);
    reader.onerror = e => reject(e);
    reader.readAsText(file);
  });
}

// Inicijalizacija forme za uspešna plaćanja
function initializeSuccessfulPaymentsForm() {
  const fileInput = document.getElementById('successful-payments-csv');
  const submitBtn = document.getElementById('process-successful-payments-btn');
  
  if (!fileInput || !submitBtn) {
    console.error('Elementi za uspešna plaćanja nisu pronađeni');
    return;
  }
  
  fileInput.addEventListener('change', handleSuccessfulCsvFileSelect);
  submitBtn.addEventListener('click', handleSuccessfulPaymentsSubmit);
}

// Handler za izbor CSV fajla za uspešna plaćanja
function handleSuccessfulCsvFileSelect(event) {
  const file = event.target.files[0];
  const submitBtn = document.getElementById('process-successful-payments-btn');
  const previewDiv = document.getElementById('successful-csv-preview');
  
  if (!file) {
    submitBtn.disabled = true;
    previewDiv.style.display = 'none';
    return;
  }
  
  if (!file.name.toLowerCase().endsWith('.csv')) {
    alert('Molimo izaberite CSV fajl!');
    event.target.value = '';
    submitBtn.disabled = true;
    previewDiv.style.display = 'none';
    return;
  }
  
  // Prikaži preview
  readFileAsText(file).then(content => {
    const lines = content.split('\n');
    const preview = lines.slice(0, 10).join('\n'); // Prikaži prvih 10 redova
    
    previewDiv.innerHTML = `<pre>${preview}</pre>`;
    previewDiv.style.display = 'block';
    submitBtn.disabled = false;
  }).catch(error => {
    console.error('Greška pri čitanju fajla:', error);
    alert('Greška pri čitanju fajla!');
    event.target.value = '';
    submitBtn.disabled = true;
    previewDiv.style.display = 'none';
  });
}

// Handler za submit forme za uspešna plaćanja
async function handleSuccessfulPaymentsSubmit() {
  const fileInput = document.getElementById('successful-payments-csv');
  const submitBtn = document.getElementById('process-successful-payments-btn');
  const resultDiv = document.getElementById('successful-payments-result');
  
  if (!fileInput.files[0]) {
    alert('Molimo izaberite CSV fajl!');
    return;
  }
  
  submitBtn.disabled = true;
  submitBtn.textContent = 'Obrađujem...';
  
  try {
    const content = await readFileAsText(fileInput.files[0]);
    const lines = content.split('\n').filter(line => line.trim());
    
    if (lines.length < 2) {
      throw new Error('CSV fajl je prazan ili ne sadrži dovoljno podataka');
    }
    
    // Parsiraj header
    const headers = lines[0].split(',').map(h => h.trim().replace(/"/g, ''));
    console.log('CSV headers:', headers);
    
    // Pronađi potrebne kolone
    const requiredColumns = ['merchantTxId', 'dateUser', 'amount', 'customerEmail', 'creditcardCardHolder', 'creditcardBinCountry'];
    const columnIndexes = {};
    
    for (const column of requiredColumns) {
      const index = headers.findIndex(h => h.toLowerCase() === column.toLowerCase());
      if (index === -1) {
        throw new Error(`CSV fajl ne sadrži kolonu: ${column}`);
      }
      columnIndexes[column] = index;
    }
    
    // Parsiraj podatke
    const csvData = [];
    for (let i = 1; i < lines.length; i++) {
      if (lines[i].trim()) {
        const values = lines[i].split(',').map(v => v.trim().replace(/"/g, ''));
        if (values[columnIndexes.merchantTxId]) {
          csvData.push({
            merchantTxId: values[columnIndexes.merchantTxId],
            dateUser: values[columnIndexes.dateUser],
            amount: parseFloat(values[columnIndexes.amount]) || 0,
            customerEmail: values[columnIndexes.customerEmail],
            creditcardCardHolder: values[columnIndexes.creditcardCardHolder],
            creditcardBinCountry: values[columnIndexes.creditcardBinCountry]
          });
        }
      }
    }
    
    if (csvData.length === 0) {
      throw new Error('Nema validnih podataka u CSV fajlu');
    }
    
    console.log('Parsirani podaci:', csvData.length, 'redova');
    
    // Pošalji na server
    const token = getToken();
    const response = await fetch('/api/check-successful-payments', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json', 
        'Accept': 'application/json',
        'Authorization': 'Bearer ' + token
      },
      body: JSON.stringify({ csv_data: csvData })
    });
    
    const responseData = await response.json();
    
    if (response.ok && responseData.success) {
      const missingTransactions = responseData.missing_transactions || [];
      
      let resultHtml = `
        <div style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 15px; border-radius: 4px;">
          <h4 style="margin: 0 0 10px 0; color: #155724;">✅ ANALIZA ZAVRŠENA!</h4>
          <p style="margin: 5px 0;"><strong>Status:</strong> Uspešna plaćanja su uspješno analizirana.</p>
          <p style="margin: 5px 0;"><strong>Ukupno u CSV:</strong> ${responseData.total_processed} transakcija</p>
          <p style="margin: 5px 0;"><strong>Nakon filtriranja refundova:</strong> ${responseData.filtered_count} transakcija</p>
          <p style="margin: 5px 0;"><strong>Postoje u reservations:</strong> ${responseData.existing_count} transakcija</p>
          <p style="margin: 5px 0;"><strong>Refundovane:</strong> ${responseData.refunded_count} transakcija</p>
          <p style="margin: 5px 0;"><strong>Nedostaju u reservations:</strong> ${responseData.missing_count} transakcija</p>
        </div>
      `;
      
      if (missingTransactions.length > 0) {
        resultHtml += `
          <div style="margin-top: 15px; background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px;">
            <h5 style="margin: 0 0 10px 0; color: #856404;">⚠️ TRANSAKCIJE KOJE NEDOSTAJU U RESERVATIONS TABELI:</h5>
            <div style="max-height: 300px; overflow-y: auto;">
        `;
        
        missingTransactions.forEach((transaction, index) => {
          resultHtml += `
            <div style="border: 1px solid #ffeaa7; padding: 10px; margin: 5px 0; border-radius: 4px; background: #fff;">
              <p style="margin: 2px 0; font-weight: bold;">Transakcija ${index + 1}:</p>
              <p style="margin: 2px 0;"><strong>Merchant ID:</strong> ${transaction.merchantTxId}</p>
              <p style="margin: 2px 0;"><strong>Datum:</strong> ${transaction.dateUser}</p>
              <p style="margin: 2px 0;"><strong>Iznos:</strong> €${transaction.amount}</p>
              <p style="margin: 2px 0;"><strong>Email:</strong> ${transaction.customerEmail}</p>
              <p style="margin: 2px 0;"><strong>Kartica:</strong> ${transaction.creditcardCardHolder}</p>
              <p style="margin: 2px 0;"><strong>Država:</strong> ${transaction.creditcardBinCountry}</p>
            </div>
          `;
        });
        
        resultHtml += `
            </div>
          </div>
        `;
      }
      
      resultDiv.innerHTML = resultHtml;
      resultDiv.style.display = 'block';
      
      if (missingTransactions.length > 0) {
        showError(`⚠️ Pronađeno ${missingTransactions.length} transakcija koje nedostaju u reservations tabeli!`);
      } else {
        showSuccess('✅ Sve transakcije su već u reservations tabeli!');
      }
    } else {
      resultDiv.innerHTML = `
        <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
          <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ GREŠKA!</h4>
          <p style="margin: 5px 0;"><strong>Status:</strong> Greška pri analizi uspješnih plaćanja.</p>
          <p style="margin: 5px 0;"><strong>Razlog:</strong> ${responseData.error || responseData.message || 'Nepoznata greška'}</p>
        </div>
      `;
      resultDiv.style.display = 'block';
      showError('❌ Greška pri analizi uspješnih plaćanja!');
    }
  } catch (error) {
    console.error('Error in handleSuccessfulPaymentsSubmit:', error);
    resultDiv.innerHTML = `
      <div style="background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 15px; border-radius: 4px;">
        <h4 style="margin: 0 0 10px 0; color: #721c24;">❌ GREŠKA!</h4>
        <p style="margin: 5px 0;"><strong>Status:</strong> Došlo je do greške pri obradi CSV fajla.</p>
        <p style="margin: 5px 0;"><strong>Detalji:</strong> ${error.message}</p>
      </div>
    `;
    resultDiv.style.display = 'block';
    showError('❌ Greška pri obradi CSV fajla!');
  } finally {
    submitBtn.disabled = false;
    submitBtn.textContent = 'Proveri uspješna plaćanja';
  }
}

// ===========================
// PROMENA DATUMA I TERMINA REZERVACIJE
// ===========================

// Funkcija za prikaz modala za promenu datuma i termina
function showChangeDateTimeModal(reservation) {
  console.log('showChangeDateTimeModal pozvan za rezervaciju:', reservation);
  
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
  
  // Formatiraj trenutni datum za prikaz
  const currentDate = new Date(reservation.reservation_date);
  const formattedCurrentDate = currentDate.toLocaleDateString('sr-RS', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit'
  });
  
  modalContent.innerHTML = `
    <h3>Promena datuma i termina rezervacije</h3>
    <p><strong>Trenutni datum:</strong> ${formattedCurrentDate}</p>
    <p><strong>Trenutni drop-off termin:</strong> ${reservation.drop_off_time_slot_id}</p>
    <p><strong>Trenutni pick-up termin:</strong> ${reservation.pick_up_time_slot_id}</p>
    
    <div style="margin: 20px 0;">
      <label>Novi datum:</label>
      <input type="date" id="new-reservation-date" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px;">
    </div>
    
    <div style="margin: 20px 0;">
      <label>Novi drop-off termin:</label>
      <select id="new-drop-off-time-slot" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Izaberi drop-off termin</option>
      </select>
    </div>
    
    <div style="margin: 20px 0;">
      <label>Novi pick-up termin:</label>
      <select id="new-pick-up-time-slot" style="width: 100%; padding: 8px; margin: 5px 0; border: 1px solid #ddd; border-radius: 4px;">
        <option value="">Izaberi pick-up termin</option>
      </select>
    </div>
    
    <div id="change-date-time-status" style="margin: 15px 0; padding: 10px; border-radius: 4px; display: none;"></div>
    
    <div style="text-align: right;">
      <button id="cancel-change-datetime-btn" style="background:#666;color:#fff;border:none;padding:8px 15px;border-radius:3px;margin-right:10px;">Otkaži</button>
      <button id="confirm-change-datetime-btn" data-reservation-id="${reservation.id}" style="background:#4caf50;color:#fff;border:none;padding:8px 15px;border-radius:3px;">Potvrdi promenu</button>
    </div>
  `;
  
  modal.appendChild(modalContent);
  document.body.appendChild(modal);
  
  // Postavi min i max datum
  const newDateInput = modalContent.querySelector('#new-reservation-date');
  const today = new Date();
  const maxDate = new Date();
  maxDate.setDate(today.getDate() + 90); // 90 dana u budućnost
  
  newDateInput.min = today.toISOString().split('T')[0];
  newDateInput.max = maxDate.toISOString().split('T')[0];
  
  // Event listener za promenu datuma
  newDateInput.addEventListener('change', function() {
    const selectedDate = this.value;
    if (selectedDate) {
      populateTimeSlotSelectsForChange(selectedDate, 'new-drop-off-time-slot', 'new-pick-up-time-slot');
      setupSlotFiltersForChange('new-drop-off-time-slot', 'new-pick-up-time-slot');
    }
  });
  
  // Event listeneri za dugmad
  const cancelBtn = modalContent.querySelector('#cancel-change-datetime-btn');
  const confirmBtn = modalContent.querySelector('#confirm-change-datetime-btn');
  
  cancelBtn.addEventListener('click', function() {
    modal.remove();
  });
  
  confirmBtn.addEventListener('click', function() {
    const reservationId = this.getAttribute('data-reservation-id');
    const newDate = newDateInput.value;
    const newDropOffSlot = modalContent.querySelector('#new-drop-off-time-slot').value;
    const newPickUpSlot = modalContent.querySelector('#new-pick-up-time-slot').value;
    
    if (!newDate || !newDropOffSlot || !newPickUpSlot) {
      showErrorInModal('Molimo popunite sva polja!', modalContent);
      return;
    }
    
    // Proveri da li su termini validni
    if (parseInt(newDropOffSlot) >= parseInt(newPickUpSlot)) {
      showErrorInModal('Pick-up termin mora biti nakon drop-off termina!', modalContent);
      return;
    }
    
    // Proveri da li je uopšte potrebna promena
    const currentDate = new Date(reservation.reservation_date).toISOString().split('T')[0];
    if (newDate === currentDate && 
        newDropOffSlot === reservation.drop_off_time_slot_id.toString() && 
        newPickUpSlot === reservation.pick_up_time_slot_id.toString()) {
      showErrorInModal('Nema promene - isti datum i termini!', modalContent);
      return;
    }
    
    // Pošalji zahtev za promenu
    changeReservationDateTime(reservationId, newDate, newDropOffSlot, newPickUpSlot, modal, modalContent);
  });
}

// Funkcija za popunjavanje termina u modalu za promenu
async function populateTimeSlotSelectsForChange(date, dropOffSelectId, pickUpSelectId) {
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
      dropOffSelect.appendChild(dropOffOption);

      const pickUpOption = document.createElement('option');
      pickUpOption.value = slot.id;
      pickUpOption.textContent = slot.time_slot;
      pickUpSelect.appendChild(pickUpOption);
    });
    
  } catch (error) {
    console.error('Greška pri učitavanju slotova:', error);
  }
}

// Funkcija za postavljanje filtera termina u modalu
function setupSlotFiltersForChange(dropOffSelectId, pickUpSelectId) {
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

// Funkcija za prikaz greške u modalu
function showErrorInModal(message, modalContent) {
  const statusDiv = modalContent.querySelector('#change-date-time-status');
  statusDiv.innerHTML = `<div style="background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px;">${message}</div>`;
  statusDiv.style.display = 'block';
}

// Funkcija za prikaz uspeha u modalu
function showSuccessInModal(message, modalContent) {
  const statusDiv = modalContent.querySelector('#change-date-time-status');
  statusDiv.innerHTML = `<div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 4px;">${message}</div>`;
  statusDiv.style.display = 'block';
}

// Funkcija za promenu datuma i termina rezervacije
async function changeReservationDateTime(reservationId, newDate, newDropOffSlot, newPickUpSlot, modal, modalContent) {
  try {
    const token = getToken();
    
    // Prvo proveri dostupnost termina
    const checkResponse = await fetch('/api/admin/check-slot-availability', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        date: newDate,
        drop_off_time_slot_id: newDropOffSlot,
        pick_up_time_slot_id: newPickUpSlot
      })
    });
    
    if (!checkResponse.ok) {
      const errorData = await checkResponse.json();
      showErrorInModal('Greška pri proveri dostupnosti: ' + (errorData.error || 'Nepoznata greška'), modalContent);
      return;
    }
    
    const checkData = await checkResponse.json();
    
    if (!checkData.available) {
      showErrorInModal('Izabrani termini nisu dostupni za novi datum!', modalContent);
      return;
    }
    
    // Ako su termini dostupni, izvrši promenu
    const changeResponse = await fetch('/api/admin/change-reservation-datetime', {
      method: 'POST',
      headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        reservation_id: reservationId,
        new_date: newDate,
        new_drop_off_time_slot_id: newDropOffSlot,
        new_pick_up_time_slot_id: newPickUpSlot
      })
    });
    
    if (!changeResponse.ok) {
      const errorData = await changeResponse.json();
      showErrorInModal('Greška pri promeni datuma: ' + (errorData.error || 'Nepoznata greška'), modalContent);
      return;
    }
    
    const changeData = await changeResponse.json();
    
    if (changeData.success) {
      showSuccessInModal('Datum i termini rezervacije su uspješno promenjeni!', modalContent);
      
      // Zatvori modal nakon 2 sekunde
      setTimeout(() => {
        modal.remove();
        // Osveži prikaz rezervacije
        selectReservation(reservationId);
      }, 2000);
    } else {
      showErrorInModal('Greška pri promeni datuma: ' + (changeData.error || 'Nepoznata greška'), modalContent);
    }
    
  } catch (error) {
    console.error('Greška pri promeni datuma:', error);
    showErrorInModal('Greška pri promeni datuma: ' + error.message, modalContent);
  }
}