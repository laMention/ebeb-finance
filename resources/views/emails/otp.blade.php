@component('mail::message')
# Vérification de votre identité

Bonjour,

Veuillez utiliser le code ci-dessous pour terminer votre authentification :

@component('mail::panel')
## {{ $code }}
@endcomponent

**Validité du code :** 10 minutes

**Sécurité :** 
- Ne partagez jamais ce code avec quiconque
- Notre équipe ne vous demandera jamais ce code par email ou par téléphone
- Si vous n'avez pas demandé ce code, ignorez simplement ce message

Merci,<br>
{{ config('app.name') }}
@endcomponent
