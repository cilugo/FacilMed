<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Models\Disponibilidade;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Clinica\SalvarUnidadeRequest;
use App\Http\Requests\Clinica\HorariosDeFuncionamento;
use App\Models\Foto;
use App\Models\Local;
use Illuminate\Http\Request;

class UnidadeController extends Controller
{
    public function index()
    {
        return view('clinica.unidades', [
            'locais' => auth()->user()->clinica->locais()->with('horarios', 'fotos')->withCount(['vinculos' => fn ($q) => $q->where('ativo', true)])->get(),
            'dias'   => Disponibilidade::DIAS,
        ]);
    }

    public function salvar(SalvarUnidadeRequest $request)
    {
        $dados = $request->validated();

        $local = DB::transaction(function () use ($request, $dados) {
            $local = Local::create([
                'clinica_id'  => $request->user()->clinica->id,
                'medico_id'   => null,
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
                'site'        => ($dados['site'] ?? '') ?: null,
                'ativo'       => true,
            ]);

            $local->definirHorarios(HorariosDeFuncionamento::preenchidos($dados['horarios'] ?? []));

            return $local;
        });

        // 01/10/2026: coordenada exata do endereço (Nominatim), fora da transação.
        // Sem internet, fica a aproximada do bairro/cidade (Local::booted).
        \App\Support\Geocodificador::atualizarLocal($local);

        return back()->with('sucesso', 'Unidade cadastrada. Agora vincule os médicos que atendem nela.');
    }

    /**
     * Horario de FUNCIONAMENTO do lugar - nao confundir com a
     * disponibilidade do medico. O bloco de agenda de um medico nao
     * pode cair fora do funcionamento da unidade.
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

        // Blocos de médicos que ficaram (em parte) fora do novo horário: a
        // CalculadoraDeHorarios já não oferece o que cai fora - só avisamos.
        $foraDoHorario = Disponibilidade::whereIn('vinculo_id', $local->vinculos()->select('id'))
            ->where('ativo', true)->get()
            ->filter(function ($d) use ($horarios) {
                $h = $horarios[$d->dia_semana] ?? null;

                return ! $h || substr($d->hora_inicio, 0, 5) < $h['abre'] || substr($d->hora_fim, 0, 5) > $h['fecha'];
            })->count();

        $msg = "Horário de funcionamento de {$local->nome} salvo.";
        if ($foraDoHorario > 0) {
            $msg .= " {$foraDoHorario} " . ($foraDoHorario === 1 ? 'bloco de atendimento de médico ficou' : 'blocos de atendimento de médicos ficaram')
                . ' fora do novo horário: a parte de fora deixa de ser oferecida aos pacientes.';
        }

        return back()->with('sucesso', $msg);
    }

    /**
     * 01/10/2026 (plano novo do grupo): site da unidade (botão "Visitar o
     * site" na página do local). Só http/https - nada de "javascript:".
     */
    public function salvarSite(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $request->merge(['site' => self::normalizarSite($request->input('site'))]);
        $dados = $request->validate(
            ['site' => ['nullable', 'url:http,https', 'max:255']],
            ['site.url' => 'Digite o endereço completo do site, por exemplo https://www.suaclinica.com.br']
        );

        $local->update(['site' => $dados['site'] ?: null]);

        return back()->with('sucesso', "Site de {$local->nome} salvo.");
    }

    /**
     * Fotos da unidade (galeria da página do local), no banco - ver
     * App\Models\Foto. Até 6 por unidade.
     */
    public function salvarFoto(Request $request, Local $local)
    {
        $this->authorize('update', $local);

        $request->validate(['foto' => Foto::regras()], Foto::mensagens());

        $total = Foto::where('local_id', $local->id)->count();
        if ($total >= Foto::MAXIMO_POR_LOCAL) {
            return back()->with('erro', 'Cada unidade pode ter até ' . Foto::MAXIMO_POR_LOCAL . ' fotos. Remova uma para enviar outra.');
        }

        Foto::create(['local_id' => $local->id, 'ordem' => $total] + Foto::dadosDoArquivo($request->file('foto')));

        return back()->with('sucesso', "Foto adicionada em {$local->nome}.");
    }

    public function removerFoto(Foto $foto)
    {
        abort_unless($foto->local_id !== null, 404);
        $this->authorize('update', $foto->local);

        $foto->delete();

        return back()->with('sucesso', 'Foto removida.');
    }

    /** "www.clinica.com.br" vira "https://www.clinica.com.br"; vazio fica vazio. */
    public static function normalizarSite(mixed $site): ?string
    {
        $site = trim((string) $site);
        if ($site === '') {
            return null;
        }

        return preg_match('#^[a-z][a-z0-9+.-]*://#i', $site) ? $site : 'https://' . $site;
    }
}
