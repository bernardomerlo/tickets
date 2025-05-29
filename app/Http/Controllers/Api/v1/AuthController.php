<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Info(title="TicketAPI", version="0.1")
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/register",
     *     summary="Registra um novo usuário",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"full_name", "username", "email", "birth_date", "password", "password_confirmation"},
     *             @OA\Property(property="full_name", type="string", example="João Silva"),
     *             @OA\Property(property="username", type="string", example="joaosilva"),
     *             @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
     *             @OA\Property(property="birth_date", type="string", format="date", example="1990-01-01"),
     *             @OA\Property(property="password", type="string", format="password", example="senhaSegura123"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="senhaSegura123"),
     *             @OA\Property(property="department", type="string", enum={"ti", "marketing", "financeiro"}, example="ti")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Usuário registrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="1|abc123...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Dados inválidos na requisição"
     *     )
     * )
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'birth_date' => $request->birth_date,
            'password' => Hash::make($request->password),
            'department' => $request->department
        ]);

        $user->assignRole("cliente");

        $token = $user->createToken('api_token')->plainTextToken;

        return response()->json([
            'user' => $user->makeHidden(['password']),
            'token' => $token,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/login",
     *     summary="Autentica um usuário",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="joao@email.com"),
     *             @OA\Property(property="password", type="string", format="password", example="senhaSegura123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="user", ref="#/components/schemas/User"),
     *             @OA\Property(property="token", type="string", example="1|abc123...")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Credenciais inválidas"
     *     )
     * )
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Credenciais inválidas.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user->makeHidden(['password']),
            'token' => $token,
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/logout",
     *     summary="Revoga o token atual do usuário autenticado",
     *     tags={"Auth"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Logout realizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Logout realizado com sucesso.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token inválido ou ausente"
     *     )
     * )
     */
    public function logout(): JsonResponse
    {
        if (!$token = auth()->user()?->currentAccessToken()) {
            return response()->json(['message' => 'Token inválido ou já revogado.'], 401);
        }
        $token->delete();

        return response()->json([
            'message' => 'Logout realizado com sucesso.'
        ], 200);
    }
}
