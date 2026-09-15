<?php

namespace App\Http\Controllers\Apiv1;

use App\Http\Controllers\BaseController;
use App\Http\Requests\StoreDeviceTokenRequest;
use App\Services\DeviceTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceTokenController extends BaseController
{
    protected DeviceTokenService $deviceTokenService;

    public function __construct(DeviceTokenService $deviceTokenService)
    {
        $this->deviceTokenService = $deviceTokenService;
    }

    public function store(StoreDeviceTokenRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $resultat = $this->deviceTokenService->enregistrer(
                $user,
                $request->validated('token'),
                $request->validated('plateforme')
            );

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse($resultat['data'], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }

    public function destroy(Request $request): JsonResponse
    {
        try {
            $request->validate(['token' => ['required', 'string']]);

            $user = auth()->user();
            $resultat = $this->deviceTokenService->supprimer($user, $request->input('token'));

            if ($resultat['success'] === false) {
                return $this->sendError($resultat['message'], [], 422);
            }

            return $this->sendResponse([], $resultat['message']);

        } catch (\Exception $e) {
            return $this->throw($e);
        }
    }
}
