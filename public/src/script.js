// --- GLOBAL FUNCTIONS ---

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
    now.setMinutes(now.getMinutes() + 1);
    minTime = now.toTimeString().slice(0, 5);
  }

  times.forEach(time => {
    if (!minTime || time >= minTime) {
      const option = document.createElement('option');
      option.value = time;
      option.textContent = time;
      select.appendChild(option);
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
  const allArrivalOptions = Array.from(arrivalSelect.options).map(opt => opt.value).filter(Boolean);
  const allDepartureOptions = Array.from(departureSelect.options).map(opt => opt.value).filter(Boolean);

  const arrivalTime = arrivalSelect.value;
  const departureTime = departureSelect.value;

  if (arrivalTime && departureTime) {
    // Filter arrival options to before departure
    arrivalSelect.innerHTML = '<option value="">Select time slot</option>';
    allArrivalOptions.forEach(time => {
      if (time < departureTime) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        arrivalSelect.appendChild(option);
      }
    });
    arrivalSelect.value = arrivalTime < departureTime ? arrivalTime : '';

    // Filter departure options to after arrival
    departureSelect.innerHTML = '<option value="">Select time slot</option>';
    allDepartureOptions.forEach(time => {
      if (time > arrivalTime) {
        const option = document.createElement('option');
        option.value = time;
        option.textContent = time;
        departureSelect.appendChild(option);
      }
    });
    departureSelect.value = departureTime > arrivalTime ? departureTime : '';
  } else {
    // If one is empty, show all options
    populateTimeSlotSelect('arrival-time-slot', allArrivalOptions, arrivalTime);
    populateTimeSlotSelect('departure-time-slot', allDepartureOptions, departureTime);
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
    registration: "Registracione tablice",
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
    privacyLink: "Pročitaj politiku"
  }
};

function setLanguage(lang) {
  const ids = [
    ['pick-date-label', 'pickDate'],
    ['arrival-label', 'arrival'],
    ['departure-label', 'departure'],
    ['company_name', 'company', 'placeholder'],
    ['country-input', 'country', 'placeholder'],
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

  const termsText = {
    en: `
      <p><strong>By using this service, you agree to abide by all rules and regulations set forth by Kotorbus.</strong></p>
      <ul>
        <li>These terms establish the ordering process, payment, and download of the products offered on the kotorbus.me website. The kotorbus.me website is available for private use without any fees and according to the following terms and conditions.</li>
        <li>The Vendor is the Municipality of Kotor and the Buyer is the visitor of this website who completes an electronic request, sends it to the Vendor and conducts a payment using a credit or debit card. The Product is one of the items on offer on the kotorbus.me website – a fee for stopping and parking in a special traffic regulation zone based on the prices established by provisions of the Assembly of the Municipality of Kotor (dependent on bus capacity).</li>
        <li>The Buyer orders the product or products by filling an electronic form. Any person who orders at least one product, enters the required information, and sends their order is considered to be a buyer.</li>
        <li>All the prices are final, shown in EUR. The Vendor, the Municipality of Kotor, as a local authority, is not a taxpayer within the VAT system; therefore the prices on the website do not include VAT.</li>
        <li>To process the services which the Buyer ordered through the website, there are no additional fees incurred on the Buyer.</li>
        <li>The goods and/or services are ordered online. The goods are considered to be ordered when the Buyer selects and confirms a payment method and when the credit or debit card authorization process is successfully terminated. Once the ordering process is completed, the Buyer gets an invoice which serves both as a confirmation of your order/proof of payment and a voucher for the service.</li>
        <li><strong>Payment:</strong> The products and services are paid online by using one of the following debit or credit cards: MasterCard®, Maestro® or Visa.</li>
        <li><strong>General conditions:</strong> Depending on the amount paid, the service is available for the vehicle of selected category, on the date and during the time indicated when making the purchase. The Voucher cannot be used outside the selected period. Once used, the Voucher can no longer be used. The Buyer is responsible for the use of the Voucher. The Municipality of Kotor bears no responsibility for the unauthorized use of the Voucher.</li>
        <li>The Municipality of Kotor reserves the right to change these terms and conditions. Any changes will be applied to the use of the kotorbus.me website. The buyer bears the responsibility for the accuracy and completeness of data during the buying process.</li>
        <li>The services provided by the Municipality of Kotor on the kotorbus.me website do not include the costs incurred by using computer equipment and internet service providers' services to access our website. The Municipality of Kotor is not responsible for any costs, including, but not limited to, telephone bills, Internet traffic bills or any other kind of costs that may be incurred.</li>
        <li>The Buyer does not have the right to a refund.</li>
        <li>The Municipality of Kotor cannot guarantee that the service will be free of errors. If an error occurs, kindly report it to: bus@kotor.me and we shall remove the error as soon as we possibly can.</li>
      </ul>
    `,
    mne: `
      <p><strong>Korišćenjem ove usluge, slažete se da poštujete sva pravila i propise koje je postavio Kotorbus.</strong></p>
      <ul>
        <li>Ovi uslovi definišu proces naručivanja, plaćanja i preuzimanja proizvoda ponuđenih na sajtu kotorbus.me. Sajt kotorbus.me je dostupan za privatnu upotrebu bez naknade i u skladu sa sljedećim uslovima korišćenja.</li>
        <li>Prodavac je Opština Kotor, a Kupac je posjetilac ovog sajta koji popuni elektronski zahtjev, pošalje ga Prodavcu i izvrši plaćanje putem kreditne ili debitne kartice. Proizvod je jedna od stavki u ponudi na sajtu kotorbus.me – naknada za zaustavljanje i parkiranje u zoni posebnog režima saobraćaja prema cijenama utvrđenim odlukom Skupštine Opštine Kotor (u zavisnosti od kapaciteta autobusa).</li>
        <li>Kupac naručuje proizvod ili proizvode popunjavanjem elektronskog formulara. Svako ko naruči makar jedan proizvod, unese potrebne podatke i pošalje narudžbu smatra se kupcem.</li>
        <li>Sve cijene su konačne, iskazane u EUR. Prodavac, Opština Kotor, kao lokalna samouprava, nije obveznik PDV-a; stoga cijene na sajtu ne sadrže PDV.</li>
        <li>Za obradu usluga koje je Kupac naručio putem sajta, Kupcu se ne naplaćuju dodatne takse.</li>
        <li>Roba i/ili usluge se naručuju online. Roba se smatra naručenom kada Kupac izabere i potvrdi način plaćanja i kada se proces autorizacije kreditne ili debitne kartice uspješno završi. Po završetku procesa naručivanja, Kupac dobija fakturu koja služi kao potvrda narudžbe/dokaz o plaćanju i vaučer za uslugu.</li>
        <li><strong>Plaćanje:</strong> Proizvodi i usluge se plaćaju online korišćenjem jedne od sljedećih debitnih ili kreditnih kartica: MasterCard®, Maestro® ili Visa.</li>
        <li><strong>Opšti uslovi:</strong> U zavisnosti od iznosa plaćanja, usluga je dostupna za vozilo izabrane kategorije, na datum i u vremenskom periodu navedenom prilikom kupovine. Vaučer se ne može koristiti van izabranog perioda. Nakon korišćenja, vaučer više nije važeći. Kupac je odgovoran za korišćenje vaučera. Opština Kotor ne snosi odgovornost za neovlašćeno korišćenje vaučera.</li>
        <li>Opština Kotor zadržava pravo izmjene ovih uslova korišćenja. Sve promjene će se primjenjivati na korišćenje sajta kotorbus.me. Kupac snosi odgovornost za tačnost i potpunost podataka tokom procesa kupovine.</li>
        <li>Usluge koje pruža Opština Kotor putem sajta kotorbus.me ne uključuju troškove nastale korišćenjem računarske opreme i usluga internet provajdera za pristup našem sajtu. Opština Kotor nije odgovorna za bilo kakve troškove, uključujući, ali ne ograničavajući se na telefonske račune, račune za internet saobraćaj ili bilo koje druge troškove koji mogu nastati.</li>
        <li>Kupac nema pravo na povraćaj novca.</li>
        <li>Opština Kotor ne može garantovati da će usluga biti bez grešaka. Ukoliko dođe do greške, molimo vas da je prijavite na: bus@kotor.me i uklonićemo je u najkraćem mogućem roku.</li>
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
}

// --- CSRF SANCTUM: Prvo postavi cookie na load ---
document.addEventListener('DOMContentLoaded', function () {
  // Ključna linija za CSRF zaštitu:
  fetch('/sanctum/csrf-cookie', { credentials: 'same-origin' });
});

document.addEventListener('DOMContentLoaded', function () {
  setLanguage('en'); // or 'mne' for default

  // Today's date string
  const today = new Date();
  const todayStr = today.toISOString().slice(0, 10);

  // Set min date for the date input to today
  const reservationDateInput = document.getElementById('reservation_date');
  if (reservationDateInput) {
    reservationDateInput.min = todayStr;
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
      validRange: { start: todayStr },
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
  if (arrivalSelect) arrivalSelect.addEventListener('change', filterTimeSlots);
  if (departureSelect) departureSelect.addEventListener('change', filterTimeSlots);

  // On date change, fetch slots and populate selects
  if (reservationDateInput) {
    reservationDateInput.addEventListener('change', function () {
      const date = this.value;
      fetchAvailableSlotsForDate(date, function(availableSlots) {
        timeSlotMap = {};
        availableSlots.forEach(s => {
          timeSlotMap[s.time_slot] = s.id;
        });
        const allTimeSlotsForDay = availableSlots.map(s => s.time_slot);
        populateTimeSlotSelect('arrival-time-slot', allTimeSlotsForDay);
        populateTimeSlotSelect('departure-time-slot', allTimeSlotsForDay);
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

      // Prepare payload
      const tempPayload = {
        drop_off_time_slot_id: timeSlotMap[data.departure_time],
        pick_up_time_slot_id: timeSlotMap[data.arrival_time],
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
        paymentResult.textContent = "Slanje...";
      }

      // 1. Save temp data
      let merchantTransactionId = '';
      try {
        const tempRes = await fetch('/api/temp-reservation', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 
            'Content-Type': 'application/json', 
            'Accept': 'application/json',
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
            paymentResult.textContent = "Neuspješan upis privremenih podataka!";
          }
          if (reserveBtn) reserveBtn.disabled = false;
          return;
        }
        merchantTransactionId = tempResp.merchant_transaction_id;
      } catch (err) {
        if (paymentResult) {
          paymentResult.style.color = "red";
          paymentResult.textContent = "Greška pri upisu privremenih podataka: " + err.message;
        }
        if (reserveBtn) reserveBtn.disabled = false;
        return;
      }

      // --- KORISTI XSRF TOKEN IZ COOKIE ZA /procesiraj-placanje ---
const xsrf = getCookie('XSRF-TOKEN');
const formData = new FormData();
formData.append('merchantTransactionId', merchantTransactionId);

try {
  const payRes = await fetch('/procesiraj-placanje', {
    method: 'POST',
    credentials: 'same-origin', // ili 'include', oba rade za isti domen
    headers: { 
      'Accept': 'application/json',
      'X-XSRF-TOKEN': xsrf
    },
    body: formData
  });

  let payResp = {};
  try { payResp = await payRes.json(); } catch (jsonErr) {}

  if (payResp.redirectUrl) {
    window.location.href = payResp.redirectUrl;
    return;
  } else if (payResp.errors) {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = Object.values(payResp.errors).flat().join(' ');
    }
  } else {
    if (paymentResult) {
      paymentResult.style.color = "red";
      paymentResult.textContent = payResp.message || "Greška pri inicijalizaciji plaćanja.";
    }
  }
} catch (err) {
  if (paymentResult) {
    paymentResult.style.color = "red";
    paymentResult.textContent = "Greška u komunikaciji sa serverom.";
  }
} finally {
  if (reserveBtn) reserveBtn.disabled = false;
}
}); // <-- Ova zagrada zatvara reservationForm.addEventListener('submit', ...)

  // Initial fetch for default date
  if (reservationDateInput) {
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

}); // <-- Ova zagrada zatvara document.addEventListener('DOMContentLoaded', ...)

// --- STARO ZA CUSTOM /csrf-token ---
// OBRISI! NIJE POTREBNO KADA KORISTIS XSRF-TOKEN COOKIE + SANCTUM
// document.addEventListener('DOMContentLoaded', function () {
//   fetch('/csrf-token')
//     .then(res => res.json())
//     .then(tokenObj => {
//       const csrfInput = document.getElementById('csrf-token-input');
//       if (csrfInput && tokenObj.csrf_token) {
//         csrfInput.value = tokenObj.csrf_token;
//         console.log('CSRF token upisan!');
//       } else {
//         console.warn('CSRF input ili token nisu pronađeni!');
//       }
//     })
//     .catch(err => {
//       console.error('Greška pri dohvaćanju CSRF tokena:', err);
//     });
// });