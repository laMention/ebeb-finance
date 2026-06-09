<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $titre }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #4CAF50;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
            margin: -20px -20px 20px -20px;
        }
        .content {
            padding: 20px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
        }
        .info {
            background-color: #e7f3ff;
            padding: 10px;
            border-left: 4px solid #2196F3;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $titre }}</h1>
        </div>
        
        <div class="content">
            @if($type === 'activation_compte')
                <p>Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,</p>
                <p>{{ $message }}</p>
                
                @if(isset($contenu['date_activation']))
                    <div class="info">
                        <strong>Date d'activation :</strong> {{ $contenu['date_activation'] }}
                    </div>
                @endif
                
                <p>Vous pouvez maintenant vous connecter à votre compte et profiter de tous nos services.</p>
                
                {{-- <div style="text-align: center;">
                    <a href="{{ url('/login') }}" class="button">Se connecter</a>
                </div> --}}
                
            @elseif($type === 'rejet_document')
                <p>Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,</p>
                <p>{{ $message }}</p>
                
                @if(isset($contenu['type_document']))
                    <div class="info">
                        <strong>Document concerné :</strong> {{ $contenu['type_document'] }}<br>
                        <strong>Raison du rejet :</strong> {{ $contenu['raison'] ?? 'Non spécifiée' }}<br>
                        <strong>Date :</strong> {{ $contenu['date_rejet'] ?? now()->format('d/m/Y H:i') }}
                    </div>
                @endif
                
                <p>Veuillez soumettre un nouveau document conforme à nos exigences.</p>
                
                <div style="text-align: center;">
                    <a href="{{ url('/kyc/documents') }}" class="button">Soumettre un nouveau document</a>
                </div>
                
            @else
                <p>{{ $message }}</p>                
            @endif
        </div>
        
        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>