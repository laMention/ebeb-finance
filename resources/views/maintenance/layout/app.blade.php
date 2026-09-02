<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Site en maintenance</title>
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/style_maintenance.css') }}">
    <link rel="icon" type="image/png" href="{{ info_public_plateforme()['logo_favicon_url'] ?? asset('assets/images/logo.png') }}"/>


</head>
<body>

  <div class="stars"></div>

  <div class="container">
    @yield('content')
  </div>

  <script>
    // Modifie cette date pour définir la date de fin de maintenance
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 5);

    function updateCountdown() {
      const now = new Date();
      let diff = endDate - now;
      if (diff < 0) diff = 0;

      const days = Math.floor(diff / (1000 * 60 * 60 * 24));
      const hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
      const minutes = Math.floor((diff / (1000 * 60)) % 60);
      const seconds = Math.floor((diff / 1000) % 60);

      document.getElementById('days').textContent = String(days).padStart(2, '0');
      document.getElementById('hours').textContent = String(hours).padStart(2, '0');
      document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
      document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);

    function handleSubmit(e) {
      e.preventDefault();
      const email = document.getElementById('emailInput').value;
      alert('Merci ! Nous vous préviendrons à ' + email + ' dès que le site sera de retour.');
      document.getElementById('emailInput').value = '';
    }
  </script>

</body>
</html>
