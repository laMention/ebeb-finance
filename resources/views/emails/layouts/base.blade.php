<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', info_public_plateforme()['nom_plateforme'])</title>
    <style>
        /* ── Reset ── */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333333;
            padding: 20px 0;
            -webkit-text-size-adjust: 100%;
        }

        /* ── Conteneur principal ── */
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }

        /* ── En-tête ── */
        .header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
            padding: 28px 40px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .header-logo {
            max-height: 60px;
            max-width: 200px;
            width: auto;
            object-fit: contain;
            display: block;
            margin: 0 auto 10px;
        }
        .header-title {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header-sub {
            color: rgba(255, 255, 255, 0.80);
            font-size: 13px;
            margin-top: 6px;
        }

        /* ── Corps ── */
        .body {
            background: #ffffff;
            padding: 36px 40px;
        }

        /* ── Séparateur ── */
        .divider {
            border: none;
            border-top: 1px solid #f0f2f5;
            margin: 24px 0;
        }

        /* ── Composants communs ── */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }
        .warning-box {
            background: #fff8e1;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            font-size: 13px;
            color: #78350f;
            line-height: 1.6;
        }
        .info-box {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            font-size: 13px;
            color: #1e40af;
            line-height: 1.6;
        }

        /* ── Pied de page ── */
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #aaaaaa;
            line-height: 1.8;
        }
        .footer-slogan {
            font-size: 13px !important;
            color: #888888 !important;
            font-style: italic;
        }
        .footer-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 12px;
        }
        .footer-separator {
            border: none;
            border-top: 1px solid #eeeeee;
            margin: 12px 0;
        }

        @stack('styles')
    </style>
</head>
<body>
<div class="wrapper">

    {{-- ── En-tête avec logo ou nom de la plateforme ── --}}
    <div class="header">
        @if(isset(info_public_plateforme()['logo_principal_url']))
            <img src="{{ info_public_plateforme()['logo_principal_url'] }}"
                 alt="{{ info_public_plateforme()['nom_plateforme'] }}"
                 class="header-logo">
        @else
            <h1 class="header-title">{{ info_public_plateforme()['nom_plateforme'] ?? "E-BEB FINANCE" }}</h1>
        @endif
        @yield('header-subtitle')
    </div>

    {{-- ── Corps de l'email (propre à chaque template) ── --}}
    <div class="body">
        @yield('content')
    </div>

    {{-- ── Pied de page avec informations de la plateforme ── --}}
    <div class="footer">
        @if(info_public_plateforme()['slogan'])
            <p class="footer-slogan">{{ info_public_plateforme()['slogan'] }}</p>
        @endif
        @if(info_public_plateforme()['site_web'])
            <p>
                <a href="{{ info_public_plateforme()['site_web'] }}" class="footer-link" target="_blank">
                    {{ info_public_plateforme()['site_web'] }}
                </a>
            </p>
        @endif
        @if(info_public_plateforme()['slogan'] || info_public_plateforme()['site_web'])
            <hr class="footer-separator">
        @endif
        <p>Cet email a été envoyé automatiquement — merci de ne pas y répondre.</p>
        <p>{{ info_public_plateforme()['copyright'] }}</p>
    </div>

</div>
</body>
</html>
