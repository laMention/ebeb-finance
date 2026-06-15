<?php

namespace App\Services;

use App\Models\CompteMobileMoney;
use App\Models\MoyenPaiement;
use App\Models\QrcodePaiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CompteMobileMoneyService
{
    public function listerComptesUtilisateur(User $user): array
    {
        try {
            $comptes = CompteMobileMoney::where('user_id', $user->id)
                ->with(['moyen_paiement', 'qrcode_paiement'])
                ->orderBy('created_at', 'desc')
                ->get();

            return [
                'success' => true,
                'message' => 'Comptes Mobile Money récupérés avec succès',
                'data'    => $comptes,
                'total'   => $comptes->count(),
            ];

        } catch (\Exception $e) {
            \Log::error('Erreur lors du listage des comptes Mobile Money', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération: ' . $e->getMessage(),
                'data'    => [],
            ];
        }
    }

    public function creerCompte(User $user, array $data): array
    {
        try {
            $moyen = MoyenPaiement::find($data['moyen_paiement_id']);

            if (!$moyen) {
                return ['success' => false, 'message' => 'Moyen de paiement introuvable.'];
            }

            if (!$moyen->est_actif) {
                return ['success' => false, 'message' => "Ce moyen de paiement n'est pas actif."];
            }

            $dejaExistant = CompteMobileMoney::where('user_id', $user->id)
                ->where('moyen_paiement_id', $moyen->id)
                ->exists();

            if ($dejaExistant) {
                return [
                    'success' => false,
                    'message' => "Vous possédez déjà un compte {$moyen->libelle}.",
                ];
            }

            $numeroCompte = $data['numero_compte'] ?? $user->telephone;

            if (empty($numeroCompte)) {
                return [
                    'success' => false,
                    'message' => 'Aucun numéro de compte fourni et aucun numéro de téléphone enregistré sur votre profil.',
                ];
            }

            DB::beginTransaction();

            $compte = CompteMobileMoney::create([
                'user_id'            => $user->id,
                'moyen_paiement_id'  => $moyen->id,
                'operateur'          => $moyen->operateur,
                'numero_compte'      => $numeroCompte,
                'est_principal'      => (bool) $moyen->par_defaut,
                'est_actif'          => true,
            ]);

            $qrcode = $this->genererQrCode($user, $compte, $moyen);

            DB::commit();

            $compte->load(['moyen_paiement', 'qrcode_paiement']);

            \Log::info('Compte Mobile Money créé', [
                'user_id'    => $user->id,
                'compte_id'  => $compte->id,
                'operateur'  => $compte->operateur,
                'qrcode_ref' => $qrcode->reference,
            ]);

            return [
                'success' => true,
                'message' => 'Compte Mobile Money créé avec succès',
                'data'    => $compte,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Erreur lors de la création du compte Mobile Money', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Erreur lors de la création: ' . $e->getMessage(),
            ];
        }
    }

    private function genererQrCode(User $user, CompteMobileMoney $compte, MoyenPaiement $moyen): QrcodePaiement
    {
        $reference = 'QR-' . strtoupper(Str::random(10));

        $valeur = json_encode([
            'ref'    => $reference,
            'compte' => $compte->id,
            'op'     => $moyen->operateur,
            'num'    => $compte->numero_compte,
        ]);

        return QrcodePaiement::create([
            'user_id'                => $user->id,
            'compte_mobile_money_id' => $compte->id,
            'reference'              => $reference,
            'valeur'                 => $valeur,
            'est_actif'              => true,
        ]);
    }
}
