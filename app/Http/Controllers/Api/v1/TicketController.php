<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\Request;

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
        $user = $request->user();
        if(!$user->hasRole('cliente')){
            return response()->json([
                'message' => 'Você não pode abrir tickets'
            ], 403);
        }
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

    /**
     * @OA\Patch(
     *     path="/api/v1/tickets/{id}/assign",
     *     summary="Atribui um ticket para um usuário",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"assigned_user_id"},
     *             @OA\Property(property="assigned_user_id", type="int", example="1"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Responsável atribuído com sucesso."
     *     ),
     *      @OA\Response(
     *          response=404,
     *          description="Ticket não encontrado."
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Credenciais inválidas."
     *      )
     * )
     */
    public function assignTicket(AssignTicketRequest $request, $id)
    {
        $ticket = Ticket::find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket não encontrado.'], 404);
        }

        $ticket->assigned_user_id = $request->assigned_user_id;
        $ticket->save();

        return response()->json([
            'message' => 'Responsável atribuído com sucesso.',
            'ticket' => $ticket->load(['assignee', 'author'])
        ], 200);
    }

    public function listTickets(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole('admin')) {
            $tickets = Ticket::all();
        } elseif ($user->hasRole('atendente')) {
            $tickets = Ticket::where('assigned_user_id', $user->id)->get();
        } else {
            return response()->json(['message' => 'Não autorizado.'], 403);
        }

        return response()->json($tickets);
    }

}
