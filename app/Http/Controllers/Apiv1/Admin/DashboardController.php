<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'periode'    => ['nullable', 'in:jour,semaine,mois,annee,personnalise'],
            'date_debut' => ['nullable', 'date'],
            'date_fin'   => ['nullable', 'date', 'after_or_equal:date_debut'],
        ]);

        return response()->json(DashboardService::resume($request->all()));
    }
}
