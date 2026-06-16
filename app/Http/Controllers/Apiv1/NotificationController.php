<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends BaseController
{
    public function __construct(private NotificationService $notificationService) {}

    /**
     * Liste les notifications de l'utilisateur connecté.
     * Filtre optionnel : ?est_lu=true|false
     */
    public function index(Request $request)
    {
        $query = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at');

        if ($request->has('est_lu')) {
            $query->where('est_lu', filter_var($request->input('est_lu'), FILTER_VALIDATE_BOOLEAN));
        }

        $notifications = $query->paginate(min((int) $request->input('per_page', 20), 50));

        return $this->sendResponse(
            NotificationResource::collection($notifications)->response()->getData(true),
            'Notifications récupérées avec succès.'
        );
    }

    /**
     * Marque une notification précise comme lue.
     */
    public function marquerLue(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return $this->sendError('Notification introuvable.', [], 404);
        }

        $this->notificationService->marquerCommeLue($notification->id);

        return $this->sendResponse([], 'Notification marquée comme lue.');
    }

    /**
     * Marque toutes les notifications de l'utilisateur comme lues.
     */
    public function marquerToutesLues(Request $request)
    {
        $this->notificationService->marquerToutesLues($request->user()->id);

        return $this->sendResponse([], 'Toutes les notifications ont été marquées comme lues.');
    }

    /**
     * Retourne le nombre de notifications non lues (utile pour un badge).
     */
    public function nombreNonLues(Request $request)
    {
        $count = $this->notificationService->compterNonLues($request->user()->id);

        return $this->sendResponse(['count' => $count], 'Nombre de notifications non lues.');
    }
}
