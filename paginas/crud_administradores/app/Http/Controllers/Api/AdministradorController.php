<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdministradorRequest;
use App\Http\Requests\UpdateAdministradorRequest;
use App\Http\Resources\AdministradorResource;
use App\Models\Administrador;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdministradorController extends Controller
{
    /**
     * Lista os administradores cadastrados.
     * Suporta pesquisa por nome (?nome=) e filtro por status (?ativo=).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Administrador::with('user');

        if ($request->filled('nome')) {
            $nome = $request->input('nome');
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$nome}%"));
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $administradores = $query->orderBy('id')->paginate(15);

        return response()->json(AdministradorResource::collection($administradores)->response()->getData(true));
    }

    /**
     * Cadastra um novo administrador, criando a conta de usuário
     * e o perfil de administrador numa única transação.
     */
    public function store(StoreAdministradorRequest $request): JsonResponse
    {
        $dadosValidados = $request->validated();

        $administrador = DB::transaction(function () use ($dadosValidados) {
            $user = User::create([
                'name' => $dadosValidados['name'],
                'email' => $dadosValidados['email'],
                'cpf' => $dadosValidados['cpf'],
                'telefone' => $dadosValidados['telefone'] ?? null,
                'password' => bcrypt($dadosValidados['password']),
                'tipo_usuario' => 'administrador',
            ]);

            return Administrador::create([
                'user_id' => $user->id,
                'cargo' => $dadosValidados['cargo'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Administrador cadastrado com sucesso.',
            'data' => new AdministradorResource($administrador->load('user')),
        ], 201);
    }

    /**
     * Exibe os dados de um administrador específico.
     */
    public function show(Administrador $administrador): JsonResponse
    {
        return response()->json([
            'data' => new AdministradorResource($administrador->load('user')),
        ]);
    }

    /**
     * Atualiza os dados do administrador e/ou da conta de usuário vinculada.
     */
    public function update(UpdateAdministradorRequest $request, Administrador $administrador): JsonResponse
    {
        $dadosValidados = $request->validated();

        DB::transaction(function () use ($dadosValidados, $administrador) {
            $dadosUser = array_intersect_key($dadosValidados, array_flip(['name', 'email', 'telefone', 'password']));

            if (isset($dadosUser['password'])) {
                $dadosUser['password'] = bcrypt($dadosUser['password']);
            }

            if (! empty($dadosUser)) {
                $administrador->user->update($dadosUser);
            }

            $dadosAdministrador = array_intersect_key($dadosValidados, array_flip(['cargo', 'ativo']));

            if (! empty($dadosAdministrador)) {
                $administrador->update($dadosAdministrador);
            }
        });

        return response()->json([
            'message' => 'Administrador atualizado com sucesso.',
            'data' => new AdministradorResource($administrador->fresh('user')),
        ]);
    }

    /**
     * Inativa (soft delete) o administrador e a conta de usuário vinculada.
     */
    public function destroy(Administrador $administrador): JsonResponse
    {
        DB::transaction(function () use ($administrador) {
            $administrador->user->update(['ativo' => false]);
            $administrador->update(['ativo' => false]);
            $administrador->user->delete();
            $administrador->delete();
        });

        return response()->json([
            'message' => 'Administrador inativado com sucesso.',
        ]);
    }
}
