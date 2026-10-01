<?php

namespace App\Http\Controllers;

use App\Models\AvaliacaoLocal;
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
     * Página do local (29/09/2026; refeita em 01/10/2026 com o plano novo do
     * grupo - "Tela 4" do PDF): galeria de fotos, tipo, nome, nota, distância,
     * endereço/telefone/site, planos aceitos ("também atende particular"),
     * "Ver médicos disponíveis (N)", horários, especialidades com preço e o
     * quadro "Avalie este local".
     *
     * ?especialidade=slug vem da busca (conta só os médicos dela).
     * ?lat=&lng=(&cep=) ou ?cidade= mostram a distância (Localizacao::origem).
     *
     * A nota do local sai de avaliacoes_locais (qualquer paciente logado
     * avalia, uma vez). Comentário NUNCA aparece para o público (AGENTS.md
     * §3) - só o paciente vê o que ELE escreveu, no próprio quadro.
     */
    public function local(Request $request, Local $local)
    {
        abort_unless($local->estaPublico(), 404);

        [$slug, $origem] = $this->buscaDaUrl($request);

        $local->load(['horarios', 'clinica', 'medico.user', 'fotos']);
        $vinculos = $this->vinculosDoLocal($local);

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

        $paciente = $request->user()?->ehPaciente() ? $request->user()->paciente : null;
        $minha = $paciente
            ? AvaliacaoLocal::where('local_id', $local->id)->where('paciente_id', $paciente->id)->first()?->makeVisible('comentario')
            : null;

        return view('publico.local', [
            'local'          => $local,
            'nota'           => Local::notas([$local->id])->get($local->id),
            'distribuicao'   => AvaliacaoLocal::where('local_id', $local->id)
                ->selectRaw('estrelas, COUNT(*) as total')->groupBy('estrelas')->pluck('total', 'estrelas'),
            'especialidades' => $especialidades,
            'escolhida'      => $slug ? Especialidade::where('slug', $slug)->first() : null,
            'slug'           => $slug,
            'totalMedicos'   => $slug
                ? $vinculos->filter(fn ($v) => $v->especialidadesOferecidas()->contains('slug', $slug))->count()
                : $vinculos->count(),
            'particular'     => $vinculos->contains('aceita_particular', true),
            'convenios'      => $vinculos->where('aceita_convenio', true)
                ->flatMap(fn ($v) => $v->medico->convenios)->unique('id')->sortBy('nome')->values(),
            'distancia'      => $origem ? $local->distanciaAte($origem['lat'], $origem['lng']) : null,
            'origem'         => $origem,
            'minha'          => $minha,
            'podeAvaliar'    => $paciente !== null,
        ]);
    }

    /**
     * Médicos disponíveis no local (01/10/2026, "Tela 5" do PDF): foto ou
     * iniciais, nome, nota, especialidades/preço e as informações do médico,
     * cada um com "Ver horários" (o agendamento de sempre). Com
     * ?especialidade=, só os médicos dela.
     */
    public function medicosDoLocal(Request $request, Local $local)
    {
        abort_unless($local->estaPublico(), 404);

        [$slug, $origem] = $this->buscaDaUrl($request);
        $local->load('clinica');
        $vinculos = $this->vinculosDoLocal($local);

        return view('publico.local-medicos', [
            'local'          => $local,
            'slug'           => $slug,
            'escolhida'      => $slug ? Especialidade::where('slug', $slug)->first() : null,
            'especialidades' => $vinculos->flatMap->especialidadesOferecidas()->unique('id')->sortBy('nome')->values(),
            'medicos'        => $slug
                ? $vinculos->filter(fn ($v) => $v->especialidadesOferecidas()->contains('slug', $slug))->values()
                : $vinculos,
            'origem'         => $origem,
        ]);
    }

    /** Especialidade e origem (distância) que vieram da busca, pela URL. */
    private function buscaDaUrl(Request $request): array
    {
        $texto = fn (string $campo) => is_string($v = $request->query($campo)) && $v !== '' ? $v : null;

        return [
            $texto('especialidade'),
            Localizacao::origem($request->query('lat'), $request->query('lng'), $texto('cidade'), $texto('cep')),
        ];
    }

    /**
     * Mesma regra da busca: só vínculo que recebe agendamento e oferece alguma
     * especialidade (preço ativo de especialidade ativa).
     */
    private function vinculosDoLocal(Local $local)
    {
        return $local->vinculos()->agendaveis()->oferece()
            ->with([
                'medico.user',
                'medico.fotoEnviada',
                'medico.especialidades' => fn ($e) => $e->where('ativo', true),
                'medico.convenios' => fn ($c) => $c->where('ativo', true),
                'precos.especialidade',
            ])
            ->get()
            ->sortBy(fn ($v) => $v->medico->user->name)
            ->values();
    }
}
