<?php

namespace App\Http\Controllers;

use App\Models\Avaliacao;
use App\Models\Clinica;
use App\Models\Especialidade;
use App\Models\Local;
use App\Models\Medico;
use App\Support\Localizacao;
use Illuminate\Http\Request;

class PerfilPublicoController extends Controller
{
    /**
     * Página pública do médico: bio, foto, especialidades, anos de carreira,
     * onde atende (com a faixa de preço), convênios aceitos e a MÉDIA das
     * avaliações. 01/10: o usuário logado avalia o médico aqui mesmo.
     *
     * 05/10/2026: comentários públicos (decisão do grupo), com o nome
     * encurtado de quem escreveu (avaliacoesPublicas).
     */
    public function medico(Request $request, Medico $medico)
    {
        abort_unless($medico->status_verificacao === 'verificado', 404);

        // Só os lugares públicos e só especialidade ativa.
        $medico->load([
            'especialidades' => fn ($e) => $e->where('ativo', true),
            'convenios' => fn ($c) => $c->where('ativo', true),
            'vinculos' => fn ($v) => $v->publicos(),
            'vinculos.local.horarios',
            'vinculos.medico.especialidades',
        ]);

        return view('publico.medico', [
            'medico' => $medico,
            // Só a distribuição de notas, sem comentário.
            'notas'  => $medico->avaliacoes()
                ->selectRaw('estrelas, COUNT(*) as total')
                ->groupBy('estrelas')
                ->pluck('total', 'estrelas'),
            'minhaAvaliacao' => $this->minhaAvaliacao($request, ['medico_id' => $medico->id]),
            'avaliacoes'     => $this->avaliacoesPublicas(['medico_id' => $medico->id]),
        ]);
    }

    /**
     * Página pública da clínica/hospital: descrição, unidades, horário de
     * funcionamento, especialidades e os médicos que atendem lá.
     */
    public function clinica(Clinica $clinica)
    {
        abort_unless($clinica->user->estaAtivo(), 404);

        $clinica->load(['user', 'locais' => fn ($l) => $l->where('ativo', true), 'locais.horarios']);

        return view('publico.clinica', [
            'clinica'        => $clinica,
            'especialidades' => $clinica->especialidades()->orderBy('nome')->get(),
            // Só médico VISÍVEL (CRM verificado) com vínculo ativo numa
            // unidade ativa desta clínica.
            'medicos' => Medico::visivel()
                ->whereHas('vinculos', fn ($v) => $v->where('ativo', true)
                    ->whereHas('local', fn ($l) => $l->where('clinica_id', $clinica->id)->where('ativo', true)))
                ->with(['especialidades' => fn ($e) => $e->where('ativo', true)])
                ->orderBy('nome')
                ->get(),
        ]);
    }

    /**
     * Página do local (29/09/2026, plano do app, tela 4): uma unidade de
     * clínica ou um hospital. Endereço, telefone, horários, nota média,
     * convênios aceitos, especialidades com a FAIXA de preço (01/10) e os
     * médicos disponíveis. O usuário logado avalia o local aqui (01/10).
     *
     * ?especialidade=slug filtra os médicos (vem da busca de locais).
     * ?lat=&lng= ou ?cidade= mostram a distância (Localizacao::origem).
     *
     * Comentários públicos desde 05/10/2026 (avaliacoesPublicas).
     */
    public function local(Request $request, Local $local)
    {
        abort_unless($local->estaPublico(), 404);

        $slug = is_string($s = $request->query('especialidade')) && $s !== '' ? $s : null;
        $cidade = is_string($c = $request->query('cidade')) && $c !== '' ? $c : null;
        $cep = is_string($c = $request->query('cep_origem')) ? $c : null;   // 07/10: veio de uma busca por CEP
        $origem = Localizacao::origem($request->query('lat'), $request->query('lng'), $cidade, $cep);

        $local->load(['horarios', 'clinica.user']);

        // Mesma regra da busca: só vínculo público de médico com alguma
        // especialidade ativa.
        $vinculos = $local->vinculos()->publicos()->oferece()
            ->with([
                'medico.especialidades' => fn ($e) => $e->where('ativo', true),
                'medico.convenios' => fn ($c) => $c->where('ativo', true),
            ])
            ->get()
            ->sortBy(fn ($v) => $v->medico->nome)
            ->values();

        // Cada especialidade uma vez. 05/10: a faixa de preço é a da unidade
        // (locais.faixa_preco), escolhida pela clínica.
        $especialidades = $vinculos->flatMap->especialidadesOferecidas()->unique('id')->sortBy('nome')->values();

        return view('publico.local', [
            'local'          => $local,
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
            'minhaAvaliacao' => $this->minhaAvaliacao($request, ['local_id' => $local->id]),
            'avaliacoes'     => $this->avaliacoesPublicas(['local_id' => $local->id]),
        ]);
    }

    /**
     * As avaliações mais recentes, COM comentário (05/10/2026: decisão do
     * grupo — comentário público). Quem escreveu aparece só com o primeiro
     * nome e a inicial do sobrenome (Formatador::nomeCurto); sem foto.
     */
    private function avaliacoesPublicas(array $alvo): \Illuminate\Support\Collection
    {
        return Avaliacao::where($alvo)->with('usuario.user:id,name')
            ->latest('updated_at')->limit(10)->get();
    }

    /**
     * A avaliação que o PRÓPRIO usuário logado já deu (para o formulário
     * vir preenchido e virar edição). Só a dele — com o comentário dele.
     */
    private function minhaAvaliacao(Request $request, array $alvo): ?Avaliacao
    {
        $usuario = $request->user()?->ehUsuario() ? $request->user()->usuario : null;

        return $usuario ? Avaliacao::where('usuario_id', $usuario->id)->where($alvo)->first() : null;
    }
}
