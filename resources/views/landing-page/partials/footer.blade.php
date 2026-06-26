<footer class="bg-[#1A1530] text-white/70 py-14 px-5 md:px-8">
  <div class="max-w-7xl mx-auto grid md:grid-cols-4 gap-10">
    <div>
      <div class="flex items-center gap-2 mb-3">
        @if(isset(info_public_plateforme()['logo_principal_url']))
            <div class="py-3">
                <img src="{{ info_public_plateforme()['logo_principal_url'] }}" alt="{{ info_public_plateforme()['nom_plateforme'] }}" class="w-16 h-16 rounded-full object-contain">
            </div>
        @else
            <div class="w-9 h-9 rounded-xl grad-cta flex items-center justify-center text-white font-bold font-display">E</div>
            <span class="font-display font-bold text-lg text-white">E-BEB Finance</span>
        @endif  
    </div>
      <p class="text-sm">La plateforme d'épargne et de cotisation sociale pour les travailleurs indépendants de Côte d'Ivoire.</p>
      <div class="flex gap-3 mt-5">
        <a href="#" aria-label="Facebook" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M22 12a10 10 0 10-11.6 9.9v-7H7.9V12h2.5V9.8c0-2.5 1.5-3.9 3.8-3.9 1.1 0 2.2.2 2.2.2v2.5h-1.3c-1.2 0-1.6.8-1.6 1.6V12h2.8l-.4 2.9h-2.4v7A10 10 0 0022 12z"/></svg></a>
        <a href="#" aria-label="Instagram" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.6 0 4.8.1 1.2 0 1.9.2 2.4.4.6.2 1 .5 1.5.9.4.4.7.9.9 1.5.2.5.3 1.2.4 2.4.1 1.2.1 1.6.1 4.8s0 3.6-.1 4.8c0 1.2-.2 1.9-.4 2.4-.2.6-.5 1-.9 1.5-.4.4-.9.7-1.5.9-.5.2-1.2.3-2.4.4-1.2.1-1.6.1-4.8.1s-3.6 0-4.8-.1c-1.2 0-1.9-.2-2.4-.4-.6-.2-1-.5-1.5-.9-.4-.4-.7-.9-.9-1.5-.2-.5-.3-1.2-.4-2.4C2.2 15.6 2.2 15.2 2.2 12s0-3.6.1-4.8c0-1.2.2-1.9.4-2.4.2-.6.5-1 .9-1.5.4-.4.9-.7 1.5-.9.5-.2 1.2-.3 2.4-.4C8.4 2.2 8.8 2.2 12 2.2zm0 1.8c-3.1 0-3.5 0-4.7.1-1 0-1.5.2-1.9.3-.5.2-.8.4-1.1.7-.3.3-.5.6-.7 1.1-.1.4-.3.9-.3 1.9-.1 1.2-.1 1.6-.1 4.7s0 3.5.1 4.7c0 1 .2 1.5.3 1.9.2.5.4.8.7 1.1.3.3.6.5 1.1.7.4.1.9.3 1.9.3 1.2.1 1.6.1 4.7.1s3.5 0 4.7-.1c1 0 1.5-.2 1.9-.3.5-.2.8-.4 1.1-.7.3-.3.5-.6.7-1.1.1-.4.3-.9.3-1.9.1-1.2.1-1.6.1-4.7s0-3.5-.1-4.7c0-1-.2-1.5-.3-1.9-.2-.5-.4-.8-.7-1.1-.3-.3-.6-.5-1.1-.7-.4-.1-.9-.3-1.9-.3-1.2-.1-1.6-.1-4.7-.1zm0 4.6a5.4 5.4 0 110 10.8 5.4 5.4 0 010-10.8zm0 1.8a3.6 3.6 0 100 7.2 3.6 3.6 0 000-7.2zm6.9-2a1.3 1.3 0 11-2.6 0 1.3 1.3 0 012.6 0z"/></svg></a>
        <a href="#" aria-label="LinkedIn" class="w-9 h-9 rounded-full bg-white/10 flex items-center justify-center hover:bg-white/20"><svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M4.98 3.5a2.5 2.5 0 11-.02 5 2.5 2.5 0 01.02-5zM3 9h4v12H3V9zm7 0h3.8v1.7h.1c.5-1 1.8-1.9 3.6-1.9 3.9 0 4.5 2.5 4.5 5.7V21h-4v-5.7c0-1.4 0-3.2-2-3.2s-2.3 1.5-2.3 3.1V21h-4V9z"/></svg></a>
      </div>
    </div>
    <div>
      <h4 class="font-bold text-white mb-3">Produit</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="#fonctionnement" class="hover:text-white">Fonctionnement</a></li>
        <li><a href="#fonctionnalites" class="hover:text-white">Fonctionnalités</a></li>
        <li><a href="#avantages" class="hover:text-white">Avantages</a></li>
        <li><a href="#faq" class="hover:text-white">FAQ</a></li>
      </ul>
    </div>
    <div>
      <h4 class="font-bold text-white mb-3">Contact</h4>
      <ul class="space-y-2 text-sm">
        <li>{{ info_public_plateforme()['email_contact'] ?? 'contact@ebebfinance.ci'}}</li>
        <li>{{info_public_plateforme()['telephone_contact'] ?? '+225 27 00 00 00 00'}}</li>
        <li>Abidjan, Côte d'Ivoire</li>
      </ul>
    </div>
    <div>
      <h4 class="font-bold text-white mb-3">Légal</h4>
      <ul class="space-y-2 text-sm">
        <li><a href="#" class="hover:text-white">Conditions Générales d'Utilisation</a></li>
        <li><a href="#" class="hover:text-white">Politique de confidentialité</a></li>
      </ul>
    </div>
  </div>
  <div class="max-w-7xl mx-auto border-t border-white/10 mt-10 pt-6 text-xs text-center">
    {{ info_public_plateforme()['copyright'] ?? '© 2026 E-BEB Finance. Tous droits réservés.' }}
  </div>
</footer>