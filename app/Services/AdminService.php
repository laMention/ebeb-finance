<?php
    namespace App\Services;

    use App\Models\Administrateur;
    use Illuminate\Http\UploadedFile;
    use Illuminate\Support\Facades\Hash;
    use Illuminate\Support\Facades\Storage;

    class AdminService
    {
        // connexion administrateur
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

            // create Sanctum token if available
            $token = null;
            if (method_exists($admin, 'createToken')) {
                $token = $admin->createToken('admin-token')->plainTextToken;
                $admin->setRememberToken($token);
            }

            // Charger les rôles et calculer les permissions pour le frontend
            $admin->load(['roles']);
            $hasAllPermissions = $admin->isSuperAdmin();
            $permissions       = $hasAllPermissions
                ? []  // frontend détectera has_all_permissions=true et accordera tout
                : $admin->getAllPermissions()->pluck('name')->values()->toArray();

            return [
                'success'             => true,
                'message'             => 'Connexion réussie.',
                'admin'               => $admin,
                'token'               => 'Bearer ' . $token,
                'permissions'         => $permissions,
                'has_all_permissions' => $hasAllPermissions,
            ];
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