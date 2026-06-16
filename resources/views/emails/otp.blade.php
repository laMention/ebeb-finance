{{-- resources/views/emails/otp.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 30px; }
        .container { background: #fff; padding: 30px; border-radius: 8px; max-width: 500px; margin: auto; }
        .code { font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #2d3748; 
                background: #f7fafc; border: 1px solid #e2e8f0; padding: 16px 24px; 
                border-radius: 6px; text-align: center; margin: 24px 0; }
        .footer { color: #718096; font-size: 13px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Vérification de votre identité</h2>
        <p>Bonjour,</p>
        <p>Veuillez utiliser le code ci-dessous pour terminer votre authentification </p>

        <div class="code">{{ $code }}</div>

        <p><strong>Validité du code :</strong> 10 minutes</p>

        <div class="footer">
            <p><strong>Sécurité :</strong></p>
            <ul>
                <li>Ne partagez jamais ce code avec quiconque</li>
                <li>Notre équipe ne vous demandera jamais ce code par téléphone</li>
                <li>Si vous n'avez pas demandé ce code, ignorez ce message</li>
            </ul>
            <p>Merci,<br>{{ config('app.name') }}</p>
        </div>
    </div>
</body>
</html>