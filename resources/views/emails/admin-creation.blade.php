@extends('emails.layouts.base')

@section('title', 'Vos accès administrateur — ' . $branding['nom_plateforme'])

@section('header-subtitle')
    <p class="header-sub">Panneau d'administration</p>
@endsection

@push('styles')
.greeting {
    font-size: 16px;
    color: #1a3a5c;
    font-weight: 600;
    margin-bottom: 12px;
}
.intro {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
    margin-bottom: 28px;
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
    color: #888888;
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
    color: #888888;
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
    color: #888888;
    text-align: center;
    margin-top: 10px;
    word-break: break-all;
}
@endpush

@section('content')

    <p class="greeting">Bonjour {{ $admin->prenom }} {{ $admin->nom }},</p>
    <p class="intro">
        Un compte administrateur vient d'être créé pour vous sur la plateforme
        <strong>{{ $branding['nom_plateforme'] }}</strong>. Vous trouverez ci-dessous
        toutes les informations nécessaires pour accéder au panneau d'administration.
    </p>

    {{-- Informations du compte --}}
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

    {{-- Mot de passe temporaire --}}
    <div class="section-title">Mot de passe temporaire</div>
    <div class="password-box">
        <div class="label">Votre mot de passe</div>
        <div class="pwd">{{ $plainPassword }}</div>
    </div>

    <div class="warning-box" style="margin-bottom: 28px;">
        &#9888;&nbsp; Pour des raisons de sécurité, veuillez modifier ce mot de passe
        dès votre première connexion. Ne le partagez avec personne.
    </div>

    {{-- Accès au panel --}}
    <div class="section-title">Accès au panneau</div>
    <div class="cta">
        <a href="{{ $panelUrl }}" target="_blank">Accéder au panneau d'administration</a>
    </div>
    <p class="cta-url">{{ $panelUrl }}</p>

@endsection
