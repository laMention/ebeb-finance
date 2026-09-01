<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFaqRequest;
use App\Http\Requests\UpdateFaqRequest;
use App\Services\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(private FaqService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->lister($request->all()));
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $result = $this->service->creer($request->validated(), $request->user());
        return response()->json($result, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->afficher($id));
    }

    public function update(UpdateFaqRequest $request, string $id): JsonResponse
    {
        $result = $this->service->modifier($id, $request->validated(), $request->user());
        return response()->json($result);
    }

    public function publier(Request $request, string $id): JsonResponse
    {
        return response()->json($this->service->publier($id, $request->user()));
    }

    public function depublier(Request $request, string $id): JsonResponse
    {
        return response()->json($this->service->depublier($id, $request->user()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        return response()->json($this->service->supprimer($id, $request->user()));
    }
}
