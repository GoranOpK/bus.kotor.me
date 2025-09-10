// --- GLOBAL FUNCTIONS ---

// Session-based rezervacija zaštita
const RESERVATION_SESSION_KEY = 'bus_kotor_active_reservation';
console.log('Script loaded - RESERVATION_SESSION_KEY:', RESERVATION_SESSION_KEY);

// Funkcija za proveru aktivne rezervacije u session-u
function getActiveReservation() {
  console.log('=== getActiveReservation POZVAN ===');
  console.log('getActiveReservation - proveravam sessionStorage...');
  console.log('getActiveReservation - sessionStorage available:', typeof sessionStorage !== 'undefined');
  const sessionData = sessionStorage.getItem(RESERVATION_SESSION_KEY);
  console.log('getActiveReservation - sessionData:', sessionData);
  if (!sessionData) {
    console.log('getActiveReservation - nema sessionData, vraćam null');
    return null;
  }
  
  try {
    const reservation = JSON.parse(sessionData);
    console.log('getActiveReservation - parsed reservation:', reservation);
    // Proveri da li je rezervacija još važeća
    if (reservation.expires_at && new Date(reservation.expires_at) > new Date()) {
      console.log('getActiveReservation - rezervacija je važeća, vraćam:', reservation);
      return reservation;
    } else {
      // Rezervacija je istekla, obriši iz session-a
      console.log('getActiveReservation - rezervacija je istekla, brišem...');
      sessionStorage.removeItem(RESERVATION_SESSION_KEY);
      console.log('getActiveReservation - rezervacija istekla, vraćam null');
      return null;
    }
  } catch (e) {
    console.error('Greška pri parsiranju session rezervacije:', e);
    sessionStorage.removeItem(RESERVATION_SESSION_KEY);
    console.log('getActiveReservation - greška pri parsiranju, vraćam null');
    return null;
  }
}

// Funkcija za čuvanje aktivne rezervacije u session-u
function setActiveReservation(reservation) {
  console.log('=== setActiveReservation POZVAN ===');
  console.log('setActiveReservation - čuvam rezervaciju:', reservation);
  sessionStorage.setItem(RESERVATION_SESSION_KEY, JSON.stringify(reservation));
  console.log('setActiveReservation - rezervacija sačuvana u sessionStorage');
  console.log('setActiveReservation - proveravam da li je sačuvano:', sessionStorage.getItem(RESERVATION_SESSION_KEY));
}

// Funkcija za brisanje aktivne rezervacije iz session-a
function clearActiveReservation() {
  console.log('=== clearActiveReservation POZVAN ===');
  console.log('clearActiveReservation - brišem rezervaciju iz session-a');
  sessionStorage.removeItem(RESERVATION_SESSION_KEY);
  console.log('clearActiveReservation - rezervacija obrisana iz sessionStorage');
  console.log('clearActiveReservation - proveravam da li je obrisano:', sessionStorage.getItem(RESERVATION_SESSION_KEY));
}

// Funkcija za proveru da li korisnik već ima aktivnu rezervaciju
function hasActiveReservation() {
  console.log('=== hasActiveReservation POZVAN ===');
  const reservation = getActiveReservation();
  console.log('hasActiveReservation - proveravam:', reservation);
  const result = reservation !== null;
  console.log('hasActiveReservation - rezultat:', result);
  return result;
}

// Funkcija za sinhronizaciju session-a između tab-ova
function setupSessionSync() {
  // Slušaj promene u sessionStorage između tab-ova
  window.addEventListener('storage', function(e) {
    if (e.key === RESERVATION_SESSION_KEY) {
      if (e.newValue) {
        // Nova rezervacija je kreirana u drugom tab-u
        try {
          const newReservation = JSON.parse(e.newValue);
          currentSlotReservation = newReservation;
          if (newReservation.expires_at) {
            startReservationCountdown(newReservation.expires_at);
          }
        } catch (e) {
          console.error('Greška pri parsiranju session promene:', e);
        }
      } else {
        // Rezervacija je obrisana u drugom tab-u
        currentSlotReservation = null;
        if (reservationTimer) {
          clearInterval(reservationTimer);
        }
        checkAndToggleReserveButton();
      }
    }
  });
}

// Helper function to check if a time slot allows same arrival and departure
function allowsSameArrivalDeparture(timeSlot) {
  // Termini koji dozvoljavaju isti dolazak i odlazak:
  // - 00:00 - 07:00 (id: 1)
  // - 20:00 - 24:00 (id: 41)
  const specialTimeSlots = ['00:00 - 07:00', '20:00 - 24:00'];
  return specialTimeSlots.includes(timeSlot);
}

// Function to show free reservation success modal
function showFreeReservationSuccess() {
  const modal = document.getElementById('free-reservation-modal');
  if (modal) {
    modal.style.display = 'block';
  }
}

// Function to hide free reservation success modal
function hideFreeReservationSuccess() {
  const modal = document.getElementById('free-reservation-modal');
  if (modal) {
    modal.style.display = 'none';
  }
}

function fetchAvailableSlotsForDate(date, callback) {
  fetch('/api/timeslots/available?date=' + encodeURIComponent(date))
    .then(res => res.json())
    .then(callback);
}
function populateTimeSlotSelect(selectId, times, selectedValue = '') {
  const select = document.getElementById(selectId);
  if (!select) return;
  select.innerHTML = '<option value="">Select time slot</option>';

  const reservationDateInput = document.getElementById('reservation_date');
  const selectedDate = reservationDateInput?.value;
  const now = new Date();
  const todayStr = now.toISOString().slice(0, 10);

  let minTime = null;
  if (selectedDate === todayStr) {
    // Dozvoli trenutni termin - ne dodajemo +1 minut
    minTime = now.toTimeString().slice(0, 5);
  }

  times.forEach(time => {
    if (!minTime) {
      // Ako nije današnji datum, prikaži sve termine
      const option = document.createElement('option');
      option.value = time;
      option.textContent = time;
      select.appendChild(option);
    } else {
      // Za današnji datum, proveri da li je termin još aktivan
      // time je u formatu "HH:MM - HH:MM", treba da poredim sa krajem termina
      const timeEnd = time.split(' - ')[1]; // Uzmi kraj termina (HH:MM)
      if (timeEnd >= minTime) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        select.appendChild(option);
      }
    }
  });

  // Restore previous selection if still valid
  if (selectedValue && Array.from(select.options).some(opt => opt.value === selectedValue)) {
    select.value = selectedValue;
  }
}

