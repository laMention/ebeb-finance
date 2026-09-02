<header class="fixed top-0 inset-x-0 z-50 backdrop-blur-md bg-[#F7F4FB]/85 border-b border-[#553B9E]/10">
  <div class="max-w-7xl mx-auto py-5 px-5 md:px-8 h-24 flex items-center justify-between">
    <a href="#hero" class="flex items-center gap-2">
        @if(isset(info_public_plateforme()['logo_principal_url']))
            <div class="py-3">
                <img src="{{ info_public_plateforme()['logo_principal_url'] }}" alt="{{ info_public_plateforme()['nom_plateforme'] }}" class="w-16 h-16 rounded-full object-contain">
            </div>
        @else
            <div class="w-9 h-9 rounded-xl grad-cta flex items-center justify-center text-white font-bold font-display">E</div>
            <span class="font-display font-bold text-lg" style="color:var(--violet)">E-BEB <span style="color:var(--terracotta)">Finance</span></span>
        @endif
    </a>
    <nav class="hidden md:flex items-center gap-7 text-sm font-semibold text-[#3A3155]">
      <a href="#fonctionnement" class="hover:text-[var(--violet)]">Fonctionnement</a>
      <a href="#fonctionnalites" class="hover:text-[var(--violet)]">Fonctionnalités</a>
      <a href="#avantages" class="hover:text-[var(--violet)]">Avantages</a>
      <a href="#avis" class="hover:text-[var(--violet)]">Avis</a>
      <a href="#faq" class="hover:text-[var(--violet)]">FAQ</a>
    </nav>
    <a href="#telecharger" class="hidden sm:inline-flex items-center gap-2 rounded-full px-5 py-2.5 text-white text-sm font-bold grad-cta shadow-lg shadow-[#553B9E]/30">
      <i class="fa fa-dowload"></i>Télécharger l'app
    </a>
  </div>
</header>