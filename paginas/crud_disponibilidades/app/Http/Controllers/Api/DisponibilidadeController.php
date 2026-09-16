<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BloquearDisponibilidadeRequest;
use App\Http\Requests\StoreDisponibilidadeRequest;
use App\Http\Requests\UpdateDisponibilidadeRequest;
use App\Http\Resources\DisponibilidadeResource;
use App\Models\Disponibilidade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisponibilidadeController extends Controller
{
    /**
     * Lista/consulta disponibilidades.
     * Suporta:
     *  - ?medico_id= (obrigatório na prática, mas não forçado aqui)
     *  - ?dia_semana=
     *  - ?data=
     *  - ?apenas_disponiveis=true (ignora bloqueadas/inativas — o que o
     *    paciente deve ver ao tentar agendar)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Disponibilidade::with(['medico.user', 'estabelecimento', 'convenio']);

        if ($request->filled('medico_id')) {
            $query->where('medico_id', $request->input('medico_id'));
        }

        if ($request->filled('dia_semana')) {
            $query->where('dia_semana', $request->input('dia_semana'));
        }

        if ($request->filled('data')) {
            $query->where('data', $request->input('data'));
        }

        if ($request->boolean('apenas_disponiveis')) {
            $query->disponiveis();
        }

        $disponibilidades = $query->orderBy('hora_inicio')->paginate(20);

        return response()->json(DisponibilidadeResource::collection($disponibilidades)->response()->getData(true));
    }

    /**
     * Cadastra uma nova disponibilidade, verificando conflito de horário
     * com outras disponibilidades já existentes do mesmo médico.
     */
    public function store(StoreDisponibilidadeRequest $request): JsonResponse
    {
        $dadosValidados = $request->validated();

        if ($this->existeConflito($dadosValidados)) {
            return response()->json([
                'message' => 'Já existe uma disponibilidade cadastrada para este médico que conflita com o horário informado.',
            ], 422);
        }

        $disponibilidade = Disponibilidade::create($dadosValidados);

        return response()->json([
            'message' => 'Disponibilidade cadastrada com sucesso.',
            'data' => new DisponibilidadeResource($disponibilidade->load(['medico.user', 'estabelecimento', 'convenio'])),
        ], 201);
    }

    /**
     * Exibe uma disponibilidade específica.
     */
    public function show(Disponibilidade $disponibilidade): JsonResponse
    {
        return response()->json([
            'data' => new DisponibilidadeResource($disponibilidade->load(['medico.user', 'estabelecimento', 'convenio'])),
        ]);
    }

    /**
     * Atualiza uma disponibilidade existente, reverificando conflitos
     * quando o dia/data ou os horários mudam.
     */
    public function update(UpdateDisponibilidadeRequest $request, Disponibilidade $disponibilidade): JsonResponse
    {
        $dadosValidados = $request->validated();
        $dadosParaChecagem = array_merge($disponibilidade->only([
            'medico_id', 'dia_semana', 'data', 'hora_inicio', 'hora_fim',
        ]), $dadosValidados);

        if ($this->existeConflito($dadosParaChecagem, $disponibilidade->id)) {
            return response()->json([
                'message' => 'A alteração gera conflito de horário com outra disponibilidade deste médico.',
            ], 422);
        }

        $disponibilidade->update($dadosValidados);

        return response()->json([
            'message' => 'Disponibilidade atualizada com sucesso.',
            'data' => new DisponibilidadeResource($disponibilidade->fresh(['medico.user', 'estabelecimento', 'convenio'])),
        ]);
    }

    /**
     * Bloqueia ou desbloqueia um período (ex: férias, licença),
     * sem excluir a disponibilidade recorrente.
     */
    public function bloquear(BloquearDisponibilidadeRequest $request, Disponibilidade $disponibilidade): JsonResponse
    {
        $disponibilidade->update($request->validated());

        $mensagem = $disponibilidade->bloqueado
            ? 'Período bloqueado com sucesso.'
            : 'Período desbloqueado com sucesso.';

        return response()->json([
            'message' => $mensagem,
            'data' => new DisponibilidadeResource($disponibilidade),
        ]);
    }

    /**
     * Remove (soft delete) uma disponibilidade.
     */
    public function destroy(Disponibilidade $disponibilidade): JsonResponse
    {
        $disponibilidade->delete();

        return response()->json([
            'message' => 'Disponibilidade removida com sucesso.',
        ]);
    }

    /**
     * Verifica se já existe uma disponibilidade do mesmo médico que
     * conflita (sobrepõe horário) no mesmo dia da semana ou na mesma data.
     */
    private function existeConflito(array $dados, ?int $ignorarId = null): bool
    {
        $query = Disponibilidade::where('medico_id', $dados['medico_id'])
            ->where('hora_inicio', '<', $dados['hora_fim'])
            ->where('hora_fim', '>', $dados['hora_inicio']);

        if (! empty($dados['dia_semana'])) {
            $query->where('dia_semana', $dados['dia_semana']);
        } elseif (! empty($dados['data'])) {
            $query->where('data', $dados['data']);
        }

        if ($ignorarId) {
            $query->where('id', '!=', $ignorarId);
        }

        return $query->exists();
    }
}
