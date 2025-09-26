// --- GLOBAL FUNCTIONS ---

// Helper function to check if a time slot allows same arrival and departure
function allowsSameArrivalDeparture(timeSlot) {
  // Termini koji dozvoljavaju isti dolazak i odlazak:
  // - 00:00 - 07:00 (id: 1)
  // - 20:00 - 24:00 (id: 41)
  const specialTimeSlots = ['00:00 - 07:00', '20:00 - 24:00'];
  return specialTimeSlots.includes(timeSlot);
}

// Globalna funkcija za proveru da li je datum u prošlosti
function isDateInPast(date) {
  const today = new Date();
  const todayStr = today.getFullYear() + '-' + 
                   String(today.getMonth() + 1).padStart(2, '0') + '-' + 
                   String(today.getDate()).padStart(2, '0');
  return date < todayStr;
}

// Globalna funkcija za dobijanje današnjeg datuma kao string
function getTodayString() {
  const today = new Date();
  return today.getFullYear() + '-' + 
         String(today.getMonth() + 1).padStart(2, '0') + '-' + 
         String(today.getDate()).padStart(2, '0');
}

// Funkcija za validaciju email adrese
function isValidEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

// Funkcija za validaciju registarskih tablica
function isValidLicensePlate(plate) {
  // Osnovna validacija - bar 3 karaktera, slova (uključujući dijakritičke simbole), brojevi i crtice
  const plateRegex = /^[A-ZŠĐŽČĆ0-9\-]{3,15}$/;
  return plateRegex.test(plate);
}

// Funkcija za validaciju naziva kompanije
function isValidCompanyName(name) {
  return name && name.trim().length >= 2 && name.trim().length <= 100;
}

