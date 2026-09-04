<?php

namespace App\Services;

use App\Models\CompteMobileMoney;
use App\Models\QrcodePaiement;
use App\Models\TransfertQr;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Paiements entre comptes mobile money ebeb, initiés par scan de QR code.
 *
 * Le scan + la confirmation du montant par l'utilisateur authentifié valent
 * preuve de paiement pour ce canal (pas de webhook opérateur externe ici) :
 * `PaiementService::traiterPaiement()` est donc appelé directement, avec la
 * même logique de répartition/prélèvements/commissions qu'un paiement
 * confirmé par un opérateur. Le compte crédité est celui du QR scanné (le
 * destinataire, qui reçoit le paiement) — jamais celui qui scanne.
 *
 * ⚠️ Aucune intégration opérateur (MTN/Wave/Orange/Moov) réelle pour
 * l'instant : rien ne débite réellement le compte mobile money de
 * l'expéditeur. Le transfert de fonds unitaire réel (avec débit effectif)
 * sera géré par une application Agent distincte.
 */
class TransfertQrService
{
    public function __construct(private PaiementService $paiementService)
    {
    }

    /**
     * Décode le contenu scanné et retrouve le compte mobile money associé,
     * sans persister quoi que ce soit — utilisé pour l'écran de confirmation
     * avant envoi.
     *
     * @throws ValidationException si le QR est invalide, inactif, identique
     *   au compte source, ou d'un opérateur différent du compte source.
     */
    public function identifierDestinataire(User $expediteur, string $compteSourceId, string $qrScanne): array
    {
        $compteSource = CompteMobileMoney::where('id', $compteSourceId)
            ->where('user_id', $expediteur->id)
            ->firstOrFail();

        $qrcode = $this->resoudreQrCode($qrScanne);
        $compteDestinataire = $qrcode->compte_mobile_money;

        $this->verifierCompatibilite($compteSource, $compteDestinataire);

        return [
            'compte_source'       => $compteSource,
            'compte_destinataire' => $compteDestinataire,
            'qrcode'              => $qrcode,
        ];
    }

    /**
     * Applique le paiement via `PaiementService::traiterPaiement()` (même
     * répartition qu'un paiement confirmé par un opérateur) puis journalise
     * le résultat — succès ou échec — dans `transferts_qr`. Re-vérifie tout
     * côté serveur avant traitement : jamais de confiance dans un
     * montant/compte validé uniquement côté mobile.
     *
     * @throws ValidationException si l'identification échoue, ou si
     *   `traiterPaiement()` refuse le paiement (référence dupliquée, service
     *   opérateur désactivé…) — le message renvoyé est alors celui du service.
     */
    public function envoyer(User $expediteur, string $compteSourceId, string $qrScanne, float $montant): TransfertQr
    {
        $identification = $this->identifierDestinataire($expediteur, $compteSourceId, $qrScanne);
        /** @var CompteMobileMoney $compteSource */
        $compteSource = $identification['compte_source'];
        /** @var CompteMobileMoney $compteDestinataire */
        $compteDestinataire = $identification['compte_destinataire'];
        /** @var QrcodePaiement $qrcode */
        $qrcode = $identification['qrcode'];

        $reference = 'QRP-' . now()->format('ymd') . '-' . strtoupper(Str::random(6));

        $resultat = $this->paiementService->traiterPaiement([
            'qr_code_ref'       => $qrcode->reference,
            'montant_brut'      => $montant,
            'reference_externe' => $reference,
            'operateur_source'  => $compteSource->operateur,
            'description'       => "Paiement scanné via QR code — {$expediteur->prenom} {$expediteur->nom}",
        ]);

        $transfert = TransfertQr::create([
            'expediteur_id'                        => $expediteur->id,
            'destinataire_id'                       => $compteDestinataire->user_id,
            'compte_mobile_money_expediteur_id'     => $compteSource->id,
            'compte_mobile_money_destinataire_id'   => $compteDestinataire->id,
            'operateur'                              => $compteSource->operateur,
            'montant'                                => $montant,
            'reference'                              => $reference,
            'statut'                                  => $resultat['success'] ? 'REUSSI' : 'ECHEC',
        ]);

        if (!$resultat['success']) {
            throw ValidationException::withMessages([
                'montant' => $resultat['message'] ?? 'Le paiement n\'a pas pu être traité.',
            ]);
        }

        // Les notifications (paiement reçu, cotisations/épargne prélevées…)
        // sont déjà envoyées par `traiterPaiement()` lui-même — inutile d'en
        // ajouter une ici.
        return $transfert->fresh(['expediteur', 'destinataire', 'compteMobileMoneyExpediteur', 'compteMobileMoneyDestinataire']);
    }

    private function resoudreQrCode(string $qrScanne): QrcodePaiement
    {
        $donnees = json_decode($qrScanne, true);
        $reference = $donnees['ref'] ?? null;

        if (!$reference) {
            throw ValidationException::withMessages([
                'qr_scanne' => 'Ce code ne correspond pas à un QR code de paiement ebeb valide.',
            ]);
        }

        $qrcode = QrcodePaiement::where('reference', $reference)
            ->where('est_actif', true)
            ->with('compte_mobile_money.user')
            ->first();

        if (!$qrcode) {
            throw ValidationException::withMessages([
                'qr_scanne' => 'Ce QR code est invalide, inactif, ou a expiré.',
            ]);
        }

        return $qrcode;
    }

    private function verifierCompatibilite(CompteMobileMoney $compteSource, CompteMobileMoney $compteDestinataire): void
    {
        if ($compteSource->id === $compteDestinataire->id) {
            throw ValidationException::withMessages([
                'qr_scanne' => 'Vous ne pouvez pas scanner votre propre QR code.',
            ]);
        }

        if ($compteSource->operateur !== $compteDestinataire->operateur) {
            throw ValidationException::withMessages([
                'qr_scanne' => "Ce QR code est un compte {$compteDestinataire->operateur}, incompatible avec votre "
                    . "compte {$compteSource->operateur}. Utilisez un compte {$compteDestinataire->operateur} pour "
                    . "effectuer ce paiement.",
            ]);
        }
    }
}
