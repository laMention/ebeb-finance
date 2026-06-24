<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class SecurityAuditRunner
{
    private array $findings = [];

    public function run(): array
    {
        $this->findings = [];

        $this->checkSanctumExpiration();
        $this->checkAppDebug();
        $this->checkAppKey();
        $this->checkCorsConfig();
        $this->checkLogLevel();
        $this->checkOtpExposure();
        $this->checkSvgUploads();
        $this->checkRateLimiting();
        $this->checkKycAutoValidation();
        $this->checkTokenRevocation();
        $this->checkFloatArithmetic();
        $this->checkOtpDebugMethod();
        $this->checkSuperAdminBypassLog();
        $this->checkEmptyCatchBlock();
        $this->checkWebhookAuth();
        $this->checkHttpsEnv();

        return $this->findings;
    }

    private function vuln(
        string $code,
        string $titre,
        string $criticite,
        string $categorie,
        string $description,
        string $impact,
        string $recommandation,
        ?string $fichier = null,
        ?string $ligne = null,
        bool $correctable = false,
        ?string $correction_label = null,
        string $correction_action = ''
    ): void {
        $this->findings[] = [
            'code'              => $code,
            'titre'             => $titre,
            'criticite'         => $criticite,
            'statut'            => 'DETECTE',
            'categorie'         => $categorie,
            'description'       => $description,
            'impact'            => $impact,
            'recommandation'    => $recommandation,
            'fichier'           => $fichier,
            'ligne'             => $ligne,
            'correctable_auto'  => $correctable,
            'correction_label'  => $correction_label,
            'correction_action' => $correction_action,
        ];
    }

    private function fileContains(string $path, string $needle): bool
    {
        return File::exists($path) && str_contains(File::get($path), $needle);
    }

    // ─── Checks ────────────────────────────────────────────────────────────

    private function checkSanctumExpiration(): void
    {
        if (config('sanctum.expiration') === null) {
            $this->vuln(
                'DYN-001',
                'Tokens Sanctum sans expiration',
                'CRITIQUE',
                'Gestion des sessions',
                "config('sanctum.expiration') est null — les tokens API n'expirent jamais. Toute session compromise est permanente.",
                "Vol de session permanent. Un token volé (fuite réseau, XSS, compromission de base de données) donne un accès sans limite de durée.",
                "Définir SANCTUM_TOKEN_EXPIRATION=10080 dans .env (7 jours). Adapter selon votre politique de sécurité.",
                'config/sanctum.php', '53',
                true,
                "Définir SANCTUM_TOKEN_EXPIRATION=10080 dans .env (7 jours)",
                'set_env:SANCTUM_TOKEN_EXPIRATION=10080'
            );
        }
    }

    private function checkAppDebug(): void
    {
        if (config('app.debug') === true) {
            $this->vuln(
                'DYN-002',
                'APP_DEBUG activé — exposition des traces d\'erreur',
                'CRITIQUE',
                'Configuration serveur',
                "APP_DEBUG=true est actif. Laravel expose des traces d'exception complètes incluant les chemins de fichiers, requêtes SQL, variables d'environnement et structure du code source.",
                "Exposition des secrets d'infrastructure dans les réponses d'erreur HTTP. Un attaquant peut cartographier l'architecture et extraire des données sensibles.",
                "Définir APP_DEBUG=false dans .env. Ne jamais activer le debug en production.",
                '.env', 'APP_DEBUG',
                true,
                "Définir APP_DEBUG=false dans .env",
                'set_env:APP_DEBUG=false'
            );
        }
    }

    private function checkAppKey(): void
    {
        $key = config('app.key', '');
        if (empty($key) || $key === 'base64:') {
            $this->vuln(
                'DYN-003',
                'Clé d\'application (APP_KEY) non configurée ou vide',
                'CRITIQUE',
                'Cryptographie',
                "APP_KEY est vide ou non définie. Cette clé est utilisée pour chiffrer tous les cookies, sessions et données sensibles via Laravel's encryption.",
                "Toutes les données chiffrées peuvent être compromises. Falsification de cookies de session possibles. Risque de déchiffrement des tokens.",
                "Exécuter 'php artisan key:generate' et s'assurer que APP_KEY est définie dans .env.",
                '.env', 'APP_KEY',
                false
            );
        }
    }

    private function checkCorsConfig(): void
    {
        $corsFile = config_path('cors.php');
        if (!File::exists($corsFile)) {
            $this->vuln(
                'DYN-004',
                'Aucune configuration CORS explicite',
                'ELEVE',
                'Configuration serveur',
                "Le fichier config/cors.php est absent. L'API utilise les valeurs par défaut de Laravel, potentiellement trop permissives.",
                "Attaques Cross-Origin : un site malveillant peut effectuer des requêtes API authentifiées au nom d'un utilisateur connecté si les cookies sont envoyés automatiquement.",
                "Créer config/cors.php avec allowed_origins limités aux domaines frontend de production via CORS_ALLOWED_ORIGINS dans .env.",
                'config/cors.php', 'N/A (fichier absent)',
                true,
                "Créer config/cors.php avec origines restreintes",
                'create_cors_config'
            );
        }
    }

    private function checkLogLevel(): void
    {
        $level = strtolower((string) env('LOG_LEVEL', 'debug'));
        if ($level === 'debug') {
            $this->vuln(
                'DYN-005',
                'LOG_LEVEL=debug — exposition de données sensibles dans les logs',
                'MOYEN',
                'Journalisation',
                "LOG_LEVEL est 'debug'. En mode debug, Laravel journalise les requêtes SQL complètes (avec paramètres), les tokens, les réponses HTTP et les données utilisateurs.",
                "Si les fichiers de log sont accessibles (mauvaises permissions, fuite via endpoint de téléchargement), un attaquant peut extraire des données sensibles et des tokens actifs.",
                "Définir LOG_LEVEL=warning dans .env de production. Les niveaux warning, error et critical suffisent pour le monitoring.",
                'config/logging.php, .env', 'LOG_LEVEL',
                true,
                "Définir LOG_LEVEL=warning dans .env",
                'set_env:LOG_LEVEL=warning'
            );
        }
    }

    private function checkOtpExposure(): void
    {
        $file = app_path('Http/Controllers/Apiv1/AuthController.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        // Chercher si code_otp ou 'otp' => $otp est dans sendResponse
        $hasOtpInResponse = str_contains($content, "'code_otp' => \$code")
            || (str_contains($content, "'otp' => \$otp") && str_contains($content, 'sendResponse'));
        if ($hasOtpInResponse) {
            $this->vuln(
                'DYN-006',
                'Code OTP retourné en clair dans les réponses API',
                'CRITIQUE',
                'Authentification',
                "AuthController inclut le code OTP (ou sa valeur brute) dans le corps de la réponse JSON de sendResponse(). Le code OTP est accessible à tout client API sans accès à l'email/SMS.",
                "Contournement total de l'authentification à deux facteurs. Tout appelant API récupère l'OTP directement sans avoir accès au canal de réception (email/SMS).",
                "Retirer toute référence au code OTP des réponses API. OtpService::generateAndSend() ne doit retourner que {success, message}.",
                'app/Http/Controllers/Apiv1/AuthController.php', '~53, ~154'
            );
        }
    }

    private function checkSvgUploads(): void
    {
        $requests = [
            app_path('Http/Requests/SaveParametreGeneralRequest.php'),
            app_path('Http/Requests/AjoutDocumentKYCRequest.php'),
            app_path('Http/Requests/ModifierDocumentKYCRequest.php'),
        ];
        $vulnFiles = [];
        foreach ($requests as $path) {
            if (File::exists($path) && preg_match('/mimes:[^\'"\n,\]]*svg/i', File::get($path))) {
                $vulnFiles[] = basename($path);
            }
        }
        if ($vulnFiles) {
            $this->vuln(
                'DYN-007',
                'SVG autorisé dans les uploads (vecteur XSS)',
                'ELEVE',
                'Upload de fichiers / XSS',
                "Les fichiers SVG sont acceptés dans : " . implode(', ', $vulnFiles) . ". Un SVG peut contenir du JavaScript exécutable via <script> ou attributs d'événements onclick/onload.",
                "Upload d'un SVG malveillant → attaque XSS stockée touchant admins et utilisateurs qui voient le logo ou les documents KYC.",
                "Retirer 'svg' de tous les mimes autorisés. Pour les logos : png,jpg,jpeg,webp uniquement. Pour KYC : jpeg,png,jpg uniquement.",
                implode(', ', $vulnFiles), 'règle mimes'
            );
        }
    }

    private function checkRateLimiting(): void
    {
        $routesFile = base_path('routes/api.php');
        if (!File::exists($routesFile)) {
            return;
        }
        $content = File::get($routesFile);
        // Vérifier que le groupe auth a bien throttle
        $authHasThrottle = preg_match("/prefix\('auth'\)\s*->\s*middleware\s*\(\s*['\"]throttle/", $content)
            || preg_match("/auth.*middleware.*throttle/s", substr($content, 0, 2000));
        if (!$authHasThrottle) {
            $this->vuln(
                'DYN-008',
                'Absence de rate limiting sur les routes d\'authentification',
                'ELEVE',
                'Protection contre les attaques',
                "Les routes /api/auth/* ne semblent pas avoir de middleware throttle. Aucune limitation du nombre de requêtes par IP.",
                "Force brute sur les codes PIN (4-6 chiffres), sur les OTP (6 chiffres = 1 million de combinaisons), création massive de comptes fictifs, déni de service applicatif.",
                "Appliquer throttle:10,1 sur les routes auth utilisateur, throttle:5,1 sur les routes admin, throttle:3,1 sur le renvoi d'OTP.",
                'routes/api.php', 'Groupe auth'
            );
        }
    }

    private function checkKycAutoValidation(): void
    {
        $file = app_path('Services/DocumentService.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        if (str_contains($content, "'statut' => 'VALIDE'") && str_contains($content, 'tousChampsRemplis')) {
            $this->vuln(
                'DYN-009',
                'Validation automatique KYC sans contrôle administrateur',
                'CRITIQUE',
                'Contrôle d\'accès / Logique métier',
                "DocumentService contient une logique qui passe automatiquement le statut du document KYC à 'VALIDE' et active le compte utilisateur dès que tous les champs requis sont remplis, sans intervention admin.",
                "Tout utilisateur peut soumettre des documents KYC falsifiés et être automatiquement activé. Fraude documentaire facilitée, accès aux services financiers sans vérification.",
                "Supprimer la logique d'auto-validation. Les documents soumis doivent rester EN_ATTENTE jusqu'à validation explicite par un administrateur via modifierStatutDocument().",
                'app/Services/DocumentService.php', '333-352 (modifierDocument), 480-498 (ajouterDocument)'
            );
        }
    }

    private function checkTokenRevocation(): void
    {
        $file = app_path('Services/AdminGestionService.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        if (!str_contains($content, "tokens()->delete()")) {
            $this->vuln(
                'DYN-010',
                'Tokens admin non révoqués lors de la désactivation de compte',
                'ELEVE',
                'Gestion des sessions',
                "AdminGestionService::changerStatut() met à jour statut_compte = INACTIF mais ne révoque pas les tokens Sanctum actifs de l'administrateur désactivé.",
                "Un administrateur révoqué (départ, faute professionnelle) peut continuer à accéder au panel d'administration pendant toute la durée de validité résiduelle de son token.",
                "Appeler \$admin->tokens()->delete() immédiatement dans changerStatut() lors du passage à INACTIF. Idem lors de l'archivage.",
                'app/Services/AdminGestionService.php', 'changerStatut()'
            );
        }
    }

    private function checkFloatArithmetic(): void
    {
        $models = ['Cotisation.php', 'Operation.php', 'PortefeuilleEpargne.php', 'Reversement.php'];
        $vulnModels = array_filter($models, fn($m) =>
            $this->fileContains(app_path("Models/{$m}"), "'float'")
            || $this->fileContains(app_path("Models/{$m}"), '"float"')
        );
        if ($vulnModels) {
            $this->vuln(
                'DYN-011',
                'Calculs financiers en virgule flottante (float)',
                'CRITIQUE',
                'Précision financière',
                "Les modèles " . implode(', ', $vulnModels) . " castent les montants en 'float' PHP. Les flottants ont des erreurs de précision inhérentes (0.1 + 0.2 ≠ 0.3 en IEEE 754).",
                "Erreurs d'arrondi cumulatives sur les cotisations, reversements et commissions. Sur des milliers de transactions, ces écarts peuvent représenter des pertes financières et des problèmes de conformité comptable.",
                "Changer tous les casts 'float' en 'string' dans les modèles. Utiliser BCMath (bcadd, bcmul, bcdiv) pour les calculs. Utiliser des colonnes DECIMAL(15,2) en base de données.",
                implode(', ', array_map(fn($m) => "app/Models/{$m}", $vulnModels)), "casts 'float'"
            );
        }
    }

    private function checkOtpDebugMethod(): void
    {
        if ($this->fileContains(app_path('Services/OtpService.php'), 'function getOtpInfo')) {
            $this->vuln(
                'DYN-012',
                'Méthode de debug OTP publique exposant les codes en clair',
                'MOYEN',
                'Exposition d\'informations',
                "OtpService::getOtpInfo() est une méthode publique qui retourne l'enregistrement SessionOtp complet incluant le code OTP en clair, le nombre de tentatives et la date d'expiration.",
                "Si exposée accidentellement via une route (copier-coller, refactoring), un attaquant connaissant un numéro de téléphone peut récupérer le code OTP actif d'un utilisateur.",
                "Supprimer cette méthode. Si nécessaire pour les tests, la rendre private ou la conditionner à app()->environment('local').",
                'app/Services/OtpService.php', 'getOtpInfo()'
            );
        }
    }

    private function checkSuperAdminBypassLog(): void
    {
        $file = app_path('Http/Middleware/CheckAdminPermission.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        $hasBypass = str_contains($content, 'isSuperAdmin');
        $hasLog    = str_contains($content, 'Log::') || str_contains($content, '\\Log::');
        if ($hasBypass && !$hasLog) {
            $this->vuln(
                'DYN-013',
                'Bypass RBAC super-admin non journalisé',
                'MOYEN',
                'Journalisation / Audit',
                "CheckAdminPermission ignore tous les contrôles de permission pour les super-admins sans aucune trace dans les logs ni dans les logs d'audit.",
                "Impossible d'auditer les accès privilégiés du super-admin. Si un compte super-admin est compromis, aucune alerte ne se déclenche sur les accès anormaux. Non-conformité réglementaire.",
                "Ajouter Log::info() dans le bloc isSuperAdmin() : journaliser admin_id, route, permission bypassed, et IP. Enregistrer également dans la table logs_audit.",
                'app/Http/Middleware/CheckAdminPermission.php', 'isSuperAdmin() bypass'
            );
        }
    }

    private function checkEmptyCatchBlock(): void
    {
        $file = app_path('Http/Controllers/Apiv1/AuthController.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        // Chercher catch avec seulement des commentaires
        if (preg_match('/catch\s*\([^)]+\)\s*\{\s*(?:\/\/[^\n]*\n\s*)+\}/m', $content)) {
            $this->vuln(
                'DYN-014',
                'Bloc catch vide dans connexion() — erreurs silencieuses',
                'ELEVE',
                'Gestion des erreurs',
                "AuthController::connexion() contient un bloc catch vide (commenté uniquement). Les exceptions Throwable sont silencieusement ignorées. Le client reçoit une réponse vide HTTP 200.",
                "Les erreurs de connexion (base de données indisponible, service OTP en panne) sont masquées. L'utilisateur croit que sa requête a fonctionné alors qu'elle a échoué silencieusement.",
                "Remplacer le catch vide par 'return \$this->throw(\$th);' pour retourner une réponse d'erreur appropriée au client.",
                'app/Http/Controllers/Apiv1/AuthController.php', 'connexion() catch(\Throwable)'
            );
        }
    }

    private function checkWebhookAuth(): void
    {
        $file = app_path('Http/Controllers/Apiv1/PaiementEntrantController.php');
        if (!File::exists($file)) {
            return;
        }
        $content = File::get($file);
        // Vérifier si la condition d'authentification peut être nulle (bypass si secret non configuré)
        if (str_contains($content, 'if ($secretAttendu &&') || str_contains($content, 'if($secretAttendu &&')) {
            $this->vuln(
                'DYN-015',
                'Webhook de paiement : authentification conditionnelle (bypass si secret absent)',
                'ELEVE',
                'Sécurité API',
                "PaiementEntrantController::webhook() utilise 'if (\$secretAttendu && ...)'. Si WEBHOOK_SECRET n'est pas configuré dans ParametreGlobal, la condition est fausse et TOUT appel est accepté sans authentification.",
                "Un attaquant peut envoyer de fausses notifications de paiement et créditer des comptes sans paiement réel. Fraude directe sur les cotisations et soldes utilisateurs.",
                "Rejeter systématiquement les webhooks si WEBHOOK_SECRET n'est pas configuré. La condition doit être : if (!hash_equals(\$secretAttendu ?? '', \$headerSecret)) { abort(401); }",
                'app/Http/Controllers/Apiv1/PaiementEntrantController.php', 'webhook()'
            );
        }
    }

    private function checkHttpsEnv(): void
    {
        $appUrl = config('app.url', '');
        if (str_starts_with((string) $appUrl, 'http://')) {
            $this->vuln(
                'DYN-016',
                'APP_URL configuré en HTTP (sans TLS)',
                'ELEVE',
                'Transport sécurisé',
                "APP_URL est '{$appUrl}' — utilise HTTP sans TLS. Toutes les communications entre le client et le serveur sont en clair.",
                "Interception des tokens Sanctum, des données financières et des codes PIN par un attaquant sur le réseau (Man-in-the-Middle). Particulièrement critique en Afrique où les connexions mobiles passent par des proxies.",
                "Configurer un certificat TLS (Let's Encrypt gratuit) et définir APP_URL=https://... dans .env. Forcer HTTPS via Nginx/Apache.",
                '.env', 'APP_URL'
            );
        }
    }
}
