<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-BEB Finance — Épargnez, cotisez, protégez votre avenir sans y penser</title>
    <meta name="description" content="E-BEB Finance est la plateforme qui répartit automatiquement vos paiements Mobile Money entre épargne, CNPS et AMU. Conçue pour les travailleurs indépendants de Côte d'Ivoire.">
    <meta name="keywords" content="E-BEB Finance, CNPS, AMU, épargne, mobile money, travailleurs indépendants, Côte d'Ivoire, cotisation sociale">
    <meta property="og:title" content="E-BEB Finance — Votre revenu, réparti intelligemment">
    <meta property="og:description" content="Encaissez, cotisez et épargnez automatiquement, à chaque paiement reçu.">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_CI">
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/styles.css') }}">
    <link rel="icon" type="image/png" href="{{ info_public_plateforme()['logo_favicon_url'] ?? asset('assets/images/logo.png') }}"/>

    
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    {{-- @vite(['resources/sass/app.scss', 'resources/js/app.js']) --}}
    @stack('style')
</head>
<body class="antialiased">
    @include('landing-page.partials.header')

    @yield('content')

    @include('landing-page.partials.footer')
    
    
    @include('landing-page.partials.script')

    @stack('script')

    {{-- <div id="app">
        
        <main class="py-4">
        </main>
    </div> --}}
</body>
</html>
