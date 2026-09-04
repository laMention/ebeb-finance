<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\EnvoyerTransfertQrRequest;
use App\Http\Requests\IdentifierDestinataireQrRequest;
use App\Models\CompteMobileMoney;
use App\Services\TransfertQrService;
use Illuminate\Validation\ValidationException;

class TransfertQrController extends BaseController
{
    public function __construct(private TransfertQrService $transfertQrService)
    {
    }

    /**
     * Après scan : décode le QR, vérifie l'existence/l'activation/la
     * compatibilité d'opérateur, et retourne les informations du compte
     * destinataire pour l'écran de confirmation — sans rien enregistrer.
     */
    public function identifier(IdentifierDestinataireQrRequest $request)
    {
        try {
            $validated = $request->validated();

            $resultat = $this->transfertQrService->identifierDestinataire(
                $request->user(),
                $validated['compte_source_id'],
                $validated['qr_scanne'],
            );

            return $this->sendResponse([
                'compte_destinataire' => $this->formaterDestinataire($resultat['compte_destinataire']),
            ], 'Compte destinataire identifié avec succès.');

        } catch (ValidationException $e) {
            return $this->sendError(collect($e->errors())->flatten()->first(), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Réponse volontairement dédiée (plutôt que la ressource partagée des
     * comptes de l'utilisateur connecté) : ici le compte appartient à un
     * tiers — on n'y expose que ce qui sert à confirmer le paiement, avec le
     * numéro partiellement masqué.
     */
    private function formaterDestinataire(CompteMobileMoney $compte): array
    {
        $numero = (string) $compte->numero_compte;
        $numeroMasque = strlen($numero) > 4
            ? str_repeat('•', max(0, strlen($numero) - 4)) . substr($numero, -4)
            : $numero;

        return [
            'uuid'           => $compte->id,
            'operateur'      => $compte->operateur,
            'numero_masque'  => $numeroMasque,
            'titulaire'      => trim($compte->user->prenom . ' ' . $compte->user->nom),
        ];
    }

    /**
     * Traite le paiement scanné : `TransfertQrService::envoyer()` applique
     * `PaiementService::traiterPaiement()` (répartition, prélèvements,
     * commissions — même logique qu'un paiement confirmé par un opérateur)
     * et journalise le résultat dans `transferts_qr`.
     */
    public function envoyer(EnvoyerTransfertQrRequest $request)
    {
        try {
            $validated = $request->validated();

            $transfert = $this->transfertQrService->envoyer(
                $request->user(),
                $validated['compte_source_id'],
                $validated['qr_scanne'],
                (float) $validated['montant'],
            );

            return $this->sendResponse([
                'reference' => $transfert->reference,
                'statut'    => $transfert->statut,
                'montant'   => $transfert->montant,
            ], 'Paiement envoyé avec succès.');

        } catch (ValidationException $e) {
            return $this->sendError(collect($e->errors())->flatten()->first(), $e->errors(), 422);
        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
