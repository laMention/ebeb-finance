<?php
    namespace App\Services;

    use App\Models\Administrateur;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Storage;

    class AdminService
    {
        public function __construct(private AdminTwoFactorService $twoFactorService)
        {
        }

        /**
         * Étape 1 de la connexion : valide les identifiants puis déclenche la
         * 2FA (voir AdminTwoFactorService::demarrer()) — ne crée jamais de
         * jeton Sanctum ici. Un mot de passe correct seul n'accorde donc
         * jamais l'accès : c'est `verifierCode2FA()` qui authentifie
         * réellement, une fois le second facteur validé.
         */
        public function connexion(array $data): array
        {
            $email_telephone = $data['email_telephone'] ?? null;
            $password = $data['password'] ?? null;

            if (empty($email_telephone) || empty($password)) {
                return [
                    'success' => false,
                    'message' => 'Email et mot de passe requis.'
                ];
            }

            $admin = Administrateur::where('email', $email_telephone)->orWhere('telephone', $email_telephone)->first();
            // Verifier si le compte est actif ou deleted
            
            if (! $admin || ! Hash::check($password, $admin->password)) {
                return [
                    'success' => false,
                    'message' => 'Identifiants invalides.'
                ];
            }

            if($admin && $admin->statut_compte !== 'ACTIF'){
                return [
                    'success'=> false,
                    'message'=> 'Votre compte est inactif. Veuillez contact l\'administrateur pour activer votre compte'
                ];
            }

            if($admin && $admin->deleted_ad !== NULL){
                return [
                    'success'=> false,
                    'message'=> 'Votre compte est archivé. Veuillez contact l\'administrateur pour le restaurer'
                ];
            }

            // Mot de passe valide : jamais de jeton ici. La 2FA prend le
            // relais — seul verifierCode2FA() (étape 2) authentifie pour de
            // bon.
            return $this->twoFactorService->demarrer($admin);
        }

        /**
         * Étape 2 : valide le code reçu par email et, seulement à ce
         * moment-là, ouvre réellement la session (jeton Sanctum, rôles,
         * permissions).
         */
        public function verifierCode2FA(string $challengeId, string $code): array
        {
            return $this->twoFactorService->verifier($challengeId, $code);
        }

        /**
         * Renvoie un nouveau code sur un challenge 2FA existant.
         */
        public function renvoyerCode2FA(string $challengeId): array
        {
            return $this->twoFactorService->renvoyer($challengeId);
        }

        // Deconnexion
        public function deconnexion($admin){

            // $admin->currentAccessToken()->get();
            $admin->tokens()->delete();


            return [
                'success' => true,
                'message' => 'Déconnexion réussie.',            
            ]; 
        }

        // Mise à jour des informations personnelles
        public function mettreAjourProfil(Administrateur $admin, array $data): array
        {
            $allowed = ['nom', 'prenom', 'email', 'telephone', 'ville', 'adresse'];
            $updates = array_intersect_key($data, array_flip($allowed));

            if (!empty($updates)) {
                $admin->update($updates);
            }

            return ['success' => true, 'admin' => $admin->fresh()->load(['roles'])];
        }

        // Changement de mot de passe
        public function changerMotDePasse(Administrateur $admin, array $data): array
        {
            if (!Hash::check($data['current_password'], $admin->password)) {
                return ['success' => false, 'message' => 'Mot de passe actuel incorrect.'];
            }

            $admin->update(['password' => $data['password']]);

            return ['success' => true, 'message' => 'Mot de passe modifié avec succès.'];
        }

        // Changement de photo de profil
        public function changerPhoto(Administrateur $admin, UploadedFile $photo): array
        {
            if ($admin->photo_profil && Storage::disk('public')->exists($admin->photo_profil)) {
                Storage::disk('public')->delete($admin->photo_profil);
            }

            $path = $photo->store('admins/photos', 'public');
            $admin->update(['photo_profil' => $path]);

            return ['success' => true, 'admin' => $admin->fresh()->load(['roles'])];
        }

        // Infos profil administrateur
        public function infoProfil(Administrateur $admin): array
        {
            $hasAllPermissions = $admin->isSuperAdmin();

            $admin->load(['roles']);

            $permissions = $hasAllPermissions
                ? []  // super-admin : le front utilise has_all_permissions
                : $admin->getAllPermissions()->pluck('name')->values()->toArray();

            return [
                'success'             => true,
                'admin'               => $admin,
                'permissions'         => $permissions,
                'has_all_permissions' => $hasAllPermissions,
            ];
        }
    }