function filterTimeSlots() {
  const arrivalSelect = document.getElementById('arrival-time-slot');
  const departureSelect = document.getElementById('departure-time-slot');
  if (!arrivalSelect || !departureSelect) return;
  
  // Uzmi sve dostupne opcije iz originalnih podataka
  const allTimeSlots = Object.keys(timeSlotMap).sort();
  
  // Proveri da li je tekući datum
  const reservationDateInput = document.getElementById('reservation_date');
  const selectedDate = reservationDateInput?.value;
  const now = new Date();
  const todayStr = now.toISOString().slice(0, 10);
  
  let minTime = null;
  if (selectedDate === todayStr) {
    // Dozvoli trenutni termin - ne dodajemo +1 minut
    minTime = now.toTimeString().slice(0, 5);
  }
  
  // Filtriraj termine za tekući datum
  const availableTimeSlots = allTimeSlots.filter(time => {
    if (!minTime) {
      // Ako nije današnji datum, prikaži sve termine
      return true;
    } else {
      // Za današnji datum, proveri da li je termin još aktivan
      // time je u formatu "HH:MM - HH:MM", treba da poredim sa krajem termina
      const timeEnd = time.split(' - ')[1]; // Uzmi kraj termina (HH:MM)
      return timeEnd >= minTime;
    }
  });
  
  const arrivalTime = arrivalSelect.value;
  const departureTime = departureSelect.value;

  if (arrivalTime && departureTime) {
    // Filter arrival options to before departure (ili isti termin ako je dozvoljen)
    arrivalSelect.innerHTML = '<option value="">Select time slot</option>';
    availableTimeSlots.forEach(time => {
      if (time < departureTime || (time === departureTime && allowsSameArrivalDeparture(time))) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        arrivalSelect.appendChild(option);
      }
    });
    arrivalSelect.value = (arrivalTime < departureTime || (arrivalTime === departureTime && allowsSameArrivalDeparture(arrivalTime))) ? arrivalTime : '';

    // Filter departure options to after arrival (ili isti termin ako je dozvoljen)
    departureSelect.innerHTML = '<option value="">Select time slot</option>';
    availableTimeSlots.forEach(time => {
      if (time > arrivalTime || (time === arrivalTime && allowsSameArrivalDeparture(time))) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        departureSelect.appendChild(option);
      }
    });
    departureSelect.value = (departureTime > arrivalTime || (departureTime === arrivalTime && allowsSameArrivalDeparture(departureTime))) ? departureTime : '';
  } else if (arrivalTime) {
    // Ako je odabran samo arrival, filtriraj departure da bude nakon arrival (ili isti termin ako je dozvoljen)
    populateTimeSlotSelect('arrival-time-slot', availableTimeSlots, arrivalTime);
    
    departureSelect.innerHTML = '<option value="">Select time slot</option>';
    availableTimeSlots.forEach(time => {
      if (time > arrivalTime || (time === arrivalTime && allowsSameArrivalDeparture(time))) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        departureSelect.appendChild(option);
      }
    });
  } else if (departureTime) {
    // Ako je odabran samo departure, filtriraj arrival da bude prije departure (ili isti termin ako je dozvoljen)
    populateTimeSlotSelect('departure-time-slot', availableTimeSlots, departureTime);
    
    arrivalSelect.innerHTML = '<option value="">Select time slot</option>';
    availableTimeSlots.forEach(time => {
      if (time < departureTime || (time === departureTime && allowsSameArrivalDeparture(time))) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        arrivalSelect.appendChild(option);
      }
    });
  } else {
    // Ako nije odabran ništa, prikaži sve opcije
    populateTimeSlotSelect('arrival-time-slot', availableTimeSlots);
    populateTimeSlotSelect('departure-time-slot', availableTimeSlots);
  }
}

// --- TRANSLATIONS ---

const translations = {
  en: {
    pickDate: "Pick a date",
    arrival: "Arrival time",
    departure: "Departure time",
    company: "Company name",
    country: "Country",
    registration: "Registration plates",
    email: "Email",
    vehicleCategory: "Select vehicle category",
    agree: "I agree to the",
    terms: "terms and conditions",
    mustAgree: "You must agree to the terms to reserve a slot.",
    reserve: "Reserve",
    termsTitle: "Terms and Conditions",
    freeParking: "Parking is free for this time segment!",
    parkingNotice: `If the carrier is unable to drop off and pick up passengers at the time slot for which the fee was paid, this action must be performed at the <a href="https://maps.app.goo.gl/oXD6SEzjyXtm4c586" target="_blank">Autoboka</a> parking and the <a href="https://maps.app.goo.gl/kPAD6mipzZTjCCYE7" target="_blank">Puč</a> parking.`,
    privacy: "I agree to the privacy policy",
    privacyLink: "Read policy"
  },
  mne: {
    pickDate: "Izaberite datum",
    arrival: "Vrijeme dolaska",
    departure: "Vrijeme odlaska",
    company: "Naziv kompanije",
    country: "Država",
    registration: "Registarske tablice",
    email: "Email",
    vehicleCategory: "Izaberite kategoriju vozila",
    agree: "Slažem se sa",
    terms: "uslovima korišćenja",
    mustAgree: "Morate prihvatiti uslove da biste rezervisali termin.",
    reserve: "Rezerviši",
    termsTitle: "Uslovi korišćenja",
    freeParking: "Parking je besplatan za ovaj vremenski segment!",
    parkingNotice: `U slučaju da prevoznik nije u mogućnosti da izvrši iskrcaj i ukucaj putnika u terminu za koji je plaćena naknada, isti navedenu radnju mora izvršiti na parkingu <a href="https://maps.app.goo.gl/oXD6SEzjyXtm4c586" target="_blank">Autoboke</a> i pakringu <a href="https://maps.app.goo.gl/kPAD6mipzZTjCCYE7" target="_blank">Puč</a>.`,
    privacy: "Slažem se sa politikom privatnosti",
    privacyLink: "Pročitaj politiku",
    // Free reservation modal translations
    freeReservationSuccess: "Besplatna rezervacija uspješna!",
    freeReservationCreated: "Vaša besplatna rezervacija je uspješno kreirana.",
    confirmationSent: "Potvrda je poslata na vaš email",
    checkEmail: "Provjerite svoju email adresu za detalje rezervacije",
    close: "Zatvori"
  }
};

