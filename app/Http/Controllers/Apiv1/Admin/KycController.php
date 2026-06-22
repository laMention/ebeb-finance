<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\BaseController;
use App\Models\DocumentKYC;
use App\Services\AlerteGenerator;
use App\Services\AuditLogger;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KycController extends BaseController
{
    public function __construct(protected DocumentService $documentService) {}

    /**
     * Liste paginée des dossiers KYC en attente avec recherche multi-champ.
     * GET /administration/panel-admin/kyc
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage   = (int) $request->input('per_page', 20);
            $recherche = $request->input('recherche');

            $query = DocumentKYC::with('user')
                ->where('statut', 'EN_ATTENTE')
                ->orderBy('created_at', 'asc');

            if ($recherche) {
                $query->where(function ($q) use ($recherche) {
                    $q->where('numero_document', 'like', "%{$recherche}%")
                      ->orWhereHas('user', function ($u) use ($recherche) {
                          $u->where('nom',         'like', "%{$recherche}%")
                            ->orWhere('prenom',     'like', "%{$recherche}%")
                            ->orWhere('telephone',  'like', "%{$recherche}%")
                            ->orWhere('profession', 'like', "%{$recherche}%")
                            ->orWhere('reference',  'like', "%{$recherche}%");
                      });
                });
            }

            $paginated = $query->paginate($perPage);

            $compteurs = [
                'en_attente' => DocumentKYC::where('statut', 'EN_ATTENTE')->count(),
                'valide'     => DocumentKYC::where('statut', 'VALIDE')->count(),
                'rejete'     => DocumentKYC::where('statut', 'REJETE')->count(),
            ];
            $compteurs['total'] = array_sum($compteurs);

            $dossiers = $paginated->map(fn(DocumentKYC $doc) => $this->formatDossier($doc));

            return $this->sendResponse([
                'dossiers'  => $dossiers,
                'meta'      => [
                    'current_page' => $paginated->currentPage(),
                    'last_page'    => $paginated->lastPage(),
                    'per_page'     => $paginated->perPage(),
                    'total'        => $paginated->total(),
                    'from'         => $paginated->firstItem(),
                    'to'           => $paginated->lastItem(),
                ],
                'compteurs' => $compteurs,
            ], 'Dossiers KYC récupérés.');

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Approuver un document KYC et activer le compte utilisateur.
     * PATCH /administration/panel-admin/kyc/{documentKYC}/approuver
     */
    public function approuver(DocumentKYC $documentKYC): JsonResponse
    {
        try {
            if ($documentKYC->statut !== 'EN_ATTENTE') {
                return $this->sendError('Ce dossier a déjà été traité.', [], 400);
            }

            $resultat = $this->documentService->validerRejeter($documentKYC, ['action' => 'VALIDER']);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            $documentKYC->refresh()->load('user');
            AuditLogger::log('KYC.APPROVE', request()->user(), 'documents_kyc', $documentKYC->id,
                ['statut' => 'EN_ATTENTE'], ['statut' => 'VALIDE']);

            $user = $documentKYC->user;
            AlerteGenerator::kyc('SUCCES',
                'Dossier KYC validé',
                "Le dossier de {$user?->prenom} {$user?->nom} a été approuvé.",
                "/kyc/{$documentKYC->id}"
            );

            return $this->sendResponse(
                ['dossier' => $this->formatDossier($documentKYC)],
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    /**
     * Rejeter un document KYC avec motif obligatoire.
     * PATCH /administration/panel-admin/kyc/{documentKYC}/rejeter
     */
    public function rejeter(DocumentKYC $documentKYC, Request $request): JsonResponse
    {
        try {
            if ($documentKYC->statut !== 'EN_ATTENTE') {
                return $this->sendError('Ce dossier a déjà été traité.', [], 400);
            }

            $motif = trim($request->input('motif', ''));
            if (empty($motif)) {
                return $this->sendError('Le motif de rejet est obligatoire.', [], 422);
            }

            $resultat = $this->documentService->validerRejeter($documentKYC, [
                'action' => 'REJETER',
                'raison' => $motif,
            ]);

            if (!$resultat['success']) {
                return $this->sendError($resultat['message'], [], 400);
            }

            $documentKYC->refresh()->load('user');
            AuditLogger::log('KYC.REJECT', request()->user(), 'documents_kyc', $documentKYC->id,
                ['statut' => 'EN_ATTENTE'], ['statut' => 'REJETE', 'motif' => $motif]);

            $user = $documentKYC->user;
            AlerteGenerator::kyc('AVERTISSEMENT',
                'Dossier KYC rejeté',
                "Le dossier de {$user?->prenom} {$user?->nom} a été rejeté. Motif : {$motif}",
                "/kyc/{$documentKYC->id}"
            );

            return $this->sendResponse(
                ['dossier' => $this->formatDossier($documentKYC)],
                $resultat['message']
            );

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    private function formatDossier(DocumentKYC $doc): array
    {
        $user = $doc->user;

        return [
            'uuid'                => $doc->id,
            'type_document'       => $doc->type_document,
            'numero_document'     => $doc->numero_document,
            'document_etablie_le' => $doc->document_etablie_le,
            'document_expire_le'  => $doc->document_expire_le,
            'url_recto'           => $doc->url_recto,
            'url_verso'           => $doc->url_verso,
            'url_selfie'          => $doc->url_selfie,
            'statut'              => $doc->statut,
            'motif_rejet'         => $doc->motif_rejet,
            'created_at'          => $doc->created_at?->format('Y-m-d H:i'),
            'user'                => $user ? [
                'uuid'         => $user->id,
                'nom'          => $user->nom,
                'prenom'       => $user->prenom,
                'telephone'    => $user->telephone,
                'profession'   => $user->profession,
                'reference'    => $user->reference,
                'email'        => $user->email,
                'photo_profil' => $user->photo_profil,
            ] : null,
        ];
    }
}
