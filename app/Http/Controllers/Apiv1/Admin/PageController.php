<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePageRequest;
use App\Http\Requests\UpdatePageRequest;
use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $result = PageService::lister($request->all());
        return response()->json($result);
    }

    public function types(): JsonResponse
    {
        return response()->json(PageService::types());
    }

    public function store(StorePageRequest $request): JsonResponse
    {
        $admin = $request->user();
        $result  = PageService::creer($request->validated(), $admin);
        return response()->json($result, 201);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(PageService::afficher($id));
    }

    public function update(UpdatePageRequest $request, string $id): JsonResponse
    {
        $result = PageService::modifier($id, $request->validated(), $request->user());
        return response()->json($result);
    }

    public function publier(Request $request, string $id): JsonResponse
    {
        return response()->json(PageService::publier($id, $request->user()));
    }

    public function depublier(Request $request, string $id): JsonResponse
    {
        return response()->json(PageService::depublier($id, $request->user()));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        return response()->json(PageService::archiver($id, $request->user()));
    }

    public function restaurer(Request $request, string $id): JsonResponse
    {
        return response()->json(PageService::restaurer($id, $request->user()));
    }
}
