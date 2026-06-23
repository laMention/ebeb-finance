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

            return [
                'success' => true,
                'message' => 'Connexion réussie.',
                'admin' => $admin,
                'token' => 'Bearer '.$token,
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
        public function infoProfil(Administrateur $admin){
            if ($admin->isSuperAdmin()) {
                // Super-admin a toutes les permissions, pas besoin de les lister
                $data = $admin->load(['roles']);
                
                //Ajouter une indication pour le front
                $data->has_all_permissions = true;
            } else {
                // Charger les rôles et leurs permissions si ce n'est pas le super-admin
                $data = $admin->load(['roles', 'roles.permissions']);
                $data->has_all_permissions = false;
            }
            

            return [
                'success'=> true,
                'admin' => $data
            ];
        }
    }