function setLanguage(lang) {
  const ids = [
    ['pick-date-label', 'pickDate'],
    ['arrival-label', 'arrival'],
    ['departure-label', 'departure'],
    ['company_name', 'company', 'placeholder'],
    ['registration-input', 'registration', 'placeholder'],
    ['email', 'email', 'placeholder'],
    ['vehicle-category-option', 'vehicleCategory'],
    ['agree-text', 'agree'],
    ['show-terms', 'terms'],
    ['agreement-error', 'mustAgree'],
    ['reserve-btn', 'reserve'],
    ['terms-title', 'termsTitle']
  ];

  ids.forEach(([id, key, attr]) => {
    const el = document.getElementById(id);
    if (el) {
      if (attr === 'placeholder') {
        el.placeholder = translations[lang][key];
      } else {
        el.textContent = translations[lang][key];
      }
    }
  });

  // Translate "Select country" option
  const countrySelect = document.getElementById('country-input');
  if (countrySelect && countrySelect.options.length > 0) {
    countrySelect.options[0].textContent = lang === 'mne' ? 'Izaberite državu' : 'Select country';
  }

  // Translate free reservation modal
  const freeModalTitle = document.getElementById('free-reservation-title');
  const freeModalText = document.getElementById('free-reservation-text');
  const freeModalConfirmation = document.getElementById('free-reservation-confirmation');
  const freeModalCheckEmail = document.getElementById('free-reservation-check-email');
  const freeModalCloseBtn = document.getElementById('close-free-reservation-btn');
  if (freeModalTitle) freeModalTitle.textContent = translations[lang].freeReservationSuccess;
  if (freeModalText) freeModalText.textContent = translations[lang].freeReservationCreated;
  if (freeModalConfirmation) freeModalConfirmation.textContent = '✅ ' + translations[lang].confirmationSent;
  if (freeModalCheckEmail) freeModalCheckEmail.textContent = translations[lang].checkEmail;
  if (freeModalCloseBtn) freeModalCloseBtn.textContent = translations[lang].close;	
  const termsText = {
    en: `
      <p><strong>By using this service, you agree to abide by all rules and regulations set forth by Kotorbus.</strong></p>
      <ul>
        <li>These terms establish the ordering process, payment, and download of the products offered on the <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> website. The <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> website is available for private use without any fees and according to the following terms and conditions.</li>
        <li>The Vendor is the Municipality of Kotor and the Buyer is the visitor of this website who completes an electronic request, sends it to the Vendor and conducts a payment using a credit or debit card. The Product is one of the items on offer on the <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> website a fee for stopping and parking in a special traffic regulation zone based on the prices established by provisions of the Assembly of the Municipality of Kotor (dependent on bus capacity).</li>
        <li>The Buyer orders the product or products by filling an electronic form. Any person who orders at least one product, enters the required information, and sends their order is considered to be a buyer.</li>
        <li>All the prices are final, shown in EUR. The Vendor, the Municipality of Kotor, as a local authority, is not a taxpayer within the VAT system; therefore the prices on the website do not include VAT.</li>
        <li>To process the services which the Buyer ordered through the website, there are no additional fees incurred on the Buyer.</li>
        <li>The goods and/or services are ordered online. The goods are considered to be ordered when the Buyer selects and confirms a payment method and when the credit or debit card authorization process is successfully terminated. Once the ordering process is completed, the Buyer gets an invoice which serves both as a confirmation of your order/proof of payment and a voucher for the service.</li>
        <li><strong>Payment:</strong> The products and services are paid online by using one of the following debit or credit cards: MasterCard®, Maestro® or Visa.</li>
        <li><strong>General conditions:</strong> Depending on the amount paid, the service is available for the vehicle of selected category, on the date and during the time indicated when making the purchase. The Voucher cannot be used outside the selected period. Once used, the Voucher can no longer be used. The Buyer is responsible for the use of the Voucher. The Municipality of Kotor bears no responsibility for the unauthorized use of the Voucher.</li>
        <li>The Municipality of Kotor reserves the right to change these terms and conditions. Any changes will be applied to the use of the <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> website. The buyer bears the responsibility for the accuracy and completeness of data during the buying process.</li>
        <li>The services provided by the Municipality of Kotor on the <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> website do not include the costs incurred by using computer equipment and internet service providers' services to access our website. The Municipality of Kotor is not responsible for any costs, including, but not limited to, telephone bills, Internet traffic bills or any other kind of costs that may be incurred.</li>
        <li>The Buyer does not have the right to a refund.</li>
        <li>The Municipality of Kotor cannot guarantee that the service will be free of errors. If an error occurs, kindly report it to: <a href="mailto:bus@kotor.me">bus@kotor.me</a> and we shall remove the error as soon as we possibly can.</li>
      </ul>
    `,
    mne: `
      <p><strong>Korišćenjem ove usluge, slažete se da poštujete sva pravila i propise koje je postavio Kotorbus.</strong></p>
      <ul>
        <li>Ovi uslovi definišu proces naručivanja, plaćanja i preuzimanja proizvoda ponuđenih na sajtu <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a>. Sajt <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> je dostupan za privatnu upotrebu bez naknade i u skladu sa sljedećim uslovima korišćenja.</li>
        <li>Prodavac je Opština Kotor, a Kupac je posjetilac ovog sajta koji popuni elektronski zahtjev, pošalje ga Prodavcu i izvrši plaćanje putem kreditne ili debitne kartice. Proizvod je jedna od stavki u ponudi na sajtu <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> – Naknada za zaustavljanje i parkiranje u zoni posebnog režima saobraćaja prema cijenama utvrđenim odlukom Skupštine Opštine Kotor (u zavisnosti od kapaciteta autobusa).</li>
        <li>Kupac naručuje proizvod ili proizvode popunjavanjem elektronskog formulara. Svako ko naruči makar jedan proizvod, unese potrebne podatke i pošalje narudžbu smatra se kupcem.</li>
        <li>Sve cijene su konačne, iskazane u EUR. Prodavac, Opština Kotor, kao lokalna samouprava, nije obveznik PDV-a; stoga cijene na sajtu ne sadrže PDV.</li>
        <li>Za obradu usluga koje je Kupac naručio putem sajta, Kupcu se ne naplaćuju dodatne takse.</li>
        <li>Roba i/ili usluge se naručuju online. Roba se smatra naručenom kada Kupac izabere i potvrdi način plaćanja i kada se proces autorizacije kreditne ili debitne kartice uspješno završi. Po završetku procesa naručivanja, Kupac dobija fakturu koja služi kao potvrda narudžbe/dokaz o plaćanju i vaučer za uslugu.</li>
        <li><strong>Plaćanje:</strong> Proizvodi i usluge se plaćaju online korišćenjem jedne od sljedećih debitnih ili kreditnih kartica: MasterCard®, Maestro® ili Visa.</li>
        <li><strong>Opšti uslovi:</strong> U zavisnosti od iznosa plaćanja, usluga je dostupna za vozilo izabrane kategorije, na datum i u vremenskom periodu navedenom prilikom kupovine. Vaučer se ne može koristiti van izabranog perioda. Nakon korišćenja, vaučer više nije važeći. Kupac je odgovoran za korišćenje vaučera. Opština Kotor ne snosi odgovornost za neovlašćeno korišćenje vaučera.</li>
        <li>Opština Kotor zadržava pravo izmjene ovih uslova korišćenja. Sve promjene će se primjenjivati na korišćenje sajta <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a>. Kupac snosi odgovornost za tačnost i potpunost podataka tokom procesa kupovine.</li>
        <li>Usluge koje pruža Opština Kotor putem sajta <a href="https://bus.kotor.me/" target="_blank">bus.kotor.me</a> ne uključuju troškove nastale korišćenjem računarske opreme i usluga internet provajdera za pristup našem sajtu. Opština Kotor nije odgovorna za bilo kakve troškove, uključujući, ali ne ograničavajući se na telefonske račune, račune za internet saobraćaj ili bilo koje druge troškove koji mogu nastati.</li>
        <li>Kupac nema pravo na povraćaj novca.</li>
        <li>Opština Kotor ne može garantovati da će usluga biti bez grešaka. Ukoliko dođe do greške, molimo vas da je prijavite na: <a href="mailto:bus@kotor.me">bus@kotor.me</a> i uklonićemo je u najkraćem mogućem roku.</li>
      </ul>
    `
  };
  const termsModalDiv = document.getElementById('terms-content');
  if (termsModalDiv) termsModalDiv.innerHTML = termsText[lang];

  const noticeDiv = document.getElementById('parking-notice');
  if (noticeDiv) noticeDiv.innerHTML = translations[lang].parkingNotice;

  // Privacy checkbox
  const privacyText = document.getElementById('privacy-text');
  if (privacyText) privacyText.innerHTML = translations[lang].parkingNotice;

  const privacyLink = document.getElementById('privacy-link');
  if (privacyLink) privacyLink.textContent = translations[lang].privacyLink;

  // Terms checkbox
  const agreeText = document.getElementById('agree-text');
  if (agreeText) agreeText.textContent = translations[lang].agree;

  const showTerms = document.getElementById('show-terms');
  if (showTerms) showTerms.textContent = translations[lang].terms;
}