// Function to show free reservation success modal
function showFreeReservationSuccess(message = null) {
  const modal = document.getElementById('free-reservation-modal');
  if (modal) {
    const currentLang = getCurrentLanguage();
    
    // Osiguraj da su svi elementi modala ažurirani sa trenutnim jezikom
    setLanguage(currentLang);
    
    // Ako je prosleđena poruka, ažuriraj modal
    if (message) {
      const titleElement = document.getElementById('free-reservation-title');
      const textElement = document.getElementById('free-reservation-text');
      const confirmationElement = document.getElementById('free-reservation-confirmation');
      const checkEmailElement = document.getElementById('free-reservation-check-email');
      const closeButton = document.getElementById('close-free-reservation-btn');
      
      if (titleElement) {
        titleElement.textContent = translations[currentLang].freeReservationSuccess;
      }
      if (textElement) {
        textElement.textContent = message;
      }
      if (confirmationElement) {
        confirmationElement.textContent = '✅ ' + translations[currentLang].confirmationSent;
      }
      if (checkEmailElement) {
        checkEmailElement.textContent = translations[currentLang].checkEmail;
      }
      if (closeButton) {
        closeButton.textContent = translations[currentLang].close;
      }
    }
    
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

// Funkcija za čišćenje svih timera i cache-a
function cleanupAllTimersAndCache() {
  // Očisti sve timere
  if (reservationTimer) {
    clearInterval(reservationTimer);
    reservationTimer = null;
  }
  if (checkButtonDebounceTimer) {
    clearTimeout(checkButtonDebounceTimer);
    checkButtonDebounceTimer = null;
  }
  
  // Očisti cache
  clearSlotAvailabilityCache();
  
  // Resetuj rezervaciju
  currentSlotReservation = null;
  
  // Resetuj flagove
  isCheckingButton = false;
  lastApiCallTime = 0;
}

function fetchAvailableSlotsForDate(date, callback) {
  // PROVERA: Da li je datum u prošlosti
  if (isDateInPast(date)) {
    console.log('fetchAvailableSlotsForDate - datum u prošlosti, ne pozivam API:', date);
    callback([]); // Vrati prazan niz
    return;
  }
  
  console.log('fetchAvailableSlotsForDate - pozivam API za datum:', date);
  
  // Timeout za API poziv (10 sekundi)
  const timeoutPromise = new Promise((_, reject) => {
    setTimeout(() => reject(new Error('API timeout')), 10000);
  });
  
  const fetchPromise = fetch('/api/timeslots/available?date=' + encodeURIComponent(date))
    .then(res => {
      if (!res.ok) {
        throw new Error(`HTTP ${res.status}: ${res.statusText}`);
      }
      return res.json();
    });
  
  Promise.race([fetchPromise, timeoutPromise])
    .then(callback)
    .catch(error => {
      console.error('fetchAvailableSlotsForDate - greška:', error);
      if (error.message.includes('timeout')) {
        console.warn('Timeout pri učitavanju dostupnih slotova');
      }
      callback([]); // Vrati prazan niz u slučaju greške
    });
}
function populateTimeSlotSelect(selectId, times, selectedValue = '') {
  const select = document.getElementById(selectId);
  if (!select) return;
  select.innerHTML = '<option value="">Select time slot</option>';

  const reservationDateInput = document.getElementById('reservation_date');
  const selectedDate = reservationDateInput?.value;
  const now = new Date();
  const todayStr = getTodayString();

  let minTime = null;
  if (selectedDate === todayStr) {
    // Dozvoli trenutni termin - ne dodajemo +1 minut
    minTime = now.toTimeString().slice(0, 5);
  }

  // PROVERA: Da li je datum u prošlosti
  if (isDateInPast(selectedDate)) {
    console.log('populateTimeSlotSelect - datum u prošlosti, ne prikazujem termine:', selectedDate);
    return;
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
  const todayStr = getTodayString();
  
  let minTime = null;
  if (selectedDate === todayStr) {
    // Dozvoli trenutni termin - ne dodajemo +1 minut
    minTime = now.toTimeString().slice(0, 5);
  }
  
  // PROVERA: Da li je datum u prošlosti
  if (isDateInPast(selectedDate)) {
    console.log('filterTimeSlots - datum u prošlosti, ne prikazujem termine:', selectedDate);
    arrivalSelect.innerHTML = '<option value="">Datum u prošlosti</option>';
    departureSelect.innerHTML = '<option value="">Datum u prošlosti</option>';
    return;
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
    const value = data.value || 9; // fallback na 9 ako API ne radi (može se promeniti u config)
    
    // Sačuvaj u cache
    availableParkingSlotsCache = value;
    availableParkingSlotsCacheTime = now;
    
    return value;
  } catch (error) {
    console.error('Error fetching available parking slots:', error);
    return availableParkingSlotsCache ||9; // koristi cache ili fallback na 9 (može se promeniti u config)
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
    privacyLink: "Read policy",
    // Free reservation modal translations
    freeReservationSuccess: "Free reservation successful!",
    freeReservationCreated: "Your free reservation has been created successfully.",
    confirmationSent: "Confirmation has been sent to your email",
    checkEmail: "Check your email address for reservation details",
    close: "Close"
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
  // Postavi lang atribut na document element
  document.documentElement.lang = lang;
  
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
  
  // Ažuriraj opacity jezičkih dugmića
  const langEn = document.getElementById('lang-en');
  const langCg = document.getElementById('lang-cg');
  if (langEn) langEn.style.opacity = lang === 'en' ? '1' : '0.5';
  if (langCg) langCg.style.opacity = lang === 'mne' ? '1' : '0.5';
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

// --- CSRF SANCTUM: Pozovi samo ako nema XSRF-TOKEN kolačića ---
async function ensureCsrfCookie() {
  if (!getCookie('XSRF-TOKEN')) {
    try {
      // Timeout za CSRF cookie fetch (5 sekundi)
      const csrfTimeoutPromise = new Promise((_, reject) => {
        setTimeout(() => reject(new Error('CSRF API timeout')), 5000);
      });
      
      const csrfFetchPromise = fetch('/sanctum/csrf-cookie', { credentials: 'include' })
        .then(response => {
          if (!response.ok) {
            throw new Error(`CSRF cookie fetch failed: ${response.status}`);
          }
          return response;
        });
      
      await Promise.race([csrfFetchPromise, csrfTimeoutPromise]);
      console.log('CSRF token uspješno dobavljen');
    } catch (error) {
      console.error('ensureCsrfCookie - greška:', error);
      if (error.message.includes('timeout')) {
        throw new Error('Timeout pri dobavljanju CSRF token-a');
      }
      throw new Error('Neuspješno dobavljanje CSRF token-a: ' + (error.message || 'Nepoznata greška'));
    }
  }
}

// Funkcija za dobavljanje CSRF token-a iz kolačića
function getCsrfToken() {
  return getCookie('XSRF-TOKEN');
}

// Funkcija za dodavanje CSRF token-a u headers
function addCsrfHeaders(headers = {}) {
  const token = getCsrfToken();
  if (token) {
    headers['X-XSRF-TOKEN'] = token;
  }
  return headers;
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
    'slot_reserved_for_you': {
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
    'critical_slot_warning': {
        'en': '⚠️ Critical slot detected! Please fill all fields to reserve this slot.',
        'mne': '⚠️ Detektovan kritičan slot! Molimo popunite sva polja da rezervišete ovaj slot.'
    },
    'critical_slot_reservation_error': {
        'en': '❌ Failed to reserve critical slot. Please try again.',
        'mne': '❌ Greška pri rezervaciji kritičnog slota. Molimo pokušajte ponovo.'
    },
    'slots_unavailable_payment': {
        'en': '❌ Selected time slots are no longer available. Please choose different times.',
        'mne': '❌ Odabrani vremenski slotovi više nisu dostupni. Molimo izaberite druga vremena.'
    },
    'availability_check_error': {
        'en': '❌ Error checking slot availability. Please try again.',
        'mne': '❌ Greška pri proveri dostupnosti slotova. Molimo pokušajte ponovo.'
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
    const docLang = document.documentElement.lang;
    const langCgOpacity = document.getElementById('lang-cg')?.style.opacity;
    const result = docLang || (langCgOpacity === '0.5' ? 'en' : 'mne');
    
    return result;
}

// --- Slot popunjenost provjera ---

async function checkSlotAvailability(date, slotId, type = 'drop_off') {
  if (!date || !slotId) return { count: 0, remaining: 0, available: false };
  
  // Proveri cache prvo
  const cacheKey = `${date}-${slotId}-${type}`;
  const cachedData = slotAvailabilityCache.get(cacheKey);
  const now = Date.now();
  
  if (cachedData && (now - cachedData.timestamp) < CACHE_DURATION_MS) {
    return cachedData.data;
  }
  
  // Rate limiting - proveri da li je prošlo dovoljno vremena od poslednjeg API poziva
  if (now - lastApiCallTime < API_RATE_LIMIT_MS) {
    await new Promise(resolve => setTimeout(resolve, API_RATE_LIMIT_MS - (now - lastApiCallTime)));
  }
  
  try {
    lastApiCallTime = Date.now(); // Ažuriraj vreme poslednjeg API poziva
    
    // Timeout za API poziv (5 sekundi)
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('API timeout')), 5000);
    });
    
    const fetchPromise = fetch(`/api/slot-count?date=${encodeURIComponent(date)}&slot_id=${slotId}&type=${type}`)
      .then(res => {
        if (!res.ok) {
          throw new Error(`HTTP ${res.status}: ${res.statusText}`);
        }
        return res.json();
      });
    
    const data = await Promise.race([fetchPromise, timeoutPromise]);
    
    // Dohvati max_capacity ako nije dostupan u odgovoru
    let maxCapacity = data.max_capacity;
    if (!maxCapacity) {
      maxCapacity = await getAvailableParkingSlots();
    }
    
    // Kreiraj objekat sa svim relevantnim podacima iz dinamičke tabele
    const result = {
      count: data.count || 0,
      remaining: data.remaining || 0,
      available: data.available || false,
      max_capacity: maxCapacity,
      table_used: data.table_used || null,
      fallback: data.fallback || false
    };
    
    // Sačuvaj u cache
    slotAvailabilityCache.set(cacheKey, {
      data: result,
      timestamp: now
    });
    
    return result;
  } catch (error) {
    console.error('Greška pri proveri dostupnosti slota:', error);
    if (error.message.includes('timeout')) {
      console.warn('Timeout pri proveri dostupnosti slota');
    }
    return { count: 0, remaining: 0, available: false };
  }
}

// Globalna varijabla za čuvanje rezervacije slota
let currentSlotReservation = null;
let reservationTimer = null;
let checkButtonDebounceTimer = null;
let isCheckingButton = false; // Flag za sprečavanje simultanih poziva
let lastApiCallTime = 0; // Za rate limiting API poziva
const API_RATE_LIMIT_MS = 1000; // Minimalno 1 sekunda između API poziva
let slotAvailabilityCache = new Map(); // Cache za slot availability podatke
const CACHE_DURATION_MS = 30000; // 30 sekundi cache

// Funkcija za čišćenje cache-a
function clearSlotAvailabilityCache() {
  slotAvailabilityCache.clear();
}

// Funkcija za proveru da li su sva polja forme popunjena
function isFormComplete() {
  const companyName = document.getElementById('company_name')?.value || '';
  const country = document.getElementById('country-input')?.value || '';
  const registrationInput = document.getElementById('registration-input')?.value || '';
  const vehicleTypeId = document.getElementById('vehicle_type_id')?.value || '';
  const email = document.getElementById('email')?.value || '';
  
  // Osnovna provera da li su polja popunjena
  if (!companyName || !country || !registrationInput || !vehicleTypeId || !email) {
    return false;
  }
  
  // Validacija email adrese
  if (!isValidEmail(email)) {
    return false;
  }
  
  // Validacija registarskih tablica
  if (!isValidLicensePlate(registrationInput)) {
    return false;
  }
  
  // Validacija naziva kompanije
  if (!isValidCompanyName(companyName)) {
    return false;
  }
  
  return true;
}

// Funkcija za ažuriranje stanja dugmeta bez API poziva
function updateReserveButtonState() {
  const reserveBtn = document.getElementById('reserve-btn');
  const paymentResult = document.getElementById('payment-result');
  
  if (!reserveBtn) return;
  
  const date = document.getElementById('reservation_date')?.value;
  const arrivalTime = document.getElementById('arrival-time-slot')?.value;
  const departureTime = document.getElementById('departure-time-slot')?.value;
  const companyName = document.getElementById('company_name')?.value || '';
  const email = document.getElementById('email')?.value || '';
  const registrationInput = document.getElementById('registration-input')?.value || '';
  
  // Proveri osnovne validacije
  if (!date || !arrivalTime || !departureTime) {
    reserveBtn.disabled = true;
    if (paymentResult) paymentResult.textContent = '';
    return;
  }
  
  // Proveri da li je datum u prošlosti
  if (isDateInPast(date)) {
    reserveBtn.disabled = true;
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = "Greška: Ne možete rezervirati datum u prošlosti.";
    }
    return;
  }
  
  // Proveri da li su arrival i departure isti - dozvoljeno samo za posebne termine
  if (arrivalTime === departureTime && !allowsSameArrivalDeparture(arrivalTime)) {
    reserveBtn.disabled = true;
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = getUserMessage('same_time_not_allowed');
    }
    return;
  }
  
  // Validacija email adrese
  if (email && !isValidEmail(email)) {
    reserveBtn.disabled = true;
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = "Greška: Neispravna email adresa.";
    }
    return;
  }
  
  // Validacija registarskih tablica
  if (registrationInput && !isValidLicensePlate(registrationInput)) {
    reserveBtn.disabled = true;
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = "Greška: Neispravan format registarskih tablica.";
    }
    return;
  }
  
  // Validacija naziva kompanije
  if (companyName && !isValidCompanyName(companyName)) {
    reserveBtn.disabled = true;
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = "Greška: Naziv kompanije mora imati 2-100 karaktera.";
    }
    return;
  }
  
  // Ako su sva polja popunjena i validna, omogući dugme
  if (isFormComplete()) {
    reserveBtn.disabled = false;
    if (paymentResult) paymentResult.textContent = '';
  } else {
    reserveBtn.disabled = true;
    if (paymentResult) paymentResult.textContent = '';
  }
}

