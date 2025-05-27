<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketRequest;
use App\Models\Ticket;

class TicketController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tickets",
     *     summary="Cria um novo Ticket",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "type"},
     *             @OA\Property(property="title", type="string", example="titulo de ticket"),
     *             @OA\Property(property="description", type="string", example="descricao de ticket"),
     *             @OA\Property(property="type", type="string", example="ti")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket criado com sucesso!"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas"
     *     )
     * )
     */
    public function createTicket(CreateTicketRequest $request)
    {
        $ticket = Ticket::create([
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'created_by' => $request->user()->id,
            'opened_at' => now(),
            'closed_at' => null,
            'assigned_user_id' => null,
        ]);

        return response()->json($ticket->load(['author', 'assignee']), 201);
    }
}
