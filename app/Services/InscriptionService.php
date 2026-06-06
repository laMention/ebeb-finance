<?php
namespace App\Services;

use App\Models\User;
use App\Models\InformationProfessionnelle;
use App\Models\DeclarationRevenu;
use App\Models\DocumentKYC;
use Hash;
use Illuminate\Support\Facades\DB;

class InscriptionService
{
    /**
     * Gère l'inscription complète d'un utilisateur et de ses données liées.
     */
    public function inscrire(array $data, array $fichiersKyc): User
    {
        // Utilisation d'une transaction pour garantir l'intégrité des données
        return DB::transaction(function () use ($data, $fichiersKyc) {
            
            // Création de l'utilisateur
            $user = User::create([
                'nom' => mettre_en_majuscule($data['nom']),
                'prenom' => mettre_en_majuscule($data['prenom']),
                'numero_cnps' => $data['numero_cnps'],
                'numero_cmu' => $data['numero_cmu'],
                'situation_familiale' => mettre_en_majuscule($data['situation_familiale']),
                'sexe' => mettre_en_majuscule($data['sexe']),
                'date_naissance' => $data['date_naissance'],
                'email' => $data['email'],
                'lieu_naissance' => mettre_en_majuscule($data['lieu_naissance']),
                'profession' => mettre_en_majuscule($data['profession']),
                'telephone' => ajout_prefix_telephone($data['telephone']),
                'ville' => $data['ville'],
                'quartier' => $data['quartier'],
                'village' => $data['village'],
                'adresse_postale' => $data['adresse_postale'],
                'pays' => mettre_en_majuscule("côte d'ivoire"),
            ]);

            // Enregistrer les informations professionnelles
            InformationProfessionnelle::create([
                'user_id' => $user->id,
                'categorie_professionnelle' => mettre_en_majuscule($data['categorie_professionnelle']),
                'metier' => mettre_en_majuscule($data['metier']),
                'date_debut_activite' => $data['date_debut_activite'],
                'ville_activite' => mettre_en_majuscule($data['ville_activite']),
                'quartier_activite' => mettre_en_majuscule($data['quartier_activite']),
                'commune_sous_prefecture_activite' => mettre_en_majuscule($data['commune_sous_prefecture_activite']),
            ]);

            // Enregistrer la déclaration de revenu
            DeclarationRevenu::create([
                'user_id' => $user->id,
                'montant_revenu' => $data['montant_revenu'],
                'montant_cotisation_regime_base' => $data['montant_cotisation_regime_base'],
                'montant_cotisation_regime_complementaire' => $data['montant_cotisation_regime_complementaire'],
                'montant_cotisation_mensuelle' => $data['montant_cotisation_mensuelle'],
                'montant_cotisation_trimestrielle' => $data['montant_cotisation_trimestrielle'],
            ]);

            // Enregistrer les documents KYC
            
            DocumentKYC::create([
                'user_id' => $user->id,
                'url_recto' => $fichiersKyc['recto'],
                'url_verso' => $fichiersKyc['verso'],
                'url_selfie' => $fichiersKyc['selfie'],
            ]);

            return $user;
        });
    }

    /**
     * Définir le mot de passe (code PIN) d'un utilisateur
     * @param array $data 
     */
    public function definirCodePin(array $data):User
    {
        return User::where('telephone', $data['telephone'])->update(
            [
                'password' => Hash::make($data['password']),
            ]
        );
    }

    /**
     * Connecter un utilisateur sur son espace
     */

    public function connecterUtilisateur(array $data): array
    {
        // Trouver l'utilisateur par son téléphone
        $user = User::where('telephone', $data['telephone'])->first();

        // Vérifier si l'utilisateur existe et si le code PIN (password) match
        // if (!$user || !Hash::check($data['password'], $user->password)) {
        //     // throw new Exception("Numéro de téléphone ou code PIN incorrect.");
        //     return [
        //         'success' => false,
        //         'message' => 'Numéro de téléphone ou code PIN incorrect'
        //     ];
        // }

        // Générer le jeton d'accès Sanctum
        $token = $user->createToken('ebebfinance_token')->plainTextToken;

        // Retourner l'utilisateur et son token
        return [
            'user' => $user,
            'token' => $token
        ];
    }
}


    