// Debounced verzija checkAndToggleReserveButton funkcije
function debouncedCheckAndToggleReserveButton() {
  if (checkButtonDebounceTimer) {
    clearTimeout(checkButtonDebounceTimer);
  }
  checkButtonDebounceTimer = setTimeout(() => {
    // Proveri da li su sva polja popunjena pre pozivanja API-ja
    if (isFormComplete()) {
      checkAndToggleReserveButton();
    }
  }, 500); // 500ms debounce delay
}

async function checkAndToggleReserveButton() {
  // Sprečavanje simultanih poziva
  if (isCheckingButton) {
    return;
  }
  
  isCheckingButton = true;
  
  try {
  const reserveBtn = document.getElementById('reserve-btn');
  if (reserveBtn) reserveBtn.disabled = false;
  const paymentResult = document.getElementById('payment-result');
  if (paymentResult) paymentResult.textContent = '';

  const date = document.getElementById('reservation_date')?.value;
  const arrivalTime = document.getElementById('arrival-time-slot')?.value;
  const departureTime = document.getElementById('departure-time-slot')?.value;
  const companyName = document.getElementById('company_name')?.value;
  const country = document.getElementById('country')?.value;
  const registrationInput = document.getElementById('registration-input')?.value;
  const vehicleTypeId = document.getElementById('vehicle_type_id')?.value;
  const email = document.getElementById('email')?.value;

  if (!date || !arrivalTime || !departureTime) return;

  // PROVERA: Da li je datum u prošlosti
  if (isDateInPast(date)) {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = "Greška: Ne možete rezervirati datum u prošlosti.";
    }
    if (reserveBtn) reserveBtn.disabled = true;
    return;
  }

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

  // PROVERA: Da li su slotovi dostupni
  if (pickupRemaining === 1 || dropoffRemaining === 1) {
    // Prikaži upozorenje za kritičan slot
    if (paymentResult) {
      paymentResult.style.color = "orange";
      paymentResult.textContent = "⚠️ " + getUserMessage('critical_slot_warning');
    }
  }
  } catch (error) {
    console.error('Greška u checkAndToggleReserveButton:', error);
  } finally {
    isCheckingButton = false; // Resetuj flag na kraju
  }
}

