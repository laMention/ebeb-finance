<?php
    namespace App\Services;

    use App\Models\Administrateur;
    use Illuminate\Support\Facades\Hash;

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

        // Infos profil administrateur
        public function infoProfil($admin){
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