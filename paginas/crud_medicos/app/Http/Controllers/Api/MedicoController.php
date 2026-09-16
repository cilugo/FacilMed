<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMedicoRequest;
use App\Http\Requests\UpdateMedicoRequest;
use App\Http\Resources\MedicoResource;
use App\Models\Medico;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MedicoController extends Controller
{
    /**
     * Lista/pesquisa médicos.
     * Suporta:
     *  - ?nome= (busca na conta de usuário vinculada)
     *  - ?especialidade_id= (filtra por especialidade)
     *  - ?estabelecimento_id= (filtra por local de atendimento)
     *  - ?convenio_id= (filtra por convênio aceito)
     *  - ?ativo= (filtra por status)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Medico::with(['user', 'especialidades', 'estabelecimentos', 'convenios']);

        if ($request->filled('nome')) {
            $nome = $request->input('nome');
            $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$nome}%"));
        }

        if ($request->filled('especialidade_id')) {
            $query->whereHas('especialidades', fn ($q) => $q->where('especialidades.id', $request->input('especialidade_id')));
        }

        if ($request->filled('estabelecimento_id')) {
            $query->whereHas('estabelecimentos', fn ($q) => $q->where('estabelecimentos.id', $request->input('estabelecimento_id')));
        }

        if ($request->filled('convenio_id')) {
            $query->whereHas('convenios', fn ($q) => $q->where('convenios.id', $request->input('convenio_id')));
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $medicos = $query->orderBy('id')->paginate(15);

        return response()->json(MedicoResource::collection($medicos)->response()->getData(true));
    }

    /**
     * Cadastra um novo médico: cria a conta de usuário, o perfil de médico
     * e vincula especialidades, estabelecimentos e convênios, numa única transação.
     */
    public function store(StoreMedicoRequest $request): JsonResponse
    {
        $dadosValidados = $request->validated();

        $medico = DB::transaction(function () use ($dadosValidados) {
            $user = User::create([
                'name' => $dadosValidados['name'],
                'email' => $dadosValidados['email'],
                'cpf' => $dadosValidados['cpf'],
                'telefone' => $dadosValidados['telefone'] ?? null,
                'data_nascimento' => $dadosValidados['data_nascimento'] ?? null,
                'password' => bcrypt($dadosValidados['password']),
                'tipo_usuario' => 'medico',
            ]);

            $medico = Medico::create([
                'user_id' => $user->id,
                'crm' => $dadosValidados['crm'],
                'uf_crm' => $dadosValidados['uf_crm'],
            ]);

            $medico->especialidades()->sync($dadosValidados['especialidades']);
            $medico->estabelecimentos()->sync($dadosValidados['estabelecimentos'] ?? []);
            $medico->convenios()->sync($dadosValidados['convenios'] ?? []);

            return $medico;
        });

        return response()->json([
            'message' => 'Médico cadastrado com sucesso.',
            'data' => new MedicoResource($medico->load(['user', 'especialidades', 'estabelecimentos', 'convenios'])),
        ], 201);
    }

    /**
     * Exibe os dados de um médico específico, com todos os relacionamentos.
     */
    public function show(Medico $medico): JsonResponse
    {
        $medico->load(['user', 'especialidades', 'estabelecimentos', 'convenios']);

        return response()->json([
            'data' => new MedicoResource($medico),
        ]);
    }

    /**
     * Atualiza os dados do médico, da conta de usuário vinculada e,
     * se enviados, os relacionamentos de especialidades/estabelecimentos/convênios.
     */
    public function update(UpdateMedicoRequest $request, Medico $medico): JsonResponse
    {
        $dadosValidados = $request->validated();

        DB::transaction(function () use ($dadosValidados, $medico) {
            $dadosUser = array_intersect_key($dadosValidados, array_flip(['name', 'email', 'telefone', 'password']));

            if (isset($dadosUser['password'])) {
                $dadosUser['password'] = bcrypt($dadosUser['password']);
            }

            if (! empty($dadosUser)) {
                $medico->user->update($dadosUser);
            }

            $dadosMedico = array_intersect_key($dadosValidados, array_flip(['crm', 'uf_crm', 'ativo']));

            if (! empty($dadosMedico)) {
                $medico->update($dadosMedico);
            }

            if (array_key_exists('especialidades', $dadosValidados)) {
                $medico->especialidades()->sync($dadosValidados['especialidades']);
            }

            if (array_key_exists('estabelecimentos', $dadosValidados)) {
                $medico->estabelecimentos()->sync($dadosValidados['estabelecimentos']);
            }

            if (array_key_exists('convenios', $dadosValidados)) {
                $medico->convenios()->sync($dadosValidados['convenios']);
            }
        });

        return response()->json([
            'message' => 'Médico atualizado com sucesso.',
            'data' => new MedicoResource($medico->fresh(['user', 'especialidades', 'estabelecimentos', 'convenios'])),
        ]);
    }

    /**
     * Inativa (soft delete) o médico e a conta de usuário vinculada,
     * impedindo novos agendamentos e preservando o histórico de consultas já realizadas.
     */
    public function destroy(Medico $medico): JsonResponse
    {
        DB::transaction(function () use ($medico) {
            $medico->user->update(['ativo' => false]);
            $medico->update(['ativo' => false]);
            $medico->user->delete();
            $medico->delete();
        });

        return response()->json([
            'message' => 'Médico inativado com sucesso. Novos agendamentos ficam bloqueados; o histórico de consultas é preservado.',
        ]);
    }

    /**
     * Reativa um médico previamente inativado.
     */
    public function reativar(int $id): JsonResponse
    {
        $medico = Medico::withTrashed()->findOrFail($id);
        $medico->restore();
        $medico->update(['ativo' => true]);
        $medico->user()->withTrashed()->first()?->restore();
        $medico->user->update(['ativo' => true]);

        return response()->json([
            'message' => 'Médico reativado com sucesso.',
            'data' => new MedicoResource($medico->load(['user', 'especialidades', 'estabelecimentos', 'convenios'])),
        ]);
    }
}
