<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\HorarioFuncionamento;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clinica\SalvarUnidadeRequest;
use App\Http\Requests\Clinica\HorariosDeFuncionamento;
use App\Models\Local;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{
    public function index()
    {
        return view('clinica.unidades', [
            'locais' => auth()->user()->clinica->locais()->with('horarios')->withCount(['vinculos' => fn ($q) => $q->where('ativo', true)])->get(),
            'dias'   => HorarioFuncionamento::DIAS,
        ]);
    }

    public function salvar(SalvarUnidadeRequest $request)
    {
        $dados = $request->validated();

        $local = DB::transaction(function () use ($request, $dados) {
            $local = Local::create([
                'clinica_id'  => $request->user()->clinica->id,
                'nome'        => $dados['nome'],
                'tipo'        => $dados['tipo'],
                'cep'         => $dados['cep'],
                'endereco'    => $dados['endereco'],
                'numero'      => $dados['numero'],
                'complemento' => $dados['complemento'] ?? null,
                'bairro'      => $dados['bairro'],
                'cidade'      => $dados['cidade'],
                'uf'          => $dados['uf'],
                'telefone'    => ($dados['telefone'] ?? '') ?: null,
                'faixa_preco' => $dados['faixa_preco'] ?? null,
                'ativo'       => true,
            ]);

            $local->definirHorarios(HorariosDeFuncionamento::preenchidos($dados['horarios'] ?? []));

            return $local;
        });

        // 07/10/2026 (trazido da main): coordenada exata do endereço (Nominatim),
        // FORA da transação - é um pedido pela internet e não pode segurar o banco.
        // Sem internet, fica a aproximada do bairro/cidade (Local::booted).
        \App\Support\Geocodificador::atualizarLocal($local);

        return back()->with('sucesso', 'Unidade cadastrada. Agora vincule os médicos que atendem nela.');
    }

    /**
     * Faixa de preço da consulta particular desta unidade, de $ a $$$$
     * (05/10/2026: a clínica escolhe; a Tabela de preços saiu). Vazio =
     * não informar. O usuário vê só os $, nunca valor.
     */
    public function salvarFaixa(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $dados = $request->validate(
            ['faixa_preco' => ['nullable', 'integer', 'between:1,4']],
            ['faixa_preco.between' => 'Escolha uma faixa de $ a $$$$.'],
        );

        $local->update(['faixa_preco' => $dados['faixa_preco'] ?? null]);

        return back()->with('sucesso', "Faixa de preço de {$local->nome} salva.");
    }

    /**
     * Horario de FUNCIONAMENTO do lugar, mostrado na pagina do local.
     */
    public function salvarHorarios(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $dados = $request->validate(HorariosDeFuncionamento::regras() + ['horarios' => ['nullable', 'array']], HorariosDeFuncionamento::mensagens());
        $horarios = HorariosDeFuncionamento::preenchidos($dados['horarios'] ?? []);

        if ($horarios === []) {
            return back()->with('erro', 'A unidade precisa abrir pelo menos um dia. Para parar de atender, desative a unidade.');
        }

        DB::transaction(fn () => $local->definirHorarios($horarios));

        $msg = "Horário de funcionamento de {$local->nome} salvo.";

        return back()->with('sucesso', $msg);
    }
}
