<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreConvenioRequest;
use App\Http\Requests\UpdateConvenioRequest;
use App\Http\Resources\ConvenioResource;
use App\Models\Convenio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConvenioController extends Controller
{
    /**
     * Lista os convênios cadastrados.
     * Suporta pesquisa por nome (?nome=) e filtro por status (?ativo=).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Convenio::withCount('medicos');

        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->input('nome') . '%');
        }

        if ($request->has('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $convenios = $query->orderBy('nome')->paginate(15);

        return response()->json(ConvenioResource::collection($convenios)->response()->getData(true));
    }

    /**
     * Cadastra um novo convênio.
     */
    public function store(StoreConvenioRequest $request): JsonResponse
    {
        $convenio = Convenio::create($request->validated());

        return response()->json([
            'message' => 'Convênio cadastrado com sucesso.',
            'data' => new ConvenioResource($convenio),
        ], 201);
    }

    /**
     * Exibe os dados de um convênio específico.
     */
    public function show(Convenio $convenio): JsonResponse
    {
        $convenio->loadCount('medicos');

        return response()->json([
            'data' => new ConvenioResource($convenio),
        ]);
    }

    /**
     * Atualiza os dados de um convênio existente.
     */
    public function update(UpdateConvenioRequest $request, Convenio $convenio): JsonResponse
    {
        $convenio->update($request->validated());

        return response()->json([
            'message' => 'Convênio atualizado com sucesso.',
            'data' => new ConvenioResource($convenio),
        ]);
    }

    /**
     * Inativa (soft delete) um convênio que não esteja mais disponível.
     */
    public function destroy(Convenio $convenio): JsonResponse
    {
        $convenio->update(['ativo' => false]);
        $convenio->delete();

        return response()->json([
            'message' => 'Convênio inativado com sucesso.',
        ]);
    }

    /**
     * Reativa um convênio previamente inativado.
     */
    public function reativar(int $id): JsonResponse
    {
        $convenio = Convenio::withTrashed()->findOrFail($id);
        $convenio->restore();
        $convenio->update(['ativo' => true]);

        return response()->json([
            'message' => 'Convênio reativado com sucesso.',
            'data' => new ConvenioResource($convenio),
        ]);
    }
}
