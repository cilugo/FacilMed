<?php

namespace App\Http\Controllers\Clinica;

use App\Http\Controllers\Controller;
use App\Services\EstatisticasDeConsultas;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Medico;
use App\Http\Requests\Clinica\CadastrarMedicoPelaClinicaRequest;
use App\Models\Convenio;
use App\Models\Especialidade;
use App\Models\Preco;
use Illuminate\Validation\Rule;
use App\Models\Foto;
use App\Models\Vinculo;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function index()
    {
        $clinica = auth()->user()->clinica;

        return view('clinica.medicos', [
            'vinculos' => $clinica->vinculos()
                ->where('vinculos.ativo', true)
                ->with('medico.user', 'medico.especialidades', 'local', 'precos.especialidade')
                ->get()
                ->sortBy(fn ($v) => $v->medico->user->name)->values(),
            'unidades' => $clinica->locais()->where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function form()
    {
        return view('clinica.medicos-form', [
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'unidades'       => auth()->user()->clinica->locais()->where('ativo', true)->get(),
        ]);
    }

    /**
     * A clinica cadastra o medico.
     *
     * O sistema gera SENHA TEMPORARIA e marca senha_temporaria = true;
     * o medico e obrigado a trocar no primeiro login. A clinica nunca
     * fica sabendo a senha definitiva do profissional.
     *
     * O CRM de médico NOVO é conferido na base simulada pelo FormRequest
     * (desde 24/09; e desde 29/09 este é o ÚNICO jeito de um médico entrar
     * no FacilMed - o autocadastro saiu): se chegou aqui, bateu, e
     * ele já nasce 'verificado'. A clínica não "verifica" nada.
     *
     * Se o medico JA existe no sistema (mesmo CRM+UF), nao crie outro:
     * crie so o vinculo com a unidade.
     */
    public function salvar(CadastrarMedicoPelaClinicaRequest $request)
    {
        $dados      = $request->validated();
        $existente  = $request->medicoExistente();
        $senhaTemp  = null;

        $vinculo = DB::transaction(function () use ($dados, $existente, $request, &$senhaTemp) {
            $medico = $existente;

            if (! $medico) {
                $senhaTemp = Str::password(10, symbols: false);

                $user = User::create([
                    'name'     => $dados['name'],
                    'email'    => $dados['email'],
                    'password' => $senhaTemp,
                    'tipo'     => User::TIPO_MEDICO,
                    'status'   => 'ativo',
                ]);

                $medico = Medico::create([
                    'user_id'            => $user->id,
                    'cpf'                => $dados['cpf'],
                    'crm'                => $dados['crm'],
                    'uf'                 => $dados['uf'],
                    'status_verificacao' => 'verificado',   // conferido na base simulada pelo FormRequest
                    'verificado_em'      => now(),
                    'anos_atuacao'       => 0,
                    'senha_temporaria'   => true,
                ]);

                $medico->especialidades()->sync(
                    collect($dados['especialidades'])->values()
                        ->mapWithKeys(fn ($id, $i) => [$id => ['principal' => $i === 0]])->all()
                );
            }

            // Reativa vínculo antigo (desvinculado antes) em vez de duplicar - UNIQUE(medico, local).
            return Vinculo::updateOrCreate(
                ['medico_id' => $medico->id, 'local_id' => $dados['local_id']],
                [
                    'aceita_particular' => $request->has('aceita_particular') ? $request->boolean('aceita_particular') : true,
                    'aceita_convenio'   => $request->boolean('aceita_convenio'),
                    'ativo'             => true,
                ],
            );
        });

        $nome = $vinculo->medico->user->name;
        $msg  = $existente
            ? "{$nome} já tinha conta no FacilMed e foi vinculado(a) à unidade."
            : "{$nome} cadastrado(a). CRM conferido na base simulada do FacilMed.";

        return redirect()->route('clinica.precos')
            ->with('sucesso', $msg . ' Agora defina os preços e, em "Horários dos médicos", quando ele atende.')
            // Mostrada UMA vez para a clínica repassar. No primeiro login o
            // médico é obrigado a trocar (middleware ExigirTrocaDeSenha).
            ->with('senha_temporaria', $senhaTemp);
    }

    public function desvincular(Request $request, Vinculo $vinculo)
    {
        $this->authorize('delete', $vinculo);

        // O vínculo não é apagado (consultas antigas apontam para ele): fica inativo.
        $futuras = EstatisticasDeConsultas::aPartirDeAgora(
            $vinculo->consultas()->getQuery()->where('status', 'agendada')
        )->get();

        if ($futuras->isNotEmpty() && ! $request->boolean('cancelar_consultas')) {
            return back()->with('erro', "{$vinculo->medico->user->name} tem {$futuras->count()} " .
                ($futuras->count() === 1 ? 'consulta futura' : 'consultas futuras') . " em {$vinculo->local->nome}. " .
                'Confirme o desvínculo marcando "cancelar as consultas" (os pacientes são avisados).');
        }

        DB::transaction(function () use ($vinculo, $futuras, $request) {
            $futuras->each->cancelar($request->user()->id, 'O médico deixou de atender nesta unidade');
            $vinculo->update(['ativo' => false]);
        });

        return back()->with('sucesso', "{$vinculo->medico->user->name} não atende mais em {$vinculo->local->nome}.");
    }

    /**
     * 01/10/2026 (plano novo do grupo): o perfil do médico passou para a
     * clínica - bio, anos de atuação, telefone, especialidades e convênios.
     * Nome e CRM ficam como estão: foram conferidos JUNTOS na base simulada
     * no cadastro, e trocar um deles sem conferir de novo abriria brecha.
     *
     * MedicoPolicy::gerenciar: só médico com vínculo ativo nesta clínica.
     */
    public function editar(Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        return view('clinica.medicos-editar', [
            'medico'         => $medico->load('user', 'especialidades', 'convenios', 'vinculos.local', 'fotoEnviada'),
            'especialidades' => Especialidade::where('ativo', true)->orderBy('nome')->get(),
            'convenios'      => Convenio::where('ativo', true)->orderBy('nome')->get(),
        ]);
    }

    public function atualizar(Request $request, Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        $request->merge(['telefone_profissional' => preg_replace('/\D/', '', (string) $request->input('telefone_profissional'))]);

        $dados = $request->validate([
            'bio'                   => ['nullable', 'string', 'max:1000'],
            'anos_atuacao'          => ['nullable', 'integer', 'min:0', 'max:70'],
            'telefone_profissional' => ['nullable', 'digits_between:10,11'],
        ], [
            'telefone_profissional.digits_between' => 'O telefone deve ter DDD + número, com 10 ou 11 dígitos.',
        ]);

        $medico->update([
            'bio'                   => $dados['bio'] ?? null,
            'anos_atuacao'          => $dados['anos_atuacao'] ?? 0,
            'telefone_profissional' => ($dados['telefone_profissional'] ?? '') ?: null,
        ]);

        return back()->with('sucesso', "Dados de {$medico->user->name} salvos.");
    }

    /**
     * especialidades[] + principal. Tirar uma especialidade desativa os
     * preços dela (some do agendamento), mas é recusado se ainda houver
     * consulta futura marcada nela. (Era Medico\PerfilController até 30/09.)
     */
    public function salvarEspecialidades(Request $request, Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        $dados = $request->validate([
            'especialidades'   => ['required', 'array', 'min:1'],
            'especialidades.*' => ['integer', Rule::exists('especialidades', 'id')->where('ativo', true)],
            'principal'        => ['nullable', 'integer', 'in:' . implode(',', array_map('intval', (array) $request->input('especialidades', [])))],
        ], [
            'especialidades.required' => 'Escolha pelo menos uma especialidade.',
            'principal.in'            => 'A principal precisa ser uma das especialidades marcadas.',
        ]);

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
     * Convênios aceitos. O vínculo é com o MÉDICO, não com o endereço
     * (decisão de 18/09/2026) - aceitando o convênio, ele aceita todos os
     * planos dele, em todos os lugares onde atende.
     */
    public function salvarConvenios(Request $request, Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        // 24/09: sem o exists, id inexistente estourava erro 500 de chave
        // estrangeira, e dava para ligar convênio desativado editando o HTML.
        $dados = $request->validate([
            'convenios'   => ['array'],
            'convenios.*' => ['integer', Rule::exists('convenios', 'id')->where('ativo', true)],
        ], [
            'convenios.*.exists' => 'Um dos convênios escolhidos não existe ou foi desativado.',
        ]);

        $medico->convenios()->sync($dados['convenios'] ?? []);

        return back()->with('sucesso', 'Convênios atualizados.');
    }

    /**
     * 01/10/2026: foto do médico (aparece na lista de médicos disponíveis e
     * no perfil público). Uma só - mandar outra troca. No banco (ver Foto).
     */
    public function salvarFoto(Request $request, Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        $request->validate(['foto' => Foto::regras()], Foto::mensagens());
        Foto::updateOrCreate(['medico_id' => $medico->id], Foto::dadosDoArquivo($request->file('foto')));

        return back()->with('sucesso', "Foto de {$medico->user->name} atualizada.");
    }

    public function removerFoto(Medico $medico)
    {
        $this->authorize('gerenciar', $medico);

        Foto::where('medico_id', $medico->id)->delete();

        return back()->with('sucesso', 'Foto removida.');
    }
}
