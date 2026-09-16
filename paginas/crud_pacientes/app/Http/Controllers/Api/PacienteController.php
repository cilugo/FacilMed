<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePacienteRequest;
use App\Http\Requests\UpdatePacienteRequest;
use App\Http\Resources\PacienteResource;
use App\Models\Paciente;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PacienteController extends Controller
{
    /**
     * Lista os pacientes cadastrados.
     * Suporta pesquisa por nome (?nome=), que busca na conta de usuário vinculada.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Paciente::with('user');

        if ($request->filled('nome')) {
            $nome = $request->input('nome');
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$nome}%"));
        }

        $pacientes = $query->orderBy('id')->paginate(15);

        return response()->json(PacienteResource::collection($pacientes)->response()->getData(true));
    }

    /**
     * Cadastra um novo paciente, criando a conta de usuário (users)
     * e o perfil de paciente (pacientes) numa única transação.
     */
    public function store(StorePacienteRequest $request): JsonResponse
    {
        $dadosValidados = $request->validated();

        $paciente = DB::transaction(function () use ($dadosValidados) {
            $user = User::create([
                'name' => $dadosValidados['name'],
                'email' => $dadosValidados['email'],
                'cpf' => $dadosValidados['cpf'],
                'telefone' => $dadosValidados['telefone'] ?? null,
                'data_nascimento' => $dadosValidados['data_nascimento'],
                'password' => bcrypt($dadosValidados['password']),
                'tipo_usuario' => 'paciente',
            ]);

            return Paciente::create([
                'user_id' => $user->id,
                'numero_carteirinha' => $dadosValidados['numero_carteirinha'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Paciente cadastrado com sucesso.',
            'data' => new PacienteResource($paciente->load('user')),
        ], 201);
    }

    /**
     * Exibe os dados de um paciente específico.
     */
    public function show(Paciente $paciente): JsonResponse
    {
        return response()->json([
            'data' => new PacienteResource($paciente->load('user')),
        ]);
    }

    /**
     * Atualiza os dados do paciente e/ou da conta de usuário vinculada.
     */
    public function update(UpdatePacienteRequest $request, Paciente $paciente): JsonResponse
    {
        $dadosValidados = $request->validated();

        DB::transaction(function () use ($dadosValidados, $paciente) {
            $dadosUser = array_intersect_key($dadosValidados, array_flip(['name', 'email', 'telefone', 'password']));

            if (isset($dadosUser['password'])) {
                $dadosUser['password'] = bcrypt($dadosUser['password']);
            }

            if (! empty($dadosUser)) {
                $paciente->user->update($dadosUser);
            }

            if (array_key_exists('numero_carteirinha', $dadosValidados)) {
                $paciente->update(['numero_carteirinha' => $dadosValidados['numero_carteirinha']]);
            }
        });

        return response()->json([
            'message' => 'Paciente atualizado com sucesso.',
            'data' => new PacienteResource($paciente->fresh('user')),
        ]);
    }

    /**
     * Inativa (soft delete) o paciente e a conta de usuário vinculada,
     * impedindo novos agendamentos e preservando o histórico de consultas.
     */
    public function destroy(Paciente $paciente): JsonResponse
    {
        DB::transaction(function () use ($paciente) {
            $paciente->user->update(['ativo' => false]);
            $paciente->user->delete();
            $paciente->delete();
        });

        return response()->json([
            'message' => 'Paciente inativado com sucesso.',
        ]);
    }
}
