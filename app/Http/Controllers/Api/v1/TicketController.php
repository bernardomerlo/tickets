<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddMessageRequest;
use App\Http\Requests\CreateTicketRequest;
use App\Http\Requests\AssignTicketRequest;
use App\Http\Requests\CloseTicketRequest;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/tickets",
     *     summary="Cria um novo ticket",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "description", "type"},
     *             @OA\Property(property="title", type="string", example="Erro ao salvar formulário"),
     *             @OA\Property(property="description", type="string", example="Nada acontece ao clicar em salvar"),
     *             @OA\Property(property="type", type="string", example="ti")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Ticket criado com sucesso",
     *         @OA\JsonContent(ref="#/components/schemas/Ticket")
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Usuário não possui permissão para abrir tickets"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function createTicket(CreateTicketRequest $request)
    {
        $user = $request->user();
        if ($user->hasRole('atendente')) {
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
     *     summary="Atribui um ticket a um usuário",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ticket",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"assigned_user_id"},
     *             @OA\Property(property="assigned_user_id", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Usuário atribuído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Responsável atribuído com sucesso."),
     *             @OA\Property(property="ticket", ref="#/components/schemas/Ticket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket não encontrado"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
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

    /**
     * @OA\Get(
     *     path="/api/v1/tickets",
     *     summary="Lista tickets filtrando por status (abertos ou fechados)",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=true,
     *         description="Filtra os tickets por status: 'abertos' ou 'fechados'",
     *         @OA\Schema(type="string", enum={"abertos", "fechados"}, example="abertos")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de tickets retornada com sucesso",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Ticket"))
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Parâmetro de status inválido"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso negado. Role necessária: admin|atendente"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function listTickets(Request $request)
    {
        $user = $request->user();
        $status = $request->query('status');

        if (!in_array($status, ['abertos', 'fechados'])) {
            return response()->json(['message' => 'Parâmetro de status inválido. Use "abertos" ou "fechados".'], 400);
        }

        $query = Ticket::query();

        if ($status === 'abertos') {
            $query->whereNull('closed_at');
        } elseif ($status === 'fechados') {
            $query->whereNotNull('closed_at');
        }

        if ($user->hasRole('admin')) {
            $tickets = $query->get();
        } elseif ($user->hasRole('atendente')) {
            $tickets = $query->where('assigned_user_id', $user->id)->get();
        } else {
            $tickets = $query->where('created_by', $user->id)->get();
        }

        return response()->json($tickets);
    }

    /**
     * @OA\Patch(
     *     path="/api/v1/tickets/{id}/close",
     *     summary="Fecha um ticket",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ticket",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ticket fechado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Ticket fechado com sucesso."),
     *             @OA\Property(property="ticket", ref="#/components/schemas/Ticket")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Ticket já está fechado"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket não encontrado"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acesso negado. Role necessária: admin|atendente"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function closeTicket(CloseTicketRequest $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        abort_if($ticket->closed_at !== null, 400, 'Ticket já está fechado.');

        $ticket->closed_at = now();
        $ticket->save();

        return response()->json([
            'message' => 'Ticket fechado com sucesso.',
            'ticket' => $ticket->load(['author', 'assignee']),
        ]);
    }


    /**
     * @OA\Post(
     *     path="/api/v1/tickets/{id}/addMessage",
     *     summary="Adiciona uma mensagem a um ticket",
     *     tags={"Ticket"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID do ticket",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="message", type="string", example="Poderia anexar o print do erro?")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mensagem adicionada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="user_id", type="integer", example=3),
     *             @OA\Property(property="message", type="string", example="Poderia anexar o print do erro?"),
     *             @OA\Property(property="created_at", type="string", format="date-time", example="2025-05-29T12:34:56Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Ticket não encontrado"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Usuário não autenticado"
     *     )
     * )
     */
    public function addMessage(AddMessageRequest $request, $ticketId)
    {
        $ticket = Ticket::findOrFail($ticketId);

        $message = $ticket->messages()->create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
        ]);

        return response()->json($message->load('author'), 201);
    }
}
