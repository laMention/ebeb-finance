@extends('emails.layouts.base')

@section('title', $titre . ' — ' . $branding['nom_plateforme'])

@section('header-subtitle')
    <p class="header-sub">{{ $titre }}</p>
@endsection

@push('styles')
.notif-intro {
    font-size: 14px;
    color: #555555;
    line-height: 1.7;
    margin-bottom: 20px;
}
.notif-detail {
    background: #eff6ff;
    border-left: 4px solid #2563eb;
    padding: 12px 16px;
    border-radius: 0 6px 6px 0;
    font-size: 13px;
    color: #1e3a5f;
    line-height: 1.8;
    margin: 16px 0;
}
.notif-detail strong {
    color: #1a3a5c;
}
.cta-btn {
    display: inline-block;
    background: linear-gradient(135deg, #1a3a5c 0%, #2563eb 100%);
    color: #ffffff;
    text-decoration: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    margin-top: 16px;
}
@endpush

@section('content')

    @if($type === 'activation_compte')

        <p class="notif-intro">
            Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,
        </p>
        <p class="notif-intro">{{ $corps }}</p>

        @if(isset($contenu['date_activation']))
            <div class="notif-detail">
                <strong>Date d'activation :</strong> {{ $contenu['date_activation'] }}
            </div>
        @endif

        <p class="notif-intro">
            Vous pouvez maintenant vous connecter à votre compte et profiter de tous nos services.
        </p>

    @elseif($type === 'rejet_document')

        <p class="notif-intro">
            Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,
        </p>
        <p class="notif-intro">{{ $corps }}</p>

        @if(isset($contenu['type_document']))
            <div class="notif-detail">
                <strong>Document concerné :</strong> {{ $contenu['type_document'] }}<br>
                <strong>Raison du rejet :</strong> {{ $contenu['raison'] ?? 'Non spécifiée' }}<br>
                <strong>Date :</strong> {{ $contenu['date_rejet'] ?? now()->format('d/m/Y H:i') }}
            </div>
        @endif

        <p class="notif-intro">
            Veuillez soumettre un nouveau document conforme à nos exigences.
        </p>

        <div style="text-align: center;">
            <a href="{{ url('/kyc/documents') }}" class="cta-btn">Soumettre un nouveau document</a>
        </div>

    @else

        <p class="notif-intro">
            Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,
        </p>
        <p class="notif-intro">{{ $corps }}</p>

    @endif

@endsection
