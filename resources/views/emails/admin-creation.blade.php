<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vos accès administrateur</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            padding: 20px 0;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
            padding: 32px 40px;
            border-radius: 12px 12px 0 0;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .header p {
            color: rgba(255,255,255,0.8);
            font-size: 13px;
            margin-top: 6px;
        }
        .body {
            background: #ffffff;
            padding: 36px 40px;
        }
        .greeting {
            font-size: 16px;
            color: #1a3a5c;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .intro {
            font-size: 14px;
            color: #555;
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 12px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 28px;
        }
        .info-table tr:not(:last-child) td {
            border-bottom: 1px solid #f0f2f5;
        }
        .info-table td {
            padding: 11px 0;
            font-size: 14px;
            vertical-align: top;
        }
        .info-table td:first-child {
            color: #888;
            width: 42%;
            font-weight: 500;
        }
        .info-table td:last-child {
            color: #1a1a2e;
            font-weight: 600;
        }
        .password-box {
            background: #f8f9ff;
            border: 1px dashed #2563eb;
            border-radius: 8px;
            padding: 18px 20px;
            margin-bottom: 28px;
            text-align: center;
        }
        .password-box .label {
            font-size: 11px;
            color: #888;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .password-box .pwd {
            font-family: 'Courier New', monospace;
            font-size: 22px;
            font-weight: 700;
            color: #1a3a5c;
            letter-spacing: 3px;
        }
        .warning-box {
            background: #fff8e1;
            border-left: 4px solid #f59e0b;
            padding: 12px 16px;
            border-radius: 0 6px 6px 0;
            font-size: 13px;
            color: #78350f;
            margin-bottom: 28px;
            line-height: 1.6;
        }
        .cta {
            text-align: center;
            margin-bottom: 32px;
        }
        .cta a {
            display: inline-block;
            background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
            color: #ffffff;
            text-decoration: none;
            padding: 14px 36px;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }
        .cta-url {
            font-size: 12px;
            color: #888;
            text-align: center;
            margin-top: 10px;
            word-break: break-all;
        }
        .divider {
            border: none;
            border-top: 1px solid #f0f2f5;
            margin: 24px 0;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 40px;
            border-radius: 0 0 12px 12px;
            text-align: center;
        }
        .footer p {
            font-size: 12px;
            color: #aaa;
            line-height: 1.7;
        }
    </style>
</head>
<body>
<div class="wrapper">

    <!-- En-tête -->
    <div class="header">
        <h1>{{ config('app.name') }}</h1>
        <p>Panneau d'administration</p>
    </div>

    <!-- Corps -->
    <div class="body">

        <p class="greeting">Bonjour {{ $admin->prenom }} {{ $admin->nom }},</p>
        <p class="intro">
            Un compte administrateur vient d'être créé pour vous sur la plateforme
            <strong>{{ config('app.name') }}</strong>. Vous trouverez ci-dessous
            toutes les informations nécessaires pour accéder au panneau d'administration.
        </p>

        <!-- Informations du compte -->
        <div class="section-title">Informations du compte</div>
        <table class="info-table">
            <tr>
                <td>Nom complet</td>
                <td>{{ $admin->prenom }} {{ $admin->nom }}</td>
            </tr>
            <tr>
                <td>Adresse email</td>
                <td>{{ $admin->email }}</td>
            </tr>
            @if ($admin->telephone)
            <tr>
                <td>Téléphone</td>
                <td>{{ $admin->telephone }}</td>
            </tr>
            @endif
            @if ($admin->roles->isNotEmpty())
            <tr>
                <td>Rôle attribué</td>
                <td>{{ $admin->roles->first()->display_name ?? $admin->roles->first()->name }}</td>
            </tr>
            @endif
            <tr>
                <td>Date de création</td>
                <td>{{ $dateCreation }}</td>
            </tr>
        </table>

        <!-- Mot de passe temporaire -->
        <div class="section-title">Mot de passe temporaire</div>
        <div class="password-box">
            <div class="label">Votre mot de passe</div>
            <div class="pwd">{{ $plainPassword }}</div>
        </div>

        <div class="warning-box">
            &#9888;&nbsp; Pour des raisons de sécurité, veuillez modifier ce mot de passe
            dès votre première connexion. Ne le partagez avec personne.
        </div>

        <!-- Accès au panel -->
        <div class="section-title">Accès au panneau</div>
        <div class="cta">
            <a href="{{ $panelUrl }}" target="_blank">Accéder au panneau d'administration</a>
        </div>
        <p class="cta-url">{{ $panelUrl }}</p>

    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>Cet email a été envoyé automatiquement — merci de ne pas y répondre.</p>
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
    </div>

</div>
</body>
</html>
