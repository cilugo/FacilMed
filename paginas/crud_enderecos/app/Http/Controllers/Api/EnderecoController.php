<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnderecoRequest;
use App\Http\Requests\UpdateEnderecoRequest;
use App\Http\Resources\EnderecoResource;
use App\Models\Endereco;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnderecoController extends Controller
{
    /**
     * Lista os endereços cadastrados.
     * Suporta filtro por cidade (?cidade=) e estado (?estado=).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Endereco::query();

        if ($request->filled('cidade')) {
            $query->where('cidade', 'like', '%' . $request->input('cidade') . '%');
        }

        if ($request->filled('estado')) {
            $query->where('estado', strtoupper($request->input('estado')));
        }

        $enderecos = $query->orderBy('cidade')->paginate(15);

        return response()->json(EnderecoResource::collection($enderecos)->response()->getData(true));
    }

    /**
     * Cadastra um novo endereço.
     * Pode vir avulso ou já vinculado a um Estabelecimento/Médico
     * (via enderecavel_type + enderecavel_id).
     */
    public function store(StoreEnderecoRequest $request): JsonResponse
    {
        $endereco = Endereco::create($request->validated());

        return response()->json([
            'message' => 'Endereço cadastrado com sucesso.',
            'data' => new EnderecoResource($endereco),
        ], 201);
    }

    /**
     * Exibe os dados de um endereço específico.
     */
    public function show(Endereco $endereco): JsonResponse
    {
        return response()->json([
            'data' => new EnderecoResource($endereco),
        ]);
    }

    /**
     * Atualiza os dados de um endereço existente.
     */
    public function update(UpdateEnderecoRequest $request, Endereco $endereco): JsonResponse
    {
        $endereco->update($request->validated());

        return response()->json([
            'message' => 'Endereço atualizado com sucesso.',
            'data' => new EnderecoResource($endereco),
        ]);
    }

    /**
     * Remove (soft delete) um endereço.
     */
    public function destroy(Endereco $endereco): JsonResponse
    {
        $endereco->delete();

        return response()->json([
            'message' => 'Endereço removido com sucesso.',
        ]);
    }
}
