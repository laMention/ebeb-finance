@extends('emails.layouts.base')

@section('title', 'Code de vérification — ' . $branding['nom_plateforme'])

@section('header-subtitle')
    <p class="header-sub">Authentification à deux facteurs</p>
@endsection

@push('styles')
.otp-intro {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
    margin-bottom: 24px;
}
.otp-code-box {
    background: #f8f9ff;
    border: 1px solid #e0e7ff;
    border-radius: 10px;
    padding: 24px 20px;
    text-align: center;
    margin: 24px 0;
}
.otp-code-box .label {
    font-size: 11px;
    color: #888888;
    text-transform: uppercase;
    letter-spacing: 0.8px;
    margin-bottom: 12px;
}
.otp-code {
    font-family: 'Courier New', monospace;
    font-size: 36px;
    font-weight: 700;
    letter-spacing: 10px;
    color: #1a3a5c;
}
.otp-validity {
    font-size: 13px;
    color: #555555;
    text-align: center;
    margin-bottom: 24px;
}
.otp-validity strong {
    color: #2563eb;
}
.security-list {
    font-size: 13px;
    color: #555555;
    line-height: 1.8;
    padding-left: 18px;
    margin-top: 8px;
}
.security-list li {
    margin-bottom: 4px;
}
@endpush

@section('content')

    <p class="otp-intro">
        Bonjour,<br><br>
        Vous avez demandé un code de vérification pour sécuriser votre connexion à
        <strong>{{ $branding['nom_plateforme'] }}</strong>.
        Veuillez utiliser le code ci-dessous pour terminer votre authentification.
    </p>

    <div class="otp-code-box">
        <div class="label">Votre code de vérification</div>
        <div class="otp-code">{{ $code }}</div>
    </div>

    <p class="otp-validity">
        Ce code est valable pendant <strong>10 minutes</strong>.
    </p>

    <div class="section-title">Consignes de sécurité</div>
    <div class="warning-box">
        <ul class="security-list">
            <li>Ne partagez jamais ce code avec quiconque, même un conseiller.</li>
            <li>Notre équipe ne vous demandera jamais ce code par téléphone ou email.</li>
            <li>Si vous n'avez pas demandé ce code, ignorez ce message et sécurisez votre compte.</li>
        </ul>
    </div>

@endsection
