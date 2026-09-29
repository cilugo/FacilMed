<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Support\Localizacao;
use Illuminate\Http\Request;

class PerfilPublicoController extends Controller
{
    /**
     * Pagina publica do medico: bio, especialidades, onde atende,
     * precos, convenios aceitos e a MEDIA das avaliacoes.
     *
     * NUNCA carregue o comentario das avaliacoes aqui. O publico ve
     * apenas a nota (AGENTS.md secao 6). O campo ja esta em $hidden
     * no model, mas nao confie so nisso: nao selecione a coluna.
     */
    public function medico(Medico $medico)
    {
        abort_unless($medico->status_verificacao === 'verificado', 404);
        abort_unless($medico->user->estaAtivo(), 404);

        // 28/09 (3ª revisão): só os lugares que recebem agendamento e só
        // especialidade ativa. Antes aparecia "Agendar aqui" em clínica
        // bloqueada (o paciente caía numa página 404) e o preço de
        // especialidade desativada pelo admin.
        $medico->load([
            'user',
            'especialidades' => fn ($e) => $e->where('ativo', true),
            'convenios',
            'vinculos' => fn ($v) => $v->agendaveis(),
            'vinculos.local.horarios',
            'vinculos.precos.especialidade',
        ]);

        return view('publico.medico', [
            'medico' => $medico,
            // So a distribuicao de notas, sem comentario.
            'notas'  => $medico->avaliacoes()
                ->selectRaw('estrelas, COUNT(*) as total')
                ->groupBy('estrelas')
                ->pluck('total', 'estrelas'),
        ]);
    }

    /**
     * Pagina publica da clinica: descricao, unidades, horario de
     * funcionamento e as especialidades atendidas.
     *
     * Aqui o paciente escolhe a ESPECIALIDADE, nao o medico - o
     * sistema aloca depois e mostra o nome antes de confirmar.
     */
    public function clinica(Clinica $clinica)
    {
        abort_unless($clinica->user->estaAtivo(), 404);

        $clinica->load(['locais.horarios']);

        return view('publico.clinica', [
            'clinica'        => $clinica,
            'especialidades' => $clinica->especialidades()->orderBy('nome')->get(),
            // 28/09: só médico VISÍVEL (CRM verificado + conta ativa) com vínculo
            // ativo numa unidade ativa. Antes a lista só olhava o CRM, e médico
            // com a conta bloqueada continuava aparecendo aqui.
            'medicos' => Medico::visivel()
                ->whereHas('vinculos', fn ($v) => $v->where('ativo', true)
                    ->whereHas('local', fn ($l) => $l->where('clinica_id', $clinica->id)->where('ativo', true)))
                ->with('user', 'especialidades')
                ->get()
                ->sortBy(fn ($m) => $m->user->name)
                ->values(),
        ]);
    }

    /**
     * Página do local (29/09/2026, plano do app): uma unidade de clínica, um
     * hospital ou o consultório de um médico. Endereço, telefone, horários,
     * nota média, formas de pagamento, especialidades com preço e os médicos
     * disponíveis - cada um com "Ver horários", que leva ao agendamento.
     *
     * ?especialidade=slug filtra os médicos (vem da busca de locais).
     * ?lat=&lng= ou ?cidade= mostram a distância (Localizacao::origem).
     *
     * A nota do local sai das avaliações das consultas feitas aqui
     * (Local::notas). Comentário NUNCA aparece (AGENTS.md §3).
     */
    public function local(Request $request, Local $local)
    {
        abort_unless($local->estaPublico(), 404);

        $slug = is_string($s = $request->query('especialidade')) && $s !== '' ? $s : null;
        $cidade = is_string($c = $request->query('cidade')) && $c !== '' ? $c : null;
        $origem = Localizacao::origem($request->query('lat'), $request->query('lng'), $cidade);

        $local->load(['horarios', 'clinica', 'medico.user']);

        // Mesma regra da busca: só vínculo que recebe agendamento e oferece
        // alguma especialidade (preço ativo de especialidade ativa).
        $vinculos = $local->vinculos()->agendaveis()->oferece()
            ->with([
                'medico.user',
                'medico.convenios' => fn ($c) => $c->where('ativo', true),
                'precos.especialidade',
            ])
            ->get()
            ->sortBy(fn ($v) => $v->medico->user->name)
            ->values();

        // Cada especialidade uma vez, com o menor preço particular daqui.
        $especialidades = $vinculos
            ->flatMap(fn ($v) => $v->precosOferecidos()->map(fn ($p) => ['preco' => $p, 'particular' => $v->aceita_particular]))
            ->groupBy(fn ($item) => $item['preco']->especialidade_id)
            ->map(fn ($itens) => (object) [
                'especialidade' => $itens->first()['preco']->especialidade,
                'aPartirDe'     => $itens->where('particular', true)->min(fn ($item) => (float) $item['preco']->valor),
            ])
            ->sortBy(fn ($e) => $e->especialidade->nome)
            ->values();

        return view('publico.local', [
            'local'          => $local,
            'nota'           => Local::notas([$local->id])->get($local->id),
            'especialidades' => $especialidades,
            'escolhida'      => $slug ? Especialidade::where('slug', $slug)->first() : null,
            'slug'           => $slug,
            'medicos'        => $slug
                ? $vinculos->filter(fn ($v) => $v->especialidadesOferecidas()->contains('slug', $slug))->values()
                : $vinculos,
            'particular'     => $vinculos->contains('aceita_particular', true),
            'convenios'      => $vinculos->where('aceita_convenio', true)
                ->flatMap(fn ($v) => $v->medico->convenios)->unique('id')->sortBy('nome')->values(),
            'distancia'      => $origem ? $local->distanciaAte($origem['lat'], $origem['lng']) : null,
            'origem'         => $origem,
        ]);
    }
}
