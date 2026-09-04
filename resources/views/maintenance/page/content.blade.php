<div class="icon-wrapper">
      <svg viewBox="0 0 24 24"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6l-3 3-4.3-4.3C.6 7.1 1 10.1 3 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.4-.4.4-1 0-1.4z"/></svg>
    </div>

    @php
      $isDisabled = ($statut ?? '') === 'DESACTIVEE';
      $title = $title ?? ($isDisabled ? 'Plateforme indisponible' : 'Site en maintenance');
      $subtitle = $message ?? ($isDisabled
        ? 'La plateforme est actuellement désactivée. Veuillez réessayer plus tard.'
        : 'Nous améliorons actuellement votre expérience. Merci de votre patience, nous serons bientôt de retour !');
    @endphp

    <h1>{{ $title }}</h1>
    <p class="subtitle">
      {{ $subtitle }}
    </p>

    <div class="progress-bar">
      <div class="progress-fill"></div>
    </div>

    <div class="countdown" id="countdown">
      <div><span id="days">00</span><small>Jours</small></div>
      <div><span id="hours">00</span><small>Heures</small></div>
      <div><span id="minutes">00</span><small>Minutes</small></div>
      <div><span id="seconds">00</span><small>Secondes</small></div>
    </div>

    <form onsubmit="handleSubmit(event)">
      <input type="email" id="emailInput" placeholder="Votre adresse email" required>
      <button type="submit">Me prévenir</button>
    </form>

    <div class="socials">
      <a href="#" aria-label="Facebook">
        <svg viewBox="0 0 24 24"><path d="M22 12c0-5.5-4.5-10-10-10S2 6.5 2 12c0 5 3.7 9.1 8.4 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7C18.3 21.1 22 17 22 12z"/></svg>
      </a>
      <a href="#" aria-label="Twitter">
        <svg viewBox="0 0 24 24"><path d="M23 4.9c-.8.4-1.7.6-2.6.8 1-.6 1.7-1.5 2-2.6-.9.5-1.9.9-3 1.1-.9-1-2.1-1.5-3.4-1.5-2.6 0-4.7 2.1-4.7 4.7 0 .4 0 .7.1 1.1-3.9-.2-7.3-2-9.6-4.9-.4.7-.6 1.5-.6 2.3 0 1.6.8 3.1 2.1 3.9-.8 0-1.5-.2-2.1-.6v.1c0 2.3 1.6 4.2 3.8 4.6-.4.1-.8.2-1.2.2-.3 0-.6 0-.8-.1.6 1.9 2.4 3.2 4.4 3.3-1.6 1.3-3.6 2-5.8 2-.4 0-.7 0-1.1-.1C2.4 20.3 5 21 7.7 21c9.2 0 14.3-7.7 14.3-14.3v-.7c1-.7 1.8-1.6 2.5-2.6z"/></svg>
      </a>
      <a href="#" aria-label="Instagram">
        <svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.9.1 1.2.1 2 .3 2.4.5.6.2 1 .5 1.5 1 .4.4.7.9 1 1.5.2.4.4 1.2.5 2.4.1 1.3.1 1.7.1 4.9s0 3.6-.1 4.9c-.1 1.2-.3 2-.5 2.4-.2.6-.5 1-1 1.5-.4.4-.9.7-1.5 1-.4.2-1.2.4-2.4.5-1.3.1-1.7.1-4.9.1s-3.6 0-4.9-.1c-1.2-.1-2-.3-2.4-.5-.6-.2-1-.5-1.5-1-.4-.4-.7-.9-1-1.5-.2-.4-.4-1.2-.5-2.4-.1-1.3-.1-1.7-.1-4.9s0-3.6.1-4.9c.1-1.2.3-2 .5-2.4.2-.6.5-1 1-1.5.4-.4.9-.7 1.5-1 .4-.2 1.2-.4 2.4-.5 1.3-.1 1.7-.1 4.9-.1zM12 0C8.7 0 8.3 0 7 .1c-1.3.1-2.2.3-3 .6-.8.3-1.5.7-2.2 1.4C1 2.8.6 3.5.3 4.3c-.3.8-.5 1.7-.6 3C-.1 8.3-.1 8.7-.1 12s0 3.7.1 5c.1 1.3.3 2.2.6 3 .3.8.7 1.5 1.4 2.2.7.7 1.4 1.1 2.2 1.4.8.3 1.7.5 3 .6 1.3.1 1.7.1 5 .1s3.7 0 5-.1c1.3-.1 2.2-.3 3-.6.8-.3 1.5-.7 2.2-1.4.7-.7 1.1-1.4 1.4-2.2.3-.8.5-1.7.6-3 .1-1.3.1-1.7.1-5s0-3.7-.1-5c-.1-1.3-.3-2.2-.6-3-.3-.8-.7-1.5-1.4-2.2-.7-.7-1.4-1.1-2.2-1.4-.8-.3-1.7-.5-3-.6C15.7 0 15.3 0 12 0z"/><path d="M12 5.8a6.2 6.2 0 1 0 0 12.4 6.2 6.2 0 0 0 0-12.4zm0 10.2a4 4 0 1 1 0-8 4 4 0 0 1 0 8zM18.4 5.6a1.4 1.4 0 1 1-2.8 0 1.4 1.4 0 0 1 2.8 0z"/></svg>
      </a>
    </div>

    <footer>&copy; 2026 — Tous droits réservés</footer>