// Funkcija za čitanje URL parametara
function getUrlParams() {
  const params = {};
  window.location.search.substring(1).split('&').forEach(function(pair) {
    if (pair) {
      var parts = pair.split('=');
      params[decodeURIComponent(parts[0])] = decodeURIComponent(parts[1] || '');
    }
  });
  return params;
}

// Popuni formu na osnovu URL parametara i onemogući izmenu cene
const params = getUrlParams();
const price = params.vehicle_price;
const vehicleTypeId = params.vehicle_type_id;

if (price && vehicleTypeId) {
  // Prikazi iznos, ali ga ne daj korisniku da menja!
  document.getElementById('payment-amount').textContent = "Iznos za plaćanje: " + price + " €";
  document.getElementById('vehicle_type_id_hidden').value = vehicleTypeId;
} else {
  document.getElementById('payment-amount').textContent = "Nije prosleđena cijena ili tip vozila.";
  document.getElementById('payment-form').style.display = 'none';
}

// Submit handler za slanje na backend i redirect na Bankart HPP
document.getElementById('payment-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  // Pretvori u objekat za JSON
  const data = {};
  formData.forEach((value, key) => { data[key] = value; });

  // Dodaj vehicle_type_id i cenu iz URL-a (NE iz forme, korisnik ne može menjati!)
  data.vehicle_type_id = vehicleTypeId;
  data.vehicle_price = price;

  document.getElementById('payment-result').textContent = "Obrada...";

  try {
    const response = await fetch('/procesiraj-placanje', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-XSRF-TOKEN': getCookie('XSRF-TOKEN')
      },
      body: JSON.stringify(data)
    });

    if (response.ok) {
      const result = await response.json();
      if (result.redirect_url) {
        window.location.href = result.redirect_url;
      } else {
        document.getElementById('payment-result').textContent = result.message || "Nepoznata greška.";
      }
    } else {
      const err = await response.json();
      document.getElementById('payment-result').textContent = err.message || "Greška pri plaćanju.";
    }
  } catch (err) {
    document.getElementById('payment-result').textContent = "Greška pri komunikaciji sa serverom.";
  }
});

// Helper za dobijanje cookie vrednosti (za CSRF)
function getCookie(name) {
  let match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
  if (match) return decodeURIComponent(match[2]);
  return null;
}