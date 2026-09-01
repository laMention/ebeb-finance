<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contenu = $this->contenuDecode();

        return [
            'uuid'       => $this->id,
            'type'       => $this->type,
            'titre'      => $this->titre ?? ($contenu['titre'] ?? null),
            'message'    => $contenu['message'] ?? null,
            'est_lu'     => (bool) $this->est_lu,
            'lu_le'      => $this->lu_le?->toISOString(),
            'canal'      => $this->canal,
            'envoye_le'  => $this->envoye_le?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }

    /**
     * `contenu` est un JSON `{titre, message, sujet}` — mais certaines
     * notifications l'ont enregistré doublement encodé (le cast `array` du
     * modèle ne décode alors qu'une fois et rend une chaîne, pas un
     * tableau). On décode une seconde fois dans ce cas plutôt que de perdre
     * silencieusement le message.
     */
    private function contenuDecode(): array
    {
        $contenu = $this->contenu;

        if (is_string($contenu)) {
            $decode = json_decode($contenu, true);
            $contenu = is_array($decode) ? $decode : [];
        }

        return is_array($contenu) ? $contenu : [];
    }
}
