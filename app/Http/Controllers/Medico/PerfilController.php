<?php

namespace App\Http\Controllers\Medico;

use App\Http\Controllers\Controller;
use App\Services\EstatisticasDeConsultas;
use App\Models\Preco;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Medico\AtualizarPerfilMedicoRequest;
use App\Models\Convenio;
use App\Models\Especialidade;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    public function edit()
    {
        return view('medico.perfil', [
            'medico'         => auth()->user()->medico->load('especialidades', 'convenios'),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function update(AtualizarPerfilMedicoRequest $request)
    {
        $dados  = $request->validated();
        $medico = $request->user()->medico;
        $mudouCrm = $request->crmMudou();

        DB::transaction(function () use ($request, $medico, $dados, $mudouCrm) {
            $request->user()->update(['name' => $dados['name']]);
            $medico->update([
                'crm' => $dados['crm'],
                'uf'  => $dados['uf'],
                'bio' => $dados['bio'] ?? null,
                'anos_atuacao' => $dados['anos_atuacao'] ?? 0,
                'telefone_profissional' => ($dados['telefone_profissional'] ?? '') ?: null,
            ] + ($mudouCrm ? ['status_verificacao' => 'verificado', 'verificado_em' => now(), 'verificado_por' => null] : []));
        });

        return back()->with('sucesso', $mudouCrm
            ? 'Dados salvos. O novo CRM foi conferido na base simulada do FacilMed.'
            : 'Dados salvos.');
    }

    /**
     * especialidades[] + principal. Tirar uma especialidade desativa os
     * preços dela (some do agendamento), mas é recusado se ainda houver
     * consulta futura marcada nela.
     */
    public function salvarEspecialidades(Request $request)
    {
        $dados = $request->validate([
            'especialidades'   => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', Rule::exists('especialidades', 'id')->where('ativo', true)],
            'principal'        => ['nullable', 'integer', 'in:' . implode(',', (array) $request->input('especialidades', []))],
        ], [
            'especialidades.required' => 'Escolha pelo menos uma especialidade.',
            'principal.in'            => 'A principal precisa ser uma das especialidades marcadas.',
        ]);

        $medico = $request->user()->medico;
        $novas  = collect($dados['especialidades'])->map(fn ($id) => (int) $id)->unique()->values();
        $saindo = $medico->especialidades()->pluck('especialidades.id')->diff($novas);

        if ($saindo->isNotEmpty()) {
            $presas = EstatisticasDeConsultas::aPartirDeAgora(
                $medico->consultas()->getQuery()->where('status', 'agendada')->whereIn('especialidade_id', $saindo)
            )->count();

            if ($presas > 0) {
                return back()->with('erro', "Não dá para tirar essa especialidade: há {$presas} " .
                    ($presas === 1 ? 'consulta futura marcada' : 'consultas futuras marcadas') . ' nela.');
            }
        }

        $principal = (int) ($dados['principal'] ?? $novas->first());

        DB::transaction(function () use ($medico, $novas, $saindo, $principal) {
            $medico->especialidades()->sync($novas->mapWithKeys(fn ($id) => [$id => ['principal' => $id === $principal]])->all());

            if ($saindo->isNotEmpty()) {
                Preco::whereIn('vinculo_id', $medico->vinculos()->pluck('id'))
                    ->whereIn('especialidade_id', $saindo)->update(['ativo' => false]);
            }
        });

        return back()->with('sucesso', 'Especialidades atualizadas.');
    }

    /**
     * Convenios aceitos. O vinculo e com o MEDICO, nao com o endereco
     * (decisao de 18/09/2026) - aceitando o convenio, ele aceita
     * todos os planos dela.
     */
    public function salvarConvenios(Request $request)
    {
        // 24/09: antes o sync recebia qualquer id direto do formulario -
        // id inexistente estourava erro 500 de chave estrangeira, e dava
        // para se vincular a convenio desativado editando o HTML.
        $dados = $request->validate([
            'convenios'   => ['array'],
            'convenios.*' => ['integer', Rule::exists('convenios', 'id')->where('ativo', true)],
        ], [
            'convenios.*.exists' => 'Um dos convenios escolhidos nao existe ou foi desativado.',
        ]);

        auth()->user()->medico->convenios()->sync($dados['convenios'] ?? []);

        return back()->with('sucesso', 'Convenios atualizados.');
    }
}