// --- ISO2 to ISO3 mapping for billing_country ---
const iso2ToIso3 = {
  ME: "MNE", HR: "HRV", RS: "SRB", BA: "BIH", MK: "MKD", SI: "SVN", AL: "ALB", AD: "AND", AT: "AUT", BY: "BLR", BE: "BEL",
  BG: "BGR", CZ: "CZE", DK: "DNK", EE: "EST", FI: "FIN", FR: "FRA", DE: "DEU", GR: "GRC", HU: "HUN", IS: "ISL", IE: "IRL",
  IT: "ITA", XK: "XKX", LV: "LVA", LI: "LIE", LT: "LTU", LU: "LUX", MT: "MLT", MD: "MDA", MC: "MCO", NL: "NLD", NO: "NOR",
  PL: "POL", PT: "PRT", RO: "ROU", RU: "RUS", SM: "SMR", SK: "SVK", ES: "ESP", SE: "SWE", CH: "CHE", UA: "UKR", GB: "GBR",
  VA: "VAT", TR: "TUR", IL: "ISR", OTHER: "OTH"
};

let timeSlotMap = {}; // key: "09:40 - 10:00", value: slot.id

// Cookie helper for CSRF (XSRF)
function getCookie(name) {
  let value = `; ${document.cookie}`;
  let parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
  return null;
}

// --- CSRF SANCTUM: Pozovi samo ako nema XSRF-TOKEN kolaÄiÄ‡a ---
async function ensureCsrfCookie() {
  if (!getCookie('XSRF-TOKEN')) {
    await fetch('/sanctum/csrf-cookie', { credentials: 'include' });
  }
}

// --- i18n helper funkcije ---
const userMessages = {
    'slot_full_arrival': {
        'en': 'Selected arrival slot is not available for reservation (full).',
        'mne': 'Za odabrani dolazni slot nije moguće napraviti rezervaciju (popunjeno).'
    },
    'slot_full_departure': {
        'en': 'Selected departure slot is not available for reservation (full).',
        'mne': 'Za odabrani odlazni slot nije moguće napraviti rezervaciju (popunjeno).'
    },
    'same_time_not_allowed': {
        'en': 'Arrival and departure time cannot be the same for selected slot.',
        'mne': 'Vrijeme dolaska i odlaska ne mogu biti isti za odabrani termin.'
    },
    'slot_reserved_10min': {
        'en': '⏰ Slot reserved for you for 10 minutes. Please complete payment.',
        'mne': '⏰ Slot je rezervisan za Vas na 10 minuta. Molimo završite plaćanje.'
    },
    'continue_payment': {
        'en': 'Continue to payment',
        'mne': 'Nastavi sa plaćanjem'
    },
    'slots_available': {
        'en': '✅ Slots are available.',
        'mne': '✅ Slotovi su dostupni.'
    },
    'slot_reservation_error': {
        'en': 'Error reserving slot.',
        'mne': 'Greška pri rezervaciji slota.'
    },
    'server_communication_error': {
        'en': 'Error communicating with server.',
        'mne': 'Greška pri komuniciranju sa serverom.'
    },
    'reservation_expired': {
        'en': '⌛ Reservation has expired. Please try again.',
        'mne': '⌛ Rezervacija je istekla. Molimo pokušajte ponovo.'
    },
    'reserve': {
        'en': 'Reserve',
        'mne': 'Rezerviši'
    },
    'creating_free_reservation': {
        'en': 'Creating free reservation...',
        'mne': 'Kreiram besplatnu rezervaciju...'
    },
    'free_reservation_successful': {
        'en': 'Free reservation successful! Email with invoice has been sent.',
        'mne': 'Besplatna rezervacija uspješna! Email sa računom je poslat.'
    },
    'free_reservation_error': {
        'en': 'Error creating free reservation: ',
        'mne': 'Greška pri kreiranju besplatne rezervacije: '
    },
    'unknown_error': {
        'en': 'Unknown error',
        'mne': 'Nepoznata greška'
    },
    'sending': {
        'en': 'Sending...',
        'mne': 'Slanje...'
    },
    'temp_data_save_failed': {
        'en': 'Failed to save temporary data!',
        'mne': 'Neuspješan upis privremenih podataka!'
    },
    'temp_data_error': {
        'en': 'Error saving temporary data: ',
        'mne': 'Greška pri upisu privremenih podataka: '
    },
    'reservation_successful': {
        'en': 'Reservation successful!',
        'mne': 'Rezervacija uspješna!'
    },
    'payment_initialization_error': {
        'en': 'Error initializing payment.',
        'mne': 'Greška pri inicijalizaciji plaćanja.'
    },
    'payment_sending_error': {
        'en': 'Error sending to payment: ',
        'mne': 'Greška pri slanju na plaćanje: '
    },
    'slot_reservation_timeout': {
        'en': '⌛ Reservation has expired. Please try again.',
        'mne': '⌛ Rezervacija je istekla. Molimo pokušajte ponovo.'
    },
    'slot_reservation_error': {
        'en': 'Error reserving slot.',
        'mne': 'Greška pri rezervaciji slota.'
    },
    'server_communication_error': {
        'en': 'Error communicating with server.',
        'mne': 'Greška pri komuniciranju sa serverom.'
    },
    'reserve': {
        'en': 'Reserve',
        'mne': 'Rezerviši'
    },
    'creating_free_reservation': {
        'en': 'Creating free reservation...',
        'mne': 'Kreiram besplatnu rezervaciju...'
    },
    'free_reservation_successful': {
        'en': 'Free reservation successful! Email with invoice has been sent.',
        'mne': 'Besplatna rezervacija uspješna! Email sa računom je poslat.'
    },
    'free_reservation_error': {
        'en': 'Error creating free reservation: ',
        'mne': 'Greška pri kreiranju besplatne rezervacije: '
    },
    'sending': {
        'en': 'Sending...',
        'mne': 'Slanje...'
    },
    'temp_data_save_failed': {
        'en': 'Failed to save temporary data!',
        'mne': 'Neuspješan upis privremenih podataka!'
    },
    'temp_data_error': {
        'en': 'Error saving temporary data: ',
        'mne': 'Greška pri upisu privremenih podataka: '
    },
    'reservation_successful': {
        'en': 'Reservation successful!',
        'mne': 'Rezervacija uspješna!'
    }
};

// Helper funkcija za dobijanje korisničke poruke
function getUserMessage(key, lang = null) {
    if (!lang) {
        lang = getCurrentLanguage();
    }
    return userMessages[key] && userMessages[key][lang] ? userMessages[key][lang] : userMessages[key]['en'];
}

