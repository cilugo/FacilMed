<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Lista os usuários cadastrados.
     * Suporta pesquisa por nome (?nome=) e filtro por tipo (?tipo=) e status (?ativo=).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->filled('nome')) {
            $query->where('name', 'like', '%' . $request->input('nome') . '%');
        }

        if ($request->filled('tipo')) {
            $query->where('tipo_usuario', $request->input('tipo'));
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $usuarios = $query->orderBy('name')->paginate(15);

        return response()->json(UserResource::collection($usuarios)->response()->getData(true));
    }

    /**
     * Cadastra um novo usuário.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $dadosValidados = $request->validated();
        $dadosValidados['password'] = bcrypt($dadosValidados['password']);

        $usuario = User::create($dadosValidados);

        return response()->json([
            'message' => 'Usuário cadastrado com sucesso.',
            'data' => new UserResource($usuario),
        ], 201);
    }

    /**
     * Exibe os dados de um usuário específico.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Atualiza os dados de um usuário existente.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $dadosValidados = $request->validated();

        if (isset($dadosValidados['password'])) {
            $dadosValidados['password'] = bcrypt($dadosValidados['password']);
        }

        $user->update($dadosValidados);

        return response()->json([
            'message' => 'Usuário atualizado com sucesso.',
            'data' => new UserResource($user),
        ]);
    }

    /**
     * Inativa (soft delete) um usuário, preservando o histórico do sistema.
     * Exclusão física não é usada para não quebrar o histórico de consultas.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->update(['ativo' => false]);
        $user->delete(); // soft delete (a linha continua no banco, apenas marcada como excluída)

        return response()->json([
            'message' => 'Usuário inativado com sucesso.',
        ]);
    }

    /**
     * Reativa um usuário previamente inativado.
     */
    public function reativar(int $id): JsonResponse
    {
        $usuario = User::withTrashed()->findOrFail($id);
        $usuario->restore();
        $usuario->update(['ativo' => true]);

        return response()->json([
            'message' => 'Usuário reativado com sucesso.',
            'data' => new UserResource($usuario),
        ]);
    }
}
