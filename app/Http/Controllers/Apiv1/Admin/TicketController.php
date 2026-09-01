<?php

namespace App\Http\Controllers\Apiv1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    public function __construct(private TicketService $service) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->service->lister($request->all()));
    }

    public function show(string $id): JsonResponse
    {
        return response()->json($this->service->afficher($id));
    }

    public function update(UpdateTicketRequest $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        $result = $this->service->modifier($ticket, $request->validated(), $request->user());

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $ticket = Ticket::findOrFail($id);
        return response()->json($this->service->supprimer($ticket, $request->user()));
    }
}