// Helper funkcija za dobijanje trenutnog jezika
function getCurrentLanguage() {
    return document.documentElement.lang || 
           (document.getElementById('lang-cg')?.style.opacity === '0.5' ? 'en' : 'mne');
}

// --- Slot popunjenost provjera ---

async function checkSlotAvailability(date, slotId, type = 'drop_off') {
  if (!date || !slotId) return { count: 0, remaining: 0, available: false };
  
  try {
    const res = await fetch(`/api/slot-count?date=${encodeURIComponent(date)}&slot_id=${slotId}&type=${type}`);
    const data = await res.json();
    
    // Vrati objekat sa svim relevantnim podacima iz dinamičke tabele
    return {
      count: data.count || 0,
      remaining: data.remaining || 0,
      available: data.available || false,
      max_capacity: data.max_capacity || 7,
      table_used: data.table_used || null,
      fallback: data.fallback || false
    };
  } catch (error) {
    console.error('Greška pri proveri dostupnosti slota:', error);
    return { count: 0, remaining: 0, available: false };
  }
}

// Globalna varijabla za čuvanje rezervacije slota
let currentSlotReservation = null;
let reservationTimer = null;

async function checkAndToggleReserveButton() {
  console.log('=== checkAndToggleReserveButton POZVAN ===');
  const reserveBtn = document.getElementById('reserve-btn');
  if (reserveBtn) reserveBtn.disabled = false;
  const paymentResult = document.getElementById('payment-result');
  if (paymentResult) paymentResult.textContent = '';

  // PROVERA 1: Da li korisnik već ima aktivnu rezervaciju (PRVI prioritet)
  console.log('checkAndToggleReserveButton - proveravam session rezervaciju...');
  if (hasActiveReservation()) {
    console.log('checkAndToggleReserveButton - PRONAĐENA AKTIVNA REZERVACIJA!');
    if (reserveBtn) {
      reserveBtn.disabled = true;
      reserveBtn.textContent = getUserMessage('active_reservation_in_other_tab');
    }
    if (paymentResult) {
      paymentResult.style.color = "orange";
      paymentResult.textContent = "⚠️ " + getUserMessage('complete_other_reservation_first');
    }
    return;
  }
  console.log('checkAndToggleReserveButton - nema aktivne rezervacije, nastavljam...');

  const date = document.getElementById('reservation_date')?.value;
  const arrivalTime = document.getElementById('arrival-time-slot')?.value;
  const departureTime = document.getElementById('departure-time-slot')?.value;

  if (!date || !arrivalTime || !departureTime) return;

  // PROVERA 2: Da li su arrival i departure isti - dozvoljeno samo za posebne termine
  if (arrivalTime === departureTime && !allowsSameArrivalDeparture(arrivalTime)) {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = getUserMessage('same_time_not_allowed');
    }
    if (reserveBtn) reserveBtn.disabled = true;
    return;
  }

  const pickUpId = timeSlotMap[arrivalTime];
  const dropOffId = timeSlotMap[departureTime];

  let pickupData = null, dropoffData = null;
  if (pickUpId && dropOffId) {
    [pickupData, dropoffData] = await Promise.all([
      checkSlotAvailability(date, pickUpId, 'pick_up'),
      checkSlotAvailability(date, dropOffId, 'drop_off')
    ]);
  }

  const pickupRemaining = pickupData?.remaining || 0;
  const dropoffRemaining = dropoffData?.remaining || 0;

  if (pickupRemaining <= 0) {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = getUserMessage('slot_full_arrival');
    }
    if (reserveBtn) reserveBtn.disabled = true;
    return;
  }

  if (dropoffRemaining <= 0) {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = getUserMessage('slot_full_departure');
    }
    if (reserveBtn) reserveBtn.disabled = true;
    return;
  }

  // NOVA LOGIKA: Ako je remaining = 1, rezerviši slot
  if (pickupRemaining === 1 || dropoffRemaining === 1) {
    console.log('checkAndToggleReserveButton - KRITIČAN SLOT! remaining pickup:', pickupRemaining, 'dropoff:', dropoffRemaining);
    console.log('checkAndToggleReserveButton - pozivam handleCriticalSlotReservation...');
    await handleCriticalSlotReservation(date, pickUpId, dropOffId);
  } else {
    console.log('checkAndToggleReserveButton - NIJE KRITIČAN SLOT, remaining pickup:', pickupRemaining, 'dropoff:', dropoffRemaining);
  }
  console.log('=== checkAndToggleReserveButton ZAVRŠEN ===');
}