// Nova funkcija za rukovanje kritičnim slotovima (remaining = 1)
async function handleCriticalSlotReservation(date, pickUpId, dropOffId) {
  const paymentResult = document.getElementById('payment-result');
  const reserveBtn = document.getElementById('reserve-btn');
  
  // Pokušaj rezervacije slota
  try {
    const reservationData = {
      drop_off_time_slot_id: dropOffId,
      pick_up_time_slot_id: pickUpId,
      reservation_date: date,
      user_name: document.getElementById('company_name')?.value || '',
      country: document.getElementById('country-input')?.value || '',
      license_plate: document.getElementById('registration-input')?.value || '',
      vehicle_type_id: document.getElementById('vehicle_type_id')?.value || '',
      email: document.getElementById('email')?.value || ''
    };

    // Detektuj trenutni jezik
    const currentLang = document.documentElement.lang || 
                       (document.getElementById('lang-cg')?.style.opacity === '0.5' ? 'en' : 'mne');
    
    // Osiguraj CSRF token za rezervaciju slota
    await ensureCsrfCookie();
    
    // Timeout za API poziv (15 sekundi)
    const timeoutPromise = new Promise((_, reject) => {
      setTimeout(() => reject(new Error('API timeout')), 15000);
    });
    
    const fetchPromise = fetch('/api/reservations/reserve-slot', {
      method: 'POST',
      headers: addCsrfHeaders({
        'Content-Type': 'application/json',
        'Accept-Language': currentLang,
        'Accept': 'application/json'
      }),
      body: JSON.stringify(reservationData)
    });
    
    const response = await Promise.race([fetchPromise, timeoutPromise]);
    
    // Pročitaj response text samo jednom
    const responseText = await response.text();
    
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${responseText}`);
    }

    // Proveri da li je response JSON
    let result;
    try {
      result = JSON.parse(responseText);
    } catch (parseError) {
      console.error('handleCriticalSlotReservation - JSON parse greška:', parseError);
      console.error('handleCriticalSlotReservation - puni response text:', responseText);
      throw new Error(`Server vraća HTML umesto JSON-a: ${responseText.substring(0, 100)}...`);
    }

    if (result.success && result.requires_payment) {
      // Slot je rezervisan za 10 minuta
      const reservation = {
        id: result.reservation_id,
        merchant_transaction_id: result.merchant_transaction_id,
        expires_at: result.expires_at
      };
      
      // Postavi server rezervaciju
      currentSlotReservation = reservation;

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
      // Ima dovoljno slotova, nema potrebe za rezervacijom
      if (paymentResult) {
        paymentResult.style.color = "green";
        paymentResult.textContent = "✅ " + getUserMessage('slots_available');
      }

    } else {
      // Greška u rezervaciji
      if (paymentResult) {
        paymentResult.style.color = "red";
        paymentResult.textContent = result.message || getUserMessage('slot_reservation_error');
      }

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
        if (error.message.includes('timeout')) {
          paymentResult.textContent = "Greška: Prekoračen timeout za API poziv. Molimo pokušajte ponovo.";
        } else {
          paymentResult.textContent = getUserMessage('server_communication_error');
        }
      }

      // Očisti sve timere i cache
      cleanupAllTimersAndCache();
      if (reserveBtn) reserveBtn.disabled = true;
    }
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
  const statusCheckInterval = setInterval(async () => {
    if (!currentSlotReservation) {
      clearInterval(statusCheckInterval);
      return;
    }
    
    try {
      // Timeout za status check API (10 sekundi)
      const statusTimeoutPromise = new Promise((_, reject) => {
        setTimeout(() => reject(new Error('API timeout')), 10000);
      });
      
      const statusFetchPromise = fetch(`/api/reservations/check-slot-reservation?reservation_id=${currentSlotReservation.id}`)
        .then(res => res.json());
      
      const result = await Promise.race([statusFetchPromise, statusTimeoutPromise]);
      
      if (!result.success) {
        // Rezervacija je obrisana ili istekla
        clearInterval(reservationTimer);
        clearInterval(statusCheckInterval);
        currentSlotReservation = null;
        await checkAndToggleReserveButton();
      }
    } catch (error) {
      console.error('Greška pri proveri statusa rezervacije:', error);
      if (error.message.includes('timeout')) {
        console.warn('Timeout pri proveri statusa rezervacije - nastavlja se sa proverom');
      }
    }
  }, 30000); // Proveravaj svakih 30 sekundi
}

  // --- DOMContentLoaded ---
  document.addEventListener('DOMContentLoaded', function () {
    // Osiguraj CSRF token na učitavanju stranice
    ensureCsrfCookie().catch(error => {
      console.warn('CSRF token nije mogao biti dobavljen:', error);
      if (error.message.includes('timeout')) {
        console.warn('Timeout pri dobavljanju CSRF token-a - pokušaće se ponovo kada bude potreban');
      }
    });
    
    // Očisti timere kada se korisnik napusti stranicu
    window.addEventListener('beforeunload', function() {
      cleanupAllTimersAndCache();
    });
  setLanguage('en'); // or 'mne' for default
  
  // Today's date string - koristi globalnu funkciju
  const todayStr = getTodayString();
  console.log('DOMContentLoaded - današnji datum (lokalno):', todayStr);

  // Calculate max date (90 days from today)
  const today = new Date();
  const maxDate = new Date(today);
  maxDate.setDate(today.getDate() + 90);
  const maxDateStr = maxDate.toISOString().slice(0, 10);

  // Set min and max date for the date input
  const reservationDateInput = document.getElementById('reservation_date');
  if (reservationDateInput) {
    reservationDateInput.min = todayStr;
    reservationDateInput.max = maxDateStr;
    
    // FORSIRANO POSTAVLJANJE DANAŠNJEG DATUMA
    console.log('DOMContentLoaded - postavljam današnji datum:', todayStr);
    reservationDateInput.value = todayStr;
    
    // Proveri da li je datum pravilno postavljen
    setTimeout(() => {
      if (reservationDateInput.value !== todayStr) {
        reservationDateInput.value = todayStr;
        updateReserveButtonState(); // Prvo ažuriraj UI
        reservationDateInput.dispatchEvent(new Event('change'));
      } else {
        updateReserveButtonState(); // Prvo ažuriraj UI
        reservationDateInput.dispatchEvent(new Event('change'));
      }
    }, 100);
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
        updateReserveButtonState(); // Prvo ažuriraj UI
        reservationDateInput.dispatchEvent(new Event('change'));
        document.getElementById('slot-section').style.display = 'block';
      }
    });
    calendar.render();
  }

  // Populate vehicle categories
  // Timeout za vehicle types API (10 sekundi)
  const vehicleTimeoutPromise = new Promise((_, reject) => {
    setTimeout(() => reject(new Error('API timeout')), 10000);
  });
  
  const vehicleFetchPromise = fetch('/api/vehicle-types')
    .then(res => res.json());
  
  Promise.race([vehicleFetchPromise, vehicleTimeoutPromise])
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
    })
    .catch(error => {
      console.error('Greška pri učitavanju tipova vozila:', error);
      if (error.message.includes('timeout')) {
        console.warn('Timeout pri učitavanju tipova vozila');
      }
      const select = document.getElementById('vehicle_type_id');
      if (select) {
        select.innerHTML = '<option id="vehicle-category-option" value="">Greška pri učitavanju tipova vozila</option>';
      }
    });

  // Attach listeners once

  const arrivalSelect = document.getElementById('arrival-time-slot');
  const departureSelect = document.getElementById('departure-time-slot');
  if (arrivalSelect) arrivalSelect.addEventListener('change', function() {
    filterTimeSlots();
    updateReserveButtonState(); // Prvo ažuriraj UI
    debouncedCheckAndToggleReserveButton(); // Zatim pozovi API sa debounce
  });

  if (departureSelect) departureSelect.addEventListener('change', function() {
    filterTimeSlots();
    updateReserveButtonState(); // Prvo ažuriraj UI
    debouncedCheckAndToggleReserveButton(); // Zatim pozovi API sa debounce
  });

  // On date change, fetch slots and populate selects
  if (reservationDateInput) {
    reservationDateInput.addEventListener('change', function () {
      const date = this.value;
      
      // Očisti cache kada se promeni datum
      clearSlotAvailabilityCache();
      
      fetchAvailableSlotsForDate(date, function(availableSlots) {
        timeSlotMap = {};
        availableSlots.forEach(s => {
          timeSlotMap[s.time_slot] = s.id;
        });
        const allTimeSlotsForDay = availableSlots.map(s => s.time_slot);
        populateTimeSlotSelect('arrival-time-slot', allTimeSlotsForDay);
        populateTimeSlotSelect('departure-time-slot', allTimeSlotsForDay);       
        updateReserveButtonState(); // Prvo ažuriraj UI
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
      const paymentResult = document.getElementById('payment-result');
      const reserveBtn = document.getElementById('reserve-btn');
      
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

        if (reserveBtn) reserveBtn.disabled = true;
        if (paymentResult) {
          paymentResult.style.color = "black";
          paymentResult.textContent = getUserMessage('creating_free_reservation');
        }

        try {          
                  // Osiguraj CSRF token za besplatnu rezervaciju
        await ensureCsrfCookie();
        
        // Timeout za free reservation API (15 sekundi)
        const freeTimeoutPromise = new Promise((_, reject) => {
          setTimeout(() => reject(new Error('API timeout')), 15000);
        });
        
        const currentLang = getCurrentLanguage();
        const freeFetchPromise = fetch('/api/reservations/reserve', {
          method: 'POST',
          headers: addCsrfHeaders({
            'Content-Type': 'application/json',
            'Accept-Language': currentLang === 'mne' ? 'mne,sr,en' : 'en'
          }),
          body: JSON.stringify(freeReservationData)
        });
        
        const freeRes = await Promise.race([freeFetchPromise, freeTimeoutPromise]);

          if (freeRes.ok) {
            const responseData = await freeRes.json();
            
            if (paymentResult) {
              paymentResult.style.color = "green";
              paymentResult.textContent = responseData.message || getUserMessage('free_reservation_successful');
            }
            // Osveži slotove
                      if (reservationDateInput) {
            updateReserveButtonState(); // Prvo ažuriraj UI
            reservationDateInput.dispatchEvent(new Event('change'));
          }
            showFreeReservationSuccess(responseData.message); // Show success modal with server message
            
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
                
                // Očisti sve timere i cache
                cleanupAllTimersAndCache();
              }
            }, 2000); // Reset after 2 seconds
          } else {
            const errorData = await freeRes.json();
            
            if (paymentResult) {
              paymentResult.style.color = "red";
              paymentResult.textContent = getUserMessage('free_reservation_error') + (errorData.message || getUserMessage('unknown_error'));
            }

            // Očisti sve timere i cache
            cleanupAllTimersAndCache();
          }
        } catch (err) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            if (err.message.includes('timeout')) {
              paymentResult.textContent = "Greška: Prekoračen timeout za API poziv. Molimo pokušajte ponovo.";
            } else {
              paymentResult.textContent = getUserMessage('free_reservation_error') + err.message;
            }
          }
          // Očisti sve timere i cache
          cleanupAllTimersAndCache();
        }
        
        if (reserveBtn) reserveBtn.disabled = false;
        return; // Prekini izvršavanje - ne ide na plaćanje
      }

      // PROVERA KRITIČNOG SLOTA: Ako je remaining = 1, rezerviši slot pre temp reservation
      const pickUpId = timeSlotMap[data.departure_time];
      const dropOffId = timeSlotMap[data.arrival_time];
      
      if (pickUpId && dropOffId) {
        const [pickupData, dropoffData] = await Promise.all([
          checkSlotAvailability(data.reservation_date, pickUpId, 'pick_up'),
          checkSlotAvailability(data.reservation_date, dropOffId, 'drop_off')
        ]);
        
        const pickupRemaining = pickupData?.remaining || 0;
        const dropoffRemaining = dropoffData?.remaining || 0;
        
        // PROVERA KRITIČNOG SLOTA (remaining = 1)
        if (pickupRemaining === 1 || dropoffRemaining === 1) {
          try {
            await handleCriticalSlotReservation(data.reservation_date, pickUpId, dropOffId);
          } catch (error) {
            console.error('Form submit - greška pri handleCriticalSlotReservation:', error);
            if (paymentResult) {
              paymentResult.style.color = "red";
              paymentResult.textContent = getUserMessage('critical_slot_reservation_error');
            }
            if (reserveBtn) reserveBtn.disabled = false;
            return;
          }
        }
        
        // FINALNA PROVERA DOSTUPNOSTI PRE PLAĆANJA
        if (pickupRemaining === 0 || dropoffRemaining === 0) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = getUserMessage('slots_unavailable_payment');
          }
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
        
        // DODATNA PROVERA RACE CONDITION-A
        const hasRaceCondition = await checkForRaceCondition(data.reservation_date, pickUpId, dropOffId);
        if (hasRaceCondition) {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = "⚠️ Race condition detected - slots became unavailable. Please try again.";
          }
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
        
        console.log('Finalna provera dostupnosti - slotovi su slobodni:', {
          pickup: { id: pickUpId, remaining: pickupRemaining },
          dropoff: { id: dropOffId, remaining: dropoffRemaining }
        });
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
        // CSRF: Osiguraj CSRF token
        await ensureCsrfCookie();

        // Timeout za temp reservation API (15 sekundi)
        const tempTimeoutPromise = new Promise((_, reject) => {
          setTimeout(() => reject(new Error('API timeout')), 15000);
        });
        
        const tempFetchPromise = fetch('/api/temp-reservation', {
          method: 'POST',
          headers: addCsrfHeaders({ 
            'Content-Type': 'application/json', 
            'Accept': 'application/json'
          }),
          body: JSON.stringify(tempPayload)
        });
        
        const tempRes = await Promise.race([tempFetchPromise, tempTimeoutPromise]);

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
            if (err.message.includes('timeout')) {
              paymentResult.textContent = "Greška: Prekoračen timeout za API poziv. Molimo pokušajte ponovo.";
            } else {
              paymentResult.textContent = getUserMessage('temp_data_error') + err.message;
            }
          }
          // Očisti sve timere i cache
          cleanupAllTimersAndCache();
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
      }

      console.log('Pripremam fetch za /procesiraj-placanje', merchantTransactionId);
      console.log('merchantTransactionId:', merchantTransactionId);

      try {
        // Umesto FormData koristi JSON payload za /procesiraj-placanje
        const payload = {
          merchantTransactionId: merchantTransactionId
        };

        // Osiguraj CSRF token za plaćanje
        await ensureCsrfCookie();
        
        // Timeout za payment API (20 sekundi)
        const payTimeoutPromise = new Promise((_, reject) => {
          setTimeout(() => reject(new Error('API timeout')), 20000);
        });
        
        const payFetchPromise = fetch('/api/procesiraj-placanje', {
          method: 'POST',
          headers: addCsrfHeaders({
            'Content-Type': 'application/json',
            'Accept': 'application/json'
          }),
          body: JSON.stringify(payload)
        });
        
        const payRes = await Promise.race([payFetchPromise, payTimeoutPromise]);

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
            updateReserveButtonState(); // Prvo ažuriraj UI
            reservationDateInput.dispatchEvent(new Event('change'));
          }

          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        } else {
          if (paymentResult) {
            paymentResult.style.color = "red";
            paymentResult.textContent = payResp.message || getUserMessage('payment_initialization_error');
          }
          currentSlotReservation = null;
          if (reservationTimer) {
            clearInterval(reservationTimer);
          }
        }
      } catch (err) {
        if (paymentResult) {
          paymentResult.style.color = "red";
          if (err.message.includes('timeout')) {
            paymentResult.textContent = "Greška: Prekoračen timeout za API poziv. Molimo pokušajte ponovo.";
          } else {
            paymentResult.textContent = getUserMessage('payment_sending_error') + err.message;
          }
        }
        // Očisti sve timere i cache
        cleanupAllTimersAndCache();
      }
      if (reserveBtn) reserveBtn.disabled = false;
    });

    // Initial fetch for default date
    if (reservationDateInput) {
      updateReserveButtonState(); // Prvo ažuriraj UI
      reservationDateInput.dispatchEvent(new Event('change'));
    }

    // Registracija velikim slovima
    const registrationInput = document.getElementById('registration-input');
    if (registrationInput) {
      registrationInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        // Ne pozivaj API dok se kuca - samo ažuriraj UI
        updateReserveButtonState();
      });
    }

    // Event listener-i za polja forme - pozivaju debouncedCheckAndToggleReserveButton kada se popune
    const companyNameInput = document.getElementById('company_name');
    if (companyNameInput) {
      companyNameInput.addEventListener('input', function() {
        // Ne pozivaj API dok se kuca - samo ažuriraj UI
        updateReserveButtonState();
      });
    }

    const countryInput = document.getElementById('country-input');
    if (countryInput) {
      countryInput.addEventListener('change', function() {
        // Ne pozivaj API dok se kuca - samo ažuriraj UI
        updateReserveButtonState();
      });
    }

    const vehicleTypeInput = document.getElementById('vehicle_type_id');
    if (vehicleTypeInput) {
      vehicleTypeInput.addEventListener('change', function() {
        // Ne pozivaj API dok se kuca - samo ažuriraj UI
        updateReserveButtonState();
      });
    }

    const emailInput = document.getElementById('email');
    if (emailInput) {
      emailInput.addEventListener('input', function() {
        // Ne pozivaj API dok se kuca - samo ažuriraj UI
        updateReserveButtonState();
      });
    }
  }
});

// Funkcija za proveru da li je došlo do race condition-a
function checkForRaceCondition(date, pickUpId, dropOffId) {
  // Proveri da li su slotovi još uvek dostupni pre plaćanja
  return Promise.all([
    checkSlotAvailability(date, pickUpId, 'pick_up'),
    checkSlotAvailability(date, dropOffId, 'drop_off')
  ]).then(([pickupData, dropoffData]) => {
    const pickupRemaining = pickupData?.remaining || 0;
    const dropoffRemaining = dropoffData?.remaining || 0;
    
    // Ako su slotovi popunjeni, to je race condition
    if (pickupRemaining === 0 || dropoffRemaining === 0) {
      console.warn('Race condition detected - slots became unavailable during payment process', {
        pickup: { id: pickUpId, remaining: pickupRemaining },
        dropoff: { id: dropOffId, remaining: dropoffRemaining }
      });
      return true;
    }
    
    return false;
  }).catch(error => {
    console.error('Error checking for race condition:', error);
    return false; // Ako ne možemo da proverimo, pretpostavimo da nema race condition-a
  });
}