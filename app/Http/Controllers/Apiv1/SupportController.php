<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportController extends BaseController
{
    public function __construct(private TicketService $ticketService) {}

    /**
     * Signalement d'un problème par l'utilisateur — crée automatiquement un
     * ticket (module Support du panel d'administration), avec référence
     * unique et historique de suivi.
     */
    public function signaler(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'description' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'description.required' => 'Décrivez le problème rencontré.',
            'description.min'      => 'La description est trop courte pour être exploitable.',
            'description.max'      => 'La description ne peut pas dépasser 2000 caractères.',
        ]);

        if ($validator->fails()) {
            return $this->sendError($validator->errors()->first(), $validator->errors(), 422);
        }

        $ticket = $this->ticketService->creerDepuisMobile(
            $request->user(),
            $validator->validated()['description']
        );

        return $this->sendResponse(
            ['reference' => $ticket->reference],
            "Votre signalement a été transmis au service d'aide (référence {$ticket->reference}). Nous reviendrons vers vous rapidement."
        );
    }
}