// Nova funkcija za rukovanje kritičnim slotovima (remaining = 1)
async function handleCriticalSlotReservation(date, pickUpId, dropOffId) {
  console.log('=== handleCriticalSlotReservation POZVAN ===');
  console.log('handleCriticalSlotReservation - parametri:', { date, pickUpId, dropOffId });
  const paymentResult = document.getElementById('payment-result');
  const reserveBtn = document.getElementById('reserve-btn');
  
  // PROVERA 1: Da li korisnik već ima aktivnu rezervaciju u session-u
  // PRIVREMENO ISKLJUČENO: PROVERA SESSION REZERVACIJE
  // if (hasActiveReservation()) {
  //   const activeReservation = getActiveReservation();
  //   if (paymentResult) {
  //     paymentResult.style.color = "orange";
  //     paymentResult.textContent = "⚠️ " + getUserMessage('active_reservation_exists');
  //   }
  //   if (reserveBtn) {
  //     reserveBtn.disabled = true;
  //     reserveBtn.textContent = getUserMessage('active_reservation_in_other_tab');
  //   }
  //   return;
  // }
  
  // PRIVREMENO ISKLJUČENO: POSTAVLJANJE SESSION REZERVACIJE
  // console.log('=== handleCriticalSlotReservation - POSTAVLJAM SESSION REZERVACIJU ===');
  // const sessionReservation = {
  //   drop_off_time_slot_id: dropOffId,
  //   pick_up_time_slot_id: pickUpId,
  //   reservation_date: date,
  //   user_name: document.getElementById('user_name')?.value || '',
  //   country: document.getElementById('country')?.value || '',
  //   license_plate: document.getElementById('license_plate')?.value || '',
  //   vehicle_type_id: document.getElementById('vehicle_type_id')?.value || '',
  //   email: document.getElementById('email')?.value || '',
  //   expires_at: new Date(Date.now() + 10 * 60 * 1000).toISOString() // 10 minuta
  // };
  // setActiveReservation(sessionReservation);
  // console.log('=== handleCriticalSlotReservation - SESSION REZERVACIJA POSTAVLJENA ===');

  // Pokušaj rezervacije slota
  try {
    const reservationData = {
      drop_off_time_slot_id: dropOffId,
      pick_up_time_slot_id: pickUpId,
      reservation_date: date,
      user_name: document.getElementById('user_name')?.value || '',
      country: document.getElementById('country')?.value || '',
      license_plate: document.getElementById('license_plate')?.value || '',
      vehicle_type_id: document.getElementById('vehicle_type_id')?.value || '',
      email: document.getElementById('email')?.value || ''
    };

    // Detektuj trenutni jezik
    const currentLang = document.documentElement.lang || 
                       (document.getElementById('lang-cg')?.style.opacity === '0.5' ? 'en' : 'mne');
    
    console.log('handleCriticalSlotReservation - šaljem API zahtev:', reservationData);
    const response = await fetch('/api/reservations/reserve-slot', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept-Language': currentLang,
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
      },
      body: JSON.stringify(reservationData)
    });

    const result = await response.json();
    console.log('handleCriticalSlotReservation - API odgovor:', result);

    if (result.success && result.requires_payment) {
      console.log('handleCriticalSlotReservation - USPEŠNA REZERVACIJA SA PLAĆANJEM!');
      // Slot je rezervisan za 10 minuta
      const reservation = {
        id: result.reservation_id,
        merchant_transaction_id: result.merchant_transaction_id,
        expires_at: result.expires_at
      };
      
      // Ažuriraj postojeću session rezervaciju sa server podacima
      console.log('=== handleCriticalSlotReservation - AŽURIRAM SESSION REZERVACIJU ===');
      console.log('handleCriticalSlotReservation - ažuriram session rezervaciju:', reservation);
      setActiveReservation(reservation);
      currentSlotReservation = reservation;
      console.log('handleCriticalSlotReservation - session rezervacija ažurirana, currentSlotReservation:', currentSlotReservation);

      if (paymentResult) {
        paymentResult.style.color = "orange";
        paymentResult.textContent = "⏰ " + getUserMessage('slot_reserved_for_you');
      }

      // Pokreni countdown timer
      startReservationCountdown(result.expires_at);

      // Omogući dugme za rezervaciju (sada ide na plaćanje)
      if (reserveBtn) {
        reserveBtn.disabled = false;
        reserveBtn.textContent = getUserMessage('continue_payment');
      }

    } else if (result.success && !result.requires_payment) {
      console.log('handleCriticalSlotReservation - SLOTOVI SU DOSTUPNI, NEMA POTREBE ZA REZERVACIJOM');
      // Ima dovoljno slotova, nema potrebe za rezervacijom
      if (paymentResult) {
        paymentResult.style.color = "green";
        paymentResult.textContent = "✅ " + getUserMessage('slots_available');
      }

    } else {
      console.log('handleCriticalSlotReservation - GREŠKA U REZERVACIJI:', result.message);
      // Greška u rezervaciji
      if (paymentResult) {
        paymentResult.style.color = "red";
        paymentResult.textContent = result.message || getUserMessage('slot_reservation_error');
      }
      // Obriši session rezervaciju u slučaju greške
      clearActiveReservation();
      currentSlotReservation = null;
      if (reservationTimer) {
        clearInterval(reservationTimer);
      }
      if (reserveBtn) reserveBtn.disabled = true;
    }

  } catch (error) {
    console.error('handleCriticalSlotReservation - GREŠKA PRI REZERVACIJI SLOTA:', error);
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = getUserMessage('server_communication_error');
    }
    // Obriši session rezervaciju u slučaju greške
    console.log('handleCriticalSlotReservation - brišem session zbog greške');
    clearActiveReservation();
    currentSlotReservation = null;
    if (reservationTimer) {
      clearInterval(reservationTimer);
    }
    if (reserveBtn) reserveBtn.disabled = true;
  }
  console.log('=== handleCriticalSlotReservation ZAVRŠEN ===');
}

// Countdown timer za rezervaciju
function startReservationCountdown(expiresAt) {
  const paymentResult = document.getElementById('payment-result');
  
  if (reservationTimer) {
    clearInterval(reservationTimer);
  }

  reservationTimer = setInterval(async () => {
    const now = new Date();
    const expires = new Date(expiresAt);
    const remainingMs = expires.getTime() - now.getTime();

    if (remainingMs <= 0) {
      // Rezervacija je istekla
      clearInterval(reservationTimer);
      currentSlotReservation = null;
      clearActiveReservation(); // Obriši iz session-a
      
      if (paymentResult) {
        paymentResult.style.color = "red";
        paymentResult.textContent = getUserMessage('slot_reservation_timeout');
      }
      
      const reserveBtn = document.getElementById('reserve-btn');
      if (reserveBtn) {
        reserveBtn.textContent = getUserMessage('reserve');
        reserveBtn.disabled = false;
      }
      
      // Ponovo proveravaj dostupnost
      await checkAndToggleReserveButton();
      return;
    }

    // Prikaži remaining vreme
    const remainingSeconds = Math.ceil(remainingMs / 1000);
    const minutes = Math.floor(remainingSeconds / 60);
    const seconds = remainingSeconds % 60;
    
    if (paymentResult) {
      paymentResult.style.color = "orange";
      paymentResult.textContent = `⏰ Slot rezervisan: ${minutes}:${seconds.toString().padStart(2, '0')} preostalo`;
    }
  }, 1000);

  // Proveri status rezervacije periodično
  setInterval(async () => {
    if (!currentSlotReservation) return;
    
    try {
      const response = await fetch(`/api/reservations/check-slot-reservation?reservation_id=${currentSlotReservation.id}`);
      const result = await response.json();
      
      if (!result.success) {
        // Rezervacija je obrisana ili istekla
        clearInterval(reservationTimer);
        currentSlotReservation = null;
        clearActiveReservation(); // Obriši iz session-a
        await checkAndToggleReserveButton();
      }
    } catch (error) {
      console.error('Greška pri proveri statusa rezervacije:', error);
    }
  }, 30000); // Proveravaj svakih 30 sekundi
}

