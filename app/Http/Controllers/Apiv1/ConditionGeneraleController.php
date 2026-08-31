<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Resources\ConditionGeneraleResource;
use App\Models\ConditionGenerale;
use Illuminate\Http\Request;

class ConditionGeneraleController extends BaseController
{
    /**
     * Condition générale active à afficher dans l'application mobile.
     */
    public function active(Request $request)
    {
        $conditionGenerale = ConditionGenerale::where('est_active', true)->first();

        return $this->sendResponse(
            $conditionGenerale ? new ConditionGeneraleResource($conditionGenerale) : null,
            $conditionGenerale
                ? 'Condition générale récupérée avec succès.'
                : "Aucune condition générale n'est actuellement publiée."
        );
    }
}
