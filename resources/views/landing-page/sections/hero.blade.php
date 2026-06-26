<!-- HERO -->
<section id="hero" class="grad-hero pt-28 pb-20 md:pt-36 md:pb-28 px-5 md:px-8 relative overflow-hidden">
  <div class="absolute inset-0 opacity-20 pointer-events-none" style="background-image:radial-gradient(circle at 80% 10%, #fff 0, transparent 45%)"></div>
  <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center relative">
    <div class="reveal in">
      <span class="chip inline-block mb-5 bg-white/10 text-white border border-white/20">Fait pour les indépendants de Côte d'Ivoire</span>
      <h1 class="font-display font-bold text-4xl md:text-5xl lg:text-[3.3rem] leading-[1.08] text-white">
        Chaque paiement reçu, <span class="text-grad">automatiquement réparti</span> entre épargne et cotisations.
      </h1>
      <p class="mt-6 text-base md:text-lg text-white/80 max-w-md">
        E-BEB Finance encaisse vos revenus Mobile Money et met de côté, pour vous, votre CNPS, votre AMU et votre épargne — sans calcul, sans oubli, sans effort.
      </p>
      <div class="mt-8 flex flex-wrap gap-3">
        <a href="#telecharger" class="btn-store">
          {{-- <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 3v18l13-9L3 3z" fill="#553B9E"/></svg> --}}
          <img src="{{ asset('assets/images/google-play-store-icon.webp') }}" class="w-8 h-8" >
          <span class="text-left leading-tight"><span class="block text-[10px] text-gray-500">Disponible sur</span><span class="block text-sm font-bold">Google Play</span></span>
        </a>
        <a href="#telecharger" class="btn-store">
          <img src="{{ asset('assets/images/app-store.png') }}" class="w-8 h-8" >

          {{-- <svg width="20" height="20" viewBox="0 0 24 24" fill="#553B9E"><path d="M16.5 1c.1 1.1-.3 2.2-1 3-.7.8-1.8 1.4-2.9 1.3-.1-1.1.4-2.3 1.1-3C14.3 1.5 15.5 1 16.5 1zM21 17.3c-.6 1.3-.9 1.9-1.7 3-1.1 1.6-2.7 3.6-4.6 3.6-1.7 0-2.1-1.1-4.4-1.1-2.3 0-2.8 1.1-4.5 1.1-1.9 0-3.4-1.8-4.5-3.4C-1.1 16.6-.4 10 3.5 7.6c1.1-.7 2.4-1.1 3.6-1.1 1.4 0 2.6.9 3.5.9.9 0 2.4-1.1 4.1-.9.9.1 2.6.5 3.6 2-.1 0-2.3 1.3-2.3 4 0 3.1 2.6 4.2 2.6 4.2-.1.2-.6 1.4-1.1 2.6z"/></svg> --}}
          <span class="text-left leading-tight"><span class="block text-[10px] text-gray-500">Disponible sur</span><span class="block text-sm font-bold">App Store</span></span>
        </a>
      </div>
      <div class="mt-10 flex items-center gap-6 text-white/70 text-sm">
        <div><span class="text-white font-bold text-lg block">12 000+</span>indépendants actifs</div>
        <div class="w-px h-8 bg-white/20"></div>
        <div><span class="text-white font-bold text-lg block">CNPS · AMU</span>cotisations conformes</div>
      </div>
    </div>
    <div class="relative flex justify-center reveal in" style="transition-delay:.15s">
      <div class="phone-frame w-[260px] md:w-[290px] overflow-hidden bg-black">
        <img src="{{ asset('assets/images/app-1.jpeg') }}" alt="Écran d'accueil E-BEB Finance affichant l'historique des transactions" class="w-full block">
      </div>
      <div class="absolute -left-6 md:-left-10 bottom-10 phone-frame w-[150px] md:w-[170px] overflow-hidden hidden sm:block rotate-[-6deg] shadow-2xl">
        <img src="{{ asset('assets/images/app-4.jpeg') }}" alt="Objectifs d'épargne dans l'application" class="w-full block">
      </div>
    </div>
  </div>
</section>