// --- DOMContentLoaded ---
document.addEventListener('DOMContentLoaded', function () {
  setLanguage('en'); // or 'mne' for default
  
  // Inicijalizuj session sinhronizaciju
  setupSessionSync();
  
  // Proveri da li već postoji aktivna rezervacija u session-u
  console.log('DOMContentLoaded - proveravam postojeću rezervaciju...');
  const existingReservation = getActiveReservation();
  if (existingReservation) {
    console.log('DOMContentLoaded - PRONAĐENA POSTOJEĆA REZERVACIJA:', existingReservation);
    currentSlotReservation = existingReservation;
    if (existingReservation.expires_at) {
      startReservationCountdown(existingReservation.expires_at);
    }
  } else {
    console.log('DOMContentLoaded - nema postojeće rezervacije');
  }

  // Today's date string
  const today = new Date();
  const todayStr = today.toISOString().slice(0, 10);

  // Calculate max date (90 days from today)
  const maxDate = new Date(today);
  maxDate.setDate(today.getDate() + 90);
  const maxDateStr = maxDate.toISOString().slice(0, 10);

  // Set min and max date for the date input
  const reservationDateInput = document.getElementById('reservation_date');
  if (reservationDateInput) {
    reservationDateInput.min = todayStr;
    reservationDateInput.max = maxDateStr;
    reservationDateInput.value = todayStr;
    reservationDateInput.dispatchEvent(new Event('change'));
  }

  // Initialize FullCalendar
  const calendarEl = document.getElementById('calendar');
  if (calendarEl) {
    const calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      headerToolbar: {
        left: '',
        center: 'title',
        right: 'prev,next'
      },
      validRange: { start: todayStr, end: maxDateStr },
      dateClick: function(info) {
        calendar.select(info.date);
        reservationDateInput.value = info.dateStr;
        reservationDateInput.dispatchEvent(new Event('change'));
        document.getElementById('slot-section').style.display = 'block';
      }
    });
    calendar.render();
  }

  // Populate vehicle categories
  fetch('/api/vehicle-types')
    .then(res => res.json())
    .then(data => {
      const select = document.getElementById('vehicle_type_id');
      if (!select) return;
      select.innerHTML = '<option id="vehicle-category-option" value="">Select vehicle category</option>';
      data.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.description_vehicle || type.name || type.category || type.title || `Type ${type.id}`;
        option.setAttribute('data-price', type.price);
        select.appendChild(option);
      });
    });

  // Attach listeners once

  const arrivalSelect = document.getElementById('arrival-time-slot');
  const departureSelect = document.getElementById('departure-time-slot');
  if (arrivalSelect) arrivalSelect.addEventListener('change', function() {
    console.log('arrivalSelect change - pozivam checkAndToggleReserveButton');
    filterTimeSlots();
    checkAndToggleReserveButton();
  });

  if (departureSelect) departureSelect.addEventListener('change', function() {
    console.log('departureSelect change - pozivam checkAndToggleReserveButton');
    filterTimeSlots();
    checkAndToggleReserveButton();
  });

  // On date change, fetch slots and populate selects
  if (reservationDateInput) {
    reservationDateInput.addEventListener('change', function () {
      console.log('reservationDateInput change - pozivam fetchAvailableSlotsForDate');
      const date = this.value;
      fetchAvailableSlotsForDate(date, function(availableSlots) {
        timeSlotMap = {};
        availableSlots.forEach(s => {
          timeSlotMap[s.time_slot] = s.id;
        });
        const allTimeSlotsForDay = availableSlots.map(s => s.time_slot);
        populateTimeSlotSelect('arrival-time-slot', allTimeSlotsForDay);
        populateTimeSlotSelect('departure-time-slot', allTimeSlotsForDay);
        console.log('fetchAvailableSlotsForDate - pozivam checkAndToggleReserveButton nakon učitavanja slotova');
        checkAndToggleReserveButton();  
      });
    });
  }

  // Terms modal
  const showTerms = document.getElementById('show-terms');
  if (showTerms) {
    showTerms.addEventListener('click', function(e) {
      e.preventDefault();
      document.getElementById('terms-modal').style.display = 'block';
    });
  }
  const closeTerms = document.getElementById('close-terms');
  if (closeTerms) {
    closeTerms.addEventListener('click', function() {
      document.getElementById('terms-modal').style.display = 'none';
    });
  }

  // Free reservation success modal
  const closeFreeReservation = document.getElementById('close-free-reservation');
  const closeFreeReservationBtn = document.getElementById('close-free-reservation-btn');
  
  if (closeFreeReservation) {
    closeFreeReservation.addEventListener('click', function() {
      hideFreeReservationSuccess();
    });
  }
  
  if (closeFreeReservationBtn) {
    closeFreeReservationBtn.addEventListener('click', function() {
      hideFreeReservationSuccess();
    });
  }
  
  // Close modal when clicking outside
  const freeReservationModal = document.getElementById('free-reservation-modal');
  if (freeReservationModal) {
    freeReservationModal.addEventListener('click', function(e) {
      if (e.target === freeReservationModal) {
        hideFreeReservationSuccess();
      }
    });
  }

  // Language switch
  const langEn = document.getElementById('lang-en');
  const langCg = document.getElementById('lang-cg');
  if (langEn) langEn.addEventListener('click', function() { setLanguage('en'); });
  if (langCg) langCg.addEventListener('click', function() { setLanguage('mne'); });

  // Reservation form submit
  const reservationForm = document.getElementById('reservation-form');
  if (reservationForm) {
    reservationForm.addEventListener('submit', async function(e) {
      e.preventDefault();

      const agreement = document.getElementById('user_agreement');
      const privacy = document.getElementById('privacy_agreement');
      const agreementError = document.getElementById('agreement-error');
      if (!agreement.checked || !privacy.checked) {
        if (agreementError) agreementError.style.display = 'inline';
        return;
      } else {
        if (agreementError) agreementError.style.display = 'none';
      }
      const data = Object.fromEntries(new FormData(reservationForm).entries());

      // Provera da li su arrival i departure isti - dozvoljeno samo za posebne termine
      if (data.arrival_time === data.departure_time && !allowsSameArrivalDeparture(data.arrival_time)) {
        if (paymentResult) {
          paymentResult.style.color = "red";
          paymentResult.textContent = getUserMessage('same_time_not_allowed');
        }
        if (reserveBtn) reserveBtn.disabled = false;
        return;
      }

      // PRIVREMENO ISKLJUČENO: PROVERA SESSION REZERVACIJE
      // if (hasActiveReservation()) {
      //   if (paymentResult) {
      //     paymentResult.style.color = "orange";
      //     paymentResult.textContent = "⚠️ " + getUserMessage('active_reservation_exists');
      //   }
      //   if (reserveBtn) {
      //     reserveBtn.disabled = true;
      //     reserveBtn.textContent = getUserMessage('active_reservation_in_other_tab');
      //   }
      //   return;
      // }

      // PRIVREMENO ISKLJUČENO: POSTAVLJANJE SESSION REZERVACIJE
      // console.log('=== POSTAVLJAM SESSION REZERVACIJU ===');
      // const sessionReservation = {
      //   drop_off_time_slot_id: timeSlotMap[data.arrival_time],
      //   pick_up_time_slot_id: timeSlotMap[data.departure_time],
      //   reservation_date: data.reservation_date,
      //   user_name: data.company_name,
      //   country: data.country,
      //   license_plate: data.registration_input,
      //   vehicle_type_id: data.vehicle_type_id,
      //   email: data.email,
      //   expires_at: new Date(Date.now() + 10 * 60 * 1000).toISOString() // 10 minuta
      // };
      // setActiveReservation(sessionReservation);
      // console.log('=== SESSION REZERVACIJA POSTAVLJENA ===');
      
      // Provera da li je besplatna rezervacija (isti termini za posebne slotove ILI posebna kombinacija)
      const isFreeReservation = (
        // Slučaj 1: Isti termini za posebne slotove
        (data.arrival_time === data.departure_time && allowsSameArrivalDeparture(data.arrival_time)) ||
        // Slučaj 2: Posebna kombinacija - dolazak "00:00 - 07:00" i odlazak "20:00 - 24:00"
        (data.arrival_time === '00:00 - 07:00' && data.departure_time === '20:00 - 24:00')
      );
      
      if (isFreeReservation) {
        
        // Kreiraj besplatnu rezervaciju direktno
        const freeReservationData = {
          drop_off_time_slot_id: timeSlotMap[data.arrival_time],
          pick_up_time_slot_id: timeSlotMap[data.departure_time],
          reservation_date: data.reservation_date,
          user_name: data.company_name,
          country: data.country,
          license_plate: data.registration_input,
          vehicle_type_id: data.vehicle_type_id,
          email: data.email,
          status: 'free' // Besplatna rezervacija
        };

        const reserveBtn = document.getElementById('reserve-btn');
        const paymentResult = document.getElementById('payment-result');
        if (reserveBtn) reserveBtn.disabled = true;
        if (paymentResult) {
          paymentResult.style.color = "black";
          paymentResult.textContent = getUserMessage('creating_free_reservation');
        }

        try {
          
          const freeRes = await fetch('/api/reservations/reserve', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(freeReservationData)
          });

          if (freeRes.ok) {
            if (paymentResult) {
              paymentResult.style.color = "green";
              paymentResult.textContent = getUserMessage('free_reservation_successful');
            }
            // Osveži slotove
            if (reservationDateInput) {
              reservationDateInput.dispatchEvent(new Event('change'));
            }
            showFreeReservationSuccess(); // Show success modal
            
            // Reset form after successful reservation
            setTimeout(() => {
              if (reservationForm) {
                reservationForm.reset();
                // Reset checkboxes
                const agreement = document.getElementById('user_agreement');
                const privacy = document.getElementById('privacy_agreement');
                if (agreement) agreement.checked = false;
                if (privacy) privacy.checked = false;
                
                // Clear payment result
                if (paymentResult) {
                  paymentResult.textContent = '';
                }
                
                // Obriši session rezervaciju nakon uspešne rezervacije
                clearActiveReservation();
                currentSlotReservation = null;
                if (reservationTimer) {
                  clearInterval(reservationTimer);
                }
              }
            }, 2000); // Reset after 2 seconds
          } else {
            const errorData = await freeRes.json();
            if (paymentResult) {
              paymentResult.style.color = "red";
              paymentResult.textContent = getUserMessage('free_reservation_error') + (errorData.message || getUserMessage('unknown_error'));
            }
            // Obriši session rezervaciju u slučaju greške
            clearActiveReservation();
            currentSlotReservation = null;
            if (reservationTimer) {
              clearInterval(reservationTimer);
            }
          }
        } catch (err) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = getUserMessage('free_reservation_error') + err.message;
          }
          // Obriši session rezervaciju u slučaju greške
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        }
        
        if (reserveBtn) reserveBtn.disabled = false;
        return; // Prekini izvršavanje - ne ide na plaćanje
      }

      // Prepare payload
      const tempPayload = {
        pick_up_time_slot_id: timeSlotMap[data.departure_time], // odlazak (veći broj)
        drop_off_time_slot_id: timeSlotMap[data.arrival_time],  // dolazak (manji broj)
        reservation_date: data.reservation_date,
        user_name: data.company_name,
        country: data.country,
        license_plate: data.registration_input,
        vehicle_type_id: data.vehicle_type_id,
        email: data.email,
      };

      const reserveBtn = document.getElementById('reserve-btn');
      const paymentResult = document.getElementById('payment-result');
      if (reserveBtn) reserveBtn.disabled = true;
      if (paymentResult) {
        paymentResult.style.color = "black";
        paymentResult.textContent = getUserMessage('sending');
      }

      // 1. Save temp data (ili koristi postojeći ako je slot već rezervisan)
      let merchantTransactionId = '';
      let xsrf = null;
      
      // Proverava da li već imamo rezervisan slot sa merchant_transaction_id
      if (currentSlotReservation && currentSlotReservation.merchant_transaction_id) {
        merchantTransactionId = currentSlotReservation.merchant_transaction_id;
        console.log("Koristi postojeći merchant_transaction_id iz slot rezervacije:", merchantTransactionId);
      } else {
        // Kreira novi temp_data zapis
        try {
        // CSRF: Pozovi samo ako nema kolaÄiÄ‡a
        await ensureCsrfCookie();
        xsrf = getCookie('XSRF-TOKEN');
        if (!xsrf) throw new Error('CSRF token nije pronađen! Očistite kolačiće i pokušajte ponovo.');

        const tempRes = await fetch('/api/temp-reservation', {
          method: 'POST',
          credentials: 'include',
          headers: { 
            'Content-Type': 'application/json', 
            'Accept': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(xsrf)
          },
          body: JSON.stringify(tempPayload)
        });

        if (!tempRes.ok) {
          const msg = await tempRes.text();
          throw new Error('API greška: ' + tempRes.status + ' ' + msg);
        }

        const tempResp = await tempRes.json();
        console.log("Temp reservation response:", tempResp);

        if (!tempResp.merchant_transaction_id) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = getUserMessage('temp_data_save_failed');
          }
          // Obriši session rezervaciju u slučaju greške
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
        merchantTransactionId = tempResp.merchant_transaction_id;
        } catch (err) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = getUserMessage('temp_data_error') + err.message;
          }
          // Obriši session rezervaciju u slučaju greške
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
      }

      console.log('Pripremam fetch za /procesiraj-placanje', merchantTransactionId);
      console.log('merchantTransactionId:', merchantTransactionId);
      console.log('_token:', decodeURIComponent(xsrf));

      try {
        // Umesto FormData koristi JSON payload za /procesiraj-placanje
        const payload = {
          merchantTransactionId: merchantTransactionId,
          _token: decodeURIComponent(xsrf)
        };

        const payRes = await fetch('/api/procesiraj-placanje', {
          method: 'POST',
          credentials: 'include',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-XSRF-TOKEN': decodeURIComponent(xsrf)
          },
          body: JSON.stringify(payload)
        });

        console.log('Završio fetch za /procesiraj-placanje');

        let payResp = {};
        let payText = await payRes.text();
        try { payResp = JSON.parse(payText); } catch (jsonErr) {
          console.error('JSON parse error:', jsonErr, payText);
        }

        if (payResp.redirectUrl) {
          window.location.href = payResp.redirectUrl;
          return;
        } else if (payResp.errors) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = Object.values(payResp.errors).flat().join(' ');
          }
          // Obriši session rezervaciju u slučaju greške
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        } else if (payResp.success) {

          // Uspeh, osveÅ¾i slotove
          if (paymentResult) {
            paymentResult.style.color = "green";
            paymentResult.textContent = getUserMessage('reservation_successful');
          }

          if (reservationDateInput) {
            reservationDateInput.dispatchEvent(new Event('change'));
          }
          
          // Obriši session rezervaciju nakon uspešne rezervacije
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        } else {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = payResp.message || getUserMessage('payment_initialization_error');
          }
          // Obriši session rezervaciju u slučaju greške
          clearActiveReservation();
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        }
      } catch (err) {
        if (paymentResult) {
          paymentResult.style.color = "red";
          paymentResult.textContent = getUserMessage('payment_sending_error') + err.message;
        }
        // Obriši session rezervaciju u slučaju greške
        clearActiveReservation();
        currentSlotReservation = null;
        if (reservationTimer) {
          clearInterval(reservationTimer);
        }
      }
      if (reserveBtn) reserveBtn.disabled = false;
    });

    // Initial fetch for default date
    if (reservationDateInput) {
      console.log('DOMContentLoaded - pozivam initial fetch za default datum');
      reservationDateInput.dispatchEvent(new Event('change'));
    }

    // Registracija velikim slovima
    const registrationInput = document.getElementById('registration-input');
    if (registrationInput) {
      registrationInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
      });
    }
  }
});
